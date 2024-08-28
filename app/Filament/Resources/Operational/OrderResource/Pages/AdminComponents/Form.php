<?php

namespace App\Filament\Resources\Operational\OrderResource\Pages\AdminComponents;

use App\Models\Buyer;
use App\Models\DeliveryTerm;
use App\Models\Name;
use App\Models\Order;
use App\Models\OrderRequest;
use App\Models\Packaging;
use App\Models\PortOfDelivery;
use App\Models\ShippingLine;
use App\Models\Supplier;
use App\Rules\EnglishAlphabet;
use App\Rules\NoMultipleProjectNumbers;
use App\Rules\UniqueTitleInOrder;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Livewire;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Wallo\FilamentSelectify\Components\ToggleButton;
use Livewire\Component as FormLivewire;


trait Form
{
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
            ->hint(new HtmlString('<span class="grayscale">📦 </span>Product<span class="red"> *</span>'))
            ->hintColor('primary')
            ->required()
            ->relationship('product', 'name')
            ->createOptionForm([
                Select::make('category_id')
                    ->relationship('category', 'name')
                    ->required(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->dehydrateStateUsing(fn(?string $state) => strtoupper($state)),
                MarkdownEditor::make('description')
                    ->maxLength(65535)
                    ->disableAllToolbarButtons()
                    ->unique()
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
            ->columnSpan(2)
            ->disabled(fn($operation) => $operation == 'edit')
            ->searchable()
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">🛍️ </span>Pro forma Invoice <span class="red"> *</span>'))
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
            ->formatStateUsing(function (?Model $record, $operation) {
                if ($record && $operation === 'edit' && isset($record->invoice_number)) {
                    return $record->invoice_number;
                }
                return $record->extra['manual_invoice_number'] ?? null;
            })
            ->disabled(fn($operation) => $operation == 'edit')
            ->dehydrateStateUsing(fn(?string $state) => strtoupper($state))
            ->hint(new HtmlString('<span class="grayscale">🗂 </span>Project Number (optional)'))
            ->rule(fn($operation) => $operation == 'create' ? new NoMultipleProjectNumbers() : null)
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
            ->columnSpan(1)
            ->default('main')
            ->options(function () {
                self::$parts[1] = "Main Part ⭐";
                for ($i = 2; $i <= 50; $i++) {
                    $userFriendly = $i - 1;
                    self::$parts[$i] = "Part $userFriendly";
                }
                return self::$parts;
            });
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
            ->relationship('purchaseStatus', 'name')
            ->hint(new HtmlString('<span class="grayscale">🚢 </span>Shipment<span class="red"> *</span>'))
            ->hintColor('primary')
            ->live()
            ->required();
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
                    ->unique()
            ])
            ->createOptionUsing(function (array $data): int {
                return Buyer::create($data)->getKey();
            })
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
                    ->unique()
            ])
            ->createOptionUsing(function (array $data): int {
                return Supplier::create($data)->getKey();
            })
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
            ->hint(new HtmlString('<span class="grayscale">°/•</span>Payable Percentage<span class="red"> *</span>'))
            ->hintColor('primary')
            ->required(fn(Get $get, FormLivewire $livewire) => ($get('part') ?? data_get($livewire, 'data.part')) && ($get('part') ?? data_get($livewire, 'data.part') == 1))
            ->placeholder('Enter the percentage number without any %')
            ->numeric()
            ->disabled(fn(Get $get, FormLivewire $livewire) => ($get('part') ?? data_get($livewire, 'data.part')) && ($get('part') ?? data_get($livewire, 'data.part') != 1))
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
            ->live()
            ->hintColor('primary')
            ->hint(new HtmlString('<span class="grayscale"></span>Currency<span class="red"> *</span>'));
    }

    /**
     * @return Toggle
     */
    public static function getLastOrder(): Toggle
    {
        return Toggle::make('extra.lastOrder')
            ->label('')
            ->live()
            ->extraAttributes([
                'class' => 'cursor-pointer mx-auto',
            ])
            ->hintColor('primary')
            ->hint(new HtmlString('<span title="Checking this will allocate the remaining initial payment for this record.">Last order</span>'));
    }

    /**
     * @return Hidden
     */
    public static function getPayment(): Hidden
    {
        return Hidden::make('extra.payment');
    }

    /**
     * @return Hidden
     */
    public static function getRemaining(): Hidden
    {
        return Hidden::make('extra.remaining');
    }

    /**
     * @return Hidden
     */
    public static function getInitialPayment(): Hidden
    {
        return Hidden::make('extra.initialPayment');
    }

    /**
     * @return Hidden
     */
    public static function getProvisionalPayment(): Hidden
    {
        return Hidden::make('extra.provisionalPayment');
    }


    /**
     * @return Hidden
     */
    public static function getTotal(): Hidden
    {
        return Hidden::make('extra.total');
    }


    /**
     * @return Hidden
     */
    public static function getInitialTotal(): Hidden
    {
        return Hidden::make('extra.initialTotal');
    }


    /**
     * @return Hidden
     */
    public static function getProvisionalTotal(): Hidden
    {
        return Hidden::make('extra.provisionalTotal');
    }


    /**
     * @return Hidden
     */
    public static function getFinalTotal(): Hidden
    {
        return Hidden::make('extra.finalTotal');
    }

    /**
     * @return Hidden
     */
    public static function getHiddenBuyingPrice(): Hidden
    {
        return Hidden::make('buying_price');
    }

    /**
     * @return Hidden
     */
    public static function getHiddenBuyingQuantity(): Hidden
    {
        return Hidden::make('buying_quantity');
    }

    /**
     * @return Hidden
     */
    public static function getHiddenPayableQuantity(): Hidden
    {
        return Hidden::make('extra.payableQuantity');
    }


    /**
     * @return TextInput
     */
    public static function getQuantity(): TextInput
    {
        return TextInput::make('buying_quantity')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale"></span>Initial<span class="red"> *</span>'))
            ->formatStateUsing(function ($operation, $state, ?Model $record) {
                if ($operation == 'edit') {
                    $part = optional($record->order)->part;
                    $proformaQuantity = (float)optional($record->order)->orderRequest->quantity;
                    if ($proformaQuantity != (float)$state && $part != 1) {
                        return $proformaQuantity;
                    }
                    return $state;
                }
            })
            ->
            disabled(fn(Get $get, FormLivewire $livewire) => ($get('part') ?? data_get($livewire, 'data.part')) && ($get('part') ?? data_get($livewire, 'data.part') != 1))
            ->hintColor('primary')
            ->placeholder('Metric ton')
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
            ->disabled(fn($livewire) => self::shouldDisableInput($livewire))
            ->hintColor('primary')
            ->placeholder('Metric ton')
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
            ->disabled(fn($livewire) => self::shouldDisableInput($livewire))
            ->hintColor('primary')
            ->placeholder('Metric ton')
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
            ->formatStateUsing(function ($operation, $state, ?Model $record) {
                if ($operation == 'edit') {
                    $part = optional($record->order)->part;
                    $proformaPrice = (float)optional($record->order)->orderRequest->price;
                    if ($proformaPrice != (float)$state && $part != 1) {
                        return $proformaPrice;
                    }
                    return $state;
                }
            })
            ->hintColor('primary')
            ->disabled(fn(Get $get, FormLivewire $livewire) => ($get('part') ?? data_get($livewire, 'data.part')) && ($get('part') ?? data_get($livewire, 'data.part') != 1))
            ->placeholder(fn(Get $get) => $get('extra.currency'))
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
            ->hintColor('primary')
            ->disabled(fn($livewire) => self::shouldDisableInput($livewire))
            ->placeholder(fn(Get $get) => $get('extra.currency'))
            ->numeric();
    }

    /**
     * @return TextInput
     */
    public
    static function getFinalPrice(): TextInput
    {
        return TextInput::make('final_price')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale"></span>Final'))
            ->disabled(fn($livewire) => self::shouldDisableInput($livewire))
            ->hintColor('primary')
            ->placeholder(fn(Get $get) => $get('extra.currency'))
            ->numeric();
    }

    /**
     * @return Select
     */
    public
    static function getPackaging(): Select
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
            ->createOptionUsing(function (array $data): int {
                return Packaging::create($data)->getKey();
            })
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
    public
    static function getDeliveryTerm(): Select
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
            ->createOptionUsing(function (array $data): int {
                return DeliveryTerm::create($data)->getKey();
            })
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
    public
    static function getShippingLine(): Select
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
            ->createOptionUsing(function (array $data): int {
                return ShippingLine::create($data)->getKey();
            })
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
    public
    static function getPortOfDelivery(): Select
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
            ->createOptionUsing(function (array $data): int {
                return PortOfDelivery::create($data)->getKey();
            })
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
    public
    static function getLoadingStartLine(): DatePicker
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
    public
    static function getLoadingDeadline(): DatePicker
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
    public
    static function getETD(): DatePicker
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
    public
    static function getETA(): DatePicker
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
    public
    static function getChangeOfStatus(): Toggle
    {
        return Toggle::make('change_of_destination')
            ->label('');
    }

    /**
     */
    public
    static function getContainerShipping()
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
    public
    static function getFCL(): TextInput
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
    public
    static function getFCLType(): TextInput
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
    public
    static function getNumberOfContainer(): TextInput
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
    public
    static function getOcceanFreight(): TextInput
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
    public
    static function getTHC(): TextInput
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
    public
    static function getBookingNumber(): TextInput
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
    public
    static function getFreeTime(): TextInput
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
    public
    static function getGrossWeight(): TextInput
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
    public
    static function getNetWeight(): TextInput
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
    public
    static function getVoyageNumber(): TextInput
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
    public
    static function getVoyageNumberSecondLeg(): TextInput
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
    public
    static function getDeclarationNumber(): TextInput
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
    public
    static function getDeclarationDate(): DatePicker
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
    public
    static function getBLNumber(): TextInput
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
    public
    static function getBLDate(): DatePicker
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
    public
    static function getBLNumberSecondLeg(): TextInput
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
    public
    static function getBLDateSecondLeg(): DatePicker
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
    public
    static function getFileUpload(): FileUpload
    {
        return FileUpload::make('file_path')
            ->label('')
            ->image()
            ->getUploadedFileNameForStorageUsing(self::nameUploadedFile())
            ->previewable(true)
            ->disk('filament')
            ->directory('/attachments/order-attachments')
            ->maxSize(2500)
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'application/pdf', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
            ->imageEditor()
            ->openable()
            ->downloadable();
    }

    /**
     * @return Select
     */
    public
    static function getAttachmentTitle(): Select
    {
        return Select::make('name')
            ->options(Name::getSortedNamesForModule('Order'))
            ->label('')
            ->placeholder('Select or Create')
            ->hint(new HtmlString('<span class="grayscale">ℹ️️️ </span>Title/Name'))
            ->hintColor('primary')
            ->columnSpan(1)
            ->columns(1)
            ->requiredWith('file_path')
            ->createOptionForm([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->rule(new EnglishAlphabet)
                    ->rule(new UniqueTitleInOrder)
                    ->dehydrateStateUsing(fn(?string $state) => slugify($state)),
                TextInput::make('module')
                    ->disabled(true)
                    ->dehydrateStateUsing(fn($state) => $state ?? 'Order')
                    ->default('Order')
            ])
            ->createOptionUsing(function (array $data): int {
                $data['module'] = $data['module'] ?? 'Order';
                return Name::create($data)->getKey();
            })
            ->createOptionAction(function (Action $action) {
                return $action
                    ->modalHeading('Create new title')
                    ->modalButton('Create')
                    ->modalWidth('lg');
            });
    }

    public
    static function getDocumentsReceived()
    {
        return CheckboxList::make('extra.docs_received')
            ->options(self::$documents)
            ->hint(new HtmlString('<span class="text-muted">Mark all documents received:</span>'))
            ->live()
            ->bulkToggleable()
            ->label('')
            ->columnSpan(4);
    }

    public static function getTagsInput(): TagsInput
    {
        return TagsInput::make('extra')
            ->label('Type your words');
    }

    public static function getModule(): Hidden
    {
        return Hidden::make('module')
            ->default('Order');
    }
}
