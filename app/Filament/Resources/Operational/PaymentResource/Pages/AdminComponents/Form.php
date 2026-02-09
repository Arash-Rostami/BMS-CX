<?php

namespace App\Filament\Resources\Operational\PaymentResource\Pages\AdminComponents;

use App\Models\Name;
use App\Rules\EnglishAlphabet;
use App\Rules\UniqueTitleInPayment;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

trait Form
{
    /**
     * @return TextInput
     */
    public static function getAmount(): TextInput
    {
        return TextInput::make('amount')
            ->label(fn() => new HtmlString('<span class="grayscale">💰 </span><span class="text-primary-500 font-normal">Amount</span>'))
            ->placeholder('The total sum')
            ->disabled(fn($operation, $record) => $operation === 'edit' && (!$record || !auth()->user()->can('canEditInput', $record)))
            ->required()
            ->numeric()
            ->live(debounce: 1000)
            ->afterStateUpdated(function ($state, Get $get) {
                $paymentRequests = $get('paymentRequests');
                if (empty($paymentRequests)) return;

                $records = \App\Models\PaymentRequest::findMany($paymentRequests);
                $expectedAmount = $records->sum(fn($pr) => $pr->adjustment_amount ?? $pr->requested_amount);

                if ($state > $expectedAmount) {
                    \Filament\Notifications\Notification::make()
                        ->warning()
                        ->title('Amount exceeds Payable Amount')
                        ->body("The entered amount ({$state}) is greater than the expected payable amount ({$expectedAmount}). Please verify.")
                        ->persistent()
                        ->send();
                }
            })
            ->hint(fn(Get $get) => is_numeric($get('amount')) ? showDelimiter($get('amount'), $get('currency')) : $get('amount'));
    }

    /**
     * @return FileUpload
     */
    public static function getAttachment(): FileUpload
    {
        return FileUpload::make('file_path')
            ->label('')
            ->image()
            ->disabled(fn($operation, $record) => $operation === 'edit' ? (auth()->user()->can('canEditInput', $record)) : false)
            ->hint(fn(?Model $record) => $record ? $record->getCreatedAtBy() : 'To add an attachment, save the record.')
            ->getUploadedFileNameForStorageUsing(self::nameUploadedFile())
            ->previewable()
            ->disk('filament')
            ->directory('/attachments/payment')
            ->maxSize(2500)
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'application/pdf', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
            ->imageEditor()
            ->openable()
            ->downloadable()
            ->columnSpanFull();
    }

    public static function getBankCharges(): TextInput
    {
        return TextInput::make('bank_charges')
            ->label(fn() => new HtmlString('<span class="grayscale">🏦 </span><span class="text-primary-500 font-normal">Bank Charges</span>'))
            ->placeholder('e.g., 13.49')
            ->disabled(fn($operation, $record) => $operation === 'edit' && (!$record || !auth()->user()->can('canEditInput', $record)))
            ->numeric()
            ->live()
            ->hint(fn(Get $get) => is_numeric($get('bank_charges')) && $get('bank_charges_currency')
                ? $get('bank_charges_currency') . ' ' . number_format($get('bank_charges'), 2)
                : 'Informational only');
    }

    public static function getBankChargesCurrency(): Select
    {
        return Select::make('bank_charges_currency')
            ->options(collect(showCurrencies())->only(['USD', 'EURO'])->toArray())
            ->live()
            ->disabled(fn($operation, $record) => $operation === 'edit' && (!$record || !auth()->user()->can('canEditInput', $record)))
            ->label(fn() => new HtmlString('<span class="grayscale">💱 </span><span class="text-primary-500 font-normal">Bank Charge Currency</span>'));
    }

    public static function getBypassCashLedger(): Toggle
    {
        return Toggle::make('bypass_cash_ledger')
            ->label(fn() => new HtmlString('<span class="grayscale">🚫</span><span class="text-primary-500 font-normal">Bypass Cash Ledger</span>'))
            ->disabled(fn($operation, $record) => $operation === 'edit' && (!$record || !auth()->user()->can('canEditInput', $record)))
            ->helperText('Check to prevent this payment from being recorded in cash ledger (e.g. External Overseas Exchange).');
    }

    /**
     * @return Select
     */
    public static function getCurrency(): Select
    {
        return Select::make('currency')
            ->options(showCurrencies())
            ->disabled(fn($operation, $record) => $operation === 'edit' && (!$record || !auth()->user()->can('canEditInput', $record)))
            ->required()
            ->label(fn() => new HtmlString('<span class="grayscale"> 💱</span><span class="text-primary-500 font-normal">Currency</span>'));
    }

    /**
     * @return DatePicker
     */
    public static function getDate(): DatePicker
    {
        return DatePicker::make('date')
            ->disabled(fn($operation, $record) => $operation === 'edit' && (!$record || !auth()->user()->can('canEditInput', $record)))
            ->label(fn() => new HtmlString('<span class="grayscale">📆️ </span><span class="text-primary-500 font-normal">Transfer Date</span>'));
    }


// Add to AdminComponents\Form trait

