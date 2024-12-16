<?php

namespace App\Filament\Resources\Operational\ProformaInvoiceResource\Pages;

use App\Filament\Resources\ProformaInvoiceResource;
use App\Models\Attachment;
use App\Models\User;
use App\Notifications\ProformaInvoiceStatusNotification;
use App\Services\AttachmentCreationService;
use App\Services\Notification\ProformaInvoiceService;
use App\Services\NotificationManager;
use App\Services\RetryableEmailService;
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

        $service = new ProformaInvoiceService();

        $service->notifyAgents($this->record);

        AttachmentCreationService::createFromExisting($this->record->id);
    }


//    public function notifyViaEmail($agents): void
//    {
//        $arguments = [$agents, new ProformaInvoiceStatusNotification($this->record)];
//// FOR TEST PURPOSE
//       $arguments = [User::getUsersByRole('admin'), new OrderRequestStatusNotification($this->record)];
//
//        RetryableEmailService::dispatchEmail('order request', ...$arguments);
//    }
}
