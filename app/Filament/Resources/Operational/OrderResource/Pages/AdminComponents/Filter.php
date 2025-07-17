<?php

namespace App\Filament\Resources\Operational\OrderResource\Pages\AdminComponents;

use App\Models\Buyer;
use App\Models\Category;
use App\Models\Grade;
use App\Models\Order;
use App\Models\PortOfDelivery;
use App\Models\Product;
use App\Models\PurchaseStatus;
use App\Models\Supplier;
use App\Models\Tag;
use App\Services\Traits\Calculator;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\Filter as FilamentFilter;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\QueryBuilder\Constraints\Constraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\DateConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\NumberConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\Operators\Operator;
use Filament\Tables\Filters\QueryBuilder\Constraints\SelectConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\TextConstraint;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;


trait Filter
{
    use Calculator;

    public static function filterOrderStatus(): SelectFilter
    {
        return SelectFilter::make('order_status')
            ->label('Status')
            ->options(self::$statusTexts);
    }

    public static function filterProforma()
    {
        return FilamentFilter::make('proforma_date')
            ->form([
                DatePicker::make('proforma_from')
                    ->placeholder(fn($state): string => 'Dec 18, ' . now()->subYear()->format('Y')),
                DatePicker::make('proforma_until')
                    ->placeholder(fn($state): string => now()->format('M d, Y')),
            ])
            ->query(function (Builder $query, array $data): Builder {
                return $query
                    ->when(
                        $data['proforma_from'],
                        fn(Builder $query, $date): Builder => $query->whereDate('proforma_date', '>=', $date),
                    )
                    ->when(
                        $data['proforma_until'],
                        fn(Builder $query, $date): Builder => $query->whereDate('proforma_date', '<=', $date),
                    );
            })
            ->indicateUsing(function (array $data): array {
                $indicators = [];
                if ($data['proforma_from'] ?? null) {
                    $indicators['proforma_from'] = 'Proforma from ' . Carbon::parse($data['proforma_from'])->toFormattedDateString();
                }
                if ($data['proforma_until'] ?? null) {
                    $indicators['proforma_until'] = 'Proforma until ' . Carbon::parse($data['proforma_until'])->toFormattedDateString();
                }

                return $indicators;
            });
    }

    public static function filterCreatedAt()
    {
        return FilamentFilter::make('created_at')
            ->form([
                DatePicker::make('created_from')
                    ->placeholder(fn($state): string => 'Dec 18, ' . now()->subYear()->format('Y')),
                DatePicker::make('created_until')
                    ->placeholder(fn($state): string => now()->format('M d, Y')),
            ])
            ->query(function (Builder $query, array $data): Builder {
                return $query
                    ->when(
                        $data['created_from'],
                        fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                    )
                    ->when(
                        $data['created_until'],
                        fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                    );
            })
            ->indicateUsing(function (array $data): array {
                $indicators = [];
                if ($data['created_at'] ?? null) {
                    $indicators['created_at'] = 'created from ' . Carbon::parse($data['created_at'])->toFormattedDateString();
                }
                if ($data['created_at'] ?? null) {
                    $indicators['created_at'] = 'created until ' . Carbon::parse($data['created_at'])->toFormattedDateString();
                }

                return $indicators;
            });
    }


    public static function filterSoftDeletes(): TrashedFilter
    {
        return TrashedFilter::make();
    }


