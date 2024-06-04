<?php

namespace App\Filament\Resources\Operational\OrderResource\Pages;

use App\Models\Buyer;
use App\Models\Category;
use App\Models\DeliveryTerm;
use App\Models\Order;
use App\Models\OrderRequest;
use App\Models\Packaging;
use App\Models\PortOfDelivery;
use App\Models\Product;
use App\Models\ShippingLine;
use App\Models\Supplier;
use App\Models\User;
use App\Notifications\FilamentNotification;
use App\Rules\EnglishAlphabet;
use App\Services\NotificationManager;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextColumn\TextColumnSize;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Component as Livewire;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Wallo\FilamentSelectify\Components\ToggleButton;

class Admin
{
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

    protected static array $parts = [
        1 => 'Part 1',
        2 => 'Part 2',
        3 => 'Part 3',
        4 => 'Part 4',
        5 => 'Part 5',
        6 => 'Part 6',
        7 => 'Part 7',
        8 => 'Part 8',
        9 => 'Part 9',
    ];

    protected static array $documents = [
        'INSURANCE' => 'Insurance',
        'COA' => 'COA',
        'COO' => 'COO',
        'PL' => 'PL',
        'SGS' => 'Inspection',
        'DECLARATION' => 'Declaration',
        'FINAL INVOICE' => 'Final Invoice',
        'FINAL LOADING LIST FROM SUPPLIER' => 'Final Loading List From Supplier',
    ];


    /**
     * @return Select
     */
    public static function getCategory(): Select
    {
        return Select::make('category_id')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">📂 </span>Category<span class="red"> *</span>'))
            ->hintColor('primary')
            ->relationship('category', 'name')
            ->required()
            ->live()
            ->createOptionForm([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->dehydrateStateUsing(fn(?string $state) => ucwords($state)),
                MarkdownEditor::make('description')
                    ->maxLength(65535)
                    ->disableAllToolbarButtons()
            ])
            ->createOptionAction(function (Action $action) {
                return $action
                    ->modalHeading('Create new category')
                    ->modalButton('Create')
                    ->modalWidth('lg');
            });
    }

    /**
     * @return Select
     */
    public static function getProduct(): Select
    {
        return Select::make('product_id')
            ->label('')
            ->options(fn(Get $get) => Product::filterCategory($get('category_id'))->pluck('name', 'id'))
            ->hint(new HtmlString('<span class="grayscale">📦 </span>Product<span class="red"> *</span>'))
            ->hintColor('primary')
            ->required()
            ->createOptionForm([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->dehydrateStateUsing(fn(?string $state) => strtoupper($state)),
                MarkdownEditor::make('description')
                    ->maxLength(65535)
                    ->disableAllToolbarButtons()
            ])
            ->createOptionAction(function (Action $action) {
                return $action
                    ->modalHeading('Create new product')
                    ->modalButton('Create')
                    ->modalWidth('lg');
            });
    }

    /**
     * @return Select
     */
    public static function getOrderRequestNumber(): Select
    {
        return Select::make('order_request_id')
            ->options(OrderRequest::getApproved())
            ->live()
            ->formatStateUsing(fn($state, $operation) => $operation == 'edit' ? (OrderRequest::find($state))?->formatted_value : ucwords($state))
            ->afterStateUpdated(function (Set $set, ?string $state) {
                self::updateForm($state, $set);
            })
            ->required()
            ->disabled(fn($operation) => $operation == 'edit')
            ->searchable()
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">🛍️ </span>Order Request <span class="red"> *</span>'))
            ->hintColor('primary');
    }

    /**
     * @return TextInput
     */
    public static function getManualInvoiceNumber(): TextInput
    {
        return TextInput::make('extra.manual_invoice_number')
            ->label('')
            ->placeholder('Enter a manual invoice number to back-date records, or leave blank for automatic generation')
            ->columnSpanFull()
            ->disabled(fn($operation) => $operation == 'edit')
            ->dehydrateStateUsing(fn(?string $state) => strtoupper($state))
            ->hint(new HtmlString('<span class="grayscale">🗂 </span>Manual Invoice Number (optional)'))
            ->hintColor('primary');
    }

    /**
     * @return TextInput
     */
    public static function getProformaNumber(): TextInput
    {
        return TextInput::make('proforma_number')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">#️⃣  </span>Pro forma Invoice No.<span class="red"> *</span>'))
            ->hintColor('primary')
            ->placeholder('')
            ->required()
            ->maxLength(255);
    }

