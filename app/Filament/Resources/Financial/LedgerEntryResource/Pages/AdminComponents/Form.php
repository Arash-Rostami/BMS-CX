<?php

namespace App\Filament\Resources\Financial\LedgerEntryResource\Pages\AdminComponents;

use App\Models\Name;
use App\Rules\EnglishAlphabet;
use App\Rules\UniqueTitleInProformaInvoice;
use App\Services\InterestCalculationService;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Component as Livewire;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

trait Form
{
    public static function getAccount(): Select
    {
        return Select::make('account_id')
            ->label(fn() => new HtmlString('<span class="grayscale">📂 </span><span class="text-primary-500 font-normal">Account (Ledger Account)</span>'))
            ->relationship('account', 'name')
            ->placeholder('Select the account related to this entry')
            ->native(false)
            ->required();
    }

    public static function getAmountForeignCurrency(): TextInput
    {
        return TextInput::make('amount_foreign_currency')
            ->label(fn() => new HtmlString('<span class="grayscale">💶 </span><span class="text-primary-500 font-normal">Money (Foreign Amount)</span>'))
            ->numeric()
            ->nullable()
            ->live(debounce: 1200)
            ->visible(fn(Get $get) => !static::isBaseCurrency($get('currency_type')))
            ->required(fn(Get $get) => !static::isBaseCurrency($get('currency_type')))
            ->afterStateUpdated(fn(Set $set, Get $get) => static::recalculateAmounts($set, $get))
            ->rules(['nullable', 'gt:0'])
            ->validationMessages(['gt' => '⚡ Foreign amount must be greater than zero.'])
            ->hint(fn(Get $get) => is_numeric($get('amount_foreign_currency')) ? showDelimiter($get('amount_foreign_currency'), $get('currency_type')) : $get('amount_foreign_currency'));
    }

    public static function getAttachmentTitle(): Select
    {
        return Select::make('name')
            ->label(fn() => new HtmlString('<span class="grayscale">ℹ️ </span><span class="text-primary-500 font-normal">Name (Title) </span>'))
            ->options(Name::getSortedNamesForModule('LedgerEntry'))
            ->placeholder('select or make')
            ->requiredWith('file_path')
            ->validationMessages([
                'required_with' => '🚫 The name is required when an attachment is uploaded.',
            ])
            ->createOptionForm([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->rule(new EnglishAlphabet)
                    ->rule(new UniqueTitleInProformaInvoice)
                    ->dehydrateStateUsing(fn(?string $state) => slugify($state)),
                Hidden::make('module')
                    ->dehydrateStateUsing(fn($state) => $state ?? 'LedgerEntry')
                    ->default('LedgerEntry')
            ])
            ->createOptionUsing(function (array $data): int {
                $data['module'] = $data['module'] ?? 'LedgerEntry';
                return Name::create($data)->getKey();
            })
            ->createOptionAction(function (Action $action) {
                return $action
                    ->modalHeading('Create new title')
                    ->modalSubmitActionLabel('Create')
                    ->modalWidth('lg');
            })
            ->columnSpan(1);
    }

    public static function getCommissionAmount(): TextInput
    {
        return TextInput::make('commission_amount')
            ->label(fn(Get $get) => new HtmlString('<span class="grayscale">💸 </span><span class="text-primary-500 font-normal">' . (static::isBaseCurrency($get('currency_type')) ? 'Fee (Bank Commission)' : 'Fee (Bank Commission - Foreign)') . '</span>'))
            ->numeric()
            ->default(0)
            ->live(debounce: 1200)
            ->helperText(fn(Get $get) => static::isBaseCurrency($get('currency_type'))
                ? '⚡ Auto-set to 0.05% of principal — editable'
                : '⚡ Entered in foreign currency, auto-converted to base for total'
            )
            ->afterStateUpdated(fn(Set $set, Get $get) => static::recalculateTotals($set, $get))
            ->rules(['nullable', 'gte:0'])
            ->validationMessages(['gte' => '⚡ Bank commission cannot be negative.'])
            ->hint(fn(Get $get) => is_numeric($get('commission_amount')) ? showDelimiter($get('commission_amount'), $get('currency_type')) : $get('commission_amount'));
    }