    public static function filterBasedOnQuery(): QueryBuilder
    {
        return QueryBuilder::make()
            ->constraints([
                SelectConstraint::make('order_status')
                    ->label('Status')
                    ->icon('heroicon-o-flag')
                    ->multiple()
                    ->options(self::$statusTexts),
                SelectConstraint::make('purchaseStatus.id')
                    ->label('Stage')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->multiple()
                    ->options(fn() => PurchaseStatus::ordered()->pluck('name', 'id')),
                SelectConstraint::make('party.supplier.id')
                    ->label('Supplier')
                    ->icon('heroicon-o-arrow-up-on-square-stack')
                    ->options(fn() => Supplier::orderBy('name')->pluck('name', 'id'))
                    ->multiple(),
                SelectConstraint::make('party.buyer.id')
                    ->label('Buyer')
                    ->icon('heroicon-o-arrow-down-on-square-stack')
                    ->options(fn() => Buyer::orderBy('name')->pluck('name', 'id'))
                    ->multiple(),
                SelectConstraint::make('category_id')
                    ->label('Category')
                    ->icon('heroicon-o-rectangle-stack')
                    ->options(fn() => Category::orderBy('name')->pluck('name', 'id'))
                    ->multiple(),
                SelectConstraint::make('product_id')
                    ->label('Product')
                    ->icon('heroicon-o-squares-2x2')
                    ->options(fn() => Product::orderBy('name')->pluck('name', 'id'))
                    ->multiple(),
                SelectConstraint::make('grade_id')
                    ->label('Grade')
                    ->icon('heroicon-m-ellipsis-horizontal-circle')
                    ->options(fn() => Grade::orderBy('name')->pluck('name', 'id'))
                    ->multiple(),
                SelectConstraint::make('logistic.portOfDelivery.id')
                    ->label('Port of Delivery')
                    ->icon('heroicon-o-truck')
                    ->options(fn() => PortOfDelivery::orderBy('name')->pluck('name', 'id'))
                    ->multiple(),
                TextConstraint::make('proforma_number')
                    ->label('Proforma Number')
                    ->icon('heroicon-s-paper-clip'),
                TextConstraint::make('invoice_number')
                    ->label('Contract Number'),
                NumberConstraint::make('orderDetail.buying_price')
                    ->label('Initial Price')
                    ->icon('heroicon-m-currency-dollar'),
                NumberConstraint::make('orderDetail.provisional_price')
                    ->label('Provisional Price')
                    ->icon('heroicon-m-currency-pound'),
                NumberConstraint::make('orderDetail.final_price')
                    ->label('Final Price')
                    ->icon('heroicon-m-currency-euro'),
                NumberConstraint::make('orderDetail.buying_quantity')
                    ->label('Initial Quantity'),
                NumberConstraint::make('orderDetail.provisional_quantity')
                    ->label('Provisional Quantity')
                    ->icon('heroicon-o-chart-pie'),
                NumberConstraint::make('orderDetail.final_quantity')
                    ->label('Final Quantity')
                    ->icon('heroicon-o-calculator'),
                DateConstraint::make('proforma_date')
                    ->label('Proforma Date')
                    ->icon('heroicon-o-calendar'),
                DateConstraint::make('doc.bl_date')
                    ->label('BL Date')
                    ->icon('heroicon-o-calendar'),
                DateConstraint::make('doc.declaration_date')
                    ->label('Declaration Date')
                    ->icon('heroicon-o-calendar'),
                DateConstraint::make('logistic.extra->loading_startline')
                    ->icon('heroicon-m-calendar')
                    ->label('Loading Start Date'),
                DateConstraint::make('logistic.loading_deadline')
                    ->icon('heroicon-m-calendar')
                    ->label('Loading Deadline'),
                DateConstraint::make('logistic.extra->etd')
                    ->icon('heroicon-m-truck')
                    ->nullable(false)
                    ->label('ETD'),
                DateConstraint::make('logistic.extra->eta')
                    ->icon('heroicon-m-map')
                    ->label('ETA'),
                DateConstraint::make('created_at')
                    ->label('Creation Time')
                    ->icon('heroicon-o-clock'),
                Constraint::make('id')
                    ->label('With Payment')
                    ->icon('heroicon-o-exclamation-circle')
                    ->operators([
                        Operator::make('payment')
                            ->label(fn(bool $isInverse): string => $isInverse ? 'under payment process' : 'completed payment')
                            ->summary(fn(bool $isInverse): string => $isInverse ? 'Orders with allowed/approved payment request' : 'Orders with completed payment process')
                            ->baseQuery(fn(Builder $query, bool $isInverse) => $query->whereHas(
                                'paymentRequests',
                                fn(Builder $query) => $query->whereIn('status', $isInverse ? ['allowed', 'approved'] : ['completed'])
                            )),
                    ]),
            ])
            ->constraintPickerWidth('5xl')
            ->constraintPickerColumns(5);
    }

