<?php

namespace App\Filament\Resources\Operational\PaymentRequestResource\Pages;

use App\Models\Allocation;
use App\Models\Contractor;
use App\Models\Department;
use App\Models\Order;
use App\Models\Payee;
use App\Models\PaymentRequest;
use App\Models\Supplier;
use App\Models\User;
use App\Notifications\FilamentNotification;
use App\Policies\PaymentRequestPolicy;
use App\Rules\EnglishAlphabet;
use App\Services\NotificationManager;
use Carbon\Carbon;
use Closure;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextColumn\TextColumnSize;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use Filament\Tables\Grouping\Group as Grouping;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Wallo\FilamentSelectify\Components\ButtonGroup;
use App\Filament\Resources\Master\PayeeResource\Pages\Admin as PayeeAdmin;
use Illuminate\Database\Eloquent\Builder;


class Admin
{
    protected static array $statusTexts = [
        'pending' => 'New',
        'processing' => 'Processing',
        'allowed' => 'Allowed',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled'
    ];

    protected static array $statusIcons = [
        'pending' => 'heroicon-m-sparkles',
        'processing' => 'heroicon-m-arrow-path',
        'allowed' => 'heroicon-m-check-badge',
        'approved' => 'heroicon-o-clipboard-document-check',
        'rejected' => 'heroicon-m-x-circle',
        'completed' => 'heroicon-o-flag',
        'cancelled' => 'heroicon-s-hand-raised',
    ];

    protected static array $statusColors = [
        'pending' => 'info',
        'processing' => 'warning',
        'allowed' => 'success',
        'approved' => 'success',
        'rejected' => 'danger',
        'completed' => 'primary',
        'cancelled' => 'secondary',
    ];


    /**
     * @return Radio
     */
    public static function getStatus(): Radio
    {
        return Radio::make('status')
            ->options(PaymentRequest::$status)
            ->descriptions([
                'pending' => 'Payment pending accounting approval',
                'allowed' => 'Accounting department approval for processing the payment',
                'approved' => 'Managerial approval for processing the payment',
                'rejected' => 'Decline the payment request',
                'processing' => 'The payment is being processed',
                'completed' => 'The payment has successfully been made',
                'cancelled' => 'The payment has been cancelled',
            ])
            ->inline()
            ->inlineLabel(false)
            ->default('pending')
            ->disableOptionWhen(fn(string $value, Model $record): bool => PaymentRequestPolicy::updateStatus($value, $record))
            ->label('')
            ->hintColor('primary')
            ->hint(new HtmlString('<span class="grayscale">🛑 </span>Status'));
    }


    /**
     * @return Select
     */
    public static function getType(): Select
    {
        return Select::make('reason_for_payment')
            ->options(Allocation::reasonsForDepartment('cx'))
            ->live()
            ->required(fn(Get $get) => $get('department_id') == 6)
            ->label('')
            ->hintColor('primary')
            ->hint(new HtmlString('<span class="grayscale">⭕ </span>Allocation For<span class="red"> *</span>'));
    }

    /**
     * @return MarkdownEditor
     */
    public static function getPurpose(): MarkdownEditor
    {
        return MarkdownEditor::make('purpose')
            ->label('')
            ->maxLength(65535)
            ->requiredIf('reason_for_payment', '26')
            ->disableAllToolbarButtons()
            ->hidden(fn(Get $get): bool => $get('reason_for_payment') != 26)
            ->hintColor('primary')
            ->columnSpanFull()
            ->placeholder('Please specify the purpose of payment request')
            ->hint(new HtmlString('<span class="grayscale">🚩 </span>Purpose<span class="red"> *</span>'));
    }

    /**
     * @return MarkdownEditor
     */
    public static function getDescription(): MarkdownEditor
    {
        return MarkdownEditor::make('description')
            ->label('')
            ->maxLength(65535)
            ->disableAllToolbarButtons()
            ->hintColor('primary')
            ->placeholder('optional')
            ->columnSpanFull()
            ->hint(new HtmlString('<span class="grayscale">ℹ️ </span>Notes (extra info)'));
    }

    /**
     * @return TextInput
     */
    public static function getPayableAmount(): TextInput
    {
        return TextInput::make('requested_amount')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">💳 </span>Payable amount<span class="red"> *</span>'))
            ->hintColor('primary')
            ->live()
            ->required()
            ->placeholder('The amount to pay')
            ->numeric();
    }

