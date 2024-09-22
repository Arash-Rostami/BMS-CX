<?php

namespace App\Filament\Resources\Operational\OrderResource\Pages;

use App\Filament\Resources\Operational\ProformaInvoiceResource\Pages\CreateProformaInvoice;
use App\Filament\Resources\Operational\ProformaInvoiceResource\Pages\EditProformaInvoice;
use App\Filament\Resources\OrderResource;
use App\Models\Attachment;
use App\Notifications\FilamentNotification;
use App\Services\AttachmentCreationService;
use App\Services\OrderService;
use App\Services\ProjectNumberGenerator;
use Filament\Resources\Pages\CreateRecord;


class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;


    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['invoice_number'])) {
            $data['invoice_number'] = ProjectNumberGenerator::generate();
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $service = new OrderService();

        $agents = $service->fetchAgents();

        $service->persistReferenceNumber($this->record);

        $service->notifyAgents($this->record, $agents);

        AttachmentCreationService::createFromExisting($this->form->getState(), $this->record->id, 'order_id');
    }
}