    // GROUP_BY FILTER/SORTING
    public static function groupByCategory(): Group
    {
        return Group::make('category_id')->label('Category')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => ucfirst($record->category->name ?? 'N/A'));
    }

    public static function groupByProduct(): Group
    {
        return Group::make('product_id')->label('Product')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => ucfirst($record->product->name ?? 'N/A'));
    }

    public static function groupByGrade(): Group
    {
        return Group::make('grade_id')->label('Grade')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => ucfirst($record->grade->name ?? 'N/A'));
    }

    public static function groupByStage(): Group
    {
        return Group::make('purchase_status_id')->label('Stage')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => ucfirst($record->purchaseStatus->name ?? 'N/A'));
    }

    public static function groupByStatus(): Group
    {
        return Group::make('order_status')->label('Status')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => self::$statusTexts[$record->order_status]
                ?? ucfirst(str_replace('_', ' ', $record->order_status))
            );
    }

    public static function groupByInvoiceNumber(): Group
    {
        return Group::make('invoice_number')->label('Project/Contract No')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record) => $record->invoice_number ?? 'N/A')
            ->getKeyFromRecordUsing(fn(Model $record) => $record->invoice_number ?? $record->proforma_number)
            ->getDescriptionFromRecordUsing(function (Model $record) {
//                $orders['paymentRequestCount'] = count($record->paymentRequests) ?? 0;
                $orders = Order::aggregateOrderGroupTotals($record);
                return sprintf(
                    "Rlsd Qty: %s ✦ Shpd Qty: %s ✦ Tot Qty: %s%s ✦ Tot Pay Rqsts: %s%s ✦ Misc Pay Rqsts: %s ✦ Pay Rqst Cnt: %s ✦ Pays Cnt: %s ✦ Ship Prt: %s ✦ Avg Ld Tm: %s days ✦ Dys Elpsd: %s",
                    $orders['releasedQuantity'],
                    $orders['shippedQuantity'],
                    $orders['quantity'],
                    $orders['quantityBalance'],
                    $orders['payment'],
                    $orders['paymentRequestBalance'],
                    $orders['totalOfOtherPaymentRequests'],
                    $orders['totalPaymentRequests'],
                    $orders['totalPayments'],
                    $orders['shipmentPart'],
                    $orders['averageLeadTime'],
                    $orders['daysPassed'],
                );
            });
    }

    public static function groupByProformaNumber(): Group
    {
        return Group::make('proforma_invoice_id')
            ->label('Pro forma Number')
            ->getTitleFromRecordUsing(fn(Model $record): string => $record->proforma_number != 'N/A' ? $record->proforma_number : 'N/A')
            ->getKeyFromRecordUsing(fn(Model $record): string => $record->proforma_number != 'N/A' ? $record->proforma_invoice_id : $record->proforma_number)
            ->collapsible();
    }

    public static function groupByPackaging(): Group
    {
        return Group::make('logistic.packaging_id')->label('Packaging')->collapsible()
            ->getKeyFromRecordUsing(fn(Model $record): string => optional($record->logistic->packaging)->name != null ? $record->logistic->packaging->name : 'N/A')
            ->getTitleFromRecordUsing(fn(Model $record): string => optional($record->logistic->packaging)->name ?? 'N/A');
    }

    public static function groupByDeliveryTerm(): Group
    {
        return Group::make('logistic.delivery_term_id')->label('Delivery Term')->collapsible()
            ->getKeyFromRecordUsing(fn(Model $record): string => optional($record->logistic->deliveryTerm)->name != null ? $record->logistic->deliveryTerm->name : 'N/A')
            ->getTitleFromRecordUsing(fn(Model $record): string => optional($record->logistic->deliveryTerm)->name ?? 'N/A');
    }

    public static function groupByShippingLine(): Group
    {
        return Group::make('logistic.shipping_line_id')->label('Shipping Carrier')->collapsible()
            ->getKeyFromRecordUsing(fn(Model $record): string => optional($record->logistic->shippingLine)->name != null ? $record->logistic->shippingLine->name : 'N/A')
            ->getTitleFromRecordUsing(fn(Model $record): string => optional($record->logistic->shippingLine)->name ?? 'N/A');
    }

    public static function groupByBuyer(): Group
    {
        return Group::make('party.buyer_id')->label('Buyer')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => optional($record->party->buyer)->name ?? 'N/A');
    }

    public static function groupBySupplier(): Group
    {
        return Group::make('party.supplier_id')->label('Supplier')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => optional($record->party->supplier)->name ?? 'N/A');
    }

    public static function groupByPart(): Group
    {
        return Group::make('part')->label('Part')->collapsible();
    }


    public static function groupByCurrency(): Group
    {
        return Group::make('orderDetail')->label('Currency')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => optional($record->orderDetail)->currency ?? 'N/A')
            ->getKeyFromRecordUsing(fn(Model $record): string => optional($record->orderDetail)->currency ?? 'N/A');
    }

    public static function groupByTags(): Group
    {
        return Group::make('tags.extra')->label('Tags')->collapsible()
            ->getKeyFromRecordUsing(fn(Model $record): string => self::useSpecificTagValue($record))
            ->getTitleFromRecordUsing(fn(Model $record): string => self::useSpecificTagValue($record));
    }


    protected static function useSpecificTagValue(Model $record): string
    {
        Tag::deleteEmptyOrderTags('Order');

        $record->load('tags');

        if ($record->tags->isEmpty()) return 'No Tag Assigned';

        $tagValues = $record->tags()
//            ->filteredForUser(auth()->id())
            ->get()
            ->map(function ($tag) {
                return implode(', ', array_filter($tag->extra));
            });

        return Tag::formatTags($tagValues);
    }
}
