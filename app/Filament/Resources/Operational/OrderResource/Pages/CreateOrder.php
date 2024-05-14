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
use App\Notifications\FilamentNotification;
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


        return sprintf(
            'ORD-%s-%s-%s', $product, $supplier, $formattedCalendar,
        );
    }


    protected function mutateFormDataBeforeCreate(array $data): array
    {

        dd($data);
        $data['invoice_number'] = 'default';

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->record->extra['manual_invoice_number']) {
            $this->record->invoice_number = $this->record->extra['invoice_number'];
        }
        $this->record->invoice_number = $this->makeInvoiceNumber();

        $this->record->save();

//        $data = [
////            'recipients' => User::getUsersByRoles(['manager','agent'])
//        ];

        foreach (User::getUsersByRole('admin') as $recipient) {
            $recipient->notify(new FilamentNotification([
                'record' => $this->record->invoice_number,
                'type' => 'new',
                'module' => 'order',
                'url' => route('filament.admin.resources.orders.view', ['record' => $this->record->id]),
            ]));
        }
    }

}