    /**
     * @return TextInput
     */
    public static function getTotalAmount(): TextInput
    {
        return TextInput::make('total_amount')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">💰 </span>Total amount<span class="red"> *</span>'))
            ->gte('requested_amount')
            ->validationMessages([
                'gte' => 'Total amount cannot be less tan the payable/requested amount.',
            ])
            ->hintColor('primary')
            ->required()
            ->placeholder('Inclusive of the payable amount')
            ->numeric();
    }

    /**
     * @return Select
     */
    public static function getCurrency(): Select
    {
        return Select::make('currency')
            ->options(showCurrencies())
            ->required()
            ->label('')
            ->hintColor('primary')
            ->hint(new HtmlString('<span class="grayscale">💱 </span>Currency<span class="red"> *</span>'));
    }

    /**
     * @return DatePicker
     */
    public static function getDeadline(): DatePicker
    {
        return DatePicker::make('deadline')
            ->label('')
            ->requiredUnless('department_id', '6')
            ->hint(function (Get $get) {
                return new HtmlString(
                    $get('department_id') == 6
                        ? '<span class="grayscale"> </span>Deadline (optional)'
                        : '<span class="grayscale">⏲ </span>Deadline<span class="red"> *</span>'
                );
            })
            ->hintColor('primary')
            ->closeOnDateSelection()
            ->minDate(now()->subDays(1))
            ->native(false);
    }

    /**
     * @return ButtonGroup
     */
    public static function getBeneficiary(): ButtonGroup
    {
        return ButtonGroup::make('beneficiary_name')
            ->label('')
            ->options(['supplier' => 'Supplier', 'contractor' => 'Contractor'])
            ->live()
            ->columnSpan(1)
            ->default('supplier')
            ->hint(new HtmlString('<span class="grayscale">✒️ </span>Beneficiary<span class="red"> *</span>'))
            ->hintColor('primary')
            ->required(fn(Get $get) => $get('department_id') == 6);
    }

    /**
     * @return Select
     */
    public static function getTypeOfPayment(): Select
    {
        return Select::make('type_of_payment')
            ->label('')
            ->options(PaymentRequest::$typesOfPayment)
            ->columnSpan(1)
            ->hint(new HtmlString('<span class="grayscale">✒️ </span>Payment Type<span class="red"> *</span>'))
            ->hintColor('primary')
            ->required();
    }


