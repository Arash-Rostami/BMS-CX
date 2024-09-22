<?php

namespace App\Filament\Resources\Operational\OrderResource\Pages;

use App\Filament\Resources\Operational\OrderResource\Pages\AdminComponents\Filter;
use App\Filament\Resources\Operational\OrderResource\Pages\AdminComponents\Form;
use App\Filament\Resources\Operational\OrderResource\Pages\AdminComponents\Table;
use App\Filament\Resources\Operational\ProformaInvoiceResource\Pages\CreateProformaInvoice;
use App\Models\Attachment;
use App\Models\Order;
use App\Models\ProformaInvoice;
use App\Notifications\FilamentNotification;
use App\Services\AttachmentDeletionService;
use App\Services\OrderService;
use App\Services\ProjectNumberGenerator;
use Archilex\ToggleIconColumn\Columns\ToggleIconColumn;
use Carbon\Carbon;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Support\Enums\Alignment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Component as Livewire;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;


class Admin
{

    use Form, Table, Filter;


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
            $name = $get('name') ?? $record->name ?? 'NO-NAME-GIVEN';
            $number = $livewire->data['proforma_number'] ?? optional($record->order)->proforma_number ?? 'No-Proforma-No-';
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
            $proformaInvoice = ProformaInvoice::findOrFail($state);
            $set('proforma_number', optional($proformaInvoice)->proforma_number ?? '');
            $set('proforma_date', optional($proformaInvoice)->proforma_date ?? '');
            $set('orderDetail.extra.percentage', optional($proformaInvoice)->percentage ?? '');
            $set('category_id', $proformaInvoice->category_id ?? '');
            $set('product_id', $proformaInvoice->product_id ?? '');
            $set('grade_id', $proformaInvoice->grade_id ?? '');
            $set('party.buyer_id', $proformaInvoice->buyer_id ?? '');
            $set('party.supplier_id', $proformaInvoice->supplier_id ?? '');
            $set('orderDetail.buying_price', $proformaInvoice->price ?? '');
            $set('orderDetail.buying_quantity', $proformaInvoice->quantity ?? '');
            $set('purchase_status_id', '1');
            $set('order_status', 'processing');
            $set('invoice_number', ProjectNumberGenerator::generate());
        }
    }

    public static function send(Model $record): void
    {
        $agents = (new OrderService())->fetchAgents();

        foreach ($agents as $recipient) {
            $recipient->notify(new FilamentNotification([
                'record' => ($record->invoice_number ?? 'N/A') . ' (' . $record->reference_number . ')',
                'type' => 'delete',
                'module' => 'order',
                'url' => route('filament.admin.resources.orders.index'),
            ]));
        }
    }

    public static function updateFormBasedOnPreviousRecords(Get $get, Set $set, ?string $state): ?int
    {
        $id = $get('proforma_invoice_id');
        if (!$id || !$state || ($orders = Order::findByProformaInvoiceId($id))->isEmpty()) {
            return null;
        }

        $projectNumbers = $orders->map(function ($order) {
            return $order->invoice_number;
        })->filter()->unique();

        $message = $projectNumbers->count() === 1
            ? $projectNumbers->first()
            : "🔴 Multiple Project Numbers: " . $projectNumbers->join(', ');

        $set('invoice_number', $message);

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


    protected static function calculateSummaries($type, $query)
    {
        $cacheKey = 'order_summaries_' . $type;

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $query = $query->join('order_details', 'orders.order_detail_id', '=', 'order_details.id');

        if ($type === 'payment') {
            $totalPayments = $query
                ->selectRaw("
                CONCAT(
                    CAST(COALESCE(SUM(JSON_EXTRACT(order_details.extra, '$.initialPayment')), 0) AS DECIMAL(10,2)),
                   ' | ',
                    CAST(COALESCE(SUM(JSON_EXTRACT(order_details.extra, '$.provisionalTotal')), 0) AS DECIMAL(10,2)),
                    ' | ',
                    CAST(COALESCE(SUM(JSON_EXTRACT(order_details.extra, '$.finalTotal')), 0) AS DECIMAL(10,2))
                ) as totals
            ")->value('totals');

            Cache::put($cacheKey, $totalPayments, 1);

            return $totalPayments;
        }

        if ($type === 'quantity') {
            $totalQuantities = $query
                ->selectRaw("
                CONCAT(
                    CAST(COALESCE(SUM(order_details.provisional_quantity), 0) AS DECIMAL(10,2)),
                    ' | ',
                    CAST(COALESCE(SUM(order_details.final_quantity), 0) AS DECIMAL(10,2))
                ) as quantities
            ")->value('quantities');

            Cache::put($cacheKey, $totalQuantities, 1);

            return $totalQuantities;
        }

        return null;
    }

    public static function isPaymentCalculated($record): bool
    {
        return (optional($record->orderDetail->extra)['provisionalTotal'] != 0.0 &&
                optional($record->orderDetail->extra)['provisionalTotal'] != null)
            ||
            (optional($record->orderDetail->extra)['finalTotal'] != 0.0 &&
                optional($record->orderDetail->extra)['finalTotal'] != null);
    }

    public static function increasePart($replica)
    {
        $highestPart = Order::where('proforma_invoice_id', $replica->proforma_invoice_id)
            ->max('part');

        $replica->part = $highestPart + 1;
        $replica->user_id = auth()->id();
    }

    public static function replicateRelatedModels(Model $replica): void
    {
        $relationships = [
            'orderDetail' => 'order_detail_id',
            'party' => 'party_id',
            'logistic' => 'logistic_id',
            'doc' => 'doc_id',
        ];

        array_map(function ($idField, $relation) use ($replica) {
            if ($replica->$idField) {
                $relatedModel = $replica->$relation->replicate();
                $relatedModel->save();
                $replica->$idField = $relatedModel->id;
            }
        }, array_values($relationships), array_keys($relationships));


        $replica->save();
    }


    public static function syncOrder(Model $replica): void
    {
        $service = new OrderService();
        $agents = $service->fetchAgents();
        $service->persistReferenceNumber($replica);
        $service->notifyAgents($replica, $agents);
    }
}
