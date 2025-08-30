<?php

namespace App\Filament\Resources\Operational\ProformaInvoiceResource\Pages;

use App\Filament\Resources\ProformaInvoiceResource;
use App\Services\AttachmentCreationService;
use App\Services\Notification\ProformaInvoiceService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Cache;

class CreateProformaInvoice extends CreateRecord
{
    protected static string $resource = ProformaInvoiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['status']) || $data['status'] == 'pending') {
            $data['status'] = 'approved';
        }

        if ($data['use_existing_attachments']) {
            Cache::put('available_attachments', $data['available_attachments'], 10);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        persistReferenceNumber($this->record, 'PI');

        (new ProformaInvoiceService())->notifyAgents($this->record);

        AttachmentCreationService::createFromExisting($this->record->id);
    }
}
