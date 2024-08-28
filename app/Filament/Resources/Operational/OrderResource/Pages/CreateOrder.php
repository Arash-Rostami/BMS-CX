<?php

namespace App\Filament\Resources\Operational\OrderResource\Pages;

use App\Filament\Resources\Operational\OrderRequestResource\Pages\CreateOrderRequest;
use App\Filament\Resources\OrderResource;
use App\Notifications\FilamentNotification;
use Filament\Resources\Pages\CreateRecord;
use Livewire\Livewire;
use Livewire\Component as LivewireData;


class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;



    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['invoice_number'] = 'N/A';

        return $data;
    }

    protected function afterCreate(): void
    {
        $agents = (new CreateOrderRequest())->fetchAgents();
        $this->persistInvoiceNumbers();
        $this->persistReferenceNumber();


        foreach ($agents as $recipient) {
            $recipient->notify(new FilamentNotification([
                'record' => $this->record->invoice_number,
                'type' => 'new',
                'module' => 'order',
                'url' => route('filament.admin.resources.orders.view', ['record' => $this->record->id]),
            ]));
        }
    }

    /**
     * @return void
     */
    // THIS HAS CHANGED INTO PROJECT NAME
    protected function persistInvoiceNumbers(): void
    {
        $newInvoiceNumber = $this->makeInvoiceNumber();

        $extra = $this->record->extra ?? [];

        $manualInvoiceNumber = $extra['manual_invoice_number'] ?? null;

        $this->record->invoice_number = $manualInvoiceNumber ?: $newInvoiceNumber;

        $extra['manual_invoice_number'] = $newInvoiceNumber;

        $this->record->extra = $extra;

        $this->record->save();
    }

    protected function persistReferenceNumber(): void
    {
        $yearSuffix = date('y');
        $orderIndex = $this->record->id;

        $referenceNumber = sprintf('O-%s%04d', $yearSuffix, $orderIndex);

        $extra = $this->record->extra ?? [];

        $extra['reference_number'] = $referenceNumber;

        $this->record->extra = $extra;

        $this->record->save();
    }

    /**
     * @return string
     */
    public function makeInvoiceNumber(): string
    {
        $product = trim($this->record->product->name ?? '');
        $supplier = trim($this->record->party->supplier->name ?? '');

//        $formattedDate = date('Y', time());

        return sprintf('ORD-%s-%s', $product, $supplier);
    }
}
