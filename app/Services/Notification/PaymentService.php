<?php

namespace App\Services\Notification;

use App\Models\User;
use App\Services\Notification\BaseService;
use App\Services\Notification\SMS\Operator;

class PaymentService extends BaseService
{
    protected string $moduleName = 'payment';
    protected string $resourceRouteName = 'payments';

    /**
     * Get the display string for a record.
     */
    public function getRecordDisplay($record): string
    {
        return $record['records'];
    }

    /**
     * Notify accountants about the payment.
     */
    public function notifyAccountants($record, $type = 'new'): void
    {
        $accountants = User::getUsersByRole('accountant');
        $recipients = $accountants;

        if ($type == 'new') {
            $paymentRequestUsers = $this->fetchPaymentRequestUsers($record);
            $recipients = $accountants->merge($paymentRequestUsers)->unique('id');
        }

        $this->notifyUsers($record, $type, users: $recipients);

        if ($type == 'new') {
            $operator = new Operator();
            $message = $this->mapModelToSMSClass($record, $type, status: true);
            $operator->send($paymentRequestUsers, $message->print());
        }
    }

    public function fetchPaymentRequestUsers($payment): mixed
    {
        $userIds = $payment->paymentRequests->pluck('user_id')->unique();

        return User::whereIn('id', $userIds)->get();
    }
}
