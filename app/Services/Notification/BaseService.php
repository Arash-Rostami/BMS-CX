<?php

namespace App\Services\Notification;

use App\Models\Department;
use App\Models\NotificationSubscription;
use App\Notifications\FilamentNotification;
use App\Notifications\OrderStatusNotification;
use App\Notifications\PaymentRequestStatusNotification;
use App\Notifications\PaymentStatusNotification;
use App\Notifications\ProformaInvoiceStatusNotification;
use App\Services\Notification\SMS\Operator;
use App\Services\Notification\SMS\OrderMessage;
use App\Services\Notification\SMS\PaymentMessage;
use App\Services\Notification\SMS\PaymentRequestMessage;
use App\Services\Notification\SMS\ProformaInvoiceMessage;
use App\Services\RetryableEmailService;
use Illuminate\Support\Facades\Cache;

abstract class BaseService
{

    private const PARVA_DEPARTMENTS = [6, 10, 17, 18, 21]; // CX, PERSORE, PERSOL, SOLSUN, MA(PA)
    private const PEDRAM_SHARED = [17]; // PERSOL
    protected string $moduleName;
    protected string $resourceRouteName;

    public function mapModelToNotificationClass($record, $type, $status = false)
    {
        $modelClass = get_class($record);

        return match ($modelClass) {
            'App\Models\ProformaInvoice' => new ProformaInvoiceStatusNotification($record, $type),
            'App\Models\Order' => new OrderStatusNotification($record, $type),
            'App\Models\PaymentRequest' => new PaymentRequestStatusNotification($record, $type, $status),
            'App\Models\Payment' => new PaymentStatusNotification($record, $type),
        };
    }

    public function mapModelToSMSClass($record, $type, $status = false)
    {
        $modelClass = get_class($record);

        return match ($modelClass) {
            'App\Models\ProformaInvoice' => new ProformaInvoiceMessage($record, $type),
            'App\Models\Order' => new OrderMessage($record, $type),
            'App\Models\PaymentRequest' => new PaymentRequestMessage($record, $type, $status),
            'App\Models\Payment' => new PaymentMessage($record, $type, $status),
        };
    }

    /**
     * Map notification type to activity preference key.
     */
    public function mapNotificationTypeToActivity(string $type): string
    {
        $mapping = [
            'new' => 'notify_create',
            'edit' => 'notify_update',
            'delete' => 'notify_delete',
        ];

        return $mapping[$type] ?? 'notify_update';
    }

    /**
     * Send notifications to users.
     */
    public function notifyUsers($record, string $type = 'new', $status = false, $users = null): void
    {
        $users = $users ?? collect();

        $notificationData = $this->getNotificationData($record, $type);

        $subscribedUsers = $this->fetchSubscribedUsers($record);

        $recipients = $users->isNotEmpty()
            ? $subscribedUsers->merge($users)->unique('id')
            : $subscribedUsers;

        $activityPreferenceKey = $this->mapNotificationTypeToActivity($type);

        foreach ($recipients as $recipient) {
            $preferences = $recipient->notificationPreferences
                ??
                (object)[
                    'email' => false,
                    'in_app' => false,
                    'sms' => false,
                    'notify_create' => false,
                    'notify_update' => false,
                    'notify_delete' => false,
                ];


            $isMandatory = $users && $users->contains('id', $recipient->id);

            $notifyForActivity = $preferences->{$activityPreferenceKey} ?? false;

            if (!$notifyForActivity && !$isMandatory) {
                continue;
            }

            if ($preferences->in_app || $isMandatory) {
                $recipient->notify(new FilamentNotification($notificationData, $status));
            }

            if ($preferences->email) {
                $arguments = [$recipient, $this->mapModelToNotificationClass($record, $type, $status)];
                RetryableEmailService::dispatchEmail(get_class($record), ...$arguments);
            }

            if ($preferences->sms) {
                $operator = new Operator();
                $message = $this->mapModelToSMSClass($record, $type, $status);
                $operator->send($recipient->phone, $message->print());
            }
        }
    }

    /**
     * Fetch subscribed users for a given record, including module-level subscriptions, with caching.
     */
    protected function fetchSubscribedUsers($record)
    {
        $subscriptions = NotificationSubscription::where('notifiable_type', get_class($record))
            ->whereIn('notifiable_id', [0])
            ->get();

        list($recordDepartments, $recordCostCenters) = $this->narrowToDepartmentOnly($record);

        return $subscriptions->map(function ($subscription) {
            $user = $subscription->user;
            $user->notificationPreferences = $subscription;
            $user->department = (int)$user->info['department'] ?? null;
            return $user;
        })->filter(function ($user) use ($recordDepartments, $recordCostCenters) {
            if (empty($recordDepartments) && empty($recordCostCenters)) {
                return true;
            }

            if (!$this->isManager($user)) {
                return in_array($user->department, $recordDepartments) || in_array($user->department, $recordCostCenters);
            }

            return $this->canManagerViewDepartment($user, $recordDepartments, $recordCostCenters);
        })->unique('id');
    }

    /**
     * Get the notification data array.
     */
    protected function getNotificationData($record, string $type): array
    {
        return [
            'record' => $this->getRecordDisplay($record),
            'type' => $type,
            'module' => $this->moduleName,
            'url' => $type == 'delete'
                ? route("filament.admin.resources.{$this->resourceRouteName}.index")
                : route("filament.admin.resources.{$this->resourceRouteName}.edit", ['record' => $record->id]),
        ];
    }

    /**
     * Get the display string for a record.
     */
    protected function getRecordDisplay($record): string
    {
        return $record->reference_number;
    }

    /**
     * @param $record
     * @return array
     */
    protected function narrowToDepartmentOnly($record): array
    {
        $recordDepartments = [];
        $recordCostCenters = [];

        if (get_class($record) == "App\Models\Payment") {
            $recordDepartments = $record->paymentRequests->pluck('department_id')->toArray();
            $recordCostCenters = $record->paymentRequests->pluck('cost_center')->toArray();
        } elseif (get_class($record) == "App\Models\PaymentRequest") {
            $recordDepartments = [$record->department_id];
            $recordCostCenters = [$record->cost_center];
        }
        return array($recordDepartments, $recordCostCenters);
    }

    private function canManagerViewDepartment($user, array $recordDepartments, array $recordCostCenters): bool
    {
        $allDepartments = array_merge($recordDepartments, $recordCostCenters);

        if (strtolower($user->first_name) === 'parva') {
            return !empty(array_intersect($allDepartments, self::PARVA_DEPARTMENTS));
        }

        $allDepartmentIds = $this->getAllDepartmentIds();

        $pedramDepartments = array_merge(
            array_diff($allDepartmentIds, self::PARVA_DEPARTMENTS),
            self::PEDRAM_SHARED
        );

        return !empty(array_intersect($allDepartments, $pedramDepartments));
    }

    private function getAllDepartmentIds(): array
    {
        return Cache::remember('dpIdsNotification', now()->addDays(2), fn() => Department::pluck('id')->toArray());
    }

    private function isManager($user): bool
    {
        return $user->role == 'manager';
    }
}