    public static function getEuroEquivalent(): TextInput
    {
        return TextInput::make('equivalent_amount')
            ->label(fn() => new HtmlString('<span class="grayscale">💶 </span><span class="text-primary-500 font-normal">EUR Equivalent</span>'))
            ->placeholder('↑ Enter Exchange Rate First')
            ->numeric()
            ->live(debounce: 500)
            ->afterStateUpdated(fn($state, Get $get, Set $set) => self::calculateEuroEquivalent($get, $set, 'equivalent_amount'))
            ->dehydrated()
            ->hint(fn(Get $get) => is_numeric($get('euro_equivalent'))
                ? '€ ' . number_format($get('euro_equivalent'), 2)
                : 'Auto-computed');
    }

    public static function getExchangeRate(): TextInput
    {
        return TextInput::make('exchange_rate')
            ->label(fn() => new HtmlString('<span class="grayscale">📊 </span><span class="text-primary-500 font-normal">Exchange Rate</span>'))
            ->placeholder('ECB: e.g., 1.1649')
            ->disabled(fn($operation, $record) => $operation === 'edit' && (!$record || !auth()->user()->can('canEditInput', $record)))
            ->numeric()
            ->step('0.000001')
            ->live(debounce: 500)
            ->afterStateUpdated(fn($state, Get $get, Set $set) => self::calculateEuroEquivalent($get, $set, 'exchange_rate'))
            ->hint('USD ⇄ EUR');
    }

    /**
     * @return MarkdownEditor
     */
    public static function getNotes(): MarkdownEditor
    {
        return MarkdownEditor::make('notes')
            ->label(fn() => new HtmlString('<span class="grayscale">ℹ️ </span><span class="text-primary-500 font-normal">Notes</span>'))
            ->maxLength(65535)
            ->disabled(fn($operation, $record) => $operation === 'edit' && (!$record || !auth()->user()->can('canEditInput', $record)))
            ->columnSpanFull()
            ->disableAllToolbarButtons()
            ->placeholder('Optional');
    }

    /**
     * @return TextInput
     */
    public static function getPayer(): TextInput
    {
        return TextInput::make('payer')
            ->label(fn() => new HtmlString('<span class="grayscale">✒️  </span><span class="text-primary-500 font-normal">Payer</span>'))
            ->required()
            ->disabled(fn($operation, $record) => $operation === 'edit' && (!$record || !auth()->user()->can('canEditInput', $record)))
            ->placeholder('The full name of payer');
    }

    /**
     * @return Select
     */
    public static function getPaymentRequest(): Select
    {
        return Select::make('paymentRequests')
            ->relationship('paymentRequests',
                'reference_number',
                modifyQueryUsing: function (Builder $query, string $operation) {
                    if ($operation == 'create') {
                        $query->whereIn('status', ['processing', 'approved', 'allowed'])
                            ->whereNull('deleted_at')
                            ->orderBy('deadline', 'asc');
                    }
                })
            ->getOptionLabelFromRecordUsing(fn(Model $record, $operation) => $record->getCustomizedDisplayName())
            ->searchable(['reference_number', 'proforma_invoice_number'])
            ->disabled(fn($operation) => $operation == 'edit')
            ->afterStateUpdated(fn($state, Set $set) => static::updateRequestedAmount($state, $set))
            ->live()
            ->multiple()
            ->required()
            ->columnSpanFull()
            ->label(fn() => new HtmlString('<span class="grayscale">💳  </span><span class="text-primary-500 font-normal">Payment Request</span>'));
    }

    /**
     * @return Select
     */
    public static function getTitleOfAttachment(): Select
    {
        return Select::make('name')
            ->options(Name::getSortedNamesForModule('Payment'))
            ->label(fn() => new HtmlString('<span class="grayscale">ℹ️️️ </span><span class="text-primary-500 font-normal">Title|Name</span>'))
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
                    ->rule(new UniqueTitleInPayment)
                    ->dehydrateStateUsing(fn(?string $state) => slugify($state)),
                Hidden::make('module')
                    ->dehydrateStateUsing(fn($state) => $state ?? 'Payment')
                    ->default('Payment')
            ])
            ->createOptionUsing(function (array $data): int {
                $data['module'] = $data['module'] ?? 'Payment';
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
     * @return TextInput
     */
    public static function getTransactionID(): TextInput
    {
        return TextInput::make('transaction_id')
            ->label(fn() => new HtmlString('<span class="grayscale"> #️⃣ </span><span class="text-primary-500 font-normal">Transaction ID</span>'))
            ->disabled(fn($operation, $record) => $operation === 'edit' && (!$record || !auth()->user()->can('canEditInput', $record)))
            ->placeholder('Optional: Confirmation Code');
    }

}
