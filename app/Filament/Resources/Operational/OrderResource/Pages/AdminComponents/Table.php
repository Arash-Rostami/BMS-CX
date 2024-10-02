<?php

namespace App\Filament\Resources\Operational\OrderResource\Pages\AdminComponents;

use App\Models\Order;
use Carbon\Carbon;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Columns\Summarizers\Count;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextColumn\TextColumnSize;
use Filament\Tables\Columns\ToggleColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as SummaryBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;


trait Table
{
    /**
     * @return TextColumn
     */
    public static function showProformaNumber(): TextColumn
    {
        return TextColumn::make('proforma_number')
            ->color('secondary')
            ->badge()
            ->icon('heroicon-s-paper-clip')
            ->iconPosition(IconPosition::Before)
            ->grow(false)
            ->tooltip(fn(string $state): string => "Pro forma Invoice Number")
            ->sortable()
            ->toggleable()
//            ->summarize(
//                Summarizer::make()
//                    ->label('Balance')
//                    ->using(function (SummaryBuilder $query): string {
//                        $results = $query
//                            ->selectRaw('
//                        COALESCE((SELECT SUM(payment_requests.requested_amount)
//                                  FROM payment_requests
//                                  WHERE payment_requests.order_id = orders.id), 0) AS total_payments,
//                        COALESCE((SELECT SUM(proforma_invoices.price * proforma_invoices.quantity)
//                                  FROM proforma_invoices
//                                  WHERE proforma_invoices.id = orders.proforma_invoice_id), 0) AS total_expected
//                    ')->first();
//
//                        return $results->total_payments > $results->total_expected ? 'Overpayment' :
//                            ($results->total_payments < $results->total_expected ? 'Underpayment' : 'Settled');
//                    })
//            )
            ->searchable(isIndividual: true,);
    }

