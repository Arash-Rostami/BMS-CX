<?php

namespace App\Filament\Resources\Operational\OrderResource\Pages\AdminComponents;

use App\Models\Buyer;
use App\Models\DeliveryTerm;
use App\Models\Name;
use App\Models\Order;
use App\Models\Packaging;
use App\Models\PortOfDelivery;
use App\Models\ProformaInvoice;
use App\Models\ShippingLine;
use App\Models\Supplier;
use App\Models\Tag;
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
use function PHPUnit\Framework\isEmpty;


trait Form
{
    /**
     * @return Select
     */
    public static function getCategory(): Select
    {
        return Select::make('category_id')
            ->label(fn() => new HtmlString('<span class="grayscale">📂 </span><span class="text-primary-500 font-normal">Category</span>'))
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
            ->label(fn() => new HtmlString('<span class="grayscale">📦 </span><span class="text-primary-500 font-normal">Product</span>'))
            ->required()
            ->live()
            ->relationship('product', 'name', fn(Builder $query) => $query->orderBy('name'))
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
    public static function getProformaInvoice(): Select
    {
        return Select::make('proforma_invoice_id')
            ->options(ProformaInvoice::getApproved())
            ->live()
            ->formatStateUsing(fn($state, $operation) => $operation == 'edit' ? (ProformaInvoice::find($state))?->formatted_value : ucwords($state))
            ->afterStateUpdated(function (Set $set, ?string $state) {
                self::updateForm($state, $set);
            })
            ->required()
            ->columnSpan(2)
            ->disabled(fn($operation) => $operation == 'edit')
            ->searchable()
            ->label(fn() => new HtmlString('<span class="grayscale">🛍️ </span><span class="text-primary-500 font-normal">Invoice No.</span>'));
    }

    /**
     * @return TextInput
     */
    public static function getManualProjectNumber(): TextInput
    {
        return TextInput::make('invoice_number')
            ->label(fn() => new HtmlString('<span class="grayscale">🗂 </span><span class="text-primary-500 font-normal">Project No. (optional)</span>'))
            ->placeholder('Enter a manual project number to back-date records, or leave blank for automatic generation')
            ->columnSpanFull()
            ->formatStateUsing(function (?Model $record, $operation) {
                if ($record && $operation === 'edit') {
                    return $record->invoice_number;
                }
                return "N/A";
            })
            ->dehydrateStateUsing(fn(?string $state) => strtoupper($state))
            ->rule(fn($operation) => $operation == 'create' ? new NoMultipleProjectNumbers() : null);
    }

    /**
     * @return TextInput
     */
    public static function getProformaNumber(): TextInput
    {
        return TextInput::make('proforma_number')
            ->label(fn() => new HtmlString('<span class="grayscale">#️⃣  </span><span class="text-primary-500 font-normal">Pro forma Invoice No.</span>'))
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
            ->label(fn() => new HtmlString('<span class="grayscale">📅 </span><span class="text-primary-500 font-normal">Pro forma Date</span>'))
            ->native(false)
            ->required();
    }

    /**
     * @return Select
     */
    public static function getPart(): Select
    {
        return Select::make('part')
            ->label(fn() => new HtmlString('<span class="grayscale">📑 </span><span class="text-primary-500 font-normal">Part</span>'))
            ->required()
            ->live()
            ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                self::updateFormBasedOnPreviousRecords($get, $set, $state);
            })
            ->columnSpan(1)
            ->options(function (Get $get, ?Model $record) {
                $proformaInvoiceId = $record->proforma_invoice_id ?? (int)$get('proforma_invoice_id');

                $existingParts = Order::where('proforma_invoice_id', $proformaInvoiceId)
                    ->when($record, fn($query, $record) => $query->where('id', '<>', $record->id))
                    ->pluck('part')
                    ->toArray();

                return array_map(function ($value) {
                    return 'Part ' . $value;
                }, array_diff(array_combine(range(1, 99), range(1, 99)), $existingParts));
            });
    }

    /**
     * @return Select
     */
    public static function getGrade(): Select
    {
        return Select::make('grade_id')
            ->label(fn() => new HtmlString('<span class="grayscale">♠️ </span><span class="text-primary-500 font-normal">Grade</span>'))
            ->relationship('grade', 'name', fn(Builder $query) => $query->orderBy('name'))
            ->live()
            ->default(0)
            ->createOptionForm([
                Select::make('product_id')
                    ->relationship('product', 'name')
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
                    ->modalHeading('Create new grade')
                    ->modalButton('Create')
                    ->modalWidth('lg');
            });
    }

    /**
     * @return Select
     */
    public static function getPurchaseStatus(): Select
    {
        return Select::make('purchase_status_id')
            ->label(fn() => new HtmlString('<span class="grayscale">🚢 </span><span class="text-primary-500 font-normal">Shipment</span>'))
            ->relationship('purchaseStatus', 'name')
            ->live()
            ->required();
    }

    /**
     * @return Select
     */
    public static function getOrderStatus(): Select
    {
        return Select::make('order_status')
            ->label(fn() => new HtmlString('<span class="grayscale">🛒 </span><span class="text-primary-500 font-normal">Order</span>'))
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

        return Select::make('buyer_id')
            ->label(fn() => new HtmlString('<span class="grayscale">📥 </span><span class="text-primary-500 font-normal">Buyer</span>'))
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
            ->label(fn() => new HtmlString('<span class="grayscale"> 📤</span><span class="text-primary-500 font-normal">Supplier</span>'))
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
            ->label(fn() => new HtmlString('<span class="grayscale">°/• </span><span class="text-primary-500 font-normal">Payable Percentage</span>'))
            ->formatStateUsing(fn(?Model $record) => $record ? (float)optional($record->order)->proformaInvoice?->percentage ?? null : null)
            ->placeholder('Enter the percentage number without any %')
            ->numeric()
            ->disabled(true)
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
        return Select::make('currency')
            ->options(showCurrencies())
            ->required()
            ->label(fn() => new HtmlString('<span class="grayscale">💱 </span><span class="text-primary-500 font-normal">Currency</span>'))
            ->live();
    }


    /**
     * @return Toggle
     */
    public static function getManualComputation(): Toggle
    {
        return Toggle::make('extra.manualComputation')
            ->label(fn() => new HtmlString('<span title="Allocate this project\'s remaining pre-payments" class="text-primary-500 font-normal">Manual</span>'))
            ->live()
            ->extraAttributes([
                'class' => 'cursor-pointer mx-auto',
            ]);
    }

    /**
     * @return Toggle
     */
    public static function getLastOrder(): Toggle
    {
        return Toggle::make('extra.lastOrder')
            ->label(fn() => new HtmlString('<span class="text-primary-500 font-normal">Last Order</span>'))
            ->live()
            ->disabled(fn(?Model $record) => $record ? $record->isLastOrderTaken() : false)
            ->tooltip('If disabled, check whether another order record has already been marked as the last order.')
            ->extraAttributes(function (?Model $record) {
                return $record && $record->isLastOrderTaken()
                    ? ['style' => 'cursor:not-allowed; opacity:0']
                    : ['class' => 'cursor-pointer mx-auto'];
            });
    }

    /**
     * @return Toggle
     */
    public
    static function getAllOrders(): Toggle
    {
        return Toggle::make('extra.allOrders')
            ->label(fn() => new HtmlString('<span title="Ignore shares, allocate all remaining pre-payments" class="text-primary-500 font-normal">All</span>'))
            ->live()
            ->disabled(fn(?Model $record) => $record ? $record->areALlOrdersTaken() : false)
            ->tooltip('If disabled, check whether another order record has already been marked for all orders.')
            ->extraAttributes(function (?Model $record) {
                return $record && $record->areALlOrdersTaken()
                    ? ['style' => 'cursor:not-allowed; opacity:0']
                    : ['class' => 'cursor-pointer mx-auto'];
            });
    }

    /**
     * @return Hidden
     */
    public
    static function getPayment(): Hidden
    {
        return Hidden::make('payment');
    }

    /**
     * @return Hidden
     */
    public
    static function getRemaining(): Hidden
    {
        return Hidden::make('remaining');
    }

    /**
     * @return Hidden
     */
    public
    static function getInitialPayment(): Hidden
    {
        return Hidden::make('initial_payment');
    }

    /**
     * @return Hidden
     */
    public
    static function getProvisionalPayment(): Hidden
    {
        return Hidden::make('provisional_payment');
    }


    /**
     * @return Hidden
     */
    public
    static function getTotal(): Hidden
    {
        return Hidden::make('total');
    }


    /**
     * @return Hidden
     */
    public
    static function getInitialTotal(): Hidden
    {
        return Hidden::make('initial_total');
    }


    /**
     * @return Hidden
     */
    public
    static function getProvisionalTotal(): Hidden
    {
        return Hidden::make('provisional_total');
    }


    /**
     * @return Hidden
     */
    public
    static function getFinalTotal(): Hidden
    {
        return Hidden::make('final_total');
    }

    /**
     * @return Hidden
     */
    public
    static function getHiddenBuyingPrice(): Hidden
    {
        return Hidden::make('buying_price');
    }

    /**
     * @return Hidden
     */
    public
    static function getHiddenBuyingQuantity(): Hidden
    {
        return Hidden::make('buying_quantity');
    }

    /**
     * @return Hidden
     */
    public
    static function getHiddenPayableQuantity(): Hidden
    {
        return Hidden::make('payable_quantity');
    }

    /**
     * @return TextInput
     */
    public
    static function getManualInitialPayment(): TextInput
    {
        return TextInput::make('initial_payment')
            ->label(fn() => new HtmlString('<span class="text-primary-500 font-normal">Init. Advance</span>'))
            ->placeholder('Optional')
            ->numeric();
    }

    /**
     * @return TextInput
     */
    public
    static function getManualProvisionalPayment(): TextInput
    {
        return TextInput::make('provisional_total')
            ->label(fn() => new HtmlString('<span class="text-primary-500 font-normal">Prov. Payment</span>'))
            ->placeholder('Optional')
            ->numeric();
    }

    /**
     * @return TextInput
     */
    public
    static function getManualFinalPayment(): TextInput
    {
        return TextInput::make('final_total')
            ->label(fn() => new HtmlString('<span class="text-primary-500 font-normal">Fin. Payment</span>'))
            ->placeholder('Optional')
            ->numeric();
    }


    /**
     * @return TextInput
     */
    public
    static function getQuantity(): TextInput
    {
        return TextInput::make('buying_quantity')
            ->label(fn() => new HtmlString('<span class="text-primary-500 font-normal">Initial</span>'))
            ->disabled(true)
            ->placeholder('Metric ton')
            ->required()
            ->numeric();
    }


    /**
     * @return TextInput
     */
    public
    static function getProvisionalQuantity(): TextInput
    {
        return TextInput::make('provisional_quantity')
            ->label(fn() => new HtmlString('<span class="text-primary-500 font-normal">Provisional</span>'))
            ->disabled(fn(?Model $record) => $record ? $record->hasApprovedRelatedRequests() : false)
            ->placeholder('Metric ton')
            ->numeric();
    }

    /**
     * @return TextInput
     */
    public
    static function getFinalQuantity(): TextInput
    {
        return TextInput::make('final_quantity')
            ->label(fn() => new HtmlString('<span class="text-primary-500 font-normal">Final</span>'))
            ->placeholder('Metric ton')
            ->numeric();
    }

    /**
     * @return TextInput
     */
    public
    static function getPrice(): TextInput
    {
        return TextInput::make('buying_price')
            ->label(fn() => new HtmlString('<span class="text-primary-500 font-normal">Initial</span>'))
            ->disabled(true)
            ->placeholder(fn(Get $get) => $get('currency'))
            ->required()
            ->numeric();
    }


    /**
     * @return TextInput
     */
    public
    static function getProvisionalPrice(): TextInput
    {
        return TextInput::make('provisional_price')
            ->label(fn() => new HtmlString('<span class="text-primary-500 font-normal">Provisional</span>'))
            ->placeholder(fn(Get $get) => $get('currency'))
            ->disabled(fn(?Model $record) => $record ? $record->hasApprovedRelatedRequests() : false)
            ->numeric();
    }

    /**
     * @return TextInput
     */
    public
    static function getFinalPrice(): TextInput
    {
        return TextInput::make('final_price')
            ->label(fn() => new HtmlString('<span class="text-primary-500 font-normal">Final</span>'))
            ->placeholder(fn(Get $get) => $get('currency'))
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
            ->label(fn() => new HtmlString('<span class="grayscale">🗳️ </span><span class="text-primary-500 font-normal">Packaging</span>'))
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
            ->label(fn() => new HtmlString('<span class="grayscale">🚛 </span><span class="text-primary-500 font-normal">Delivery Term</span>'))
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
            ->label(fn() => new HtmlString('<span class="grayscale">⛵ </span><span class="text-primary-500 font-normal">Shipping Company (Cargo Carrier)</span>'))
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
            ->label(fn() => new HtmlString('<span class="grayscale">⚓ </span><span class="text-primary-500 font-normal">Port of Delivery</span>'))
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
            ->label(fn() => new HtmlString('<span class="grayscale">⌛ </span><span class="text-primary-500 font-normal">Delivery Time Start Date</span>'))
            ->native(false);
    }

    /**
     * @return DatePicker
     */
    public
    static function getLoadingDeadline(): DatePicker
    {
        return DatePicker::make('loading_deadline')
            ->label(fn() => new HtmlString('<span class="grayscale">⌛ </span><span class="text-primary-500 font-normal">Delivery Time End Date</span>'))
            ->native(false);
    }

    /**
     * @return DatePicker
     */
    public
    static function getETD(): DatePicker
    {
        return DatePicker::make('extra.etd')
            ->label(fn() => new HtmlString('<span class="grayscale">⌛ </span><span class="text-primary-500 font-normal">ETD</span>'))
            ->native(false);
    }

    /**
     * @return DatePicker
     */
    public
    static function getETA(): DatePicker
    {
        return DatePicker::make('extra.eta')
            ->label(fn() => new HtmlString('<span class="grayscale">⌛ </span><span class="text-primary-500 font-normal">ETA</span>'))
            ->native(false);
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
            ->label(fn() => new HtmlString('<span class="grayscale">📦 </span><span class="text-primary-500 font-normal">For this order, is there any container shipping?</span>'));
    }

    /**
     * @return TextInput
     */
    public
    static function getFCL(): TextInput
    {
        return TextInput::make('FCL')
            ->label(fn() => new HtmlString('<span class="grayscale">⚖️ </span><span class="text-primary-500 font-normal">FCL/Weight</span>'))
            ->maxLength(255);
    }

    /**
     * @return TextInput
     */
    public
    static function getFCLType(): TextInput
    {
        return TextInput::make('full_container_load_type')
            ->label(fn() => new HtmlString('<span class="grayscale">🔤 </span><span class="text-primary-500 font-normal">FCL Type</span>'))
            ->maxLength(255);
    }

    /**
     * @return TextInput
     */
    public
    static function getNumberOfContainer(): TextInput
    {
        return TextInput::make('number_of_containers')
            ->label(fn() => new HtmlString('<span class="grayscale">🗑️ </span><span class="text-primary-500 font-normal">No. of Containers</span>'))
            ->numeric()
            ->default(0);
    }

    /**
     * @return TextInput
     */
    public
    static function getOceanFreight(): TextInput
    {
        return TextInput::make('ocean_freight')
            ->label(fn() => new HtmlString('<span class="grayscale">🌊 </span><span class="text-primary-500 font-normal">Ocean Freight</span>'))
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
            ->label(fn() => new HtmlString('<span class="grayscale">🔚 </span><span class="text-primary-500 font-normal">THX</span>'))
            ->numeric();
    }

    /**
     * @return TextInput
     */
    public
    static function getBookingNumber(): TextInput
    {
        return TextInput::make('booking_number')
            ->label(fn() => new HtmlString('<span class="grayscale">🎫</span><span class="text-primary-500 font-normal">Booking No.</span>'))
            ->maxLength(255);
    }

    /**
     * @return TextInput
     */
    public
    static function getFreeTime(): TextInput
    {
        return TextInput::make('free_time_POD')
            ->label(fn() => new HtmlString('<span class="grayscale">🕘 </span><span class="text-primary-500 font-normal">Free Time (POD)</span>'))
            ->numeric();
    }

    /**
     * @return TextInput
     */
    public
    static function getGrossWeight(): TextInput
    {
        return TextInput::make('gross_weight')
            ->label(fn() => new HtmlString('<span class="grayscale">⚖</span><span class="text-primary-500 font-normal">Gross Weight</span>'))
            ->numeric();
    }

    /**
     * @return TextInput
     */
    public
    static function getNetWeight(): TextInput
    {
        return TextInput::make('net_weight')
            ->label(fn() => new HtmlString('<span class="grayscale">⚖ </span><span class="text-primary-500 font-normal">Net Weight</span>'))
            ->numeric();
    }

    /**
     * @return TextInput
     */
    public
    static function getVoyageNumber(): TextInput
    {
        return TextInput::make('voyage_number')
            ->label(fn() => new HtmlString('<span class="grayscale">🛳️ </span><span class="text-primary-500 font-normal">Voyage No.</span>'))
            ->maxLength(255);
    }

    /**
     * @return TextInput
     */
    public
    static function getVoyageNumberSecondLeg(): TextInput
    {
        return TextInput::make('extra.voyage_number_second_leg')
            ->label(fn() => new HtmlString('<span class="grayscale"> 🛳️</span><span class="text-primary-500 font-normal">Voyage No. (ii)</span>'))
            ->maxLength(255);
    }

    /**
     * @return TextInput
     */
    public
    static function getDeclarationNumber(): TextInput
    {
        return TextInput::make('declaration_number')
            ->label(fn() => new HtmlString('<span class="grayscale">#️⃣ ️  </span><span class="text-primary-500 font-normal">Declaration No.</span>'))
            ->maxLength(255);
    }

    /**
     * @return DatePicker
     */
    public
    static function getDeclarationDate(): DatePicker
    {
        return DatePicker::make('declaration_date')
            ->label(fn() => new HtmlString('<span class="grayscale">📅 </span><span class="text-primary-500 font-normal">Declaration Date</span>'))
            ->native(false);
    }

    /**
     * @return TextInput
     */
    public
    static function getBLNumber(): TextInput
    {
        return TextInput::make('BL_number')
            ->label(fn() => new HtmlString('<span class="grayscale"> #️⃣</span><span class="text-primary-500 font-normal">BL No.</span>'))
            ->maxLength(255);
    }

    /**
     * @return DatePicker
     */
    public
    static function getBLDate(): DatePicker
    {
        return DatePicker::make('BL_date')
            ->label(fn() => new HtmlString('<span class="grayscale">️📅 </span><span class="text-primary-500 font-normal">BL Date</span>'))
            ->native(false);
    }


    /**
     * @return TextInput
     */
    public
    static function getBLNumberSecondLeg(): TextInput
    {
        return TextInput::make('extra.BL_number_second_leg')
            ->label(fn() => new HtmlString('<span class="grayscale">#️ </span><span class="text-primary-500 font-normal">BL No. (ii)</span>'))
            ->maxLength(255);
    }

    /**
     * @return DatePicker
     */
    public
    static function getBLDateSecondLeg(): DatePicker
    {
        return DatePicker::make('extra.BL_date_second_leg')
            ->label(fn() => new HtmlString('<span class="grayscale">📅  </span><span class="text-primary-500 font-normal">BL Date (ii)</span>'))
            ->native(false);
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
            ->hint(fn(?Model $record) => $record ? $record->getCreatedAtBy() : 'To add an attachment, save the record.')
            ->getUploadedFileNameForStorageUsing(self::nameUploadedFile())
            ->previewable(true)
            ->disk('filament')
            ->directory('/attachments/order')
            ->maxSize(2500)
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'application/pdf', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
            ->imageEditor()
            ->openable()
            ->downloadable();
    }


    /**
     * @return Toggle
     */
    public
    static function getAttachmentToggle(): Toggle
    {
        return Toggle::make('use_existing_attachments')
            ->label('Use existing attachments')
            ->default(false)
            ->onIcon('heroicon-m-bolt')
            ->offIcon('heroicon-o-no-symbol')
            ->extraAttributes(fn($state) => $state == 0 ? ['class' => 'bg-white'] : [])
            ->columnSpan(2)
            ->live();
    }

    /**
     * @return Select
     */
    public
    static function getAllProformaInvoices(): Select
    {
        return Select::make('source_proforma_invoice')
            ->label(fn() => new HtmlString('<span class="text-primary-500 font-normal">Select Proforma Invoice (Ref No)</span>'))
            ->options(ProformaInvoice::getProformaInvoicesCached())
            ->live()
            ->required()
            ->columnSpan(2)
            ->searchable();
    }

    /**
     * @return Select
     */
    public
    static function getProformaInvoicesAttachments(): Select
    {
        return Select::make('available_attachments')
            ->label(fn() => new HtmlString('<span class="grayscale">📌 </span><span class="text-primary-500 font-normal">Select Attachment</span>'))
            ->required()
            ->columnSpan(2)
            ->live()
            ->options(fn(Get $get, Set $set) => (!empty($get('source_proforma_invoice'))) ? ProformaInvoice::find($get('source_proforma_invoice'))->attachments->pluck('name', 'id')->toArray() : []);
    }

    /**
     * @return Select
     */
    public
    static function getAttachmentTitle(): Select
    {
        return Select::make('name')
            ->options(Name::getSortedNamesForModule('Order'))
            ->label(fn() => new HtmlString('<span class="grayscale">ℹ️️️  </span><span class="text-primary-500 font-normal">Title|Name</span>'))
            ->placeholder('Select or Create')
            ->columnSpan(1)
            ->columns(1)
            ->requiredWith('file_path')
            ->validationMessages([
                'required_with' => '🚫 The name is required when an attachment is uploaded.',
            ])
            ->createOptionForm([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->rule(new EnglishAlphabet)
                    ->rule(new UniqueTitleInOrder)
                    ->dehydrateStateUsing(fn(?string $state) => slugify($state)),
                Hidden::make('module')
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
            ->options(self::getDynamicDocuments())
            ->hint(new HtmlString())
            ->live()
            ->bulkToggleable()
            ->label(fn() => new HtmlString('<span class="text-muted">Mark all documents received:</span>'))
            ->columnSpan(4);
    }

    public
    static function getTagsInput(): TagsInput
    {
        return TagsInput::make('extra')
            ->label(fn() => new HtmlString('<span class="grayscale">🔖 </span><span class="text-primary-500 font-normal">Type Your words</span>'));
    }


    public
    static function getModule(): Hidden
    {
        return Hidden::make('module')
            ->default('Order');
    }
}
