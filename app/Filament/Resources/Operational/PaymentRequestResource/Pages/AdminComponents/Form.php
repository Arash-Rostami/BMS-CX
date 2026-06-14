<?php

namespace App\Filament\Resources\Operational\PaymentRequestResource\Pages\AdminComponents;

use App\Filament\Resources\Master\BeneficiaryResource\Pages\Admin as BeneficiaryAdmin;
use App\Models\Allocation;
use App\Models\Beneficiary;
use App\Models\Department;
use App\Models\Name;
use App\Models\Order;
use App\Models\PaymentRequest;
use App\Models\ProformaInvoice;
use App\Models\SupplierSummary;
use App\Models\User;
use App\Policies\PaymentRequestPolicy;
use App\Rules\EnglishAlphabet;
use App\Rules\UniqueTitleInPaymentRequest;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Livewire\Component as Livewire;


trait Form
{
    public static function getAccountNumber(): TextInput
    {
        return TextInput::make('account_number')
            ->label(fn() => new HtmlString('<span class="grayscale"> #️⃣2️⃣️ </span><span class="text-primary-500 font-normal">Account No.</span>'))
            ->placeholder('Enter bank account number')
            ->formatStateUsing(fn($state, $operation, $record) => ($operation !== 'create' && $record) ? $record->account_number : null)
            ->columnSpanFull()
            ->disabled(fn($operation) => self::isEditingDisabled($operation))
            ->visible(fn($get) => $get('extra.paymentMethod') === 'bank_account');
    }

    public static function getAdditionalBankDetailsToggle(): Toggle
    {
        return Toggle::make('extra.show_more_bank_details')
            ->label('Supplementary Banking Details')
            ->helperText('Enable to specify additional routing identifiers (e.g., IBAN, IFSC, MICR) typically required for cross-border transactions.')
            ->live()
            ->afterStateUpdated(fn(Set $set, $state) => !$state
                ? collect(['IBAN', 'IFSC', 'MICR'])->each(fn($f) => $set($f, null))
                : null
            );
    }

    public static function getAdjustmentAmount(): Group
    {
        return Group::make([
            Toggle::make('extra.show_manual_credit')
                ->label('Enable Credit Adjustment')
                ->helperText('Enter allocated credit to automatically calculate the adjustment, or manually specify the net adjustment amount.')
                ->afterStateHydrated(fn (Toggle $component, ?Model $record) => $component->state(
                    data_get($record, 'extra.show_manual_credit') || data_get($record, 'extra.credit_to_use') > 0
                ))
                ->live()
                ->afterStateUpdated(function (Set $set, $state) {
                    if (!$state) {
                        $set('extra.credit_to_use', null);
                        $set('adjustment_amount', null);
                    }
                }),

            TextInput::make('extra.credit_to_use')
                ->label(fn() => new HtmlString('<span class="grayscale">💳 </span><span class="text-primary-500 font-normal">Allocated Credit</span>'))
                ->numeric()
                ->live(debounce: 1000)
                ->placeholder('Enter credit amount')
                ->hidden(fn(Get $get) => !$get('extra.show_manual_credit'))
                ->hint(fn(Get $get) => is_numeric($get('extra.credit_to_use')) ? showDelimiter($get('extra.credit_to_use'), $get('currency')) : $get('extra.credit_to_use'))
                ->tooltip('This reduces the payable amount and recalculates the adjustment (Automatic).')
                ->afterStateUpdated(function ($state, Get $get, Set $set) {
                    $requested = (double)$get('requested_amount');
                    $creditToUse = (double)$state;
                    $set('adjustment_amount', (double)($requested - $creditToUse));
                })
                ->rules([
                    function (Get $get) {
                        return function (string $attribute, $value, $fail) use ($get) {
                            $creditToUse = (double)$value;
                            $availableCredit = (double)$get('extra.available_supplier_credit');
                            $requestedAmount = (double)$get('requested_amount');

                            if ($creditToUse > $availableCredit && $availableCredit > 0) {
                                $fail('🚫 The credit to use cannot exceed the available supplier credit.');
                            }
                            if ($creditToUse > $requestedAmount) {
                                $fail('🚫 The credit to use cannot exceed the payable amount.');
                            }
                        };
                    },
                ])
                ->disabled(fn($operation) => self::isEditingDisabled($operation)),

            TextInput::make('adjustment_amount')
                ->label(fn() => new HtmlString('<span class="grayscale">🎚️ </span><span class="text-primary-500 font-normal">Adjustment Amount</span>'))
                ->numeric()
                ->live(debounce: 1000)
                ->placeholder('Enter final adjusted payable (Credit/Debit)')
                ->tooltip('This replaces the payable amount when applying credits or adjustments (Manual)')
                ->disabled(fn($operation) => self::isEditingDisabled($operation))
                ->visible(fn(Get $get) => $get('extra.show_manual_credit'))
                ->hint(fn(Get $get) => is_numeric($get('adjustment_amount')) ? showDelimiter($get('adjustment_amount'), $get('currency')) : $get('adjustment_amount')),
        ]);
    }