    public static function getCurrencyType(): Select
    {
        return Select::make('currency_type')
            ->label(fn() => new HtmlString('<span class="grayscale">💱 </span><span class="text-primary-500 font-normal">Currency</span>'))
            ->required()
            ->default(config('financial.base_currency', 'USD'))
            ->options(showCurrencies())
            ->live()
            ->afterStateUpdated(function (Set $set, Get $get) {
                if (static::isBaseCurrency($get('currency_type'))) {
                    $set('amount_foreign_currency', null);
                    $set('exchange_rate', null);
                    $set('principal_amount_base', null);
                    $set('total_disbursed_base', null);
                    $set('remaining_principal', null);
                } else {
                    $set('principal_amount_base', null);
                    $set('total_disbursed_base', null);
                    $set('remaining_principal', null);
                }
                static::recalculateTotals($set, $get);
            });
    }

    public static function getDescription(): TextInput
    {
        return TextInput::make('description')
            ->label(fn() => new HtmlString('<span class="grayscale">📝 </span><span class="text-primary-500 font-normal">Description</span>'))
            ->placeholder('Add brief details to transaction')
            ->required()
            ->maxLength(255)
            ->rules(['not_regex:/^' . preg_quote(InterestCalculationService::OVERPAYMENT_DESCRIPTION, '/') . '$/'])
            ->validationMessages(['not_regex' => 'This description is reserved for system-generated records.']);
    }

    public static function getExchangeRate(): TextInput
    {
        $mode = config('financial.exchange_rate_mode', 'divide');
        $hint = $mode === 'divide'
            ? 'Foreign units per 1 base unit (e.g. 1.08 → 1 USD = 1.08 EUR)'
            : 'Base units per 1 foreign unit (e.g. 1.08 → 1 EUR = 1.08 USD)';

        return TextInput::make('exchange_rate')
            ->label(fn() => new HtmlString('<span class="grayscale">📈 </span><span class="text-primary-500 font-normal">Rate (Exchange Rate)</span>'))
            ->numeric()
            ->nullable()
            ->live(debounce: 1000)
            ->helperText($hint)
            ->rules(['nullable', 'gt:0'])
            ->validationMessages(['gt' => '⚡ Exchange rate must be greater than zero.'])
            ->visible(fn(Get $get) => !static::isBaseCurrency($get('currency_type')))
            ->required(fn(Get $get) => !static::isBaseCurrency($get('currency_type')))
            ->afterStateUpdated(fn(Set $set, Get $get) => static::recalculateAmounts($set, $get))
            ->hint(fn(Get $get) => is_numeric($get('exchange_rate')) ? showDelimiter($get('exchange_rate'), $get('currency_type')) : $get('exchange_rate'));
    }

    public static function getFileUpload(): FileUpload
    {
        return FileUpload::make('file_path')
            ->label('')
            ->image()
            ->hint(fn(?Model $record) => $record ? $record->getCreatedAtBy() : 'To add an attachment, save the record.')
            ->getUploadedFileNameForStorageUsing(self::nameUploadedFile())
            ->previewable(true)
            ->disk('filament')
            ->directory('/attachments/ledger-entry')
            ->maxSize(2500)
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'application/pdf', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
            ->imageEditor()
            ->openable()
            ->downloadable()
            ->columnSpan(1);
    }

    public static function getPrincipalAmountBase(): TextInput
    {
        return TextInput::make('principal_amount_base')
            ->label(fn() => new HtmlString('<span class="grayscale">💵 </span><span class="text-primary-500 font-normal">Base (Principal Amount)</span>'))
            ->required()
            ->numeric()
            ->live(debounce: 1200)
            ->readOnly(fn(Get $get) => !static::isBaseCurrency($get('currency_type')))
            ->helperText(fn(Get $get) => static::isBaseCurrency($get('currency_type'))
                ? 'Enter the principal amount directly'
                : '⚡ Auto-calculated from foreign amount and exchange rate'
            )
            ->afterStateUpdated(function (Set $set, Get $get) {
                if (!static::isBaseCurrency($get('currency_type'))) {
                    return;
                }
                static::recalculateTotals($set, $get, autoCommission: true);
            })
            ->rules(['gt:0'])
            ->validationMessages(['gt' => '⚡ Principal amount must be greater than zero.'])
            ->hint(fn(Get $get) => is_numeric($get('principal_amount_base')) ? showDelimiter($get('principal_amount_base'), config('financial.base_currency', 'USD')) : $get('principal_amount_base'));
    }

