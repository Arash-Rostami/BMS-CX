<?php

namespace App\Filament\Resources\Operational\PaymentRequestResource\Pages\AdminComponents;

use App\Filament\Resources\Master\PayeeResource\Pages\Admin as PayeeAdmin;
use App\Models\Allocation;
use App\Models\Department;
use App\Models\Name;
use App\Models\Order;
use App\Models\Payee;
use App\Models\PaymentRequest;
use App\Models\ProformaInvoice;
use App\Models\User;
use App\Policies\PaymentRequestPolicy;
use App\Rules\EnglishAlphabet;
use App\Rules\UniqueTitleInPaymentRequest;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Livewire\Component as Livewire;
use Wallo\FilamentSelectify\Components\ButtonGroup;

trait Form
{
    /**
     * @return Radio
     */
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
            ->disableOptionWhen(fn(string $value, Model $record): bool => PaymentRequestPolicy::updateStatus($value, $record))
            ->label(fn() => new HtmlString('<span class="grayscale">🛑 </span><span class="text-primary-500 font-normal">Status</span>'));
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
            ->label(fn() => new HtmlString('<span class="grayscale">🏟️ </span><span class="text-primary-500 font-normal">Department</span>'));
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
            ->label(fn() => new HtmlString('<span class="grayscale">↗️  </span><span class="text-primary-500 font-normal">Department of Allocation</span>'));
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
            ->label(fn() => new HtmlString('<span class="grayscale">🎯</span><span class="text-primary-500 font-normal">Reason for Allocation</span>'));
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
            ->label(fn() => new HtmlString('<span class="grayscale">⭕  </span><span class="text-primary-500 font-normal">Allocation for</span>'));
    }

    /**
     * @return MarkdownEditor
     */
    public static function getPurpose(): MarkdownEditor
    {
        return MarkdownEditor::make('purpose')
            ->label(fn() => new HtmlString('<span class="grayscale">🚩 </span><span class="text-primary-500 font-normal">Purpose</span>'))
            ->maxLength(65535)
            ->requiredIf('reason_for_payment', '26')
            ->disableAllToolbarButtons()
            ->hidden(fn(Get $get): bool => $get('reason_for_payment') != 26)
            ->columnSpanFull()
            ->placeholder('Please specify the purpose of payment request');
    }

    /**
     * @return MarkdownEditor
     */
    public static function getDescription(): MarkdownEditor
    {
        return MarkdownEditor::make('description')
            ->label(fn() => new HtmlString('<span class="grayscale">ℹ️ </span><span class="text-primary-500 font-normal">Notes</span>'))
            ->maxLength(65535)
            ->disableAllToolbarButtons()
            ->placeholder('optional')
            ->columnSpanFull();
    }

    /**
     * @return TextInput
     */
    public static function getPayableAmount(): TextInput
    {
        return TextInput::make('requested_amount')
            ->label(fn() => new HtmlString('<span class="grayscale">💳 </span><span class="text-primary-500 font-normal">Payable Amount</span>'))
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
            ->label(fn() => new HtmlString('<span class="grayscale">💰 </span><span class="text-primary-500 font-normal">Total amount</span>'))
            ->gte('requested_amount')
            ->validationMessages([
                'gte' => 'Total amount cannot be less tan the payable/requested amount.',
            ])
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
            ->label(fn() => new HtmlString('<span class="grayscale">💱  </span><span class="text-primary-500 font-normal">Currency</span>'));
    }

    /**
     * @return DatePicker
     */
    public static function getDeadline(): DatePicker
    {
        return DatePicker::make('deadline')
            ->label(function (Get $get) {
                return new HtmlString(
                    $get('department_id') == 6
                        ? '<span class="grayscale"> </span><span class="text-primary-500 font-normal">Deadline (optional)</span>'
                        : '<span class="grayscale">⏲ </span><span class="text-primary-500 font-normal">Deadline</span><span class="red"> *</span>'
                );
            })
            ->live()
            ->requiredUnless('department_id', '6')
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
            ->label(fn() => new HtmlString('<span class="grayscale">✒️  </span><span class="text-primary-500 font-normal">Beneficiary</span>'))
            ->options(['supplier' => 'Supplier', 'contractor' => 'Contractor'])
            ->live()
            ->columnSpan(1)
            ->default('supplier')
            ->required(fn(Get $get) => $get('department_id') == 6);
    }

    /**
     * @return Select
     */
    public static function getTypeOfPayment(): Select
    {
        return Select::make('type_of_payment')
            ->label(fn() => new HtmlString('<span class="grayscale">✒️ </span><span class="text-primary-500 font-normal">Payment Type</span>'))
            ->options(PaymentRequest::$typesOfPayment)
            ->columnSpan(1)
            ->live()
            ->afterStateUpdated(fn($state, Set $set) => ($state != 'advance') ? $set('extra.collectivePayment', 0) : $set('extra.collectivePayment', 1))
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
            ->label(fn() => new HtmlString('<span class="grayscale"> 🤝</span><span class="text-primary-500 font-normal">Supplier</span>'))
            ->relationship('supplier', 'name')
            ->searchable()
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
            ->label(fn() => new HtmlString('<span class="grayscale">🤝 </span><span class="text-primary-500 font-normal">Contractor</span>'))
            ->relationship('contractor', 'name')
            ->searchable()
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
            ->label(fn() => new HtmlString('<span class="grayscale"> 🤝</span><span class="text-primary-500 font-normal">Payee</span>'))
            ->options(Payee::all()->pluck('name', 'id'))
            ->searchable()
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
            ->label(fn() => new HtmlString('<span class="grayscale">✒️ </span><span class="text-primary-500 font-normal">Recipient</span>'))
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
            ->label(fn() => new HtmlString('<span class="grayscale"> 📍</span><span class="text-primary-500 font-normal">Beneficiary Address</span>'))
            ->maxLength(65535)
            ->disableAllToolbarButtons()
            ->placeholder('optional');
    }

    /**
     * @return TextInput
     */
    public static function getBankName(): TextInput
    {
        return TextInput::make('bank_name')
            ->label(fn() => new HtmlString('<span class="grayscale"> 🏛️</span><span class="text-primary-500 font-normal">Bank Name</span>'))
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
            ->label(fn() => new HtmlString('<span class="grayscale">📍</span><span class="text-primary-500 font-normal">Bank Address</span>'))
            ->maxLength(65535)
            ->disableAllToolbarButtons()
            ->placeholder('optional');
    }

    /**
     * @return TextInput
     */
    public static function getAccountNumber(): TextInput
    {
        return TextInput::make('account_number')
            ->label(fn() => new HtmlString('<span class="grayscale"> #️⃣ ️ </span><span class="text-primary-500 font-normal">Account No.</span>'))
            ->placeholder('optional')
            ->maxLength(255);
    }

    /**
     * @return TextInput
     */
    public static function getSwiftCode(): TextInput
    {
        return TextInput::make('swift_code')
            ->label(fn() => new HtmlString('<span class="grayscale"># </span><span class="text-primary-500 font-normal">Swift Code</span>'))
            ->placeholder('optional')
            ->maxLength(255);
    }

    /**
     * @return TextInput
     */
    public static function getIBANCode(): TextInput
    {
        return TextInput::make('IBAN')
            ->label(fn() => new HtmlString('<span class="grayscale"># </span><span class="text-primary-500 font-normal">IBAN Code</span>'))
            ->placeholder('optional')
            ->maxLength(255);
    }

    /**
     * @return TextInput
     */
    public static function getIFSCCode(): TextInput
    {
        return TextInput::make('IFSC')
            ->label(fn() => new HtmlString('<span class="grayscale"># </span><span class="text-primary-500 font-normal">IFSC Code</span>'))
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
            ->label(fn() => new HtmlString('<span class="grayscale"># </span><span class="text-primary-500 font-normal">MICR Code</span>'))
            ->placeholder('optional')
            ->numeric()
            ->placeholder('optional')
            ->maxLength(255);
    }

    /**
     * @return Select
     */
    public static function getProformaInvoiceNumber(): Select
    {
        return Select::make('proforma_invoice_number')
            ->options(ProformaInvoice::getDistinctProformaNumbers()->toArray())
            ->required(fn(Get $get) => $get('department_id') == 6)
            ->live()
            ->disabled(fn($operation, Get $get) => $operation == 'edit' || $get('type_of_payment') == 'advance')
            ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state) {
                $set('part', []);
            })
            ->visible(fn(Get $get) => $get('type_of_payment') != 'advance')
            ->columnSpan(2)
            ->label(fn() => new HtmlString('<span class="grayscale">🛒 </span><span class="text-primary-500 font-normal">Pro forma Invoice</span>'));
    }

    public static function hiddenInvoiceNumber()
    {
        return Hidden::make('hidden_proforma_number');
    }


    /**
     * @return Select
     */
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
            ->live()
            ->getOptionLabelFromRecordUsing(fn(Model $record) => $record->showSearchResult())
            ->searchingMessage('⌕')
