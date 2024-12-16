<?php

namespace App\Services\Notification;

use App\Services\Notification\BaseService;

class ProformaInvoiceService extends BaseService
{

    protected string $moduleName = 'proformaInvoice';
    protected string $resourceRouteName = 'proforma-invoices';

    /**
     * Override to display the proforma number and reference number.
     */
    protected function getRecordDisplay($record): string
    {
        return $record->proforma_number . ' (' . $record->reference_number . ')';
    }

    /**
     * Notify agents about the proforma invoice.
     */
    public function notifyAgents($record, $type = 'new', $status = false): void
    {
        $this->notifyUsers($record, type: $type, status: $status);
    }
}
