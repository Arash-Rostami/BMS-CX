<?php

namespace App\Filament\Resources\Operational\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Attachment;
use App\Services\AttachmentCreationService;
use App\Services\Notification\OrderService;
use App\Services\ProjectNumberGenerator;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Cache;


class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;


    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['invoice_number'])) {
            $data['invoice_number'] = ProjectNumberGenerator::generate();
        }
        if ($data['use_existing_attachments']) {
            Cache::put('available_attachments', $data['available_attachments'], 10);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        persistReferenceNumber($this->record, 'O');

        $service = new OrderService();

        $service->notifyAgents($this->record);

        AttachmentCreationService::createFromExisting($this->record->id, 'order_id');
    }
}