//            ->getSearchResultsUsing(function (string $search): array {
//                if (!ProformaInvoice::hasMatchingProformaNumber($search)) {
//                    return ['No results found'];
//                }
//                return ProformaInvoice::getProformaInvoicesWithSearch($search)
//                    ->mapWithKeys(fn($invoice) => self::showSearchResults($invoice))
//                    ->toArray() ?: ['No results found'];
//            })
            ->searchable(['reference_number', 'proforma_number'])
            ->disabled(fn($operation) => $operation == 'edit')
            ->visible(fn(Get $get) => $get('type_of_payment') == 'advance')
            ->multiple()
            ->columnSpan(2)
            ->label(fn() => new HtmlString('<span class="grayscale">🛒 </span><span class="text-primary-500 font-normal">Pro forma Invoice</span>'));
    }


    /**
     * @return ButtonGroup
     */
    public static function getTotalOrPart(): ButtonGroup
    {
        return ButtonGroup::make('extra.collectivePayment')
            ->label(fn() => new HtmlString('<span class="grayscale">🔍 </span><span class="text-primary-500 font-normal">Scope</span>'))
            ->options([1 => 'Total', 0 => 'Part'])
            ->disabled(fn($operation, Get $get) => $operation == 'edit' or $get('type_of_payment') == 'advance' or $get('type_of_payment') == '')
            ->default(1)
            ->beforeStateDehydrated(fn() => 1)
            ->afterStateUpdated(fn($state, Set $set) => ($state != 1) ? $set('type_of_payment', 'balance') : $set('type_of_payment', 'advance'))
            ->hidden(fn(Get $get) => $get('department_id') != 6)
            ->live()
            ->columnSpan(1)
            ->required(fn($get)=> $get('type_of_payment') != 'advance');
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
            ->requiredIf('extra.collectivePayment', 0)
            ->visible(fn(Get $get) => $get('extra.collectivePayment') == 0 && $get('type_of_payment') != 'advance')
            ->disabled(fn($operation) => $operation == 'edit')
            ->live()
            ->label(fn() => new HtmlString('<span class="grayscale">🔢 </span><span class="text-primary-500 font-normal">Order Detail</span>'))
            ->validationMessages([
                'required_if' => 'This field is required when the payment scope is based on the part.'
            ]);
    }

    public static function getOrder(): Select
    {
        return Select::make('order_id')
            ->options(fn(Get $get, Set $set) => static::getOrderOptions($get,$set))
            ->requiredIf('extra.collectivePayment', 0)
            ->visible(fn(Get $get) => $get('extra.collectivePayment') == 0 && !empty($get('part')))
            ->disabled(fn($operation) => $operation == 'edit')
            ->afterStateUpdated(function (Get $get, Set $set, $old, $state) {
                $details = self::calculateOrderFinancials($state);

                $set('currency', $details['currency']);
                $set('total_amount', $details['total']);
                $set('requested_amount', $details['requested']);
            })
            ->live()
            ->label(fn() => new HtmlString('<span class="grayscale">🔢 </span><span class="text-primary-500 font-normal">Order</span>'))
            ->columnSpan(2)
            ->validationMessages([
                'required_if' => 'This field is required when the payment scope is based on the part.'
            ]);
    }

    /**
     * @return Toggle
     */
    public static function getAttachmentToggle(): Toggle
    {
        return Toggle::make('use_existing_attachments')
            ->label('Use existing attachments')
            ->default(false)
            ->columnSpan(2)
            ->live();
    }

    /**
     * @return Select
     */
    public static function getSourceSelection(): Select
    {
        return Select::make('source_type')
            ->label(fn() => new HtmlString('<span class="text-primary-500 font-normal">Choose Source Type</span>'))
            ->options([
                'proforma_invoice' => 'Proforma Invoice',
                'order' => 'Order',
            ])
            ->live()
            ->required()
            ->columnSpan(1);
    }

    /**
     * @return Select
     */
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
            ->columnSpan(1)
            ->searchable();
    }

    /**
     * @return Select
     */
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
                        return ProformaInvoice::find($sourceProformaInvoice)->attachments->pluck('name', 'id')->toArray();
                    } elseif ($sourceType === 'order') {
                        return Order::find($sourceProformaInvoice)->attachments->pluck('name', 'id')->toArray();
                    }
                }

                return [];
            });
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
                    ->hint(fn(?Model $record) => $record ? $record->getCreatedAtBy() : 'To add an attachment, save the record.')
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
     * @return Select
     */
    public static function getAttachmentFileName(): Select
    {
        return Select::make('name')
            ->options(Name::getSortedNamesForModule('PaymentRequest'))
            ->label(fn() => new HtmlString('<span class="grayscale">ℹ️️️  </span><span class="text-primary-500 font-normal">Title|Name</span>'))
            ->placeholder('Type in English ONLY')
            ->requiredWith('file_path')
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


    /**
     * @return RichEditor
     */
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
            ->fileAttachmentsDirectory('/attachments/payment-attachments/chats')
            ->fileAttachmentsVisibility('public')
            ->label('')
            ->hint(fn(?Model $record, $operation) => ($record && $record->message) ? $record->getChatWriter() : new HtmlString('Content<span class="red"> *</span>'))
            ->placeholder('Type your message here. You may attach any image or send any link by just clicking on the buttons above.')
            ->columnSpan(3)
            ->required();
    }

    /**
     * @return Select
     */
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

    /**
     * @return Hidden
     */
    public static function getChatRecord(): Hidden
    {
        return Hidden::make('record_id')
            ->default(fn(Livewire $livewire) => data_get($livewire, 'data.id'));
    }

    /**
     * @return Hidden
     */
    public static function getChatModule(): Hidden
    {
        return Hidden::make('record_type')
            ->default('payment_request');
    }
}
