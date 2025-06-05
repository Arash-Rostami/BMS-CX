<?php

namespace App\Filament\Resources\Operational\OrderResource\Pages;

use App\Filament\Resources\Operational\OrderResource\Pages\AdminComponents\Filter;
use App\Filament\Resources\Operational\OrderResource\Pages\AdminComponents\Form;
use App\Filament\Resources\Operational\OrderResource\Pages\AdminComponents\Table;
use App\Filament\Resources\Operational\ProformaInvoiceResource\Pages\CreateProformaInvoice;
use App\Models\Attachment;
use App\Models\Name;
use App\Models\Order;
use App\Models\PortOfDelivery;
use App\Models\ProformaInvoice;
use App\Notifications\FilamentNotification;
use App\Services\AttachmentDeletionService;
use App\Services\ColorTheme;
use App\Services\InfoExtractor;
use App\Services\Notification\OrderService;
use App\Services\ProjectNumberGenerator;
use Archilex\ToggleIconColumn\Columns\ToggleIconColumn;
use Carbon\Carbon;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Component as Livewire;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;


class Admin
{
    use Form, Table, Filter;

    public static array $statusTexts = [
        'processing'          => 'Processing',
        'closed'              => 'Closed',
        'cancelled'           => 'Cancelled',
        'accounting_review'   => 'Under Accounting Review',
        'accounting_approved' => 'Accounting Approved',
        'accounting_rejected' => 'Accounting Rejected',
    ];

    public static array $statusIcons = [
        'processing'          => 'heroicon-s-arrow-path-rounded-square',
        'closed'              => 'heroicon-s-check-circle',
        'cancelled'           => 'heroicon-s-no-symbol',
        'accounting_review'   => 'heroicon-s-eye',
        'accounting_approved' => 'heroicon-s-check-badge',
        'accounting_rejected' => 'heroicon-s-x-circle',
    ];

    public static array $statusColors = [
        'processing'          => 'warning',
        'closed'              => 'success',
        'cancelled'           => 'danger',
        'accounting_review'   => 'info',
        'accounting_approved' => 'success',
        'accounting_rejected' => 'danger',
    ];


    public static array $documents = [
        'COA' => 'COA',
        'COO' => 'COO',
        'DECLARATION' => 'Declaration',
        'FINAL INVOICE' => 'Final Invoice',
        'FINAL LOADING LIST FROM SUPPLIER' => 'Final Loading List',
        'INSURANCE' => 'Insurance',
        'PL' => 'PL',
        'SGS' => 'Inspection',
        'TELEX RELEASE' => 'Telex Release'
    ];

    public static function getDynamicDocuments()
    {
        try {
            $documentsFromDatabase = Name::where('module', 'Order')
                ->orderBy('title')
                ->pluck('title')
                ->toArray();

            $documents = [];
            foreach ($documentsFromDatabase as $title) {
                $documents[strtoupper($title)] = Str::ucfirst($title);
            }

            return $documents;
        } catch (\Exception $e) {
            return self::$documents;
        }
    }


    public static function nameUploadedFile(): \Closure
    {
        return function (TemporaryUploadedFile $file, Get $get, ?Model $record, Livewire $livewire): string {
            $name = $get('name') ?? $record->name ?? 'NO-NAME-GIVEN';
            $number = $livewire->data['proforma_number'] ?? optional($record->order)->proforma_number ?? 'No-Proforma-No-';
            // File extension
            $extension = $file->getClientOriginalExtension();

            // Unique identifier
            $timestamp = Carbon::now()->format('YmdHis');
            $randomString = Str::random(5);

            // New filename with extension
            $newFileName = "O-{$number}-{$timestamp}-{$randomString}-{$name}";

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
            $set('invoice_number',
                optional($proformaInvoice)->contract_number
                ?? ProjectNumberGenerator::generate());
        }
    }

    public static function send(Model $record): void
    {
        $service = new OrderService();
        $service->notifyAgents($record, 'delete');
    }

