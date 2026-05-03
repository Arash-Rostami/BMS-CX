<?php

namespace App\Filament\Resources\Financial\SettlementResource\Pages\AdminComponents;

use App\Models\Account;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

trait Form
{
    public static function getAccount(): Select
    {
        return Select::make('account_id')
            ->label(fn() => new HtmlString('<span class="grayscale">📂 </span><span class="text-primary-500 font-normal">Account</span>'))
            ->placeholder('Select the account for this settlement')
            ->options(Account::pluck('name', 'id'))
            ->required()
            ->searchable()
            ->afterStateHydrated(function (Select $component, ?Model $record) {
                if ($record) {
                    $component->state($record->ledgerEntry?->account_id);
                }
            })
            ->helperText(fn (string $operation): string => $operation === 'edit' ? '🔒 Account is locked after creation.' : '❗This cannot be changed after saving.')
            ->disabled(fn(string $operation): bool => $operation === 'edit');
    }

    public static function getCurrencyType(): Select
    {
        return Select::make('currency_type')
            ->label(fn() => new HtmlString('<span class="grayscale">💱 </span><span class="text-primary-500 font-normal">Currency Type</span>'))
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

    public static function getForeignSettlementAmount(): TextInput
    {
        return TextInput::make('foreign_settlement_amount')
            ->label(fn() => new HtmlString('<span class="grayscale">💶 </span><span class="text-primary-500 font-normal">Foreign Amount</span>'))
            ->placeholder('Enter amount in foreign currency')
            ->numeric()
            ->nullable()
            ->columnSpan(1)
            ->live(debounce: 1200)
            ->visible(fn(Get $get) => !static::isBaseCurrency($get('currency_type')))
            ->required(fn(Get $get) => !static::isBaseCurrency($get('currency_type')))
            ->afterStateUpdated(fn(Set $set, Get $get) => static::recalculateSettlement($set, $get))
            ->hint(fn(Get $get) => is_numeric($get('foreign_settlement_amount')) ? showDelimiter($get('foreign_settlement_amount'), $get('currency_type')) : $get('foreign_settlement_amount'));
    }

    public static function getSettlementAmount(): TextInput
    {
        return TextInput::make('settlement_amount')
            ->label(fn() => new HtmlString('<span class="grayscale">💵 </span><span class="text-primary-500 font-normal">Settlement Amount (Base)</span>'))
            ->required()
            ->numeric()
            ->columnSpan(1)
            ->live(debounce: 1200)
            ->readOnly(fn(Get $get, string $operation) => $operation === 'create' && !static::isBaseCurrency($get('currency_type')))
            ->helperText(fn(Get $get, string $operation) => match (true) {
                $operation === 'edit' => '⚡ Editable — recalculation will run on save',
                !static::isBaseCurrency($get('currency_type')) => '⚡ Auto-calculated from foreign amount and exchange rate',
                default => 'Enter settlement amount directly in base currency',
            })
            ->afterStateUpdated(fn(Set $set, Get $get) => static::recalculateSettlement($set, $get))
            ->hint(fn(Get $get) => is_numeric($get('settlement_amount')) ? showDelimiter($get('settlement_amount'), config('financial.base_currency', 'USD')) : $get('settlement_amount'));
    }

    public static function getSettlementExchangeRate(): TextInput
    {
        $mode = config('financial.exchange_rate_mode', 'multiply');
        $hint = $mode === 'divide'
            ? 'Foreign units per 1 base unit (e.g. 1.08 → 1 USD = 1.08 EUR)'
            : 'Base units per 1 foreign unit (e.g. 1.08 → 1 EUR = 1.08 USD)';

        return TextInput::make('settlement_exchange_rate')
            ->label(fn() => new HtmlString('<span class="grayscale">📈 </span><span class="text-primary-500 font-normal">Exchange Rate</span>'))
            ->numeric()
            ->nullable()
            ->live(debounce: 1200)
            ->helperText($hint)
            ->visible(fn(Get $get) => !static::isBaseCurrency($get('currency_type')))
            ->required(fn(Get $get) => !static::isBaseCurrency($get('currency_type')))
            ->afterStateUpdated(fn(Set $set, Get $get) => static::recalculateSettlement($set, $get))
            ->hint(fn(Get $get) => is_numeric($get('settlement_exchange_rate')) ? showDelimiter($get('settlement_exchange_rate'), $get('currency_type')) : $get('settlement_exchange_rate'));
    }

    public static function getTransactionDate(): DatePicker
    {
        return DatePicker::make('transaction_date')
            ->label(fn() => new HtmlString('<span class="grayscale">📅 </span><span class="text-primary-500 font-normal">Transaction Date</span>'))
            ->native(false)
            ->placeholder('Date the settlement occurs')
            ->required();
    }

    protected static function isBaseCurrency(?string $currency): bool
    {
        return $currency === config('financial.base_currency', 'USD');
    }
}
