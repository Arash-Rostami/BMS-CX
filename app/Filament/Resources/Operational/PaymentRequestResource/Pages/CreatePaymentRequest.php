<?php

namespace App\Filament\Resources\Operational\PaymentRequestResource\Pages;

use App\Filament\Resources\PaymentRequestResource;
use App\Services\AttachmentCreationService;
use App\Services\SmartPaymentRequest;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Cache;


class CreatePaymentRequest extends CreateRecord
{
    private const CX_DEP = 6;
    private const THROTTLE_SECONDS = 15;
    private const CACHE_TTL = 10;
    private const PAYMENT_METHODS = [
        'sheba' => 'sheba_number',
        'card_transfer' => 'card_transfer_number',
        'bank_account' => 'account_number',
    ];
    protected static string $resource = PaymentRequestResource::class;
    public ?int $id = null;
    public ?string $module = null;
    public ?string $type = null;
    public bool $duplicateConfirmed = false;
    public bool $isCreating = false;
    protected array $queryString = ['id', 'module', 'type'];

    public function handleConfirmDuplicate(): void
    {
        if ($this->isCreating) return;

        $this->duplicateConfirmed = true;
        $this->isCreating = true;
        $this->create();
    }

    public function normalizeAccountNumber(array $data): array
    {
        $method = data_get($data, 'extra.paymentMethod');
        if (!$method || $method === 'cash') return $data;

        $field = self::PAYMENT_METHODS[$method] ?? null;
        if ($field) $data['account_number'] = data_get($data, $field);

        return $data;
    }

    protected function afterCreate(): void
    {
        persistReferenceNumber($this->record, 'PR');
        AttachmentCreationService::createFromExisting($this->record->id, 'payment_request_id');
//        (new PaymentRequestService())->notifyAccountants($this->record);
    }

    protected function afterFill(): void
    {
        SmartPaymentRequest::fillForm($this->id, $this->module, $this->form, $this->type);
    }

    protected function getListeners(): array
    {
        return array_merge(parent::getListeners(), [
            'confirmDuplicateCreate' => 'handleConfirmDuplicate',
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->validateThrottle(auth()->id());

        $data = $this->enrichData($data);
        $data = $this->normalizeAccountNumber($data);
        $data = $this->normalizeProformaData($data);

        $this->validateDuplicate($data);
        $this->setThrottle(auth()->id());

        return $data;
    }

    private function enrichData(array $data): array
    {
        $data['extra']['made_by'] = auth()->user()?->full_name;
        if (!isset($data['extra']['collectivePayment'])) $data['extra']['collectivePayment'] = 1;
        if (!isset($data['total_amount'])) $data['total_amount'] = $data['requested_amount'];

        return $data;
    }

    private function normalizeProformaData(array $data): array
    {
        if (!isset($data['proforma_invoice_number']) && $data['department_id'] == self::CX_DEP) {
            $data['proforma_invoice_number'] = $data['hidden_proforma_number'];
        }

        if (isset($data['hidden_proforma_number'])) {
            unset($data['hidden_proforma_number']);
        }

        if ($data['use_existing_attachments'] ?? false) {
            Cache::put('available_attachments', $data['available_attachments'], self::CACHE_TTL);
        }

        return $data;
    }

    private function setThrottle(int $userId): void
    {
        Cache::put(
            "pr_throttle_{$userId}",
            now()->addSeconds(self::THROTTLE_SECONDS)->timestamp,
            self::THROTTLE_SECONDS
        );
    }

    private function validateDuplicate(array $data): void
    {
        if ($this->duplicateConfirmed || $data['department_id'] != self::CX_DEP) return;

        $exists = static::getModel()::query()
            ->where('type_of_payment', $data['type_of_payment'])
            ->where('requested_amount', $data['requested_amount'])
            ->where('currency', $data['currency'])
            ->where('department_id', self::CX_DEP)
            ->where('proforma_invoice_number', $data['proforma_invoice_number'] ?? null)
            ->where('order_id', $data['order_id'] ?? null)
            ->where(fn($subQuery) => $subQuery
                ->where('contractor_id', $data['contractor_id'] ?? null)
                ->orWhere('supplier_id', $data['supplier_id'] ?? null))
            ->whereNull('deleted_at')
            ->exists();

        if (!$exists) return;

        Notification::make()
            ->warning()
            ->title('Possible Duplicate')
            ->body('Similar payment request found. Do you want to continue?')
            ->persistent()
            ->actions([
                Action::make('continue')
                    ->button()
                    ->color('warning')
                    ->dispatch('confirmDuplicateCreate'),
                Action::make('cancel')
                    ->color('gray')
                    ->close(),
            ])
            ->send();

        $this->halt();
    }

    private function validateThrottle($userId): void
    {
        $cacheKey = "pr_throttle_{$userId}";

        if (!Cache::has($cacheKey)) return;

        Notification::make()
            ->danger()
            ->title('Please Wait')
            ->body('You can create another payment request in ' . (Cache::get($cacheKey) - now()->timestamp) . ' seconds.')
            ->persistent()
            ->send();

        $this->halt();
    }
}