    /**
     * @return TextColumn
     */
    public static function showProformaDate(): TextColumn
    {
        return TextColumn::make('proforma_date')
            ->color('secondary')
            ->badge()
            ->grow(false)
            ->tooltip(fn(string $state): string => "Pro forma Invoice Date")
            ->date()
            ->toggleable()
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public static function showOrderPart(): TextColumn
    {
        return TextColumn::make('part')
            ->color('secondary')
            ->badge()
            ->label('Part')
            ->grow(true)
            ->tooltip(fn(Model $record) => 'Order Part ' . $record->part)
            ->sortable()
            ->toggleable()
            ->summarize(
                Count::make()->label('Total')
            )
            ->description('')
            ->searchable();
    }

    /**
     * @return TextColumn
     */
    public static function showPurchaseStatus(): TextColumn
    {
        return TextColumn::make('purchaseStatus.name')
            ->label('Stage')
            ->numeric()
            ->badge()
            ->alignRight()
            ->toggleable()
            ->searchable()
            ->color(getTableDesign() === 'modern' ? '' : 'secondary')
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public static function showCategory(): TextColumn
    {
        return TextColumn::make('category.name')
            ->icon('heroicon-o-rectangle-stack')
            ->tooltip(fn(string $state): string => "Category")
            ->iconPosition(IconPosition::Before)
            ->badge()
            ->color('secondary')
            ->searchable()
            ->toggleable()
            ->grow(false)
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public static function showProduct(): TextColumn
    {
        return TextColumn::make('product.name')
            ->icon('heroicon-o-squares-2x2')
            ->grow(false)
            ->tooltip(fn(string $state): string => "Product")
            ->iconPosition(IconPosition::Before)
            ->badge()
            ->searchable()
            ->toggleable()
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public static function showGrade(): TextColumn
    {
        return TextColumn::make('grade.name')
            ->icon('heroicon-m-ellipsis-horizontal-circle')
            ->badge()
            ->color('secondary')
            ->grow()
            ->toggleable()
            ->searchable()
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public static function showCreator(): TextColumn
    {
        return TextColumn::make('user.fullName')
            ->label('Created by')
            ->badge()
            ->color('secondary')
            ->searchable(['first_name', 'middle_name', 'last_name'])
            ->toggleable()
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public static function showOrderStatus(): TextColumn
    {
        return TextColumn::make('order_status')
            ->label('Status')
            ->formatStateUsing(fn($state): string => self::$statusTexts[$state] ?? 'Unknown')
            ->icon(fn($state): string => self::$statusIcons[$state] ?? null)
            ->color(fn($state): string => self::$statusColors[$state] ?? null)
            ->sortable()
            ->alignRight()
            ->grow(false)
            ->badge()
            ->toggleable()
            ->searchable();
    }

    public static function showProjectNumber(): TextColumn
    {
        return TextColumn::make('invoice_number')
            ->label('Project No.')
            ->color('primary')
            ->grow(false)
            ->state(fn(Model $record) => $record->invoice_number ?? 'N/A')
            ->size(TextColumnSize::ExtraSmall)
            ->tooltip(fn(string $state): string => "Invoice Number")
            ->sortable()
            ->toggleable()
            ->searchable(isIndividual: true);
    }

    public static function showReferenceNumber(): TextColumn
    {
        return TextColumn::make('reference_number')
            ->label(new HtmlString('<span class="text-primary-500 cursor-pointer" title="Record Unique ID">Ref. No. ⋮ ID</span>'))
            ->grow(false)
            ->weight(FontWeight::ExtraLight)
            ->tooltip(fn(?string $state): ?string => "Order Ref. No. / ID")
            ->sortable()
            ->toggleable()
            ->formatStateUsing(fn(Model $record) => $record->reference_number ?? sprintf('O-%s%04d', $record->created_at->format('y'), $record->id))
            ->sortable()
            ->extraAttributes(['class' => 'col-freeze'])
            ->searchable();
    }

    /**
     * @return TextColumn
     */
    public static function showOrderNumber(): TextColumn
    {
        return TextColumn::make('order_number')
            ->tooltip(fn(string $state): string => "Unique Order Number")
            ->sortable()
            ->searchable()
            ->color('gray')
            ->toggleable()
            ->size(TextColumnSize::ExtraSmall)
            ->weight(FontWeight::ExtraLight);
    }


    /**
     * @return TextColumn
     */
    public static function showPaymentRequests(): TextColumn
    {
        return TextColumn::make('paymentRequest')
            ->state(function (Model $record): string {
                return 'Payment requests: ' . count($record->paymentRequests) ?? 0;
            })
            ->alignRight()
            ->color(fn($state) => $state == 'Payment requests: 0' ? 'secondary' : 'warning')
            ->icon('heroicon-s-arrow-right-on-rectangle')
            ->iconPosition(IconPosition::Before)
            ->grow()
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public static function showPayments(): TextColumn
    {
        return TextColumn::make('payments')
            ->state(fn(Model $record) => 'Payment: ' . ($record->paymentRequests->pluck('payments')->flatten()->count()))
            ->alignRight()
            ->icon('heroicon-o-credit-card')
            ->iconPosition(IconPosition::Before)
            ->color(fn($state) => $state == 'Payment: 0' ? 'secondary' : 'success')
            ->grow(false)
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public static function showUpdatedAt(): TextColumn
    {
        return TextColumn::make('updated_at')
            ->dateTime()
            ->icon('heroicon-s-calendar-days')
            ->sortable()
            ->alignRight()
            ->description(fn($record): string => "Created " . Carbon::parse($record->created_at)->diffForHumans())
            ->toggleable(isToggledHiddenByDefault: false)
            ->formatStateUsing(fn(string $state): string => "Last updated: " . Carbon::parse($state)->format('M j, Y H:i:s'));
    }

    /**
     * @return TextColumn
     */
    public static function showSupplier(): TextColumn
    {
        return TextColumn::make('party.supplier.name')
            ->label('Supplier')
            ->sortable()
            ->grow(false)
            ->alignRight()
            ->searchable()
            ->toggleable()
            ->color('secondary');
    }

    /**
     * @return TextColumn
     */
    public static function showBuyer(): TextColumn
    {
        return TextColumn::make('party.buyer.name')
            ->label('Buyer')
            ->sortable()
            ->grow(false)
            ->alignRight()
            ->searchable()
            ->toggleable()
            ->color('secondary');
    }

    /**
     * @return TextColumn
     */
    public static function showQuantities(): TextColumn
    {
        return TextColumn::make('orderDetail.extra.payableQuantity')
            ->label('Ini | Pro | Fin Quantities')
            ->state(function (Model $record): string {
                return sprintf(
                    "%s | %s | %s (mt)",
                    numberify($record->orderDetail->buying_quantity ?? 0),
                    numberify($record->orderDetail->provisional_quantity ?? 0),
                    numberify($record->orderDetail->final_quantity ?? 0)
                );
            })
            ->summarize(Summarizer::make()->label('⚖️ Prov. | Final')->using(fn(SummaryBuilder $query) => self::calculateSummaries('quantity', $query)))
            ->toggleable()
            ->color('info')
            ->badge();
    }

    public static function showAllPayments(): TextColumn
    {
        return TextColumn::make('orderDetail.extra.provisionalTotal')
            ->label('Ini | Pro | Fin Price')
            ->toggleable()
            ->color('info')
            ->state(function (Model $record): string {
                return sprintf(
                    "%s %s | %s | %s",
                    $record->orderDetail->extra['currency'] ?? '',
                    numberify($record->orderDetail?->extra['initialPayment'] ?? $record->orderDetail->buying_price ?? 0),
                    numberify($record->orderDetail?->extra['provisionalTotal'] ?? $record->orderDetail->provisional_price ?? 0),
                    numberify($record->orderDetail?->extra['finalTotal'] ?? $record->orderDetail->final_price ?? 0)
                );
            })

            ->summarize(Summarizer::make()->label('💰 Initial | Provisional | Final')->using(fn(SummaryBuilder $query) => self::calculateSummaries('payment', $query)))
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public
    static function showProvisionalPayment(): TextColumn
    {
        return TextColumn::make('orderDetail.extra.provisionalTotal')
            ->label('Initial Payment')
            ->toggleable()
            ->color('info')
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public
    static function showFinalPayment(): TextColumn
    {
        return TextColumn::make('orderDetail.extra.finalTotal')
            ->label('Initial Payment')
            ->toggleable()
            ->color('info')
            ->badge();
    }


    /**
     * @return TextColumn
     */
    public
    static function showDeliveryTerm(): TextColumn
    {
        return TextColumn::make('logistic.deliveryTerm.name')
            ->label('Delivery Term')
            ->color('secondary')
            ->searchable()
            ->toggleable()
            ->sortable()
            ->color('secondary');
    }

    /**
     * @return TextColumn
     */
    public
    static function showPackaging(): TextColumn
    {
        return TextColumn::make('logistic.packaging.name')
            ->label('Packaging')
            ->color('primary')
            ->badge()
            ->searchable()
            ->toggleable()
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public
    static function showShippingLine(): TextColumn
    {
        return TextColumn::make('logistic.shippingLine.name')
            ->label('Shipping Line')
            ->color('secondary')
            ->searchable()
            ->sortable()
            ->toggleable()
            ->color('secondary');
    }

    /**
     * @return TextColumn
     */
    public
    static function showPortOfDelivery(): TextColumn
    {
        return TextColumn::make('logistic.portOfDelivery.name')
            ->label('Port of Delivery')
            ->color('secondary')
            ->searchable()
            ->toggleable()
            ->sortable()
            ->color('secondary');
    }

    /**
     * @return ToggleColumn
     */
    public
    static function showChangeOfDestination(): ToggleColumn
    {
        return ToggleColumn::make('logistic.change_of_destination')
            ->label('Change of Destination')
            ->toggleable()
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public
    static function showLoadingStartline(): TextColumn
    {
        return TextColumn::make('logistic.extra.loading_startline')
            ->label('Loading Start Date')
            ->color('secondary')
            ->date()
            ->toggleable()
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public
    static function showLoadingDeadline(): TextColumn
    {
        return TextColumn::make('logistic.loading_deadline')
            ->label('Loading End Date')
            ->color('danger')
            ->sortable()
            ->date()
            ->toggleable()
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public
    static function showEtd(): TextColumn
    {
        return TextColumn::make('logistic.extra.etd')
            ->label('ETD')
            ->color('secondary')
            ->date()
            ->toggleable()
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public
    static function showEta(): TextColumn
    {
        return TextColumn::make('logistic.extra.eta')
            ->label('ETA')
            ->color('secondary')
            ->date()
            ->toggleable()
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public
    static function showFCL(): TextColumn
    {
        return TextColumn::make('logistic.FCL')
            ->label('FCL')
            ->color('secondary')
            ->sortable()
            ->toggleable()
            ->color('secondary');
    }

    /**
     * @return TextColumn
     */
    public
    static function showFCLType(): TextColumn
    {
        return TextColumn::make('logistic.full_container_load_type')
            ->label('FCL Type')
            ->searchable()
            ->sortable()
            ->toggleable()
            ->color('secondary');
    }

    /**
     * @return TextColumn
     */
    public
    static function showNumberOfContainers(): TextColumn
    {
        return TextColumn::make('logistic.number_of_containers')
            ->label('No. of Containers')
            ->toggleable()
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public
    static function showOceanFreight(): TextColumn
    {
        return TextColumn::make('logistic.ocean_freight')
            ->label('Ocean Freight')
            ->toggleable()
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public
    static function showTHC(): TextColumn
    {
        return TextColumn::make('logistic.terminal_handling_charges')
            ->label('THC')
            ->toggleable()
            ->color('secondary')
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public
    static function showFreeTimePOD(): TextColumn
    {
        return TextColumn::make('logistic.free_time_POD')
            ->label('Free Time (POD)')
            ->toggleable()
            ->color('secondary')
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public
    static function showGrossWeight(): TextColumn
    {
        return TextColumn::make('logistic.gross_weight')
            ->label('Gross Weight')
            ->color('secondary')
            ->toggleable()
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public
    static function showNetWeight(): TextColumn
    {
        return TextColumn::make('logistic.net_weight')
            ->label('Net Weight')
            ->color('secondary')
            ->toggleable()
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public
    static function showBookingNumber(): TextColumn
    {
        return TextColumn::make('logistic.booking_number')
            ->color('secondary')
            ->badge()
            ->tooltip(fn() => "Booking Number")
            ->grow(false)
            ->label('Booking Number')
            ->searchable(isIndividual: true,)
            ->toggleable()
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public
    static function showVoyageNumber(): TextColumn
    {
        return TextColumn::make('doc.voyage_number')
            ->label('Voyage Number')
            ->color('amber')
            ->badge()
            ->toggleable()
            ->searchable()
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public
    static function showVoyageNumberLegTwo(): TextColumn
    {
        return TextColumn::make('doc.extra.voyage_number_second_leg')
            ->label('Voyage Number ii')
            ->color('amber')
            ->badge()
            ->toggleable()
            ->searchable(query: function (Builder $query, string $search): Builder {
                return $query->whereJsonContains('extra->voyage_number_second_leg', $search);
            });
    }

    /**
     * @return TextColumn
     */
    public
    static function showDeclarationNumber(): TextColumn
    {
        return TextColumn::make('doc.declaration_number')
            ->label('Declaration Number')
            ->color('amber')
            ->badge()
            ->toggleable()
            ->searchable()
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public
    static function showBLNumber(): TextColumn
    {
        return TextColumn::make('doc.BL_number')
            ->label('BL Number')
            ->color('secondary')
            ->tooltip(fn() => "BL Number")
            ->badge()
            ->grow()
            ->badge()
            ->toggleable()
            ->searchable(isIndividual: true,)
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public
    static function showBLNumberLegTwo(): TextColumn
    {
        return TextColumn::make('doc.extra.BL_number_second_leg')
            ->label('BL Number ii')
            ->color('amber')
            ->badge()
            ->toggleable()
            ->searchable(query: function (Builder $query, string $search): Builder {
                return $query->whereJsonContains('extra->BL_number_second_leg', $search);
            });
    }

    /**
     * @return TextColumn
     */
    public
    static function showDeclarationDate(): TextColumn
    {
        return TextColumn::make('doc.declaration_date')
            ->color('secondary')
            ->badge()
            ->label('Declaration Date')
            ->date()
            ->toggleable()
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public
    static function showBLDate(): TextColumn
    {
        return TextColumn::make('doc.BL_date')
            ->color('secondary')
            ->badge()
            ->label('BL Date')
            ->date()
            ->toggleable()
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public
    static function showBLDateLegTwo(): TextColumn
    {
        return TextColumn::make('doc.extra.BL_date_second_leg')
            ->color('secondary')
            ->badge()
            ->label('BL Date ii')
            ->toggleable()
            ->date();
    }
}
