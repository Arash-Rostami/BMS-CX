<?php

namespace App\Filament\Resources\Operational\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Attachment;
use App\Models\Doc;
use App\Models\Logistic;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Party;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Services\NotificationManager;
use Filament\Forms\Get;
use Filament\Resources\Pages\CreateRecord;
use Livewire\Livewire;
use Livewire\Component as LivewireData;


class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;


    /**
     * @return string
     */
    public function makeInvoiceNumber(): string
    {
        $product = Product::find($this->record->product_id)->name;

        $supplier = Supplier::find($this->record->party->supplier_id)->name;

        $formattedCalendar = date('Y', time());

        $orderNumber = Order::latest('id')->value('id') + 1;


        return sprintf(
            'ORD-%s-%s-%s-%s', $orderNumber, $product, $supplier, $formattedCalendar,
        );
    }


    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['invoice_number'] = 'default';

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->invoice_number = $this->makeInvoiceNumber();

        $this->record->save();

        $data = [
            'record' => $this->record->invoice_number,
            'type' => 'new',
            'module' => 'order',
            'url' =>  route('filament.admin.resources.orders.view', ['record' => $this->record->id]),
            'recipients' => User::getUsersByRoles(['manager','agent'])
        ];

        NotificationManager::send($data);
    }

}