    public static function updateFormBasedOnPreviousRecords(Get $get, Set $set, ?string $state): ?int
    {
        $id = $get('proforma_invoice_id');

        if (!$id || !$state) {
            return null;
        }

        $proformaInvoice = ProformaInvoice::find($id);


        if (!$proformaInvoice) {
            return null;
        }

        $matchedPortData = InfoExtractor::getPortInfo($proformaInvoice, $state);

        if ($matchedPortData) {
            $portOfDeliveryId = PortOfDelivery::where('name', $matchedPortData['city'])->value('id');

            $set('logistic.port_of_delivery_id', $portOfDeliveryId ?? null);
            $set('orderDetail.provisional_quantity', $matchedPortData['quantity'] ?? '');
        }

        $set('orderDetail.provisional_price', $proformaInvoice->price ?? '');

        if (($orders = Order::findByProformaInvoiceId($id))->isEmpty()) {
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
        foreach (self::getDynamicDocuments() as $key => $label) {
            $labelTrimmed = slugify($label);

            $columns[] = TextColumn::make("extra.docs_received.$key")
                ->label($label)
                ->grow(false)
                ->state(function () use ($label) {
                    return $label;
                })
                ->color(fn() => ColorTheme::White)
                ->extraAttributes(fn($record) => self::getExtraAttributes($record, $labelTrimmed))
                ->tooltip(fn($record) => self::getTooltip($record, $labelTrimmed))
                ->sortable();
        }

        return $columns;
    }

    public static function formatPaySlip(Model $record): string
    {
        if (!$record->orderDetail) {
            return 'N/A';
        }

        $extra = $record->orderDetail;
        return sprintf(
            '<div class="percentage-display">
                    <span class="currency">%s</span>:
                    <span class="payment">%s</span>/<span class="total">%s</span>
                    (<span class="remaining">🧾 %s</span>)
                 </div>',
            $extra['currency'] ?? '',
            numberify($extra->payment ?? 0),
            numberify($extra->total ?? 0),
            numberify($extra->remaining ?? 0)
        );
    }

    public static function hasRelevantAttachment($title, $order)
    {
//        $cacheKey = 'attachment_title_containing_part_' . $title . '_' . $order->id;

//        return Cache::remember($cacheKey, 120, function () use ($title, $order) {
        return $order->attachments->contains(function ($attachment) use ($title) {
            $haystack = $attachment->name ?: $attachment->file_path;

            $distance = levenshtein($title, $haystack);

            return $distance === 0;
        });
//        });
    }


    private static function getExtraAttributes($record, $labelTrimmed)
    {
        $hasAttachment = self::hasRelevantAttachment($labelTrimmed, $record);
        $borderColor = $hasAttachment ? ColorTheme::MidnightTeal[500] : ColorTheme::DarkMaroon[500];
        $bgColor = $hasAttachment ? ColorTheme::MidnightTeal[500] : ColorTheme::DarkMaroon[500];

        return [
            'style' => "
            padding: 2px 4px;
            border-radius: 5px;
            background-color:  rgb({$bgColor});
            border: 2px solid rgb({$borderColor});
            display: inline-flex;
            font-size: 12px;
            align-items: center;
            justify-content: center;
            min-width: 50px;
        ", 'title' => $hasAttachment ? 'Has Attachment' : 'No Attachment',
        ];
    }

    private static function getDefaultValue($record, $labelTrimmed)
    {
        return self::hasRelevantAttachment($labelTrimmed, $record);
    }

    private static function getTooltip($record, $labelTrimmed)
    {
        return self::hasRelevantAttachment($labelTrimmed, $record)
            ? strtoupper($labelTrimmed) . ' Attached'
            : strtoupper($labelTrimmed) . ' Not Given';
    }


    protected static function calculateSummaries($type, $query)
    {

        $groupValue = $query->clone()->value('invoice_number');
        $cacheKey = 'order_summaries_' . $type . '_' . $groupValue;

        $query = $query->join('order_details', 'orders.order_detail_id', '=', 'order_details.id');


        if ($type === 'payment') {
            $totalPayments = $query
                ->selectRaw("
                 CONCAT(
                    FORMAT(COALESCE(SUM(order_details.initial_payment), 0), 2),
                    ' ┆ ',
                    FORMAT(COALESCE(SUM(order_details.provisional_total), 0), 2),
                    ' ┆ ',
                    FORMAT(COALESCE(SUM(order_details.final_total), 0), 2)
                ) as totals
            ")->value('totals');

            Cache::put($cacheKey, $totalPayments, now()->addMinutes(3));

            return $totalPayments;
        }

        if ($type === 'quantity') {
            $totalQuantities = $query
                ->selectRaw("
                CONCAT(
                    FORMAT(COALESCE(SUM(order_details.provisional_quantity), 0), 2),
                    ' ┆ ',
                    FORMAT(COALESCE(SUM(order_details.final_quantity - order_details.provisional_quantity), 0), 2)
                ) as quantities
            ")->value('quantities');

            Cache::put($cacheKey, $totalQuantities, now()->addMinutes(3));

            return $totalQuantities;
        }

        return null;
    }


    public static function isPaymentCalculated($record): bool
    {
        return (optional($record->orderDetail)->provisional_total != 0.0 &&
                optional($record->orderDetail)->provisional_total != null)
            ||
            (optional($record->orderDetail)->final_total != 0.0 &&
                optional($record->orderDetail)->final_total != null);
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
                if ($relation === 'orderDetail') {
                    self::updateAutoCompute($relatedModel);
                }
            }
        }, array_values($relationships), array_keys($relationships));


        $replica->save();
    }


