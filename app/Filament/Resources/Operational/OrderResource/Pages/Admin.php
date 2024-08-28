<?php

namespace App\Filament\Resources\Operational\OrderResource\Pages;

use App\Filament\Resources\Operational\OrderRequestResource\Pages\CreateOrderRequest;
use App\Filament\Resources\Operational\OrderResource\Pages\AdminComponents\Filter;
use App\Filament\Resources\Operational\OrderResource\Pages\AdminComponents\Form;
use App\Filament\Resources\Operational\OrderResource\Pages\AdminComponents\Table;
use App\Models\Attachment;
use App\Models\Order;
use App\Models\OrderRequest;
use App\Notifications\FilamentNotification;
use App\Services\ProjectNumberGenerator;
use Archilex\ToggleIconColumn\Columns\ToggleIconColumn;
use Carbon\Carbon;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Support\Enums\Alignment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Livewire\Component as Livewire;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;


class Admin
{

    use Form, Table, Filter;

    protected static array $parts = [];

    protected static array $statusTexts = [
        'processing' => 'Processing',
        'closed' => 'Closed',
        'cancelled' => 'Cancelled',
    ];

    protected static array $statusIcons = [
        'processing' => 'heroicon-s-arrow-path-rounded-square',
        'closed' => 'heroicon-s-check-circle',
        'cancelled' => 'heroicon-s-no-symbol',
    ];

    protected static array $statusColors = [
        'processing' => 'warning',
        'closed' => 'success',
        'cancelled' => 'danger',
    ];

    public static array $documents = [
        'INSURANCE' => 'Insurance',
        'COA' => 'COA',
        'COO' => 'COO',
        'PL' => 'PL',
        'SGS' => 'Inspection',
        'DECLARATION' => 'Declaration',
        'FINAL INVOICE' => 'Final Invoice',
        'FINAL LOADING LIST FROM SUPPLIER' => 'Final Loading List',
    ];


    public static function nameUploadedFile(): \Closure
    {
        return function (TemporaryUploadedFile $file, Get $get, ?Model $record, Livewire $livewire): string {
            $name = $get('name') ?? $record->name;
            $number = $livewire->data['proforma_number'] ?? optional($record->order)->proforma_number;
            // File extension
            $extension = $file->getClientOriginalExtension();

            // Unique identifier
            $timestamp = Carbon::now()->format('YmdHis');
            // New filename with extension
            $newFileName = "Order-{$number}-{$timestamp}-{$name}";

            // Sanitizing the file name
            return Str::slug($newFileName, '-') . ".{$extension}";
        };
    }


    private static function updateForm(?string $state, Set $set): void
    {
        if ($state) {
            $orderRequest = OrderRequest::findOrFail($state);
            $set('proforma_number', optional($orderRequest->extra)['proforma_number'] ?? '');
            $set('proforma_date', optional($orderRequest->extra)['proforma_date'] ?? '');
            $set('orderDetail.extra.percentage', optional($orderRequest->extra)['percentage'] ?? '');
            $set('category_id', $orderRequest->category_id ?? '');
            $set('product_id', $orderRequest->product_id ?? '');
            $set('grade', $orderRequest->grade ?? '');
            $set('party.buyer_id', $orderRequest->buyer_id ?? '');
            $set('party.supplier_id', $orderRequest->supplier_id ?? '');
            $set('orderDetail.buying_price', $orderRequest->price ?? '');
            $set('orderDetail.buying_quantity', $orderRequest->quantity ?? '');
            $set('purchase_status_id', '1');
            $set('order_status', 'processing');
            $set('extra.manual_invoice_number', ProjectNumberGenerator::generate());
        }
    }

    public static function send(Model $record): void
    {
        $agents = (new CreateOrderRequest())->fetchAgents();

        foreach ($agents as $recipient) {
            $recipient->notify(new FilamentNotification([
                'record' => $record->invoice_number,
                'type' => 'delete',
                'module' => 'order',
                'url' => route('filament.admin.resources.orders.index'),
            ]));
        }
    }

    public static function updateFormBasedOnPreviousRecords(Get $get, Set $set, ?string $state): ?int
    {
        $id = $get('order_request_id');
        if (!$id || !$state || $state === '1' || ($orders = Order::findByOrderRequestId($id))->isEmpty()) {
            return null;
        }

        $invoiceNumbers = $orders->map(function ($order) {
            return $order->invoice_number ?? $order->extra['manual_invoice_number'] ?? null;
        })->filter()->unique();

        $message = $invoiceNumbers->count() === 1
            ? $invoiceNumbers->first()
            : "🔴 Multiple Project Numbers: " . $invoiceNumbers->join(', ');

        $set('extra.manual_invoice_number', $message);

        return $id;
    }



    public static function showAllDocs()
    {
        $columns = [];
        foreach (self::$documents as $key => $label) {
            $labelTrimmed = slugify($label);

            $columns[] = ToggleIconColumn::make("extra.docs_received.$key")
                ->disabled(true)
                ->onColor('main')
                ->offColor('main')
                ->alignment(Alignment::Center)
                ->toggleable()
                ->extraAttributes(fn($record) => self::getExtraAttributes($record, $labelTrimmed))
                ->tooltip(fn($record) => self::getTooltip($record, $labelTrimmed))
                ->label($label);
        }

        return $columns;
    }

    public static function formatPaySlip(Model $record): string
    {
        if (!$record->orderDetail || !$record->orderDetail->extra) {
            return 'N/A';
        }

        $extra = $record->orderDetail->extra;
        return sprintf(
            '<div class="percentage-display">
                    <span class="currency">%s</span>:
                    <span class="payment">%s</span>/<span class="total">%s</span>
                    (<span class="remaining">🧾 %s</span>)
                 </div>',
            $extra['currency'] ?? '',
            numberify($extra['payment'] ?? 0),
            numberify($extra['total'] ?? 0),
            numberify($extra['remaining'] ?? 0)
        );
    }


    private static function getExtraAttributes($record, $labelTrimmed)
    {
        return Attachment::hasTitleContainingPart($labelTrimmed, $record->id)
            ? ['style' => 'background-color: #41a441; border-radius: 50%; transform: scale(.65); cursor:help ']
            : ['style' => 'background-color: #b34747; border-radius: 50%; transform: scale(.65); cursor:help '];
    }

    private static function getTooltip($record, $labelTrimmed)
    {
        return Attachment::hasTitleContainingPart($labelTrimmed, $record->id) ? 'Attached' : 'Not Given';
    }

    private static function shouldDisableInput($livewire): bool
    {

        $data = $livewire->data;
        $currentPart = $data['part'] ?? null;
        $manualInvoiceNumber = $data['invoice_number'] ?? $data['extra']['manual_invoice_number'] ?? null;

        if ($currentPart == 1) {
            return Order::where('invoice_number', $manualInvoiceNumber)
                ->where('part', '!=', 1)
                ->exists();
        }

        return false;
    }
}
