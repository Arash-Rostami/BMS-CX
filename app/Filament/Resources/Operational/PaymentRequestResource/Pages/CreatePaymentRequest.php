<?php

namespace App\Filament\Resources\Operational\PaymentRequestResource\Pages;

use App\Filament\Resources\PaymentRequestResource;

use App\Models\User;
use App\Notifications\FilamentNotification;
use App\Notifications\PaymentRequestStatusNotification;
use App\Services\AttachmentCreationService;
use App\Services\PaymentRequestService;
use App\Services\RetryableEmailService;
use App\Services\SmartPaymentRequest;
use Filament\Resources\Pages\CreateRecord;


class CreatePaymentRequest extends CreateRecord
{
    protected static string $resource = PaymentRequestResource::class;


    public ?int $id = null;
    public ?string $module = null;

    protected array $queryString = ['id', 'module'];


    protected function afterFill(): void
    {
        SmartPaymentRequest::fillForm($this->id, $this->module, $this->form);
    }


    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['extra']['made_by'] = auth()->user()->full_name;

        if (!isset($data['extra']['collectivePayment'])) $data['extra']['collectivePayment'] = 1;

        $data = $this->getProformaInvoiceNumber($data);


        return $data;
    }

    protected function getProformaInvoiceNumber(array $data): array
    {
        if (!isset($data['proforma_invoice_number']) && $data['department_id'] == 6) {
            $data['proforma_invoice_number'] = $data['hidden_proforma_number'];
        }

        if (isset($data['hidden_proforma_number'])) {
            unset($data['hidden_proforma_number']);
        }
        return $data;
    }

    protected function afterCreate(): void
    {
        $service = new PaymentRequestService();
        $accountants = $service->fetchAccountants();
        $service->persistReferenceNumber($this->record);
        $service->notifyAccountants($this->record, $accountants);

        AttachmentCreationService::createFromExisting($this->form->getState(), $this->record->id, 'payment_request_id');

        $this->notifyViaEmail($accountants, $this->record);

//        $this->notifyManagement();
    }


    /**
     * @return void
     */
    public function notifyManagement(): void
    {
        foreach (User::getUsersByRole('manager') as $recipient) {
            $recipient->notify(new FilamentNotification([
                'record' => Admin::getOrderRelation($this->record),
                'type' => 'pending',
                'module' => 'payment',
                'url' => route('filament.admin.resources.payment-requests.index'),
            ], true));
        }
    }


    public function notifyViaEmail($accountants, $record): void
    {
        $arguments = [$accountants, new PaymentRequestStatusNotification($record)];
// FOR TEST PURPOSE
//       $arguments = [User::getUsersByRole('admin'), new PaymentRequestStatusNotification($this->record)];

        RetryableEmailService::dispatchEmail('payment request', ...$arguments);
    }
}
