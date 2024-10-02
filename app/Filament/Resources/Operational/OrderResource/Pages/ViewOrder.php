<?php

namespace App\Filament\Resources\Operational\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Carbon\Carbon;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;


class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Tabs::make('Tabs')
                    ->tabs([
                        Tabs\Tab::make('✨Main')
                            ->schema([
                                Section::make()
                                    ->columns([
                                        'sm' => 2,
                                        'md' => 2,
                                        'xl' => 3,
                                        '2xl' => 3,
                                    ])->schema([
                                        $this->viewProjectNumber(),
                                        $this->viewCategory(),
                                        $this->viewProduct(),
                                        $this->viewProformaNumber(),
                                        $this->viewPart(),
                                        $this->viewProformaDate(),
                                        $this->viewGrade(),
                                    ]),
                                Section::make()
                                    ->columns([
                                        'sm' => 2,
                                        'md' => 2,
                                        'xl' => 3,
                                        '2xl' => 3,
                                    ])->schema([
                                        $this->viewOrderNumber(),
                                        $this->viewPurchaseStatus(),
                                        $this->viewOrderStatus(),
                                    ]),
                            ]),
                        Tabs\Tab::make('📍 Details | Logistics')
                            ->schema([
                                Section::make()
                                    ->columns([
                                        'sm' => 2,
                                        'md' => 2,
                                        'xl' => 4,
                                        '2xl' => 4,
                                    ])->schema([
                                        $this->viewSupplier(),
                                        $this->viewBuyer(),
                                        $this->viewPrice(),
                                        $this->viewQuantity(),
                                        $this->viewPercentage()
                                    ]),
                                Section::make()
                                    ->columns([
                                        'sm' => 2,
                                        'md' => 2,
                                        'xl' => 4,
                                        '2xl' => 4,
                                    ])
                                    ->schema([
                                        $this->viewPackaging(),
                                        $this->viewDeliveryTerm(),
                                        $this->viewShippingLine(),
                                        $this->viewPortOfDelivery(),
                                        $this->viewLoadingStartline(),
                                        $this->viewLoadingDeadline(),
                                        $this->viewEtd(),
                                        $this->viewEta(),
                                        $this->viewChangeOfDestination(),

                                        $this->viewFCL(),
                                        $this->viewFCLType(),
                                        $this->viewNumberOfContainer(),
                                        $this->viewOceanFreight(),
                                        $this->viewTHC(),
                                        $this->viewBookingNumber(),
                                        $this->viewPOD(),
                                        $this->viewGrossWeight(),
                                        $this->viewNetWeight()
                                    ])
                            ]),
                        Tabs\Tab::make('🔗 Docs | Attachments')
                            ->schema([
                                Section::make()
                                    ->columns([
                                        'sm' => 2,
                                        'md' => 2,
                                        'xl' => 3,
                                        '2xl' => 3,
                                    ])->schema([
                                        $this->viewDeclarationNumber(),
                                        $this->viewDeclarationDate(),
                                        $this->viewVoyageNumber(),
                                        $this->viewBLNumber(),
                                        $this->viewBLDate(),
                                        $this->viewVoyageNumberSecondLeg(),
                                        $this->viewBLNumberSecondLeg(),
                                        $this->viewBLDateSecondLeg(),
                                    ]),
                                Section::make('Attachments')
                                    ->description('Click to view all files attached in this Order ↓')
                                    ->schema([
                                        RepeatableEntry::make('attachments')
                                            ->label('')
                                            ->schema([
                                                $this->viewImage()
                                            ])->columnSpanFull(2)
                                    ])->collapsible()->collapsed()
                            ]),
                    ])->columnSpanFull()
            ]);
    }

    /**
     * @return TextEntry
     */
    public function viewProjectNumber(): TextEntry
    {
        return TextEntry::make('invoice_number')
            ->label('Project Number')
            ->state(fn(Model $record) => $record->invoice_number ?? 'N/A')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public function viewCategory(): TextEntry
    {
        return TextEntry::make('category.name')
            ->label('Category')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public function viewProduct(): TextEntry
    {
        return TextEntry::make('product.name')
            ->label('Product')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public function viewProformaNumber(): TextEntry
    {
        return TextEntry::make('proforma_number')
            ->label('PFI Number')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public function viewPart(): TextEntry
    {
        return TextEntry::make('part')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public function viewProformaDate(): TextEntry
    {
        return TextEntry::make('proforma_date')
            ->label('PFI Date')
            ->formatStateUsing(fn(?string $state) => $this->formatDate($state))
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public function viewGrade(): TextEntry
    {
        return TextEntry::make('grade.name')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public function viewOrderNumber(): TextEntry
    {
        return TextEntry::make('order_number')
            ->label('Unique ID')
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public function viewPurchaseStatus(): TextEntry
    {
        return TextEntry::make('purchaseStatus.name')
            ->label('Stage')
            ->color('secondary')
            ->badge();
    }

    /**
     * @return IconEntry
     */
    public function viewOrderStatus(): IconEntry
    {
        return IconEntry::make('order_status')
            ->label('Status')
            ->tooltip(fn(string $state): string => match ($state) {
                'processing' => 'Processing',
                'closed' => 'Closed',
                'cancelled' => 'Cancelled',
            })
            ->icon(fn(string $state): string => match ($state) {
                'processing' => 'heroicon-s-arrow-path-rounded-square',
                'closed' => 'heroicon-s-check-circle',
                'cancelled' => 'heroicon-s-no-symbol',
            });
    }

    /**
     * @return TextEntry
     */
    public function viewSupplier(): TextEntry
    {
        return TextEntry::make('party.supplier_id')
            ->label('Supplier')
            ->state(function (Model $record): string {
                return $record->party->supplier->name;
            })
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public function viewBuyer(): TextEntry
    {
        return TextEntry::make('party.buyer_id')
            ->label('Buyer')
            ->state(function (Model $record): string {
                return $record->party->buyer->name;
            })
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public function viewPrice(): TextEntry
    {
        return TextEntry::make('orderDetail.buying_price')
            ->label('Ini. | Pro. | Fin. Prices')
            ->state(function (Model $record): string {
                return sprintf(
                    "%s | %s | %s",
                    number_format($record->orderDetail->buying_price, 2),
                    number_format($record->orderDetail->provisional_price, 2),
                    number_format($record->orderDetail->final_price, 2)
                );
            })
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public function viewQuantity(): TextEntry
    {
        return TextEntry::make('orderDetail.buying_quantity')
            ->label('Ini. | Pro. | Fin. Quantities')
            ->state(function (Model $record): string {
                return sprintf(
                    "%s | %s | %s",
                    $record->orderDetail->buying_quantity,
                    $record->orderDetail->provisional_quantity ?? 0,
                    $record->orderDetail->final_quantity ?? 0
                );
            })
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public function viewPercentage(): TextEntry
    {
        return TextEntry::make('orderDetail.extra')
            ->label('Payments')
            ->state(fn(Model $record) => $this->formatPayslip($record))
            ->tooltip(fn(Model $record) => $this->formatPayslip($record))
            ->grow()
            ->color('primary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public function viewDeliveryTerm(): TextEntry
    {
        return TextEntry::make('logistic.delivery_term_id')
            ->label('Delivery Term')
            ->color('secondary')
            ->state(function (Model $record): string {
                return optional($record->logistic->deliveryTerm)->name ?? 'N/A';
            })
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public function viewShippingLine(): TextEntry
    {
        return TextEntry::make('logistic.shipping_line_id')
            ->label('Shipping Line')
            ->color('secondary')
            ->state(function (Model $record): string {
                return optional($record->logistic->shippingLine)->name ?? 'N/A';
            })
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public function viewPortOfDelivery(): TextEntry
    {
        return TextEntry::make('logistic.port_of_delivery_id')
            ->label('Port of Delivery')
            ->color('secondary')
            ->state(function (Model $record): string {
                return optional($record->logistic->portOfDelivery)->name ?? 'N/A';
            })
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public function viewLoadingStartLine(): TextEntry
    {
        return TextEntry::make('logistic.extra.loading_startline')
            ->label('Delivery Start Date')
            ->color('secondary')
            ->formatStateUsing(fn(?string $state) => $this->formatDate($state))
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public function viewLoadingDeadline(): TextEntry
    {
        return TextEntry::make('logistic.loading_deadline')
            ->label('Delivery Deadline')
            ->color('secondary')
            ->formatStateUsing(fn(?string $state) => $this->formatDate($state))
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public function viewEtd(): TextEntry
    {
        return TextEntry::make('logistic.extra.etd')
            ->label('ETD')
            ->color('secondary')
            ->formatStateUsing(fn(?string $state) => $this->formatDate($state))
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public function viewEta(): TextEntry
    {
        return TextEntry::make('logistic.extra.eta')
            ->label('ETA')
            ->color('secondary')
            ->formatStateUsing(fn(?string $state) => $this->formatDate($state))
            ->badge();
    }

    /**
     * @return IconEntry
     */
    public function viewChangeOfDestination(): IconEntry
    {
        return IconEntry::make('logistic.change_of_destination')
            ->label('Change of Destination')
            ->icon(fn(string $state): string => match ($state) {
                '0' => 'heroicon-s-x-circle',
                '1' => 'heroicon-s-check-circle',
            })
            ->color(fn(string $state): string => match ($state) {
                '0' => 'secondary',
                '1' => 'warning',
            });
    }

    /**
     * @return TextEntry
     */
    public function viewFCL(): TextEntry
    {
        return TextEntry::make('logistic.FCL')
            ->label('FCL')
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public function viewFCLType(): TextEntry
    {
        return TextEntry::make('logistic.full_container_load_type')
            ->label('FCL Type')
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public function viewNumberOfContainer(): TextEntry
    {
        return TextEntry::make('logistic.number_of_containers')
            ->label('Number of Containers')
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public function viewOceanFreight(): TextEntry
    {
        return TextEntry::make('logistic.ocean_freight')
            ->label('Ocean Freight')
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public function viewTHC(): TextEntry
    {
        return TextEntry::make('logistic.terminal_handling_charges')
            ->label('THC')
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public function viewBookingNumber(): TextEntry
    {
        return TextEntry::make('logistic.booking_number')
            ->label('Booking Number')
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public function viewPOD(): TextEntry
    {
        return TextEntry::make('logistic.free_time_POD')
            ->label('Free Time (POD)')
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public function viewGrossWeight(): TextEntry
    {
        return TextEntry::make('logistic.gross_weight')
            ->label('Gross Weight')
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public function viewNetWeight(): TextEntry
    {
        return TextEntry::make('logistic.net_weight')
            ->label('Net Weight')
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public function viewVoyageNumber(): TextEntry
    {
        return TextEntry::make('doc.voyage_number')
            ->label('Voyage Number')
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public function viewVoyageNumberSecondLeg(): TextEntry
    {
        return TextEntry::make('doc.extra.voyage_number_second_leg')
            ->label('Voyage Number ii')
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public function viewDeclarationNumber(): TextEntry
    {
        return TextEntry::make('doc.declaration_number')
            ->label('Declaration Number')
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public function viewBLNumber(): TextEntry
    {
        return TextEntry::make('doc.BL_number')
            ->label('BL Number')
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public function viewBLNumberSecondLeg(): TextEntry
    {
        return TextEntry::make('doc.extra.BL_number_second_leg')
            ->label('BL Number ii')
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public function viewDeclarationDate(): TextEntry
    {
        return TextEntry::make('doc.declaration_date')
            ->label('Declaration Date')
            ->color('secondary')
            ->formatStateUsing(fn(?string $state) => $this->formatDate($state))
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public function viewBLDate(): TextEntry
    {
        return TextEntry::make('doc.BL_date')
            ->label('BL Date')
            ->color('secondary')
            ->formatStateUsing(fn(?string $state) => $this->formatDate($state))
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public function viewBLDateSecondLeg(): TextEntry
    {
        return TextEntry::make('doc.extra.BL_date_second_leg')
            ->label('BL Date ii')
            ->color('secondary')
            ->formatStateUsing(fn(?string $state) => $this->formatDate($state))
            ->badge();
    }

    /**
     * @return ImageEntry
     */
    public function viewImage(): ImageEntry
    {
        return ImageEntry::make('file_path')
            ->label('')
            ->extraAttributes(fn($state) => $state ? [
                'class' => 'cursor-pointer',
                'title' => '👁️‍',
                'onclick' => "showImage('" . url($state) . "')",
            ] : [])
            ->disk('filament')
            ->alignCenter()
            ->visibility('public');
    }

    /**
     * @return TextEntry
     */
    public function viewPackaging(): TextEntry
    {
        return TextEntry::make('logistic.packaging_id')
            ->label('Packaging')
            ->color('secondary')
            ->state(function (Model $record): string {
                return optional($record->logistic->packaging)->name ?? 'N/A';
            })
            ->badge();
    }

    protected function formatDate(?string $date): ?string
    {
        return isset($date) ? Carbon::parse($date)->format('Y-m-d') : null;
    }

    /**
     * @param Model $record
     * @return string
     */
    protected function formatPayslip(Model $record): string
    {
        if (!$record->orderDetail || !$record->orderDetail->extra) {
            return 'N/A';
        }

        $extra = $record->orderDetail->extra;
        $paid = ($extra['initialPayment'] ?? 0) + ($extra['provisionalTotal'] ?? 0) + ($extra['finalTotal'] ?? 0);
        return sprintf(
            '%s %s',
            $extra['currency'] ?? '',
            numberify(($paid) ?? 0),
        );
    }
}