    /**
     * @return Select
     */
    public static function getSupplier(): Select
    {
        return Select::make('supplier_id')
            ->requiredIf('beneficiary_name', 'supplier')
            ->visible(fn(Get $get): bool => $get('department_id') == 6 && $get('beneficiary_name') == 'supplier')
            ->required(fn(Get $get): bool => $get('department_id') == 6 && $get('beneficiary_name') == 'supplier')
            ->label('')
            ->relationship('supplier', 'name')
            ->searchable()
            ->hintColor('primary')
            ->hint(new HtmlString('<span class="grayscale">🤝 </span>Supplier Name<span class="red"> *</span>'))
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
     * @return Select
     */
    public static function getContractor(): Select
    {
        return Select::make('contractor_id')
            ->relationship('contractor', 'name')
            ->visible(fn(Get $get): bool => $get('department_id') == 6 && $get('beneficiary_name') == 'contractor')
            ->required(fn(Get $get): bool => $get('department_id') == 6 && $get('beneficiary_name') == 'contractor')
            ->label('')
            ->relationship('contractor', 'name')
            ->searchable()
            ->hintColor('primary')
            ->hint(new HtmlString('<span class="grayscale">🤝 </span>Contractor Name<span class="red"> *</span>'))
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
                    ->modalHeading('Create new contractor')
                    ->modalButton('Create')
                    ->modalWidth('lg');
            });
    }


    /**
     * @return Select
     */
    public static function getPayee(): Select
    {
        return Select::make('payee_id')
            ->relationship('payee', 'name')
            ->hidden(fn(Get $get): bool => $get('department_id') == 6)
            ->required(fn(Get $get): bool => $get('department_id') != 6)
            ->label('')
            ->options(Payee::all()->pluck('name', 'id'))
            ->searchable()
            ->hintColor('primary')
            ->hint(new HtmlString('<span class="grayscale">🤝 </span>Payee Name<span class="red"> *</span>'))
            ->createOptionForm([
                PayeeAdmin::class::getType(),
                PayeeAdmin::class::getName(),
                PayeeAdmin::class::getPhoneNumber(),
            ])
            ->createOptionAction(function (Action $action) {
                return $action
                    ->modalHeading('Create new payee')
                    ->modalButton('Create')
                    ->modalWidth('lg');
            });
    }


    /**
     * @return TextInput
     */
    public static function getRecipientName(): TextInput
    {
        return TextInput::make('recipient_name')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">✒️ </span>Recipient Name<span class="red"> *</span>'))
            ->hintColor('primary')
            ->placeholder('If same, enter the beneficiary\'s name again')
            ->required()
            ->maxLength(255);
    }

    /**
     * @return MarkdownEditor
     */
    public static function getBeneficiaryAddress(): MarkdownEditor
    {
        return MarkdownEditor::make('beneficiary_address')
            ->label('')
            ->maxLength(65535)
            ->disableAllToolbarButtons()
            ->hintColor('primary')
            ->placeholder('optional')
            ->hint(new HtmlString('<span class="grayscale">📌 </span>Beneficiary Address'));
    }

    /**
     * @return TextInput
     */
    public static function getBankName(): TextInput
    {
        return TextInput::make('bank_name')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">🏛️  </span>Bank Name<span class="red"> *</span>'))
            ->hintColor('primary')
            ->placeholder('')
            ->required()
            ->maxLength(255);
    }

    /**
     * @return MarkdownEditor
     */
    public static function getBankAddress(): MarkdownEditor
    {
        return MarkdownEditor::make('bank_address')
            ->label('')
            ->maxLength(65535)
            ->disableAllToolbarButtons()
            ->hintColor('primary')
            ->placeholder('optional')
            ->hint(new HtmlString('<span class="grayscale">📌 </span>Bank Address'));
    }

    /**
     * @return TextInput
     */
    public static function getAccountNumber(): TextInput
    {
        return TextInput::make('account_number')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">#️⃣ ️ </span>Account No.'))
            ->hintColor('primary')
            ->placeholder('optional')
            ->maxLength(255);
    }

    /**
     * @return TextInput
     */
    public static function getSwiftCode(): TextInput
    {
        return TextInput::make('swift_code')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">#️ </span>Swift Code'))
            ->hintColor('primary')
            ->placeholder('optional')
            ->maxLength(255);
    }

    /**
     * @return TextInput
     */
    public static function getIBANCode(): TextInput
    {
        return TextInput::make('IBAN')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">#️ </span>IBAN Code'))
            ->hintColor('primary')
            ->placeholder('optional')
            ->maxLength(255);
    }

    /**
     * @return TextInput
     */
    public static function getIFSCCode(): TextInput
    {
        return TextInput::make('IFSC')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">#️ </span>IFSC Code'))
            ->hintColor('primary')
            ->placeholder('')
            ->placeholder('optional')
            ->maxLength(255);
    }

    /**
     * @return TextInput
     */
    public static function getMICRCode(): TextInput
    {
        return TextInput::make('MICR')
            ->label('')
            ->hint(new HtmlString('<span class="grayscale">#️ </span>MICR Code'))
            ->hintColor('primary')
            ->placeholder('optional')
            ->numeric()
            ->placeholder('optional')
            ->maxLength(255);
    }

    /**
     * @return Select
     */
    public static function getOrderNumber(): Select
    {
        return Select::make('order_invoice_number')
            ->options(Order::uniqueInvoiceNumber()->pluck('invoice_number_with_reference_number', 'invoice_number')->toArray())
            ->required(fn(Get $get) => $get('department_id') == 6)
            ->live()
            ->searchable()
            ->disabled(fn($operation) => $operation == 'edit')
            ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state) {
                $set('part', []);
            })
            ->label('')
            ->hintColor('primary')
            ->hint(new HtmlString('<span class="grayscale">🛒 </span>Order<span class="red"> *</span>'));
    }


    /**
     * @return ButtonGroup
     */
    public static function getTotalOrPart(): ButtonGroup
    {
        return ButtonGroup::make('extra.collectivePayment')
            ->label('')
            ->options([1 => 'Total', 0 => 'Part'])
            ->disabled(fn($operation) => $operation == 'edit')
            ->default(1)
            ->live()
            ->columnSpan(1)
            ->hint(new HtmlString('<span class="grayscale">🔍 </span>Scope<span class="red"> *</span>'))
            ->hintColor('primary')
            ->required();
    }


    public static function getOrderPart(): Select
    {
        return Select::make('part')
            ->options(fn(Get $get) => static::getOrderPartsOptions($get))
            ->requiredIf('extra.collectivePayment', 0)
            ->visible(fn(Get $get) => $get('extra.collectivePayment') == 0)
            ->disabled(fn($operation) => $operation == 'edit')
            ->live()
            ->label('')
            ->hintColor('primary')
            ->hint(new HtmlString('<span class="grayscale">🔢 </span>Part<span class="red"> *</span>'))
            ->validationMessages([
                'required_if' => 'This field is required when the payment scope is based on the part.'
            ]);
    }


    /**
     * @return Section
     */
    public static function getAttachmentFile(): Section
    {
        return Section::make()
            ->schema([
                FileUpload::make('file_path')
                    ->label('')
                    ->image()
                    ->getUploadedFileNameForStorageUsing(self::nameUploadedFile())
                    ->previewable(true)
                    ->disk('filament')
                    ->directory('/attachments/payment-attachments')
                    ->maxSize(2500)
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'application/pdf', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                    ->imageEditor()
                    ->openable()
                    ->downloadable()
                    ->rules(['max:2500'])
                    ->validationMessages([
                        'max' => 'The file size must NOT exceed 2.5 MB!',
                    ])
                    ->columnSpanFull()
            ]);
    }

    /**
     * @return TextInput
     */
    public static function getAttachmentFileName(): TextInput
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

    /**
     * @return TextColumn
     */
    public static function showReferenceNumber(): TextColumn
    {
        return TextColumn::make('order.extra.reference_number')
            ->label('Order Ref. No.')
            ->grow(false)
            ->formatStateUsing(fn(Model $record, $state) => $record->part ? ($record->orderPart ? $record->orderPart->extra['reference_number'] : 'N/A') : $record->mainOrder->extra['reference_number'] ?? 'N/A')
            ->searchable(query: function (Builder $query, string $search): Builder {
                return $query->whereHas('order', function ($order) use ($search) {
                    $order->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(extra, '$.reference_number')) LIKE ?", ["%{$search}%"]);
                });
            })
            ->sortable(query: function (Builder $query, string $direction) {
                $query->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(extra, '$.reference_number')) $direction");
            })
            ->toggleable()
            ->color('info')
            ->badge();
    }


    /**
     * @return TextColumn
     */
    public static function showInvoiceNumber(): TextColumn
    {
        return TextColumn::make('order.invoice_number')
            ->label('Project No.')
            ->sortable(query: function (Builder $query, string $direction): Builder {
                return $query->orderBy('order_invoice_number', $direction);
            })
            ->grow(false)
            ->searchable()
            ->badge();
    }


    /**
     * @return TextColumn
     */
    public static function showPart(): TextColumn
    {
        return TextColumn::make('order.part')
            ->label('Part')
            ->grow(false)
            ->formatStateUsing(fn(Model $record) => $record->part
                ? (getTableDesign() === 'modern' ? 'PART: ' . ($record->orderPart?->part ? $record->orderPart->part - 1 : 'N/A') : ($record->orderPart?->part ? $record->orderPart->part - 1 : 'N/A'))
                : '⭐'
            )
            ->tooltip(fn(Model $record) => $record->part
                ? ($record->orderPart->logistic->booking_number ?? 'Booking Number Unavailable')
                : ($record->mainOrder->logistic->booking_number ?? 'Booking Number Unavailable')
            )
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public static function showType(): TextColumn
    {
        return TextColumn::make('type_of_payment')
            ->label('Payment Type')
            ->grow(false)
            ->formatStateUsing(fn($state) => ucwords($state))
            ->sortable()
            ->searchable()
            ->badge();
    }

    public static function showID(): TextColumn
    {
        return TextColumn::make('id')
            ->label(new HtmlString('<span class="text-primary-500 cursor-pointer" title="Record Unique ID">Ref. No. ⋮ ID</span>'))
            ->weight(FontWeight::ExtraLight)
            ->sortable()
            ->formatStateUsing(fn(Model $record) => $record->extra['reference_number'] ?? sprintf('PR-%s%04d', $record->created_at->format('y'), $record->id))
            ->grow(false)
            ->tooltip(fn(?string $state): ?string => ($state) ? "Payment Request Ref. No. / ID" : '')
            ->toggleable()
            ->searchable(query: function (Builder $query, string $search): Builder {
                return $query->where(function ($query) use ($search) {
                    $search = strtolower($search);
                    if (str_starts_with($search, 'pr-')) {
                        return $query->whereRaw("DATE_FORMAT(created_at, '%y') = ?", [substr($search, 3, 2)])
                            ->whereRaw("id = ?", [ltrim(substr($search, 5), '0')]);
                    } else {
                        return $query->whereRaw("LOWER(json_extract(extra, '$.reference_number')) LIKE ?", ['%' . $search . '%']);
                    }
                });
            });
    }


    /**
     * @return TextColumn
     */
    public static function showDepartment(): TextColumn
    {
        return TextColumn::make('department.code')
            ->label('Dept.')
            ->grow(false)
            ->tooltip(fn(Model $record) => $record->department->name)
            ->color('secondary')
            ->sortable()
            ->searchable(['code', 'name'])
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public static function showCostCenter(): TextColumn
    {
        return TextColumn::make('extra.costCenter')
            ->label('Cost Center')
            ->grow(false)
            ->tooltip(fn(Model $record) => $record->department->name)
            ->formatStateUsing(fn($state, Model $record) => $record->department->getByCode($state))
            ->color('secondary')
            ->badge();
    }


    /**
     * @return TextColumn
     */
    public static function showReasonForPayment(): TextColumn
    {
        return TextColumn::make('reason.reason')
            ->label('Reason')
//            ->formatStateUsing(fn($state) => PaymentRequest::showAmongAllReasons($state))
            ->sortable()
            ->searchable()
            ->badge();
    }


    /**
     * @return TextColumn
     */
    public static function showBeneficiaryName(): TextColumn
    {
        return TextColumn::make('beneficiary_name')
            ->label('Beneficiary Name')
            ->color('gray')
            ->grow(false)
            ->sortable()
            ->state(function (Model $record) {
                return $record->contractor?->name
                    ?? $record->supplier?->name
                    ?? $record->payee?->name
                    ?? null;
            })->tooltip('Beneficiary Name')
            ->toggleable()
            ->searchable(query: function (Builder $query, string $search): Builder {
                return $query->where(function ($query) use ($search) {
                    $query->whereHas('contractor', function ($subQuery) use ($search) {
                        $subQuery->where('name', 'like', '%' . $search . '%');
                    })
                        ->orWhereHas('supplier', function ($subQuery) use ($search) {
                            $subQuery->where('name', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('payee', function ($subQuery) use ($search) {
                            $subQuery->where('name', 'like', '%' . $search . '%');
                        });
                });
            })
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public static function showBankName(): TextColumn
    {
        return TextColumn::make('bank_name')
            ->label('Bank Name')
            ->color('gray')
            ->sortable()
            ->toggleable()
            ->searchable()
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public static function showStatus(): TextColumn
    {
        return TextColumn::make('status')
            ->alignRight()
            ->grow(false)
            ->alignRight()
            ->tooltip('Status')
            ->formatStateUsing(fn($state): string => self::$statusTexts[$state] ?? 'Unknown')
            ->icon(fn($state): string => self::$statusIcons[$state] ?? null)
            ->color(fn($state): string => self::$statusColors[$state] ?? null)
            ->sortable()
            ->searchable()
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public static function showPayableAmount(): TextColumn
    {
        return TextColumn::make('requested_amount')
            ->label('Payable Amount')
            ->color('warning')
            ->grow(false)
            ->state(fn(?Model $record) => self::concatenateSum($record))
            ->sortable()
            ->toggleable()
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public static function showDeadline(): TextColumn
    {
        return TextColumn::make('deadline')
            ->color('danger')
            ->dateTime()
            ->sortable()
            ->badge()
            ->tooltip(fn(?Model $record) => self::showRemainingDays($record))
            ->toggleable(isToggledHiddenByDefault: false)
            ->formatStateUsing(fn(string $state): string => '📅 Deadline: ' . Carbon::parse($state)->format('M j, Y'));
    }

    /**
     * @return TextColumn
     */
    public static function showTimeStamp(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label('Creation Time')
            ->icon('heroicon-s-calendar-days')
            ->dateTime()
            ->sortable()
            ->alignRight()
            ->toggleable();
    }

    /**
     * @return Grouping
     */
    public static function filterByType(): Grouping
    {
        return Grouping::make('type_of_payment')->collapsible()
            ->label('Type')
            ->getTitleFromRecordUsing(fn(Model $record): string => PaymentRequest::$typesOfPayment[$record->type_of_payment]);
    }

    /**
     * @return Grouping
     */
    public static function filterByStatus(): Grouping
    {
        return Grouping::make('status')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => ucfirst($record->status));
    }

    /**
     * @return Grouping
     */
    public static function filterByOrder(): Grouping
    {
        return Grouping::make('order_invoice_number')->label('Order')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => $record->order_invoice_number ?? 'No Invoice Number');
    }

    /**
     * @return Grouping
     */
    public static function filterByCurrency(): Grouping
    {
        return Grouping::make('currency')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => ucfirst($record->currency));
    }

    /**
     * @return Grouping
     */
    public static function filterByDepartment(): Grouping
    {
        return Grouping::make('department_id')->collapsible()
            ->label('Dep.')
            ->getTitleFromRecordUsing(fn(Model $record): string => Department::getByName($record->department_id));
    }

    /**
     * @return Grouping
     */
    public static function filterByReason(): Grouping
    {
        return Grouping::make('reason_for_payment')->collapsible()
            ->label('Reason')
            ->getTitleFromRecordUsing(fn(Model $record): string => PaymentRequest::showAmongAllReasons($record->reason_for_payment));
    }

    /**
     * @return Grouping
     */
    public static function filterByContractor(): Grouping
    {
        return Grouping::make('contractor.name')->collapsible()
            ->label('Contractor')
            ->getTitleFromRecordUsing(fn(Model $record): string => $record->contractor->name ?? 'No contractor');
    }

    /**
     * @return Grouping
     */
    public static function filterBySupplier(): Grouping
    {
        return Grouping::make('supplier.name')->collapsible()
            ->label('Supplier')
            ->getTitleFromRecordUsing(fn(Model $record): string => $record->supplier->name ?? 'No supplier');
    }

    /**
     * @return Grouping
     */
    public static function filterByPayee(): Grouping
    {
        return Grouping::make('payee.name')->collapsible()
            ->label('Payee')
            ->getTitleFromRecordUsing(fn(Model $record): string => $record->payee->name ?? 'No payee');
    }


    /**
     * @return TextColumn
     */
    public static function showBeneficiaryAddress(): TextColumn
    {
        return TextColumn::make('beneficiary_address')
            ->label('Beneficiary Address')
            ->words(2)
            ->color('amber')
            ->size(TextColumnSize::ExtraSmall)
            ->sortable()
            ->toggleable()
            ->searchable();
    }

    /**
     * @return TextColumn
     */
    public static function showBankAddress(): TextColumn
    {
        return TextColumn::make('bank_address')
            ->label('Bank Address')
            ->words(2)
            ->color('amber')
            ->size(TextColumnSize::ExtraSmall)
            ->sortable()
            ->toggleable()
            ->searchable();
    }

    /**
     * @return TextColumn
     */
    public static function showExtraDescription(): TextColumn
    {
        return TextColumn::make('description')
            ->words(2)
            ->color('amber')
            ->size(TextColumnSize::ExtraSmall)
            ->sortable()
            ->toggleable()
            ->searchable();
    }


    /**
     * @return TextColumn
     */
    public static function showAccountNumber(): TextColumn
    {
        return TextColumn::make('account_number')
            ->label('Account Number')
            ->color('info')
            ->sortable()
            ->toggleable()
            ->searchable()
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public static function showSwiftCode(): TextColumn
    {
        return TextColumn::make('swift_code')
            ->label('Swift Code')
            ->color('info')
            ->sortable()
            ->toggleable()
            ->searchable()
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public static function showIBAN(): TextColumn
    {
        return TextColumn::make('IBAN')
            ->label('IBAN')
            ->color('info')
            ->sortable()
            ->toggleable()
            ->searchable()
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public static function showIFSC(): TextColumn
    {
        return TextColumn::make('IFSC')
            ->label('IFSC')
            ->color('info')
            ->sortable()
            ->toggleable()
            ->searchable()
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public static function showMICR(): TextColumn
    {
        return TextColumn::make('MICR')
            ->label('MISCR')
            ->color('info')
            ->sortable()
            ->toggleable()
            ->searchable()
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public static function showRequestMaker(): TextColumn
    {
        return TextColumn::make('extra.made_by')
            ->label('Made By')
            ->tooltip(fn(Model $record) => $record->created_at)
            ->color('secondary')
            ->toggleable()
            ->searchable(query: function (Builder $query, string $search): Builder {
                return $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(extra, '$.made_by')) LIKE ?", ["%{$search}%"]);
            })
            ->sortable(query: function (Builder $query, string $direction) {
                $query->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(extra, '$.made_by')) $direction");
            })
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public static function showStatusChanger(): TextColumn
    {
        return TextColumn::make('extra.statusChangeInfo.changed_by')
            ->tooltip(fn(Model $record) => Arr::get($record->extra, 'statusChangeInfo.changed_at', 'No status change recorded'))
            ->label('Status Changed By')
            ->toggleable()
            ->searchable(query: function (Builder $query, string $search): Builder {
                return $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(extra, '$.statusChangeInfo.changed_by')) LIKE ?", ["%{$search}%"]);
            })
            ->sortable(query: function (Builder $query, string $direction) {
                $query->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(extra, '$.statusChangeInfo.changed_by')) $direction");
            })
            ->badge();
    }


    /**
     * @return TextEntry
     */
    public static function viewOrder(): TextEntry
    {
        return TextEntry::make('order_invoice_number')
            ->label('Order')
            ->default('N/A')
            ->badge();
    }


    /**
     * @return TextEntry
     */
    public static function viewPart(): TextEntry
    {
        return TextEntry::make('part')
            ->label('Part')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewType(): TextEntry
    {
        return TextEntry::make('type_of_payment')
            ->color('secondary')
            ->state(fn(Model $record) => PaymentRequest::$typesOfPayment[$record->type_of_payment])
            ->badge();
    }

    public static function viewReason(): TextEntry
    {
        return TextEntry::make('reason_for_payment')
            ->state(fn(Model $record) => PaymentRequest::showAmongAllReasons($record->reason_for_payment))
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewBeneficiaryName(): TextEntry
    {
        return TextEntry::make('beneficiary_name')
            ->label('Beneficiary Name')
            ->state(function (Model $record) {
                return $record->contractor?->name
                    ?? $record->supplier?->name
                    ?? $record->payee?->name
                    ?? null;
            })
            ->color('secondary')
            ->badge();
    }


    /**
     * @return TextEntry
     */
    public static function viewRecipientName(): TextEntry
    {
        return TextEntry::make('recipient_name')
            ->label('Recipient Name')
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewBankName(): TextEntry
    {
        return TextEntry::make('bank_name')
            ->label('Bank Name')
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewBeneficiaryAddress(): TextEntry
    {
        return TextEntry::make('beneficiary_address')
            ->label('Beneficiary Address')
            ->columnSpanFull()
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewDepartment(): TextEntry
    {
        return TextEntry::make('department.code')
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewDescription(): TextEntry
    {
        return TextEntry::make('description')
            ->label('Extra')
            ->columnSpanFull()
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewBankAddress(): TextEntry
    {
        return TextEntry::make('bank_address')
            ->label('Bank Address')
            ->columnSpanFull()
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewAccountNumber(): TextEntry
    {
        return TextEntry::make('account_number')
            ->label('Account Number')
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewSwiftCode(): TextEntry
    {
        return TextEntry::make('swift_code')
            ->label('Swift Code')
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewIBAN(): TextEntry
    {
        return TextEntry::make('IBAN')
            ->label('IBAN')
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewIFSC(): TextEntry
    {
        return TextEntry::make('IFSC')
            ->label('IFSC')
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewMICR(): TextEntry
    {
        return TextEntry::make('MICR')
            ->label('MICR')
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewStatus(): TextEntry
    {
        return TextEntry::make('status')
            ->formatStateUsing(fn($state): string => self::$statusTexts[$state] ?? 'Unknown')
            ->icon(fn($state): string => self::$statusIcons[$state] ?? null)
            ->color(fn($state): string => self::$statusColors[$state] ?? null)
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewAmount(): TextEntry
    {
        return TextEntry::make('amount')
            ->state(function (?Model $record) {
                return self::concatenateSum($record);
            })
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewDeadline(): TextEntry
    {
        return TextEntry::make('deadline')
            ->state(fn(?Model $record): string => self::showRemainingDays($record))
            ->badge();
    }

    /**
     * @param Model|null $record
     * @return string
     */
    protected static function showRemainingDays(?Model $record): string
    {
        if (!$record || !$record->deadline) {
            return "No deadline set";
        }

        $daysLeft = Carbon::parse($record->deadline)->diffInDays(Carbon::now());

        return match (true) {
            $daysLeft > 1 => "{$daysLeft} days left",
            $daysLeft === 1 => "1 day left",
            $daysLeft === 0 => "Deadline is today",
            default => "Deadline passed",
        };
    }

    /**
     * @param Model|null $record
     * @return string
     */
    private static function concatenateSum(?Model $record): string
    {
        return '💰 Sum: ' . $record->currency . ' ' . number_format($record->requested_amount) . '/' . number_format($record->total_amount);
    }


    /**
     * @param Model $record
     * @return void
     */
    public static function send(Model $record): void
    {
        $accountants = (new CreatePaymentRequest())->fetchAccountants();

        foreach ($accountants as $recipient) {
            $recipient->notify(new FilamentNotification([
                'record' => self::getOrderRelation($record),
                'type' => 'delete',
                'module' => 'paymentRequest',
                'url' => route('filament.admin.resources.payment-requests.index'),
            ]));
        }
    }

    /**
     * @return Select
     */
    public static function getDepartment(): Select
    {
        return Select::make('department_id')
            ->options(Department::getAllDepartmentNames())
            ->live()
            ->afterStateUpdated(fn($state, Set $set) => $set('extra.costCenter', $state))
            ->required()
            ->label('')
            ->hintColor('primary')
            ->hint(new HtmlString('<span class="grayscale">🏟️ </span>Department<span class="red"> *</span>'));
    }

    /**
     * @return Select
     */
    public static function getCostCenter(): Select
    {
        return Select::make('extra.costCenter')
            ->options(Department::getAllDepartmentNames())
            ->required()
            ->default('all')
            ->disabled(fn(Get $get) => $get('department_id') == 6)
            ->hidden(fn(Get $get) => $get('department_id') == 6)
            ->label('')
            ->hintColor('primary')
            ->hint(new HtmlString('<span class="grayscale">↗️ </span>Department of Allocation<span class="red"> *</span>'));
    }

    /**
     * @return Select
     */
    public static function getCPSReasons(): Select
    {
        return Select::make('reason_for_payment')
            ->options(fn(Get $get) => $get('department_id') ? Allocation::getUniqueReasonsForCPS($get('department_id')) : [])
            ->live()
            ->disabled(fn(Get $get) => $get('department_id') == 6)
            ->hidden(fn(Get $get) => $get('department_id') == 6)
            ->required()
            ->label('')
            ->hintColor('primary')
            ->hint(new HtmlString('<span class="grayscale">🎯 </span>Reason for Allocation<span class="red"> *</span>'));
    }

    /**
     * @param Model $record
     * @return mixed
     */
    public static function getOrderRelation(Model $record)
    {

        if (!isset($record->order_invoice_number)) {
            return PaymentRequest::showAmongAllReasons($record->reason_for_payment);
        }

        if (isset($record->part) && $record->part != null) {
            return $record->orderPart->invoice_number;
        }

        return $record->order->invoice_number;
    }


    /**
     * @return \Closure
     */
    public static function nameUploadedFile(): \Closure
    {
        return function (TemporaryUploadedFile $file, Get $get, ?Model $record): string {

            $name = $get('name') ?? $file->getClientOriginalName();
            // File extension
            $extension = $file->getClientOriginalExtension();

            // Unique identifier
            $timestamp = Carbon::now()->format('YmdHis');
            // New filename with extension
            $newFileName = "Payment-Request-{$timestamp}-{$name}";

            // Sanitizing the file name
            return Str::slug($newFileName, '-') . ".{$extension}";
        };
    }

    protected static function getOrderPartsOptions(Get $get): array
    {
        $orderNumber = $get('order_invoice_number');
        $total = $get('extra.collectivePayment');

        if (!$orderNumber && $total !== 0) return [];


        return Order::where('invoice_number', $orderNumber)
            ->where('order_status', '<>', 'closed')
            ->where('part', '<>', 1)
            ->get()
            ->pluck('invoice_number_with_part', 'id')
            ->toArray();
    }
}
