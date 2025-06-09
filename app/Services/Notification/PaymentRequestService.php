<?php

namespace App\Services\Notification;

use App\Filament\Resources\Operational\PaymentRequestResource\Pages\Admin;
use App\Models\User;
use App\Services\Notification\SMS\Operator;
use App\Services\RetryableEmailService;


class PaymentRequestService extends BaseService
{
    protected const CX_DEPARTMENT_ID = 6;
    protected const MDR_POSITION = 'mdr';
    protected const ROLE_ACCOUNTANT = 'accountant';
    protected const ROLE_MANAGER = 'manager';
    protected const PAYMENT_CASH = 'cash';
    protected const ALLOWED_STATUSES = ['approved'];
    private const ALLOWED_CURRENCIES = ['EURO', 'USD'];
    protected string $moduleName = 'paymentRequest';
    protected string $resourceRouteName = 'payment-requests';

    /**
     * Notify accountants (and managers if applicable) about the payment request.
     */
    public function notifyAccountants($record, $type = 'new', $status = false, $accountants = null): void
    {
        $accountants = $this->getEligibleAccountants($accountants);
        $recipients = $this->addManagementToAccountants($accountants, $status, $record['status']);

        $this->notifyUsers($record, type: $type, status: $status, users: $recipients);
        $this->handleAdditionalNotifications($status, $type, $record, $accountants);
    }

    protected function getEligibleAccountants(mixed $accountants): mixed
    {
        $accountants = $accountants ?: User::getUsersByRole('accountant');
        return $this->filterAccountantsByPosition($accountants);
    }

    protected function filterAccountantsByPosition(mixed $accountants): mixed
    {
        return $accountants->filter(function ($user) {
            if (strtolower($user->role) == self::ROLE_ACCOUNTANT) {
                return strtolower($user->info['position'] ?? '') == self::MDR_POSITION;
            }
            return true;
        });
    }

    protected function addManagementToAccountants(mixed $accountants, mixed $status, $recordStatus): mixed
    {
        $recipients = $accountants;
        if ($status && in_array($recordStatus, self::ALLOWED_STATUSES)) {
            $managers = User::getUsersByRole(self::ROLE_MANAGER) ?: collect();
            $recipients = $accountants->merge($managers);
        }
        return $recipients;
    }

    protected function handleAdditionalNotifications(mixed $status, mixed $type, $record, mixed $accountants): void
    {
        $operator = new Operator();

        if (!$status && $type == 'new') {
            $accountants = $this->addCxHeadIfNeeded($record, $accountants);
            $this->sendSMS($record, $type, $operator, $accountants);

        } elseif ($this->isForCx($status, $record) || $this->isNonRial($status, $record)) {
            $this->sendEmail($record, $status);

        } elseif ($status && $record['status'] == 'rejected') {
            $this->sendSMS($record, $type, $operator, $accountants, $status);
        }
    }

    protected function addCxHeadIfNeeded($record, mixed $accountants): mixed
    {
        if ($record['department_id'] == self::CX_DEPARTMENT_ID) {
            $head = User::getByDepAndPos(self::CX_DEPARTMENT_ID, self::MDR_POSITION) ?: collect();
            $accountants = $accountants->merge($head);
        }
        return $accountants;
    }

    protected function sendSMS($record, mixed $type, Operator $operator, mixed $accountants, $status = false): void
    {
        $message = $status ? $this->mapModelToSMSClass($record, $type, $status) : $this->mapModelToSMSClass($record, $type);
        $operator->send($accountants, $message->print());
    }

    protected function isForCx($status, $record): bool
    {
        return $this->hasBaseConditions($status, $record) && $record['department_id'] == self::CX_DEPARTMENT_ID;
    }

    protected function hasBaseConditions($status, $record): bool
    {
        return $status
            && in_array($record['status'], self::ALLOWED_STATUSES)
            && strtolower($record['extra']['paymentMethod'] ?? '') != self::PAYMENT_CASH
            && in_array($record['currency'], self::ALLOWED_CURRENCIES);
    }

    protected function isNonRial($status, $record): bool
    {
        return $this->hasBaseConditions($status, $record);
    }

    protected function sendEmail($record, mixed $status): void
    {
        $CxRecipients = User::getPartnersWithPosition();
        $arguments = [$CxRecipients, $this->mapModelToNotificationClass($record, 'partner', $status)];
        RetryableEmailService::dispatchEmail(get_class($record), ...$arguments);
    }

    /**
     * Override to display order relation information.
     */
    protected function getRecordDisplay($record): string
    {
        return Admin::getOrderRelation($record);
    }
}
