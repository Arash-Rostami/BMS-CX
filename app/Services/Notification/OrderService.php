<?php

namespace App\Services\Notification;

use App\Services\Notification\BaseService;

class OrderService extends BaseService
{
    protected string $moduleName = 'order';
    protected string $resourceRouteName = 'orders';

    /**
     * Override to display the invoice number and reference number.
     */
    protected function getRecordDisplay($record): string
    {
        return $record->invoice_number . ' (' . $record->reference_number . ')';
    }

    /**
     * Notify agents about the order.
     */
    public function notifyAgents($record, $type = 'new'): void
    {
        $this->notifyUsers($record, $type);
    }
}
