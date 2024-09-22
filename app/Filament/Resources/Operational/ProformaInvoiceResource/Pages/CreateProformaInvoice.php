<?php

namespace App\Filament\Resources\Operational\ProformaInvoiceResource\Pages;

use App\Filament\Resources\ProformaInvoiceResource;
use App\Models\User;
use App\Notifications\ProformaInvoiceStatusNotification;
use App\Services\AttachmentCreationService;
use App\Services\NotificationManager;
use App\Services\ProformaInvoiceService;
use App\Services\RetryableEmailService;
use Filament\Resources\Pages\CreateRecord;

class CreateProformaInvoice extends CreateRecord
{
    protected static string $resource = ProformaInvoiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['status']) || $data['status'] == 'pending') {
            $data['status'] = 'approved';
        }
        return $data;
    }

    protected function afterCreate(): void
    {
        $service = new ProformaInvoiceService();

        $agents = $service->fetchAgents();

        $service->persistReferenceNumber($this->record);

        $service->notifyAgents($this->record, $agents);

        AttachmentCreationService::createFromExisting($this->form->getState(), $this->record->id);

//        $this->notifyViaEmail($agents);
//        $this->notifyManagement();
    }


    public function notifyManagement(): void
    {
        $dataStatus = [
            'record' => $this->record->proforma_number . ' (' . $this->record->reference_number . ')',
            'type' => 'pending',
            'module' => 'proformaInvoice',
            'url' => route('filament.admin.resources.proforma-invoices.index'),
//            'recipients' => User::getUsersByRole('manager').
            'recipients' => User::getUsersByRole('admin')
        ];

        NotificationManager::send($dataStatus, true);
    }

    public function notifyViaEmail($agents): void
    {
        $arguments = [$agents, new ProformaInvoiceStatusNotification($this->record)];
// FOR TEST PURPOSE
//       $arguments = [User::getUsersByRole('admin'), new OrderRequestStatusNotification($this->record)];

        RetryableEmailService::dispatchEmail('order request', ...$arguments);
    }
}
