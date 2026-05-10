<?php

namespace App\Filament\Resources\Financial\SettlementResource\Pages\AdminComponents;

use App\Models\Account;
use App\Models\Name;
use App\Rules\EnglishAlphabet;
use App\Rules\UniqueTitleInProformaInvoice;
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
            ->placeholder('Select the account for this settlement')
            ->options(Account::pluck('name', 'id'))
            ->required()
            ->searchable()
            ->afterStateHydrated(function (Select $component, ?Model $record) {
                if ($record) {
                    $component->state($record->ledgerEntry?->account_id);
                }
            })
            ->helperText(fn(string $operation): string => $operation === 'edit'
                ? '🔒 Account is locked after creation.'
                : '❗This cannot be changed after saving.')
            ->disabled(fn(string $operation): bool => $operation === 'edit');
    }

    public static function getAttachmentTitle(): Select
    {
        return Select::make('name')
            ->label(fn() => new HtmlString('<span class="grayscale">ℹ️ </span><span class="text-primary-500 font-normal">Name (Title)</span>'))
            ->options(Name::getSortedNamesForModule('Settlement'))
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
                    ->dehydrateStateUsing(fn($state) => $state ?? 'Settlement')
                    ->default('Settlement'),
            ])
            ->createOptionUsing(function (array $data): int {
                $data['module'] = $data['module'] ?? 'Settlement';

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
                    $set('foreign_settlement_amount', null);
                    $set('settlement_exchange_rate', null);
                }

                static::recalculateSettlement($set, $get);
            });
    }

    public static function getFileUpload(): FileUpload
    {
        return FileUpload::make('file_path')
            ->label('')
            ->image()
            ->hint(fn(?Model $record) => $record
                ? $record->getCreatedAtBy()
                : 'To add an attachment, save the record.')
            ->getUploadedFileNameForStorageUsing(self::nameUploadedFile())
            ->previewable(true)
            ->disk('filament')
            ->directory('/attachments/settlement')
            ->maxSize(2500)
            ->acceptedFileTypes([
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/svg+xml',
                'application/pdf',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            ])
            ->imageEditor()
            ->openable()
            ->downloadable()
            ->columnSpan(1);
    }

    public static function getForeignSettlementAmount(): TextInput
    {
        return TextInput::make('foreign_settlement_amount')
            ->label(fn() => new HtmlString('<span class="grayscale">💶 </span><span class="text-primary-500 font-normal">Money (Foreign Amount)</span>'))
            ->placeholder('Enter amount in foreign currency')
            ->numeric()
            ->nullable()
            ->columnSpan(1)
            ->live(debounce: 1200)
            ->visible(fn(Get $get) => !static::isBaseCurrency($get('currency_type')))
            ->required(fn(Get $get) => !static::isBaseCurrency($get('currency_type')))
            ->rules(['nullable', 'gt:0'])
            ->validationMessages(['gt' => '⚡ Foreign amount must be greater than zero.'])
            ->afterStateUpdated(fn(Set $set, Get $get) => static::recalculateSettlement($set, $get))
            ->hint(fn(Get $get) => is_numeric($get('foreign_settlement_amount'))
                ? showDelimiter($get('foreign_settlement_amount'), $get('currency_type'))
                : $get('foreign_settlement_amount'));
    }

    public static function getSettlementAmount(): TextInput
    {
        return TextInput::make('settlement_amount')
            ->label(fn() => new HtmlString('<span class="grayscale">💵 </span><span class="text-primary-500 font-normal">Base (Settlement Amount)</span>'))
            ->required()
            ->numeric()
            ->columnSpan(1)
            ->live(debounce: 1200)
            ->readOnly(fn(Get $get, string $operation) => $operation === 'create'
                && !static::isBaseCurrency($get('currency_type')))
            ->helperText(fn(Get $get, string $operation) => match (true) {
                $operation === 'edit' => '⚡ Editable — recalculation will run on save',
                !static::isBaseCurrency($get('currency_type')) => '⚡ Auto-calculated from foreign amount and exchange rate',
                default => 'Enter settlement amount directly in base currency',
            })
            ->rules(['gt:0'])
            ->validationMessages(['gt' => '⚡ Settlement amount must be greater than zero.'])
            ->afterStateUpdated(fn(Set $set, Get $get) => static::recalculateSettlement($set, $get))
            ->hint(fn(Get $get) => is_numeric($get('settlement_amount'))
                ? showDelimiter($get('settlement_amount'), config('financial.base_currency', 'USD'))
                : $get('settlement_amount'));
    }

    public static function getSettlementExchangeRate(): TextInput
    {
        $mode = config('financial.exchange_rate_mode', 'multiply');

        $hint = $mode === 'divide'
            ? 'Foreign units per 1 base unit (e.g. 1.08 → 1 USD = 1.08 EUR)'
            : 'Base units per 1 foreign unit (e.g. 1.08 → 1 EUR = 1.08 USD)';

        return TextInput::make('settlement_exchange_rate')
            ->label(fn() => new HtmlString('<span class="grayscale">📈 </span><span class="text-primary-500 font-normal">Rate (Exchange Rate)</span>'))
            ->numeric()
            ->nullable()
            ->live(debounce: 1200)
            ->helperText($hint)
            ->visible(fn(Get $get) => !static::isBaseCurrency($get('currency_type')))
            ->required(fn(Get $get) => !static::isBaseCurrency($get('currency_type')))
            ->rules(['nullable', 'gt:0'])
            ->validationMessages(['gt' => '⚡ Exchange rate must be greater than zero.'])
            ->afterStateUpdated(fn(Set $set, Get $get) => static::recalculateSettlement($set, $get))
            ->hint(fn(Get $get) => is_numeric($get('settlement_exchange_rate'))
                ? showDelimiter($get('settlement_exchange_rate'), $get('currency_type'))
                : $get('settlement_exchange_rate'));
    }

    public static function getTransactionDate(): DatePicker
    {
        return DatePicker::make('transaction_date')
            ->label(fn() => new HtmlString('<span class="grayscale">📅 </span><span class="text-primary-500 font-normal">Date (Transaction Date)</span>'))
            ->native(false)
            ->placeholder('Date the settlement occurs')
            ->required();
    }

    protected static function isBaseCurrency(?string $currency): bool
    {
        return $currency === config('financial.base_currency', 'USD');
    }

    protected static function nameUploadedFile(): \Closure
    {
        return function (TemporaryUploadedFile $file, Get $get, ?Model $record, Livewire $livewire): string {
            $name = $get('name') ?? $record?->name ?? 'NO-NAME-GIVEN';
            $number = $livewire->data['currency_type'] ?? 'noSettlement';
            $timestamp = now()->format('YmdHis');
            $random = Str::random(5);
            $extension = $file->getClientOriginalExtension();

            $filename = "ST-{$number}-{$timestamp}-{$random}-{$name}";

            return Str::slug($filename, '-') . '.' . $extension;
        };
    }
}