    public static function getRateMatrixSnapshot(): Hidden
    {
        return Hidden::make('rate_matrix_snapshot')
            ->default(fn() => config('financial.interest_tiers'));
    }

    public static function getRemainingPrincipal(): TextInput
    {
        return TextInput::make('remaining_principal')
            ->label(fn() => new HtmlString('<span class="grayscale">⚖️ </span><span class="text-primary-500 font-normal">Balance (Remaining Principal)</span>'))
            ->numeric()
            ->readOnly()
            ->helperText(fn(Get $get) => static::isBaseCurrency($get('currency_type'))
                ? 'Remaining balance in base currency'
                : '⚡ Foreign Remaining: ' . (is_numeric($get('amount_foreign_currency')) ? showDelimiter((float)$get('amount_foreign_currency'), $get('currency_type')) : '0')
            )
            ->hint(fn(Get $get) => is_numeric($get('remaining_principal')) ? showDelimiter($get('remaining_principal'), config('financial.base_currency', 'USD')) : $get('remaining_principal'));
    }

    public static function getTotalDisbursedBase(): TextInput
    {
        return TextInput::make('total_disbursed_base')
            ->label(fn() => new HtmlString('<span class="grayscale">💰 </span><span class="text-primary-500 font-normal">Base + Fee (Total Disbursed)</span>'))
            ->required()
            ->numeric()
            ->readOnly()
            ->helperText(fn(Get $get) => static::isBaseCurrency($get('currency_type'))
                ? 'Sum of principal and commission in base currency'
                : '⚡ Foreign Total: ' . (is_numeric($get('amount_foreign_currency')) ? showDelimiter((float)$get('amount_foreign_currency') + (float)$get('commission_amount'), $get('currency_type')) : '0')
            )
            ->hint(fn(Get $get) => is_numeric($get('total_disbursed_base')) ? showDelimiter($get('total_disbursed_base'), config('financial.base_currency', 'USD')) : $get('total_disbursed_base'));
    }

    public static function getTransactionDate(): DatePicker
    {
        return DatePicker::make('transaction_date')
            ->label(fn() => new HtmlString('<span class="grayscale">📅 </span><span class="text-primary-500 font-normal">Date (Transaction Date)</span>'))
            ->native(false)
            ->placeholder('Date the transaction begins')
            ->required()
            ->rules(['date', 'before_or_equal:today'])
            ->validationMessages(['before_or_equal' => '⚡ Transaction date cannot be in the future.']);
    }

    public static function getType(): Select
    {
        return Select::make('type')
            ->label(fn() => new HtmlString('<span class="grayscale">🧾 </span><span class="text-primary-500 font-normal">Type</span>'))
            ->placeholder('Specify if paying out or receiving funds')
            ->options([
                'disbursement' => 'Disbursement (Debit)',
                'receipt' => 'Receipt (Credit)',
            ])
            ->disabled(fn(string $operation): bool => $operation === 'edit')
            ->native(false)
            ->required();
    }

    public static function getUserId(): Hidden
    {
        return Hidden::make('user_id')
            ->default(fn() => auth()->id());
    }

    protected static function isBaseCurrency(?string $currency): bool
    {
        return $currency === config('financial.base_currency', 'USD');
    }

    protected static function nameUploadedFile(): \Closure
    {
        return function (TemporaryUploadedFile $file, Get $get, ?Model $record, Livewire $livewire): string {
            $name = $get('name') ?? $record?->name ?? 'NO-NAME-GIVEN';
            $number = $livewire->data['currency_type'] ?? 'noLedgerEntry';
            $timestamp = now()->format('YmdHis');
            $random = Str::random(5);
            $extension = $file->getClientOriginalExtension();

            $filename = "LE-{$number}-{$timestamp}-{$random}-{$name}";

            return Str::slug($filename, '-') . '.' . $extension;
        };
    }
}
