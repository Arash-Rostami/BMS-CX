<?php

namespace App\Filament\Resources\Operational\OrderResource\Pages;

use App\Filament\Resources\Operational\OrderResource\Pages\AdminComponents\Filter;
use App\Filament\Resources\Operational\OrderResource\Pages\AdminComponents\Form;
use App\Filament\Resources\Operational\OrderResource\Pages\AdminComponents\Table;
use App\Models\Name;
use App\Models\Order;
use App\Models\PortOfDelivery;
use App\Models\ProformaInvoice;
use App\Services\ColorTheme;
use App\Services\DeliveryDocumentService;
use App\Services\InfoExtractor;
use App\Services\Notification\OrderService;
use App\Services\ProjectNumberGenerator;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
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
        'processing' => 'Processing',
        'closed' => 'Closed',
        'cancelled' => 'Cancelled',
        'accounting_review' => 'Under Accounting Review',
        'accounting_approved' => 'Accounting Approved',
        'accounting_rejected' => 'Accounting Rejected',
    ];

    public static array $statusIcons = [
        'processing' => 'heroicon-s-arrow-path-rounded-square',
        'closed' => 'heroicon-s-check-circle',
        'cancelled' => 'heroicon-s-no-symbol',
        'accounting_review' => 'heroicon-s-eye',
        'accounting_approved' => 'heroicon-s-check-badge',
        'accounting_rejected' => 'heroicon-s-x-circle',
    ];

    public static array $statusColors = [
        'processing' => 'warning',
        'closed' => 'success',
        'cancelled' => 'danger',
        'accounting_review' => 'info',
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

    private static array $requestCache = [];


    public static function nameUploadedFile(): \Closure
    {
        return function (TemporaryUploadedFile $file, Get $get, ?Model $record, Livewire $livewire): string {
            $name = $get('name') ?? $record->name ?? 'NO-NAME-GIVEN';
            $number = $livewire->data['proforma_number'] ?? optional($record->order)->proforma_number ?? 'No-Proforma-No-';
            // File extension
            $extension = $file->getClientOriginalExtension();

            // New filename with extension & unique identifier
            $newFileName = sprintf('O-%s-%s-%s-%s', $number, now()->format('YmdHis'), Str::random(5), $name);

            // Sanitizing the file name
            return Str::slug($newFileName, '-') . ".{$extension}";
        };
    }

    public static function updatePortData(mixed $proformaInvoice, mixed $replica): void
    {
        [$matchedPortData, $portOfDeliveryId] = self::extractPortData($proformaInvoice, (string)$replica->part);
        if ($matchedPortData && ($matchedPortData['partNumber'] ?? null) == $replica->part) {
            $replica->orderDetail()->update(['provisional_quantity' => $matchedPortData['quantity'] ?? null]);
            $replica->logistic()->update(['port_of_delivery_id' => $portOfDeliveryId]);
        }
    }

    public static function extractPortData(ProformaInvoice $pi, string $state = '1'): array
    {
        $data = InfoExtractor::getPortInfo($pi, $state);
        $city = $data['city'] ?? '';

        $portId = $city !== ''
            ? PortOfDelivery::where('name', $city)->value('id')
            : null;

        return [$data, $portId];
    }

    public static function updateFormBasedOnPreviousRecords(Get $get, Set $set, ?string $state): ?int
    {
        $id = $get('proforma_invoice_id');
        if (!$id || !$state) return null;


        $proformaInvoice = ProformaInvoice::find($id);
        if (!$proformaInvoice) return null;


        [$matchedPortData, $portOfDeliveryId] = self::extractPortData($proformaInvoice, $state);

        if ($matchedPortData) {
            $set('logistic.port_of_delivery_id', $portOfDeliveryId);
            $set('orderDetail.provisional_quantity', $matchedPortData['quantity'] ?? '');
        }

        $set('orderDetail.provisional_price', $proformaInvoice->price ?? '');

        if (($orders = Order::findByProformaInvoiceId($id))->isEmpty()) {
            return null;
        }

        $orders
            ->pluck('invoice_number')
            ->filter()
            ->unique()
            ->whenNotEmpty(function ($projectNumbers) use ($set) {
                $message = $projectNumbers->count() === 1
                    ? $projectNumbers->first()
                    : "🔴 Multiple Project Numbers: {$projectNumbers->join(', ')}";
                $set('invoice_number', $message);
            });

        return $id;
    }

    public static function showAllDocs()
    {
        $columns = [];
        foreach (self::getDynamicDocuments() as $key => $label) {
            $labelTrimmed = slugify($label);

            $columns[$labelTrimmed] = TextColumn::make("extra.docs.$key")
                ->label($label)
                ->grow(false)
                ->state(fn() => $label)
                ->color(fn() => ColorTheme::White)
                ->extraAttributes(fn($record) => self::getExtraAttributes($record, $labelTrimmed))
                ->tooltip(fn($record) => self::getTooltip($record, $labelTrimmed))
                ->sortable();
        }

        return $columns;
    }

    public static function getDynamicDocuments(): array
    {
        return Cache::remember('dynamic_order_documents', 3600, function () {
            try {
                $documentsFromDatabase = Name::where('module', 'Order')->orderBy('title')->pluck('title');
                $documents = [];
                foreach ($documentsFromDatabase as $title) {
                    $documents[strtoupper($title)] = Str::ucfirst($title);
                }
                return $documents;
            } catch (\Exception $e) {
                return self::$documents;
            }
        });
    }

    private static function getExtraAttributes($record, $labelTrimmed)
    {
        $deliveryTermName = $record->logistic?->deliveryTerm?->name;

        if ($deliveryTermName) {
            $deliveryTermMap = DeliveryDocumentService::getForTerm(trim($deliveryTermName));
            $shouldBeShown = isset($deliveryTermMap[$labelTrimmed]) && $deliveryTermMap[$labelTrimmed] === true;

            if (!$shouldBeShown) {
                return ['style' => 'display:none;'];
            }
        }

        $hasAttachment = self::hasRelevantAttachment($labelTrimmed, $record);
        $color = $hasAttachment ? ColorTheme::MidnightTeal[500] : ColorTheme::DarkMaroon[500];

        return [
            'style' => "display:inline-flex;align-items:center;justify-content:center;padding:.25rem .5rem;margin:0 .25rem .25rem 0;gap:.25rem;min-width:3rem;font-size:.75rem;border:2px solid rgb({$color});background:rgb({$color});border-radius:.375rem;box-sizing:border-box;",
            'title' => $hasAttachment ? 'Has Attachment' : 'No Attachment',
        ];
    }

    public static function hasRelevantAttachment($title, $order)
    {
        $order->loadMissing([
            'attachments',
            'proformaInvoice.attachments'
        ]);

        $cacheKey = "lookup_{$order->id}_{$title}";
        if (array_key_exists($cacheKey, self::$requestCache)) {
            return self::$requestCache[$cacheKey];
        }

        $found = $order->attachments
            ->concat(optional($order->proformaInvoice)->attachments ?? collect())
            ->contains(function ($attachment) use ($title) {
                $haystack = $attachment->name ?: $attachment->file_path;
                return levenshtein($title, $haystack) === 0;
            });

        self::$requestCache[$cacheKey] = $found;
        return $found;
    }

    private static function getTooltip($record, $labelTrimmed)
    {
        return self::hasRelevantAttachment($labelTrimmed, $record)
            ? strtoupper($labelTrimmed) . ' Attached'
            : strtoupper($labelTrimmed) . ' Not Given';
    }

    public static function formatPaySlip(Model $record): string
    {
        $extra = $record->orderDetail;
        if (!$extra) return 'N/A';


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

    public static function isPaymentCalculated($record): bool
    {
        $detail = optional($record->orderDetail);
        $provisional = $detail->provisional_total;
        $final = $detail->final_total;

        return ($provisional !== null && $provisional != 0.0) || ($final !== null && $final != 0.0);
    }


    public static function increasePart($replica)
    {
        $highestPart = Order::where('proforma_invoice_id', $replica->proforma_invoice_id)->max('part');
        $replica->part = ($highestPart ?? 0) + 1;
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


    public static function updateAutoCompute(Model $replica): void
    {
        $replica->fill([
            'payment' => null,
            'remaining' => null,
            'total' => null,
            'initial_payment' => null,
            'initial_total' => null,
            'provisional_total' => null,
            'final_total' => null,
            'payable_quantity' => null,
        ]);

        $existingExtra = is_array($replica->extra)
            ? $replica->extra
            : (json_decode($replica->extra ?? '[]', true) ?? []);

        $replica->extra = array_merge($existingExtra, [
            'manualComputation' => false,
            'lastOrder' => false,
            'allOrders' => false,
        ]);

        $replica->save();
    }

    public static function syncOrder(Model $replica): void
    {
        persistReferenceNumber($replica, 'O');
        (new OrderService())->notifyAgents($replica);
    }

    public static function separateRecordsIntoDeletableAndNonDeletable(Collection $records): void
    {
        if ($records->isEmpty()) return;

        $records->loadMissing('paymentRequests');

        [$recordsNotDeleted, $recordsToDelete] = $records->partition(fn($record) => $record->paymentRequests->isNotEmpty());

        // Delete the records that have no paymentRequests
        if ($recordsToDelete->isNotEmpty()) {
            $recordsToDelete->each(function (Model $record) {
                $record->delete();
                self::send($record);
            });
        }

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

    public static function send(Model $record): void
    {
        (new OrderService())->notifyAgents($record, 'delete');
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

    private static function updateForm(?string $state, Set $set): void
    {
        if (!$state) return;

        $pi = ProformaInvoice::find($state);
        if (!$pi) return;

        $set('proforma_number', $pi->proforma_number);
        $set('proforma_date', $pi->proforma_date);
        $set('orderDetail.extra.percentage', $pi->percentage);
        $set('category_id', $pi->category_id);
        $set('product_id', $pi->product_id);
        $set('grade_id', $pi->grade_id);
        $set('party.buyer_id', $pi->buyer_id);
        $set('party.supplier_id', $pi->supplier_id);
        $set('orderDetail.buying_price', $pi->price);
        $set('orderDetail.buying_quantity', $pi->quantity);
        $set('purchase_status_id', '1');
        $set('order_status', 'processing');
        $set('invoice_number', $pi->contract_number ?? ProjectNumberGenerator::generate());
    }
}