    public static function syncOrder(Model $replica): void
    {
        persistReferenceNumber($replica, 'O');
        $service = new OrderService();
        $service->notifyAgents($replica);
    }

    public static function separateRecordsIntoDeletableAndNonDeletable(Collection $records): void
    {
        $recordsToDelete = $records->filter(fn($record) => $record->paymentRequests->isEmpty());
        $recordsNotDeleted = $records->filter(fn($record) => $record->paymentRequests->isNotEmpty());

        // Delete the records that have no paymentRequests
        $recordsToDelete->each->delete();
        $recordsToDelete->each(fn(Model $selectedRecord) => Admin::send($selectedRecord));

        if ($recordsNotDeleted->isNotEmpty()) {
            $recordNames = $recordsNotDeleted->pluck('reference_number')->join(', ');
            Notification::make()
                ->title('Some records were not deleted')
                ->body("The following records could not be deleted because they have payment requests: $recordNames.")
                ->warning()
                ->persistent()
                ->send();
        } else {
            Notification::make()
                ->title('Records deleted successfully')
                ->success()
                ->send();
        }
    }

    public static function extractPortData(ProformaInvoice $proformaInvoice, $state = '1'): array
    {
        $matchedPortData = InfoExtractor::getPortInfo($proformaInvoice, $state);

        if ($matchedPortData && $matchedPortData['city']) {
            $portOfDeliveryId = PortOfDelivery::where('name', $matchedPortData['city'])->value('id');
        }
        return [$matchedPortData, $portOfDeliveryId ?? null];
    }

    public static function updatePortData(mixed $proformaInvoice, mixed $replica): void
    {
        list($matchedPortData, $portOfDeliveryId) = Admin::extractPortData($proformaInvoice, (string)$replica->part);
        if ($matchedPortData && array_key_exists('partNumber', $matchedPortData) && $matchedPortData['partNumber'] == $replica->part) {
            $replica->orderDetail()->update(['provisional_quantity' => $matchedPortData['quantity'] ?? null]);
            $replica->logistic()->update(['port_of_delivery_id' => $portOfDeliveryId]);
        }
    }

    public static function updateAutoCompute(mixed $replica): void
    {
        $replica->payment = null;
        $replica->remaining = null;
        $replica->total = null;
        $replica->initial_payment = null;
        $replica->initial_total = null;
        $replica->provisional_total = null;
        $replica->final_total = null;
        $replica->payable_quantity = null;


        $existingExtra = is_array($replica->extra) ? $replica->extra : json_decode($replica->extra, true);
        $updatedExtra = array_merge($existingExtra ?? [], [
            'manualComputation' => false,
            'lastOrder' => false,
            'allOrders' => false,
        ]);

        $replica->extra = $updatedExtra;
        $replica->save();
    }
}