    /**
     * @return DatePicker
     */
    public static function getProformaDate(): DatePicker
    {
        return DatePicker::make('proforma_date')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">📅 </span>Pro forma Date<span class="red"> *</span>'))
            ->hintColor('primary')
            ->native(false)
            ->required();
    }

    /**
     * @return Select
     */
    public static function getPart(): Select
    {
        return Select::make('part')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">📑 </span>Part<span class="red"> *</span>'))
            ->hintColor('primary')
            ->required()
            ->live()
            ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                self::updateFormBasedOnPreviousRecords($get, $set, $state);
            })
            ->default('main')
            ->options(self::$parts);
    }

    /**
     * @return TextInput
     */
    public static function getGrade(): TextInput
    {
        return TextInput::make('grade')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">📏 </span>Grade<span class="red"> *</span>'))
            ->hintColor('primary')
            ->dehydrateStateUsing(fn(?string $state) => strtoupper($state))
            ->maxLength(255);
    }

    /**
     * @return Select
     */
    public static function getPurchaseStatus(): Select
    {
        return Select::make('purchase_status_id')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">🚢 </span>Shipment<span class="red"> *</span>'))
            ->hintColor('primary')
            ->required()
            ->relationship('purchaseStatus', 'name');
    }

    /**
     * @return Select
     */
    public static function getOrderStatus(): Select
    {
        return Select::make('order_status')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">🛒 </span>Order<span class="red"> *</span>'))
            ->hintColor('primary')
            ->options([
                'processing' => 'Processing',
                'closed' => 'Closed',
                'cancelled' => 'Cancelled',
            ])
            ->required();
    }

    /**
     * @return Select
     */
    public static function getBuyer(): Select
    {
//        return Buyer::all()->pluck('name', 'id');

        return Select::make('buyer_id')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">📥 </span>Buyer<span class="red"> *</span>'))
            ->hintColor('primary')
            ->options(Buyer::all()->pluck('name', 'id'))
            ->searchable()
            ->required()
            ->createOptionForm([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->dehydrateStateUsing(fn(?string $state) => strtoupper($state)),
                MarkdownEditor::make('description')
                    ->maxLength(65535)
                    ->disableAllToolbarButtons()
            ])
            ->createOptionAction(function (Action $action) {
                return $action
                    ->modalHeading('Create new buyer')
                    ->modalButton('Create')
                    ->modalWidth('lg');
            });
    }

    /**
     * @return Select
     */
    public static function getSupplier(): Select
    {
        return Select::make('supplier_id')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">📤 </span>Supplier<span class="red"> *</span>'))
            ->hintColor('primary')
            ->options(Supplier::all()->pluck('name', 'id'))
            ->searchable()
            ->required()
            ->createOptionForm([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->dehydrateStateUsing(fn(?string $state) => strtoupper($state)),
                MarkdownEditor::make('description')
                    ->maxLength(65535)
                    ->disableAllToolbarButtons()
            ])
            ->createOptionAction(function (Action $action) {
                return $action
                    ->modalHeading('Create new supplier')
                    ->modalButton('Create')
                    ->modalWidth('lg');
            });
    }

    /**
     * @return TextInput
     */
    public static function getPercentage(): TextInput
    {
        return TextInput::make('extra.percentage')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale"></span>Percentage<span class="red"> *</span>'))
            ->hintColor('primary')
            ->required()
            ->afterStateUpdated(fn(Get $get, Set $set) => self::calculatePaymentAndTotal($get, $set))
            ->live(debounce: 1000)
            ->placeholder('Enter the percentage number without any %')
            ->numeric()
            ->in(range(0, 100))
            ->validationMessages([
                'in' => 'The percentage point should be a number between 0 and 100!',
            ]);
    }

    /**
     * @return Select
     */
    public static function getCurrency(): Select
    {
        return Select::make('extra.currency')
            ->options(showCurrencies())
            ->required()
            ->label('')
            ->hintColor('primary')
            ->hint(new HtmlString('<span class="grayscale"></span>Currency<span class="red"> *</span>'));
    }

    /**
     * @return TextInput
     */
    public static function getPayment(): TextInput
    {
        return TextInput::make('extra.payment')
            ->label('')
            ->hint(new HtmlString('Payment ⚠<span class="grayscale text-xs"> Read Only</span>'))
            ->placeholder('± Automatic Computation')
            ->readOnly()
            ->hintColor('primary');
    }

    /**
     * @return TextInput
     */
    public static function getTotal(): TextInput
    {
        return TextInput::make('extra.total')
            ->label('')
            ->hint(new HtmlString('Total ⚠<span class="grayscale text-xs"> Read Only</span>'))
            ->placeholder('± Automatic Computation')
            ->readOnly()
            ->hintColor('primary');
    }


    /**
     * @return TextInput
     */
    public static function getQuantity(): TextInput
    {
        return TextInput::make('buying_quantity')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale"></span>Initial<span class="red"> *</span>'))
            ->hintColor('primary')
            ->afterStateUpdated(function (?float $state, Get $get, Set $set) {
                if (!empty($state)) {
                    self::updateQuantityAndCalculate($state, $get, $set);
                }
            })
            ->live(debounce: 2000)
            ->default(0)
            ->required()
            ->numeric();
    }


    /**
     * @return TextInput
     */
    public static function getProvisionalQuantity(): TextInput
    {
        return TextInput::make('provisional_quantity')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale"></span>Provisional'))
            ->hintColor('primary')
            ->numeric();
    }

    /**
     * @return TextInput
     */
    public static function getFinalQuantity(): TextInput
    {
        return TextInput::make('final_quantity')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale"></span>Final'))
            ->hintColor('primary')
            ->numeric();
    }

    /**
     * @return TextInput
     */
    public static function getPrice(): TextInput
    {
        return TextInput::make('buying_price')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale"></span>Initial<span class="red"> *</span>'))
            ->formatStateUsing(fn($state) => ($state !== null) ? (float)$state : 0)
            ->afterStateUpdated(function (?float $state, Get $get, Set $set) {
                if (!empty($state)) {
                    self::updatePriceAndCalculate($state, $get, $set);
                }
            })->live(debounce: 1000)
            ->hintColor('primary')
            ->required()
            ->numeric();
    }


    /**
     * @return TextInput
     */
    public static function getProvisionalPrice(): TextInput
    {
        return TextInput::make('provisional_price')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale"></span>Provisional'))
            ->formatStateUsing(fn($state) => ($state !== null) ? (float)$state : 0)
            ->hintColor('primary')
            ->numeric();
    }

    /**
     * @return TextInput
     */
    public static function getFinalPrice(): TextInput
    {
        return TextInput::make('final_price')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale"></span>Final'))
            ->formatStateUsing(fn($state) => ($state !== null) ? (float)$state : 0)
            ->hintColor('primary')
            ->numeric();
    }

    /**
     * @return Select
     */
    public static function getPackaging(): Select
    {
        return Select::make('packaging_id')
            ->options(Packaging::pluck('name', 'id'))
            ->searchable()
            ->label('')
            ->hintColor('primary')
            ->hint(new HtmlString('<span class="grayscale">🗳️ </span>Packaging'))
            ->createOptionForm([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->dehydrateStateUsing(fn(?string $state) => ucwords($state)),
                MarkdownEditor::make('description')
                    ->maxLength(65535)
                    ->disableAllToolbarButtons()
            ])
            ->createOptionAction(function (Action $action) {
                return $action
                    ->modalHeading('Create new package')
                    ->modalButton('Create')
                    ->modalWidth('lg');
            });
    }

    /**
     * @return Select
     */
    public static function getDeliveryTerm(): Select
    {
        return Select::make('delivery_term_id')
            ->options(DeliveryTerm::all()->pluck('name', 'id'))
            ->searchable()
            ->label('')
            ->hintColor('primary')
            ->hint(new HtmlString('<span class="grayscale">🚛 </span>Delivery Term'))
            ->createOptionForm([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->dehydrateStateUsing(fn(?string $state) => strtoupper($state)),
                MarkdownEditor::make('description')
                    ->maxLength(65535)
                    ->disableAllToolbarButtons()
            ])
            ->createOptionAction(function (Action $action) {
                return $action
                    ->modalHeading('Create new delivery term')
                    ->modalButton('Create')
                    ->modalWidth('lg');
            });
    }

    /**
     * @return Select
     */
    public static function getShippingLine(): Select
    {
        return Select::make('shipping_line_id')
            ->options(ShippingLine::all()->pluck('name', 'id'))
            ->searchable()
            ->label('')
            ->hintColor('primary')
            ->hint(new HtmlString('<span class="grayscale">⛵ </span>Shipping Company (Cargo Carrier)'))
            ->createOptionForm([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->dehydrateStateUsing(fn(?string $state) => strtoupper($state)),
                MarkdownEditor::make('description')
                    ->maxLength(65535)
                    ->disableAllToolbarButtons()
            ])
            ->createOptionAction(function (Action $action) {
                return $action
                    ->modalHeading('Create new Cargo carrier (shipping co)')
                    ->modalButton('Create')
                    ->modalWidth('lg');
            });
    }

    /**
     * @return Select
     */
    public static function getPortOfDelivery(): Select
    {
        return Select::make('port_of_delivery_id')
            ->options(fn() => PortOfDelivery::all()->pluck('name', 'id'))
            ->searchable()
            ->label('')
            ->hintColor('primary')
            ->hint(new HtmlString('<span class="grayscale">⚓ </span>Port of Delivery'))
            ->createOptionForm([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->dehydrateStateUsing(fn(?string $state) => strtoupper($state)),
                MarkdownEditor::make('description')
                    ->maxLength(65535)
                    ->disableAllToolbarButtons()
            ])
            ->createOptionAction(function (Action $action) {
                return $action
                    ->modalHeading('Create new port of delivery')
                    ->modalButton('Create')
                    ->modalWidth('lg');
            });
    }

    /**
     * @return DatePicker
     */
    public static function getLoadingStartLine(): DatePicker
    {
        return DatePicker::make('extra.loading_startline')
            ->label('')
            ->native(false)
            ->hintColor('primary')
            ->hint(new HtmlString('<span class="grayscale">⌛ </span>Delivery Time Start Date'));
    }

    /**
     * @return DatePicker
     */
    public static function getLoadingDeadline(): DatePicker
    {
        return DatePicker::make('loading_deadline')
            ->label('')
            ->native(false)
            ->hintColor('primary')
            ->hint(new HtmlString('<span class="grayscale">⌛ </span>Delivery Time End Date'));
    }

    /**
     * @return DatePicker
     */
    public static function getETD(): DatePicker
    {
        return DatePicker::make('extra.etd')
            ->label('')
            ->native(false)
            ->hintColor('primary')
            ->hint(new HtmlString('<span class="grayscale">⌛ </span>ETD'));
    }

    /**
     * @return DatePicker
     */
    public static function getETA(): DatePicker
    {
        return DatePicker::make('extra.eta')
            ->label('')
            ->native(false)
            ->hintColor('primary')
            ->hint(new HtmlString('<span class="grayscale">⌛ </span>ETA'));
    }

    /**
     * @return Toggle|string|null
     */
    public static function getChangeOfStatus(): Toggle
    {
        return Toggle::make('change_of_destination')
            ->label('');
    }

    /**
     */
    public static function getContainerShipping()
    {
        return ToggleButton::make('container_shipping')
            ->inlineLabel()
            ->onLabel('✔ Yes')
            ->offLabel('✖ No')
            ->columnSpanFull()
            ->live()
            ->hint(new HtmlString('<span class="grayscale">📦 </span>For this order, is there any container shipping?'))
            ->label('');
    }

    /**
     * @return TextInput
     */
    public static function getFCL(): TextInput
    {
        return TextInput::make('FCL')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">⚖️ </span>FCL/Weight'))
            ->hintColor('primary')
            ->maxLength(255);
    }

    /**
     * @return TextInput
     */
    public static function getFCLType(): TextInput
    {
        return TextInput::make('full_container_load_type')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">🔤 </span>FCL Type'))
            ->hintColor('primary')
            ->maxLength(255);
    }

    /**
     * @return TextInput
     */
    public static function getNumberOfContainer(): TextInput
    {
        return TextInput::make('number_of_containers')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">🗑️</span> Number of Containers'))
            ->hintColor('primary')
            ->numeric()
            ->default(0);
    }

    /**
     * @return TextInput
     */
    public static function getOcceanFreight(): TextInput
    {
        return TextInput::make('ocean_freight')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">🌊 </span>Ocean Freight'))
            ->hintColor('primary')
            ->numeric()
            ->default(0);
    }

    /**
     * @return TextInput
     */
    public static function getTHC(): TextInput
    {
        return TextInput::make('terminal_handling_charges')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">🔚 </span>THC'))
            ->hintColor('primary')
            ->numeric();
    }

    /**
     * @return TextInput
     */
    public static function getBookingNumber(): TextInput
    {
        return TextInput::make('booking_number')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">🎫 </span>Booking Number'))
            ->hintColor('primary')
            ->maxLength(255);
    }

    /**
     * @return TextInput
     */
    public static function getFreeTime(): TextInput
    {
        return TextInput::make('free_time_POD')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">🕘 </span>Free Time (POD)'))
            ->hintColor('primary')
            ->numeric();
    }

    /**
     * @return TextInput
     */
    public static function getGrossWeight(): TextInput
    {
        return TextInput::make('gross_weight')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">⚖ </span>Gross Weight'))
            ->hintColor('primary')
            ->numeric();
    }

    /**
     * @return TextInput
     */
    public static function getNetWeight(): TextInput
    {
        return TextInput::make('net_weight')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">⚖ </span>Net Weight'))
            ->hintColor('primary')
            ->numeric();
    }

    /**
     * @return TextInput
     */
    public static function getVoyageNumber(): TextInput
    {
        return TextInput::make('voyage_number')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">🛳️ </span>Voyage No.'))
            ->hintColor('primary')
            ->maxLength(255);
    }

    /**
     * @return TextInput
     */
    public static function getVoyageNumberSecondLeg(): TextInput
    {
        return TextInput::make('extra.voyage_number_second_leg')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">🛳️ </span>Voyage No. (ii)'))
            ->hintColor('primary')
            ->maxLength(255);
    }

    /**
     * @return TextInput
     */
    public static function getDeclarationNumber(): TextInput
    {
        return TextInput::make('declaration_number')
            ->label('')
            ->hintColor('primary')
            ->hint(new HtmlString('<span class="grayscale">#️⃣ ️ </span>Declaration No.'))
            ->maxLength(255);
    }

    /**
     * @return DatePicker
     */
    public static function getDeclarationDate(): DatePicker
    {
        return DatePicker::make('declaration_date')
            ->label('')
            ->native(false)
            ->hintColor('primary')
            ->hint(new HtmlString('<span class="grayscale">️📅 ️ </span>Declaration Date'));
    }

    /**
     * @return TextInput
     */
    public static function getBLNumber(): TextInput
    {
        return TextInput::make('BL_number')
            ->label('')
            ->hintColor('primary')
            ->hint(new HtmlString('<span class="grayscale">#️⃣ ️ </span>BL No.'))
            ->maxLength(255);
    }

    /**
     * @return DatePicker
     */
    public static function getBLDate(): DatePicker
    {
        return DatePicker::make('BL_date')
            ->label('')
            ->native(false)
            ->hintColor('primary')
            ->hint(new HtmlString('<span class="grayscale">️📅 ️</span>BL Date'));
    }


    /**
     * @return TextInput
     */
    public static function getBLNumberSecondLeg(): TextInput
    {
        return TextInput::make('extra.BL_number_second_leg')
            ->label('')
            ->hintColor('primary')
            ->hint(new HtmlString('<span class="grayscale">#️⃣ ️ </span>BL No. (ii)'))
            ->maxLength(255);
    }

    /**
     * @return DatePicker
     */
    public static function getBLDateSecondLeg(): DatePicker
    {
        return DatePicker::make('extra.BL_date_second_leg')
            ->label('')
            ->native(false)
            ->hintColor('primary')
            ->hint(new HtmlString('<span class="grayscale">️📅 ️</span>BL Date (ii)'));
    }

    /**
     * @return FileUpload
     */
    public static function getFileUpload(): FileUpload
    {
        return FileUpload::make('file_path')
            ->label('')
            ->image()
            ->getUploadedFileNameForStorageUsing(self::nameUploadedFile())
            ->previewable(true)
            ->disk('filament')
            ->directory('/attachments/order-attachments')
            ->maxSize(2500)
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'application/pdf'])
            ->imageEditor()
            ->openable()
            ->downloadable()
            ->columnSpanFull();
    }

    /**
     * @return TextInput
     */
    public static function getAttachmentTitle(): TextInput
    {
        return TextInput::make('name')
            ->label('')
            ->placeholder('Type in English ONLY')
            ->hint(new HtmlString('<span class="grayscale">ℹ️️️ </span>Title/Name'))
            ->hintColor('primary')
            ->requiredWith('file_path')
            ->rule(new EnglishAlphabet)
            ->columnSpanFull();
    }

    public static function getDocumentsReceived()
    {

        return CheckboxList::make('extra.docs_received')
            ->options(self::$documents)
            ->hint(new HtmlString('<span class="text-muted">Mark all documents received:</span>'))
            ->live()
            ->bulkToggleable()
            ->label('')
            ->columns(4);
    }


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
            ->searchable();
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
            ->grow(false)
            ->state(fn(Model $record) => (getTableDesign() === 'modern' ? self::$parts[$record->part] : (($record->part === 0) ? 'Main' : $record->part)))
            ->color(fn(Model $record) => ($record->part === 0 ? 'primary' : 'secondary'))
            ->sortable()
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
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public static function showGrade(): TextColumn
    {
        return TextColumn::make('grade')
            ->badge()
            ->color('secondary')
            ->grow()
            ->searchable()
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

    public static function showInvoiceNumber(): TextColumn
    {
        return TextColumn::make('invoice_number')
            ->color('primary')
            ->grow(false)
            ->size(TextColumnSize::ExtraSmall)
            ->sortable()
            ->toggleable()
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
            ->grow(false)
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
                return 'Payment requests: ' . count($record->paymentRequests);
            })
            ->alignRight()
            ->color(fn($state) => $state == 'Payment requests: 0' ? 'secondary' : 'warning')
            ->icon('heroicon-s-arrow-right-on-rectangle')
            ->iconPosition(IconPosition::Before)
            ->grow(false)
