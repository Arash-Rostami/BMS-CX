<?php

namespace App\Filament\Resources\Operational\OrderResource\Pages\AdminComponents;

use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
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
use Filament\Tables\Filters\Filter as FilamentFilter;


trait Filter
{
    public static function filterOrderStatus(): SelectFilter
    {
        return SelectFilter::make('order_status')
            ->label('Status')
            ->options([
                'processing' => 'Processing',
                'closed' => 'Closed',
                'cancelled' => 'Cancelled',
            ]);
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
                        $data['proforma_from'] ?? null,
                        function (Builder $query, $date): Builder {
                            $formattedDate = Carbon::parse($date)->format('Y-m-d');
                            return $query->whereRaw("TRIM(BOTH '\"' FROM json_extract(details, '$.proforma_date')) >= ?", [$formattedDate]);
                        }
                    )
                    ->when(
                        $data['proforma_until'] ?? null,
                        function (Builder $query, $date): Builder {
                            $formattedDate = Carbon::parse($date)->format('Y-m-d');
                            return $query->whereRaw("TRIM(BOTH '\"' FROM json_extract(details, '$.proforma_date')) <= ?", [$formattedDate]);
                        }
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
            ->constraintPickerWidth('2xl')
            ->constraints([
                SelectConstraint::make('order_status')
                    ->multiple()
                    ->options(['processing' => 'Processing', 'closed' => 'Closed', 'cancelled' => 'Cancelled']),
                TextConstraint::make('invoice_number')
                    ->label('Invoice Number'),
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
                DateConstraint::make('created_at')
                    ->label('Creation Time')
                    ->icon('heroicon-o-clock'),
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
            ->constraintPickerColumns(3);
    }

    // GROUP_BY FILTER/SORTING
    public static function groupByCategory(): Group
    {
        return Group::make('category_id')->label('Category')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => ucfirst($record->category->name));
    }

    public static function groupByProduct(): Group
    {
        return Group::make('product_id')->label('Product')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => ucfirst($record->product->name));
    }

    public static function groupByStage(): Group
    {
        return Group::make('purchase_status_id')->label('Stage')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => ucfirst($record->purchaseStatus->name));
    }

    public static function groupByStatus(): Group
    {
        return Group::make('order_status')->label('Status')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => ucfirst($record->order_status));
    }

    public static function groupByInvoiceNumber(): Group
    {
        return Group::make('invoice_number')->label('Project No.')
            ->collapsible();
    }

    public static function groupByProformaNumber(): Group
    {
        return Group::make('proforma_number')->label('Pro forma Number')
            ->collapsible();
    }

    public static function groupByPackaging(): Group
    {
        return Group::make('logistic.packaging_id')->label('Packaging')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => optional($record->logistic->packaging)->name ?? 'N/A');
    }

    public static function groupByDeliveryTerm(): Group
    {
        return Group::make('logistic.delivery_term_id')->label('Delivery Term')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => optional($record->logistic->deliveryTerm)->name ?? 'N/A');
    }

    public static function groupByShippingLine(): Group
    {
        return Group::make('logistic.shipping_line_id')->label('Shipping Carrier')->collapsible()
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
        return Group::make('part')->label('Part')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => $record->part == 1 ? '⭐ Main Part' : ($record->part - 1) ?? 'N/A');
    }


    public static function groupByCurrency(): Group
    {
        return Group::make('orderDetail.extra')->label('Currency')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => optional($record->orderDetail)->extra['currency'] ?? 'N/A')
            ->getKeyFromRecordUsing(fn(Model $record): string => optional($record->orderDetail)->extra['currency'] ?? 'N/A');
    }

    public static function groupByTags(): Group
    {
        return Group::make('tags.extra')->label('Tags')->collapsible()
            ->getKeyFromRecordUsing(fn(Model $record): string => self::useSpecificTagValue($record))
            ->getTitleFromRecordUsing(fn(Model $record): string => self::useSpecificTagValue($record));
    }

    /**
     * @param Model $record
     * @return string
     */
    protected static function useSpecificTagValue(Model $record): string
    {
        $tags = optional($record->tags)->flatMap(function ($tag) {
            return $tag->extra;
        });

        return $tags->isNotEmpty() ? $tags->unique()->join(', ') : 'No Tag Assigned';
    }
}