    public static function getAllProformaInvoicesOrOrders(): Select
    {
        return Select::make('source_proforma_invoice')
            ->label('')
            ->hint(fn(Get $get) => new HtmlString(
                $get('source_type') === 'proforma_invoice'
                    ? '<span class="text-primary-500">Proforma Invoice<span class="red"> *</span>'
                    : ($get('source_type') === 'order'
                    ? '<span class="text-primary-500">Order<span class="red"> *</span>'
                    : '<span class="text-primary-500">Ref No.<span class="red"> *</span>'
                )
            ))
            ->placeholder(fn(Get $get) => $get('source_type') === 'proforma_invoice'
                ? 'Choose Proforma Invoice'
                : ($get('source_type') === 'order' ? 'Choose Order' : 'First choose source type')
            )
            ->options(fn(Get $get) => $get('source_type') === 'proforma_invoice'
                ? ProformaInvoice::getProformaInvoicesCached()
                : ($get('source_type') === 'order' ? Order::getOrdersCached() : [])
            )
            ->live()
            ->required()
            ->disabled(fn($operation) => self::isEditingDisabled($operation))
            ->columnSpan(1)
            ->searchable();
    }

    public static function getAttachmentFile(): Section
    {
        return Section::make()
            ->schema([
                FileUpload::make('file_path')
                    ->label('')
                    ->hint(fn(?Model $record) => $record ? $record->getCreatedAtBy() : 'To add an attachment, save the record.')
                    ->getUploadedFileNameForStorageUsing(self::nameUploadedFile())
                    ->previewable(true)
                    ->disk('filament')
                    ->directory('/attachments/payment-request')
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

    public static function getAttachmentFileName(): Select
    {
        return Select::make('name')
            ->options(Name::getSortedNamesForModule('PaymentRequest'))
            ->label(fn() => new HtmlString('<span class="grayscale">ℹ️️️  </span><span class="text-primary-500 font-normal">Title|Name</span>'))
            ->placeholder('Type in English ONLY')
            ->requiredWith('file_path')
            ->validationMessages([
                'required_with' => '🚫 The name is required when an attachment is uploaded.',
            ])
            ->createOptionForm([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->rule(new EnglishAlphabet)
                    ->rule(new UniqueTitleInPaymentRequest)
                    ->dehydrateStateUsing(fn(?string $state) => slugify($state)),
                Hidden::make('module')
                    ->dehydrateStateUsing(fn($state) => $state ?? 'PaymentRequest')
                    ->default('PaymentRequest')
            ])
            ->createOptionUsing(function (array $data): int {
                $data['module'] = $data['module'] ?? 'PaymentRequest';
                return Name::create($data)->getKey();
            })
            ->createOptionAction(function (Action $action) {
                return $action
                    ->modalHeading('Create new title')
                    ->modalButton('Create')
                    ->modalWidth('lg');
            })
            ->rule(new EnglishAlphabet)
            ->columnSpanFull();
    }

    public static function getAttachmentToggle(): Toggle
    {
        return Toggle::make('use_existing_attachments')
            ->label('Use existing attachments')
            ->default(false)
            ->onIcon('heroicon-m-bolt')
            ->offIcon('heroicon-o-no-symbol')
            ->offColor('gray')
            ->columnSpan(2)
            ->disabled(fn($operation) => self::isEditingDisabled($operation))
            ->live();
    }

    public static function getBankAddress(): MarkdownEditor
    {
        return MarkdownEditor::make('bank_address')
            ->label(fn() => new HtmlString('<span class="grayscale">📍</span><span class="text-primary-500 font-normal">Bank Address</span>'))
            ->maxLength(65535)
            ->disableAllToolbarButtons()
            ->disabled(fn($operation) => self::isEditingDisabled($operation))
            ->placeholder('optional');
    }

    public static function getBankName(): TextInput
    {
        return TextInput::make('bank_name')
            ->label(fn() => new HtmlString('<span class="grayscale"> 🏛️</span><span class="text-primary-500 font-normal">Bank Name</span>'))
            ->placeholder('')
            ->required()
            ->maxLength(255)
            ->disabled(fn($operation) => self::isEditingDisabled($operation))
            ->reactive();
    }

    public static function getBeneficiary(): ToggleButtons
    {
        return ToggleButtons::make('beneficiary_name')
            ->label(fn() => new HtmlString('<span class="grayscale">✒️  </span><span class="text-primary-500 font-normal">Beneficiary</span>'))
            ->options(fn(Get $get) => $get('type_of_payment') == 'advance' ? ['supplier' => 'Supplier'] : ['supplier' => 'Supplier', 'contractor' => 'Contractor'])
            ->inline()
            ->grouped()
            ->live()
            ->columnSpan(1)
            ->disabled(fn(string $operation) => self::isEditingDisabled($operation))
            ->default('supplier')
            ->extraAttributes(['style' => 'margin: auto!important'])
            ->required(fn(Get $get) => $get('department_id') == 6);
    }

    public static function getBeneficiaryAddress(): MarkdownEditor
    {
        return MarkdownEditor::make('beneficiary_address')
            ->label(fn() => new HtmlString('<span class="grayscale"> 📍</span><span class="text-primary-500 font-normal">Recipient Address</span>'))
            ->maxLength(65535)
            ->disableAllToolbarButtons()
            ->disabled(fn($operation) => self::isEditingDisabled($operation))
            ->placeholder('optional');
    }

    public static function getCPSReasons(): Select
    {
        return Select::make('reason_for_payment')
            ->options(function (Get $get) {
                $departmentId = $get('department_id') ?? '';
                $departmentCode = $departmentId ? Department::find($departmentId)?->code : '';

                return $departmentCode
                    ? Allocation::getUniqueReasonsForCPS($departmentCode)
                    : Allocation::all()->pluck('reason', 'id')->toArray();
            })
            ->required()
            ->reactive()
            ->hidden(fn(Get $get) => $get('department_id') == 6)
            ->required()
            ->disabled(fn(Get $get) => $get('department_id') == 6)
            ->disabled(fn($operation) => self::isEditingDisabled($operation))
            ->label(fn() => new HtmlString('<span class="grayscale">🎯</span><span class="text-primary-500 font-normal">Reason for Allocation</span>'));
    }

    public static function getCardTransfer()
    {
        return TextInput::make('card_transfer_number')
            ->label(fn() => new HtmlString('<span class="grayscale"> #️⃣️3️⃣ </span><span class="text-primary-500 font-normal">Card No.</span>'))
            ->placeholder('Enter card transfer number')
            ->formatStateUsing(fn($state, $operation, $record) => ($operation !== 'create' && $record) ? $record->account_number : null)
            ->columnSpanFull()
            ->placeholder('XXXX-XXXX-XXXX-XXXX ')
            ->maxLength(19)
            ->minLength(19)
            ->afterStateUpdated(function ($state, $set) {
                $cleanState = preg_replace('/[^0-9-]/', '', $state);

                $numericState = substr(preg_replace('/[^0-9]/', '', $cleanState), 0, 16);

                $formattedState = implode('-', str_split($numericState, 4));

                $set('card_transfer_number', $formattedState);
            })
            ->validationMessages([
                'min' => '🚫 The card number must be exactly 16 digits long, exclusive of dashes.',
                'max' => '🚫 The card number must be exactly 16 digits long, exclusive of dashes.',
            ])
            ->reactive()
            ->disabled(fn($operation) => self::isEditingDisabled($operation))
            ->visible(fn($get) => $get('extra.paymentMethod') === 'card_transfer');
    }

    public static function getCaseNumber(): TextInput
    {
        return TextInput::make('case_number')
            ->label(fn() => new HtmlString('<span class="grayscale">📑️ </span><span class="text-primary-500 font-normal">Case/Contract No.</span>'))
            ->placeholder('Optional for tracking')
            ->tooltip('Add a Case/Project number for improved searchability and more detailed payment notifications within BMS.')
            ->disabled(fn($operation) => self::isEditingDisabled($operation))
            ->columnSpan(1);
    }

    public static function getChatContent(): RichEditor
    {
        return RichEditor::make('message')
            ->disableToolbarButtons([
                'blockquote',
                'codeBlock',
                'h3',
                'italic',
            ])
            ->disabled(fn(?Model $record) => ($record && $record->message))
            ->fileAttachmentsDisk('filament')
            ->fileAttachmentsDirectory('/attachments/payment-request/chats')
            ->fileAttachmentsVisibility('public')
            ->label('')
            ->hint(fn(?Model $record, $operation) => ($record && $record->message) ? $record->getChatWriter() : new HtmlString('Content<span class="red"> *</span>'))
            ->placeholder('Type your message here. You may attach any image or send any link by just clicking on the buttons above.')
            ->columnSpan(3)
            ->required();
    }

    public static function getChatMentionedUsers(): Select
    {
        return Select::make('mentions')
            ->options(User::all()->pluck('fullName', 'id')->sortBy('fullName'))
            ->disabled(fn(?Model $record) => ($record && $record->mentions))
            ->multiple()
            ->label('')
            ->columnSpan(1)
            ->hint(fn(?Model $record, $operation) => ($record && $record->mentions) ? new HtmlString("@ <span class='italic'>Tagged Users</span>") : 'Tag Users In message')
            ->placeholder(fn(?Model $record) => ($record && $record->mentions) ? '' : 'Choose users to mention in the message');
    }

    public static function getChatModule(): Hidden
    {
        return Hidden::make('record_type')
            ->default('payment_request');
    }

    public static function getChatRecord(): Hidden
    {
        return Hidden::make('record_id')
            ->default(fn(Livewire $livewire) => data_get($livewire, 'data.id'));
    }

    public static function getContractor(): Select
    {
        return Select::make('contractor_id')
            ->relationship('contractor', 'name')
            ->disabled(fn(Get $get) => $get('type_of_payment') == 'advance')
            ->visible(fn(Get $get): bool => $get('department_id') == 6 && $get('beneficiary_name') == 'contractor')
            ->required(fn(Get $get): bool => $get('department_id') == 6 && $get('beneficiary_name') == 'contractor')
            ->label(fn() => new HtmlString('<span class="grayscale">🤝 </span><span class="text-primary-500 font-normal">Contractor</span>'))
            ->searchable()
            ->disabled(fn($operation) => self::isEditingDisabled($operation))
            ->createOptionForm([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->dehydrateStateUsing(fn(?string $state) => strtoupper($state)),
                MarkdownEditor::make('description')
                    ->maxLength(65535)
                    ->disableAllToolbarButtons()
            ])
            ->live()
            ->createOptionAction(function (Action $action) {
                return $action
                    ->modalHeading('Create new contractor')
                    ->modalButton('Create')
                    ->modalWidth('lg');
            });
    }

    public static function getCostCenter(): Select
    {
        return Select::make('cost_center')
            ->options(Department::getGroupedDepartmentOptions())
            ->required()
            ->reactive()
            ->default('all')
            ->default(auth()->user()->info['department'] ?? '')
            ->hidden(fn(Get $get) => $get('department_id') == 6)
            ->disabled(fn(Get $get) => $get('department_id') == 6)
            ->disabled(fn($operation) => self::isEditingDisabled($operation))
            ->label(fn() => new HtmlString('<span class="grayscale">↗️  </span><span class="text-primary-500 font-normal">Cost Center</span>'));
    }

    public static function getCurrency(): Select
    {
        return Select::make('currency')
            ->reactive()
            ->options(function (callable $get) {
                $departmentId = $get('department_id');

                return in_array($departmentId, [2, 5, 6, 8, 10, 17, 21, 22, 23])
                    ? showCurrencies()
                    : ['Rial' => new HtmlString('<span class="mr-2">🇮🇷</span> Rial')];
            })
            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                $set('extra.paymentMethod', $state !== 'Rial' ? 'bank_account' : '');

                self::updateSupplierCreditState($set, $get, $get('supplier_id'), $state);
            })
            ->required()
            ->disabled(fn($operation) => self::isEditingDisabled($operation))
            ->label(fn() => new HtmlString('<span class="grayscale">💱  </span><span class="text-primary-500 font-normal">Currency</span>'));
    }

    public static function getDeadline(): DatePicker
    {
        return DatePicker::make('deadline')
            ->label(fn(Get $get) => new HtmlString('<span class="grayscale">⏲ </span><span class="text-primary-500 font-normal">Deadline</span>'))
            ->live()
            ->required()
            ->validationMessages([
                'required' => '🚫 The date is required.',
                'after_or_equal' => '🚫 The deadline cannot be in the past. Please select today or a future date.',
            ])
            ->closeOnDateSelection()
            ->disabled(fn($operation) => self::isEditingDisabled($operation))
            ->minDate(fn($operation, Get $get) => $operation == 'create' ? now()->subDays(1) : null)
            ->format('Y-m-d 12:00:00')
            ->native(false);
    }

    public static function getDepartment(): Select
    {
        return Select::make('department_id')
            ->options(Department::getGroupedDepartmentOptions())
            ->live()
            ->afterStateUpdated(function ($state, Set $set) {
                $set('cost_center', $state);
                return in_array($state, [2, 5, 6, 8, 21, 22, 23]) ? $set('currency', 'USD') : $set('currency', 'Rial');
            })
            ->default(auth()->user()->info['department'] ?? '')
            ->disabled(fn($operation) => self::isEditingDisabled($operation))
            ->required()
            ->label(fn() => new HtmlString('<span class="grayscale">🏟️ </span><span class="text-primary-500 font-normal">Department</span>'));
    }

    public static function getDescription(): MarkdownEditor
    {
        return MarkdownEditor::make('description')
            ->label(fn() => new HtmlString('<span class="grayscale">ℹ️ </span><span class="text-primary-500 font-normal">Notes</span>'))
            ->maxLength(65535)
            ->disabled(fn($operation) => self::isEditingDisabled($operation))
            ->disableAllToolbarButtons()
            ->placeholder('optional')
            ->columnSpan(2);
    }

    public static function getIBANCode(): TextInput
    {
        return TextInput::make('IBAN')
            ->label(fn() => new HtmlString('<span class="grayscale"># </span><span class="text-primary-500 font-normal">IBAN</span>'))
            ->placeholder('optional')
            ->visible(fn(Get $get) => $get('currency') != 'Rial' && ($get('extra.show_more_bank_details') || filled($get('IBAN'))))
            ->disabled(fn($operation) => self::isEditingDisabled($operation))
            ->maxLength(255);
    }

    public static function getIFSCCode(): TextInput
    {
        return TextInput::make('IFSC')
            ->label(fn() => new HtmlString('<span class="grayscale"># </span><span class="text-primary-500 font-normal">IFSC</span>'))
            ->placeholder('optional')
            ->visible(fn(Get $get) => $get('currency') != 'Rial' && ($get('extra.show_more_bank_details') || filled($get('IFSC'))))
            ->disabled(fn($operation) => self::isEditingDisabled($operation))
            ->maxLength(255);
    }

    public static function getMICRCode(): TextInput
    {
        return TextInput::make('MICR')
            ->label(fn() => new HtmlString('<span class="grayscale"># </span><span class="text-primary-500 font-normal">MICR</span>'))
            ->placeholder('optional')
            ->numeric()
            ->visible(fn(Get $get) => $get('currency') != 'Rial' && ($get('extra.show_more_bank_details') || filled($get('MICR'))))
            ->disabled(fn($operation) => self::isEditingDisabled($operation))
            ->maxLength(255);
    }

    public static function getOrder(): Select
    {
        return Select::make('order_id')
            ->options(fn(Get $get, Set $set) => static::getOrderOptions($get, $set))
            ->requiredIf('extra.collectivePayment', 0)
            ->visible(fn(Get $get) => $get('extra.collectivePayment') == 0 && !empty($get('part')))
            ->disabled(fn($operation) => $operation == 'edit')
            ->afterStateUpdated(function ($set, $get, $state) {
                if (in_array($get('type_of_payment'), ['advance', 'balance'])) {
                    $details = self::calculateOrderFinancials($state);
                    $set('currency', $details['currency']);
                    $set('total_amount', $details['total']);
                    $set('requested_amount', $details['requested']);
                }
            })
            ->disabled(fn($operation) => self::isEditingDisabled($operation))
            ->live()
            ->label(fn() => new HtmlString('<span class="grayscale">🔢 </span><span class="text-primary-500 font-normal">Order</span>'))
            ->columnSpan(1)
            ->validationMessages([
                'required_if' => 'This field is required when the payment scope is based on the part.'
            ]);
    }

    public static function getPart(): Select
    {
        return Select::make('part')
            ->options([
                'BL' => 'Bill of Lading No.',
                'BN' => 'Booking No.',
                'REF' => 'Reference No.',
                'PN' => 'Project No. (Part)',
                'PR/GR' => 'Product (Grade)'
            ])
            ->columnSpan(1)
            ->requiredIf('extra.collectivePayment', 0)
            ->visible(fn(Get $get) => $get('extra.collectivePayment') == 0 && $get('type_of_payment') != 'advance')
            ->disabled(fn($operation) => $operation == 'edit')
            ->live()
            ->disabled(fn($operation) => self::isEditingDisabled($operation))
            ->label(fn() => new HtmlString('<span class="grayscale">🔢 </span><span class="text-primary-500 font-normal">Order Detail</span>'))
            ->validationMessages([
                'required_if' => 'This field is required when the payment scope is based on the part.'
            ]);
    }

    public static function getPayableAmount(): TextInput
    {
        return TextInput::make('requested_amount')
            ->label(fn() => new HtmlString('<span class="grayscale">💳 </span><span class="text-primary-500 font-normal">Payable Amount</span>'))
            ->live(debounce: 1000)
            ->numeric()
            ->required()
            ->placeholder('The amount to pay')
            ->afterStateUpdated(function ($state, Get $get, Set $set) {

                $currency = $get('currency');
                $paymentMethod = $get('extra.paymentMethod');

                if ($currency === 'Rial' && $paymentMethod === 'card_transfer' && $state > 100000000) {
                    $set('extra.paymentMethod', null);

                    Notification::make()
                        ->warning()
                        ->title('Consider using Sheba or Account No')
                        ->body('It is not possible to transfer more than 100M Rial with a card. Please choose another payment method.')
                        ->persistent()
                        ->send();
                }

                if ($currency === 'Rial' && $paymentMethod === 'bank_account' && $state > 500000000) {
                    Notification::make()
                        ->info()
                        ->title('Consider using Sheba for large transfers')
                        ->body('For amount more than 500M Rial, Sheba is recommended for better assurance. Please choose another payment method.')
                        ->persistent()
                        ->send();
                }

                if ($get('extra.credit_to_use')) {
                    $requested = (double)$state;
                    $creditToUse = (double)$get('extra.credit_to_use');
                    $set('adjustment_amount', (double)($requested - $creditToUse));
                }
            })
            ->rules([
                function (Get $get) {
                    return function (string $attribute, $value, $fail) use ($get) {
                        $currency = $get('currency');
                        $department = $get('department_id');
                        $paymentMethod = $get('extra.paymentMethod');

                        if ($currency === 'Rial' && $value < 200000000 && !in_array($department, [6, 10])) {
                            $fail('🚫 The requested amount must be at least 200,000,000 Rials.');
                        }
                        if ($currency === 'Rial' && $paymentMethod === 'card_transfer' && $value > 100000000) {
                            $fail('🚫 It is not possible to transfer more than 100,000,000 Rial with a card. Please choose another payment method.');
                        }
                        if ($currency === 'Rial' && $paymentMethod === 'bank_account' && $value > 500000000) {
                            $fail('🚫 For amount more than 500,000,000 Rial, Sheba is recommended for better assurance. Please choose another payment method.');
                        }
                    };
                },
            ])
            ->disabled(fn($operation) => self::isEditingDisabled($operation))
            ->hint(fn(Get $get) => is_numeric($get('requested_amount')) ? showDelimiter($get('requested_amount'), $get('currency')) : $get('requested_amount'));
    }

    public static function getPayee(): Select
    {
        return Select::make('payee_id')
            ->relationship('beneficiary', 'name')
            ->hidden(fn(Get $get): bool => $get('department_id') == 6)
            ->required(fn(Get $get): bool => $get('department_id') != 6)
            ->label(fn() => new HtmlString('<span class="grayscale"> 🤝</span><span class="text-primary-500 font-normal">Beneficiary</span>'))
            ->searchable()
            ->live()
            ->disabled(fn($operation) => self::isEditingDisabled($operation))
            ->afterStateUpdated(function ($state, Set $set) {
                $value = $state ? Beneficiary::find($state)?->name : null;
                $set('recipient_name', $value);
            })
            ->createOptionForm([
                BeneficiaryAdmin::getType(),
                BeneficiaryAdmin::getName(),
                BeneficiaryAdmin::getPhoneNumber(),
                BeneficiaryAdmin::getExtra(),
            ])
            ->createOptionAction(function (Action $action) {
                return $action
                    ->modalHeading('Create new beneficiary')
                    ->modalButton('Create')
                    ->modalWidth('lg');
            });
    }

    public static function getPaymentMethod()
    {
        return Select::make('extra.paymentMethod')
            ->label(fn() => new HtmlString('<span class="grayscale">📲</span><span class="text-primary-500 font-normal">Payment Method</span>'))
            ->options([
                'sheba' => 'SHEBA',
                'bank_account' => 'Bank Account',
                'card_transfer' => 'Card Transfer',
                'cash' => 'Cash',
            ])
            ->disableOptionWhen(function (string $value, Get $get) {
                $currency = $get('currency');
                $requestedAmount = $get('requested_amount');

                if ($currency === 'Rial') {
                    return $value === 'card_transfer' && $requestedAmount > 10000000;
                }
                return !in_array($value, ['bank_account', 'cash']);
            })
            ->reactive()
            ->required()
            ->disabled(fn($operation) => self::isEditingDisabled($operation))
            ->afterStateUpdated(fn($state, Set $set, Get $get) => self::fetchBankAccountDetails($get, $state, $set));
    }

    public static function getProformaInvoiceNumber(): Select
    {
        return Select::make('proforma_invoice_number')
            ->options(ProformaInvoice::getDistinctProformaNumbers()->toArray())
            ->required(fn(Get $get) => $get('department_id') == 6)
            ->live()
            ->disabled(fn($operation, Get $get) => $operation == 'edit' || $get('type_of_payment') == 'advance')
            ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state) { $set('part', []); })
            ->visible(fn(Get $get) => $get('type_of_payment') != 'advance')
            ->columnSpan(1)
            ->disabled(fn($operation) => self::isEditingDisabled($operation))
            ->label(fn() => new HtmlString('<span class="grayscale">🛒 </span><span class="text-primary-500 font-normal">Pro forma Invoice</span>'));
    }

    public static function getProformaInvoiceNumbers(): Select
    {
        return Select::make('proforma_invoice_numbers')
            ->relationship('associatedProformaInvoices', 'reference_number')
            ->allowHtml()
            ->required(fn(Get $get) => $get('department_id') == 6)
            ->afterStateUpdated(function (Get $get, Set $set, $old, $state) {
                $records = ProformaInvoice::findMany($state)->keyBy('id');
                $details = self::aggregateProformaInvoiceDetails($records);

                $set('total_amount', $details['total']);
                $set('requested_amount', $details['requested']);
                $set('hidden_proforma_number', trim($details['number']));
            })
            ->disabled(fn($operation) => self::isEditingDisabled($operation))
            ->live()
            ->getOptionLabelFromRecordUsing(fn(Model $record) => $record->showSearchResult())
            ->searchingMessage('⌕')
            ->searchable(['reference_number', 'proforma_number'])
            ->disabled(fn($operation) => $operation == 'edit')
            ->visible(fn(Get $get) => $get('type_of_payment') == 'advance')
            ->multiple()
            ->columnSpan(1)
            ->label(fn() => new HtmlString('<span class="grayscale">🛒 </span><span class="text-primary-500 font-normal">Pro forma Invoice</span>'));
    }

    public static function getProformaInvoicesAttachments(): Select
    {
        return Select::make('available_attachments')
            ->label(fn() => new HtmlString('<span class="text-primary-500 font-normal">Choose Attachment</span>'))
            ->required()
            ->columnSpan(1)
            ->live()
            ->options(function (Get $get) {
                $sourceType = $get('source_type');
                $sourceProformaInvoice = $get('source_proforma_invoice');
                if (!empty($sourceProformaInvoice)) {
                    if ($sourceType === 'proforma_invoice') {
                        return ProformaInvoice::find($sourceProformaInvoice)?->attachments->pluck('name', 'id')->toArray();
                    } elseif ($sourceType === 'order') {
                        return Order::find($sourceProformaInvoice)?->attachments->pluck('name', 'id')->toArray();
                    }
                }

                return [];
            });
    }

    public static function getPurpose(): MarkdownEditor
    {
        return MarkdownEditor::make('purpose')
            ->label(fn() => new HtmlString('<span class="grayscale">🚩 </span><span class="text-primary-500 font-normal">Purpose</span>'))
            ->maxLength(65535)
            ->requiredIf('reason_for_payment', '26')
            ->disableAllToolbarButtons()
            ->hidden(fn(Get $get): bool => $get('reason_for_payment') != 26)
            ->columnSpan(2)
            ->disabled(fn($operation) => self::isEditingDisabled($operation))
            ->placeholder('Please specify the purpose of payment request');
    }

    public static function getRecipientName(): TextInput
    {
        return TextInput::make('recipient_name')
            ->label(fn() => new HtmlString('<span class="grayscale">✒️ </span><span class="text-primary-500 font-normal">Recipient</span>'))
            ->placeholder('If same, enter the beneficiary\'s name again')
            ->required()
            ->disabled(fn($operation) => self::isEditingDisabled($operation))
            ->maxLength(255);
    }

    public static function getShebaAccount()
    {
        return TextInput::make('sheba_number')
            ->label(fn() => new HtmlString('<span class="grayscale"> #️⃣ ️1️⃣</span><span class="text-primary-500 font-normal"> SHEBA No.</span>'))
            ->placeholder('XXXXXXXXXXXXXXXXXXXXXXXX (24 digits)')
            ->formatStateUsing(fn($state, $operation, $record) => ($operation !== 'create' && $record) ? $record->account_number : null)
            ->prefix('IR')
            ->columnSpanFull()
            ->rules(['regex:/^\d{24}$/'])
            ->validationMessages([
                'regex' => '🚫 The SHEBA number must be exactly 24 digits long and contain only digits.',
            ])
            ->disabled(fn($operation) => self::isEditingDisabled($operation))
            ->visible(fn($get) => $get('extra.paymentMethod') === 'sheba');
    }

    public static function getSourceSelection(): Select
    {
        return Select::make('source_type')
            ->label(fn() => new HtmlString('<span class="text-primary-500 font-normal">Choose Source Type</span>'))
            ->options([
                'proforma_invoice' => 'Proforma Invoice',
                'order' => 'Order',
            ])
            ->live()
            ->disabled(fn($operation) => self::isEditingDisabled($operation))
            ->required()
            ->columnSpan(1);
    }

    public static function getStatus(): Radio
    {
        return Radio::make('status')
            ->options(PaymentRequest::$status)
            ->descriptions([
                'pending' => 'Awaiting accounting review',
                'allowed' => 'Initial approval granted',
                'approved' => 'Managerial approval received',
                'rejected' => 'Payment request denied',
                'processing' => 'Payment in progress',
                'completed' => 'Payment successfully processed',
                'cancelled' => 'Payment request canceled',
            ])
            ->inline()
            ->live()
            ->inlineLabel(false)
            ->default('pending')
            ->disabled(fn($operation) => self::isEditingDisabled($operation))
            ->disableOptionWhen(fn(string $value, Model $record): bool => PaymentRequestPolicy::updateStatus($value, $record))
            ->label(fn() => new HtmlString('<span class="grayscale">🛑 </span><span class="text-primary-500 font-normal">Status</span>'));
    }

    public static function getSupplier(): Select
    {
        return Select::make('supplier_id')
            ->requiredIf('beneficiary_name', 'supplier')
            ->visible(fn(Get $get): bool => $get('department_id') == 6 && $get('beneficiary_name') == 'supplier')
            ->required(fn(Get $get): bool => $get('department_id') == 6 && $get('beneficiary_name') == 'supplier')
            ->label(fn() => new HtmlString('<span class="grayscale"> 🤝</span><span class="text-primary-500 font-normal">Supplier</span>'))
            ->relationship('supplier', 'name')
            ->searchable()
            ->live()
            ->afterStateUpdated(function ($state, Get $get, Set $set) { self::updateSupplierCreditState($set, $get, $state, $get('currency')); })
            ->hintAction(
                Action::make('view_credit_log')
                    ->label('Log')
                    ->icon('heroicon-m-document-text')
                    ->color('warning')
                    ->modalHeading('Supplier Credit Balance')
                    ->modalContent(function (Get $get) {
                        $transactions = SupplierSummary::where('supplier_id', $get('supplier_id'))
                            ->where('currency', $get('currency'))
                            ->where('diff', '>', 0)
                            ->get();

                        return view('filament.resources.payment-request-resource.credit-log', [
                            'transactions' => $transactions, 'currency' => $get('currency')
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->visible(fn(Get $get) => $get('supplier_id') && $get('currency') && (double)$get('extra.available_supplier_credit') > 0)
            )
            ->disabled(fn($operation) => self::isEditingDisabled($operation))
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

    public static function getSwiftCode(): TextInput
    {
        return TextInput::make('swift_code')
            ->label(fn() => new HtmlString('<span class="grayscale"># </span><span class="text-primary-500 font-normal">Swift</span>'))
            ->placeholder('optional')
            ->visible(fn(Get $get) => $get('currency') != 'Rial')
            ->disabled(fn($operation) => self::isEditingDisabled($operation))
            ->maxLength(255);
    }

    public static function getTotalAmount(): TextInput
    {
        return TextInput::make('total_amount')
            ->label(fn() => new HtmlString('<span class="grayscale">💰 </span><span class="text-primary-500 font-normal">Total amount</span>'))
            ->numeric()
            ->live()
            ->tooltip('Enter the total amount expected for this request. This is used for tracking partial, advance, and balance payments. If not provided, it will be automatically set to the requested amount under certain conditions.')->gte('requested_amount')
            ->validationMessages([
                'gte' => 'Total amount cannot be less tan the payable/requested amount.',
                'required' => '🚫 The field is required.',
            ])
            ->required(fn(Get $get) => $get('currency') != 'Rial' && $get('department_id') != 8)
            ->visible(fn(Get $get) => in_array($get('type_of_payment'), ['partial', 'advance', 'balance']))
            ->placeholder('Inclusive of the payable amount')
            ->disabled(fn($operation) => self::isEditingDisabled($operation))
            ->hint(fn(Get $get) => is_numeric($get('total_amount')) ? showDelimiter($get('total_amount'), $get('currency')) : $get('total_amount'));
    }

    public static function getTotalOrPart(): ToggleButtons
    {
        return ToggleButtons::make('extra.collectivePayment')
            ->label(fn() => new HtmlString('<span class="grayscale">🔍 </span><span class="text-primary-500 font-normal">Scope</span>'))
            ->options([1 => 'Contract-wide', 0 => 'Order-level'])
            ->grouped()
            ->default(1)
            ->beforeStateDehydrated(fn() => 1)
            ->afterStateUpdated(fn($state, Set $set) => $set('type_of_payment', $state != 1 ? 'balance' : 'advance'))
            ->hidden(fn(Get $get) => $get('department_id') != 6)
            ->live()
            ->disabled(fn(string $operation, Get $get) => $operation === 'edit' || in_array($get('type_of_payment'), ['advance', '']) || self::isEditingDisabled($operation))
            ->columnSpan(1)
            ->extraAttributes(['style' => 'margin: auto!important'])
            ->required(fn(Get $get) => $get('type_of_payment') !== 'advance');
    }

    public static function getType(): Select
    {
        return Select::make('reason_for_payment')
            ->options(Allocation::reasonsForDepartment('cx'))
            ->live()
            ->columnSpan(1)
            ->disabled(fn($operation) => self::isEditingDisabled($operation))
            ->required(fn(Get $get) => $get('department_id') == 6)
            ->label(fn() => new HtmlString('<span class="grayscale">⭕  </span><span class="text-primary-500 font-normal">Allocation for</span>'));
    }

    public static function getTypeOfPayment(): Select
    {
        return Select::make('type_of_payment')
            ->label(fn() => new HtmlString('<span class="grayscale">✒️ </span><span class="text-primary-500 font-normal">Payment Type</span>'))
            ->options(PaymentRequest::$typesOfPaymentInFarsi)
            ->columnSpan(1)
            ->live()
            ->tooltip('For "Advance", "Partial", and "Balance" payment requests, you need to enter the total amount as well.')
            ->afterStateUpdated(function ($state, Get $get, Set $set, Livewire $livewire) {
                ($state != 'advance') ? $set('extra.collectivePayment', 0) : $set('extra.collectivePayment', 1);
                if ($get('currency') === null) {
                    return;
                }

                if ($get('department_id') == 6) {
                    $livewire->dispatch('triggerNext');
                    return;
                }

                if ($get('reason_for_payment') !== null && $get('cost_center') !== null) {
                    $livewire->dispatch('triggerNext');
                }
            })
            ->disabled(fn($operation) => self::isEditingDisabled($operation))
            ->required();
    }

    public static function hiddenInvoiceNumber()
    {
        return Hidden::make('hidden_proforma_number');
    }

    protected static function isEditingDisabled($operation = null): bool
    {
        return $operation === 'edit' && auth()->user()->role == 'partner';
    }
}