//            ->hidden(fn($record) => is_null($record) || $record->paymentRequests->isEmpty())
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public static function showPayments(): TextColumn
    {
        return TextColumn::make('payments')
            ->state(function (Model $record): string {
                return 'Payment: ' . count($record->payments);
            })
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
            ->searchable()
            ->toggleable()
            ->color('secondary');
    }

    /**
     * @return TextColumn
     */
    public static function showQuantities(): TextColumn
    {
        return TextColumn::make('orderDetail.buying_quantity')
            ->label('Ini. | Pro. | Fin. Quantities')
            ->state(function (Model $record): string {
                return sprintf(
                    "%s | %s | %s",
                    $record->orderDetail->buying_quantity,
                    $record->orderDetail->provisional_quantity ?? 0,
                    $record->orderDetail->final_quantity ?? 0
                );
            })
            ->color('info')
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public static function showPrices(): TextColumn
    {
        return TextColumn::make('orderDetail.buying_price')
            ->label('Ini. | Pro. | Fin. Prices')
            ->state(function (Model $record): string {
                return sprintf(
                    "%s | %s | %s",
                    number_format($record->orderDetail->buying_price, 2),
                    number_format($record->orderDetail->provisional_price, 2),
                    number_format($record->orderDetail->final_price, 2)
                );
            })
            ->color('info')
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public static function showPercentage(): TextColumn
    {
        return TextColumn::make('orderDetail.extra')
            ->label('Percentage')
            ->state(function (Model $record): string {
                if ($record->orderDetail->extra) {
                    return sprintf(
                        "%s%% (%s %s/%s)",
                        $record->orderDetail->extra['percentage'],
                        $record->orderDetail->extra['currency'],
                        number_format($record->orderDetail->extra['payment'], 0),
                        number_format($record->orderDetail->extra['total'], 0),
                    );
                }
                return 'N/A';
            })
            ->grow()
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public static function showDeliveryTerm(): TextColumn
    {
        return TextColumn::make('logistic.deliveryTerm.name')
            ->label('Delivery Term')
            ->color('secondary')
            ->searchable()
            ->sortable()
            ->color('secondary');
    }

    /**
     * @return TextColumn
     */
    public static function showPackaging(): TextColumn
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
    public static function showShippingLine(): TextColumn
    {
        return TextColumn::make('logistic.shippingLine.name')
            ->label('Shipping Line')
            ->color('secondary')
            ->searchable()
            ->sortable()
            ->color('secondary');
    }

    /**
     * @return TextColumn
     */
    public static function showPortOfDelivery(): TextColumn
    {
        return TextColumn::make('logistic.portOfDelivery.name')
            ->label('Port of Delivery')
            ->color('secondary')
            ->searchable()
            ->sortable()
            ->color('secondary');
    }

    /**
     * @return ToggleColumn
     */
    public static function showChangeOfDestination(): ToggleColumn
    {
        return ToggleColumn::make('logistic.change_of_destination')
            ->label('Change of Destination')
            ->toggleable()
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public static function showLoadingStartline(): TextColumn
    {
        return TextColumn::make('logistic.extra.loading_startline')
            ->label('Delivery Start Date')
            ->color('secondary')
            ->date()
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public static function showLoadingDeadline(): TextColumn
    {
        return TextColumn::make('logistic.loading_deadline')
            ->label('Delivery End Date')
            ->color('danger')
            ->sortable()
            ->date()
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public static function showEtd(): TextColumn
    {
        return TextColumn::make('logistic.extra.etd')
            ->label('ETD')
            ->color('secondary')
            ->date()
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public static function showEta(): TextColumn
    {
        return TextColumn::make('logistic.extra.eta')
            ->label('ETA')
            ->color('secondary')
            ->date()
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
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public
    static function showBookingNumber(): TextColumn
    {
        return TextColumn::make('logistic.booking_number')
            ->color('amber')
            ->badge()
            ->label('Booking Number')
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
            ->color('amber')
            ->badge()
            ->searchable()
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
            ->date();
    }

    /**
     * @return SelectFilter
     * @throws \Exception
     */
    public
    static function filterOrderStatus(): SelectFilter
    {
        return SelectFilter::make('order_status')
            ->label('Status')
            ->options([
                'processing' => 'Processing',
                'closed' => 'Closed',
                'cancelled' => 'Cancelled',
            ]);
    }

    /**
     * @return Filter
     * @throws \Exception
     */
    public
    static function filterCreatedAt(): Filter
    {
        return Filter::make('created_at')
            ->form([
                Forms\Components\DatePicker::make('created_from')
                    ->placeholder(fn($state): string => 'Dec 18, ' . now()->subYear()->format('Y')),
                Forms\Components\DatePicker::make('created_until')
                    ->placeholder(fn($state): string => now()->format('M d, Y')),
            ])
            ->query(function (Builder $query, array $data): Builder {
                return $query
                    ->when(
                        $data['created_from'] ?? null,
                        fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                    )
                    ->when(
                        $data['created_until'] ?? null,
                        fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                    );
            })
            ->indicateUsing(function (array $data): array {
                $indicators = [];
                if ($data['created_from'] ?? null) {
                    $indicators['created_from'] = 'Order from ' . Carbon::parse($data['created_from'])->toFormattedDateString();
                }
                if ($data['created_until'] ?? null) {
                    $indicators['created_until'] = 'Order until ' . Carbon::parse($data['created_until'])->toFormattedDateString();
                }

                return $indicators;
            });
    }

    /**
     * @return TrashedFilter
     * @throws \Exception
     */
    public
    static function filterSoftDeletes(): TrashedFilter
    {
        return TrashedFilter::make();
    }

    /**
     * @return \Closure
     */
    public
    static function nameUploadedFile(): \Closure
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

    /**
     * @return Group
     */
    public
    static function groupByCategory(): Group
    {
        return Group::make('category_id')->label('Category')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => ucfirst($record->category->name));
    }

    /**
     * @return Group
     */
    public
    static function groupByProduct(): Group
    {
        return Group::make('product_id')->label('Product')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => ucfirst($record->product->name));
    }

    /**
     * @return Group
     */
    public
    static function groupByStage(): Group
    {
        return Group::make('purchase_status_id')->label('Stage')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => ucfirst($record->purchaseStatus->name));
    }


    /**
     * @return Group
     */
    public
    static function groupByStatus(): Group
    {
        return Group::make('order_status')->label('Status')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => ucfirst($record->order_status));
    }

    /**
     * @return Group
     */
    public
    static function groupByInvoiceNumber(): Group
    {
        return Group::make('invoice_number')->label('Invoice Number')
            ->collapsible();
    }

    /**
     * @return Group
     */
    public
    static function groupByProformaNumber(): Group
    {
        return Group::make('proforma_number')->label('Pro forma Number')
            ->collapsible();
    }

    /**
     * @return Group
     */
    public
    static function groupByPackaging(): Group
    {
        return Group::make('logistic.packaging_id')->label('Packaging')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => optional($record->logistic->packaging)->name ?? 'N/A');

    }

    /**
     * @return Group
     */
    public
    static function groupByDeliveryTerm(): Group
    {
        return Group::make('logistic.delivery_term_id')->label('Delivery Term')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => optional($record->logistic->deliveryTerm)->name ?? 'N/A');

    }

    /**
     * @return Group
     */
    public
    static function groupByShippingLine(): Group
    {
        return Group::make('logistic.shipping_line_id')->label('Shipping Carrier')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => optional($record->logistic->shippingLine)->name ?? 'N/A');

    }

    /**
     * @return Group
     */
    public
    static function groupByBuyer(): Group
    {
        return Group::make('party.buyer_id')->label('Buyer')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => optional($record->party->buyer)->name ?? 'N/A');

    }

    /**
     * @return Group
     */
    public
    static function groupBySupplier(): Group
    {
        return Group::make('party.supplier_id')->label('Supplier')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => optional($record->party->supplier)->name ?? 'N/A');

    }

    /**
     * @return Group
     */
    public
    static function groupByPart(): Group
    {
        return Group::make('part')->label('Part')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => $record->part ?? 'N/A');

    }

    /**
     * @return Group
     */
    public
    static function groupByCurrency(): Group
    {
        return Group::make('orderDetail.extra')->label('Currency')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => optional($record->orderDetail)->extra['currency'] ?? 'N/A')
            ->getKeyFromRecordUsing(fn(Model $record): string => optional($record->orderDetail)->extra['currency'] ?? 'N/A');


    }

    /**
     * @param string|null $state
     * @param Set $set
     * @return void
     */
    private
    static function updateForm(?string $state, Set $set): void
    {
        if ($state) {
            $orderRequest = OrderRequest::findOrFail($state);
            $set('category_id', $orderRequest->category_id);
            $set('product_id', $orderRequest->product_id);
            $set('grade', $orderRequest->grade);
            $set('party.buyer_id', $orderRequest->buyer_id);
            $set('party.supplier_id', $orderRequest->supplier_id);
            $set('orderDetail.buying_quantity', $orderRequest->quantity);
            $set('orderDetail.buying_price', $orderRequest->price);
        }
    }


    /**
     * @param Model $record
     * @return void
     */
    public
    static function send(Model $record): void
    {
        foreach (User::getUsersByRole('admin') as $recipient) {
            $recipient->notify(new FilamentNotification([
                'record' => $record->invoice_number,
                'type' => 'delete',
                'module' => 'order',
                'url' => route('filament.admin.resources.orders.index'),
            ]));
        }
    }

    /**
     * @param Get $get
     * @param string|null $state
     * @param Set $set
     * @return mixed
     */
    public
    static function updateFormBasedOnPreviousRecords(Get $get, Set $set, ?string $state): mixed
    {
        $orderRequestId = $get('order_request_id');

        // Check conditions and fetch order
        if ($orderRequestId && $state && $state != 1) {
            $order = Order::where('order_request_id', $orderRequestId)->first();

            if ($order) {
                $set('proforma_date', $order->performa_date);
                $set('grade', $order->grade);
                $set('orderDetail.buying_quantity', $order->orderDetail->buying_quantity);
                $set('orderDetail.buying_price', $order->orderDetail->buying_price);
            }
        }

        return $orderRequestId;
    }


    protected
    static function updateQuantityAndCalculate($state, $get, $set): void
    {
        $set('initial_quantity', sprintf("%.2f", (float)($state ?? 0)));

        static::calculatePaymentAndTotal($get, $set);
    }

    protected
    static function updatePriceAndCalculate($state, $get, $set): void
    {
        $set('initial_price', sprintf("%.2f", (float)($state ?? 0)));

        static::calculatePaymentAndTotal($get, $set);
    }

    protected
    static function calculatePaymentAndTotal($get, $set): void
    {
        $percentage = (float)$get('extra.percentage') ?? 0;
        $price = (float)$get('initial_price') ?? 0;
        $quantity = (float)$get('initial_quantity') ?? 0;

        $total = $quantity * $price;
        $payment = ($percentage * $total) / 100;

        $set('extra.payment', sprintf("%.2f", $payment));
        $set('extra.total', sprintf("%.2f", $total));
    }


}
