<?php

namespace App\Services\Notification;

use App\Filament\Resources\Operational\PaymentRequestResource\Pages\Admin;
use App\Filament\Resources\Operational\PaymentRequestResource\Pages\CreatePaymentRequest;
use App\Filament\Resources\Operational\PaymentRequestResource\Pages\EditPaymentRequest;
use App\Models\User;
use App\Services\Notification\BaseService;
use App\Services\Notification\SMS\Operator;
use App\Services\RetryableEmailService;


class PaymentRequestService extends BaseService
{
    protected string $moduleName = 'paymentRequest';
    protected string $resourceRouteName = 'payment-requests';

    /**
     * Override to display order relation information.
     */
    protected function getRecordDisplay($record): string
    {
        return Admin::getOrderRelation($record);
    }

    /**
     * Notify accountants (and managers if applicable) about the payment request.
     */
    public function notifyAccountants($record, $type = 'new', $status = false, $accountants = null): void
    {
        $accountants = $accountants ?: User::getUsersByRole('accountant');

        $accountants = $this->checkPermissionOfCXHead($record, $accountants);

        $recipients = $accountants;

        if ($status && in_array($record['status'], ['allowed', 'approved'])) {
            $managers = User::getUsersByRole('manager') ?: collect();
            $recipients = $accountants->merge($managers);
        }



        $this->notifyUsers($record, type: $type, status: $status, users: $recipients);

        if (!$status && $type == 'new') {
            $operator = new Operator();
            $message = $this->mapModelToSMSClass($record, $type);
            $operator->send($accountants, $message->print());
        }
        // for CX department only
        elseif ($status && ($record['status'] == 'allowed' && $record['department_id'] == 6)) {
            $operator = new Operator();
            $message = $this->mapModelToSMSClass($record, $type, $status);
            $operator->send(User::getUsersByRole('accountant'), $message->print());
        }
        elseif ($status && $record['status'] == 'rejected') {
            $operator = new Operator();
            $message = $this->mapModelToSMSClass($record, $type, $status);
            $operator->send($accountants, $message->print());
        }
    }

    // for CX department only
    protected function checkPermissionOfCXHead($record, mixed $accountants): mixed
    {
        if ($record['department_id'] == 6) {
            $head = User::getByDepAndPos(6, 'mdr') ?: collect();
            $accountants = $accountants->merge($head);
        }
        return $accountants;
    }
}
