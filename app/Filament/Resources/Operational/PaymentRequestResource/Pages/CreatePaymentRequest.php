<?php

namespace App\Filament\Resources\Operational\PaymentRequestResource\Pages;

use App\Filament\Resources\PaymentRequestResource;
use App\Models\User;
use App\Notifications\PaymentRequestStatusNotification;
use App\Services\AttachmentCreationService;
use App\Services\Notification\PaymentRequestService;
use App\Services\RetryableEmailService;
use App\Services\SmartPaymentRequest;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Cache;
use VXM\Async\AsyncFacade as Async;


class CreatePaymentRequest extends CreateRecord
{
    protected static string $resource = PaymentRequestResource::class;


    public ?int $id = null;
    public ?string $module = null;
    public ?string $type = null;

    protected array $queryString = ['id', 'module', 'type'];


    protected function afterFill(): void
    {
        SmartPaymentRequest::fillForm($this->id, $this->module, $this->form, $this->type);
    }


    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['extra']['made_by'] = auth()->user()->full_name;

        if (!isset($data['extra']['collectivePayment'])) $data['extra']['collectivePayment'] = 1;

        if (!isset($data['total_amount'])) $data['total_amount'] = $data['requested_amount'];

        $data = $this->persistAccountNo($data);

        return $this->getProformaInvoiceNumber($data);
    }

    protected function getProformaInvoiceNumber(array $data): array
    {
        if (!isset($data['proforma_invoice_number']) && $data['department_id'] == 6) {
            $data['proforma_invoice_number'] = $data['hidden_proforma_number'];
        }

        if (isset($data['hidden_proforma_number'])) {
            unset($data['hidden_proforma_number']);
        }

        if ($data['use_existing_attachments']) {
            Cache::put('available_attachments', $data['available_attachments'], 10);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->record;

        persistReferenceNumber($record, 'PR');

        $service = new PaymentRequestService();

        Async::run(function () use ($record, $service) {
            AttachmentCreationService::createFromExisting($record->id, 'payment_request_id');

            $service->notifyAccountants($record);
        });
    }

    /**
     * @param array $data
     * @return array
     */
    public function persistAccountNo(array $data): array
    {
        if (!data_get($data, 'extra.paymentMethod') || data_get($data, 'extra.paymentMethod') === 'cash') {
            return $data;
        }

        $methods = [
            'sheba' => 'sheba_number',
            'card_transfer' => 'card_transfer_number',
            'bank_account' => 'account_number',
        ];

        $paymentMethod = data_get($data, 'extra.paymentMethod');
        $data['account_number'] = data_get($data, $methods[$paymentMethod] ?? null);

        return $data;
    }
}
