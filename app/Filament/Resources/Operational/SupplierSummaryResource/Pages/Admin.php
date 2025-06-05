<?php

namespace App\Filament\Resources\Operational\SupplierSummaryResource\Pages;

use App\Models\Supplier;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class Admin
{

    /**
     * @return Select
     */
    public static function getSupplier(): Select
    {
        return Select::make('supplier_id')
            ->label('Supplier')
            ->relationship('supplier', 'name')
            ->options(Supplier::all()->pluck('name', 'id'))
            ->searchable()
            ->preload()
            ->required();
    }

    /**
     * @return Hidden
     */
    public static function getType(): Hidden
    {
        return Hidden::make('type')
            ->label('Type')
            ->default('adjustment');
    }

    /**
     * @return Select
     */
    public static function getCurrency(): Select
    {
        return Select::make('currency')
            ->required()
            ->options([
                'USD' => '$ - USD',
                'EURO' => '€ - EURO',
                'Yuan' => '¥ - Yuan',
                'Dirham' => 'D - Dirham',
                'Ruble' => '₽ - Ruble',
                'Rial' => 'R - Rial'
            ]);
    }

    /**
     * @return TextInput
     */
    public static function getDifference(): TextInput
    {
        return TextInput::make('diff')
            ->label('Amount')
            ->numeric()
            ->required()
            ->default(0.00);
    }

    /**
     * @return Select
     */
    public static function gtStatus(): Select
    {
        return Select::make('status')
            ->required()
            ->options([
                'Overpaid' => '🔴 Credit',
                'Underpaid' => '🟢 Debit',
            ]);
    }

    /**
     * @return TextInput
     */
    public static function getContractNumber(): TextInput
    {
        return TextInput::make('contract_number')
            ->placeholder('Optional for reference only')
            ->maxLength(255);
    }

    /**
     * @return TextInput
     */
    public static function getPaidAmount(): TextInput
    {
        return TextInput::make('paid')
            ->numeric()
            ->placeholder('The actual paid amount in the contract/PI.')
            ->helperText('The actual paid amount in the contract/PI.')
            ->default(0.00);
    }

    /**
     * @return TextInput
     */
    public static function getExpectedAmount(): TextInput
    {
        return TextInput::make('expected')
            ->numeric()
            ->placeholder('The expected amount to be paid according to contract/PI.')
            ->helperText('The expected amount to be paid according to contract/PI.')
            ->default(0.00);
    }

    /**
     * @return TextColumn
     */
    public static function showSupplier(): TextColumn
    {
        return TextColumn::make('supplier.name')
            ->label('Supplier')
            ->searchable()
            ->grow(false)
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public static function showContractNumber(): TextColumn
    {
        return TextColumn::make('contract_number')
            ->tooltip(fn(Model $record) => $record->type)
            ->searchable()
            ->badge('primary')
            ->grow(false)
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public static function showCurrency(): TextColumn
    {
        return TextColumn::make('currency')
            ->searchable()
            ->color('info')
            ->grow(false)
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public static function showPaid(): TextColumn
    {
        return TextColumn::make('paid')
            ->money(fn(Model $record) => $record->currency)
            ->grow(false)
            ->badge()
            ->color('secondary');
    }

    /**
     * @return TextColumn
     */
    public static function showExpected(): TextColumn
    {
        return TextColumn::make('expected')->money(fn(Model $record) => $record->currency)
            ->grow(false)
            ->badge()
            ->color('secondary');
    }

    /**
     * @return TextColumn
     */
    public static function showBalance(): TextColumn
    {
        return TextColumn::make('diff')
            ->label('Balance (Difference)')
            ->money(fn(Model $record) => $record->currency)
            ->grow(false)
            ->color(fn(string $state): string => match (true) {
                (float)$state < 0 => 'success',
                (float)$state > 0 => 'danger',
                default => 'gray',
            })
            ->summarize(self::getSummarizers());
    }

    /**
     * @return TextColumn
     */
    public static function showStatus(): TextColumn
    {
        return TextColumn::make('status')
            ->searchable()
            ->grow(false)
            ->badge()
            ->color(fn(string $state): string => match ($state) {
                'Overpaid' => 'danger',
                'Settled' => 'primary',
                'Underpaid' => 'success',
                default => 'gray',
            })
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public static function showTimeStamp(): TextColumn
    {
        return TextColumn::make('updated_at')
            ->dateTime()
            ->sortable()
            ->grow(false)
            ->toggleable(isToggledHiddenByDefault: true);
    }


    /**
     * @return SelectFilter
     * @throws \Exception
     */
    public static function filterBySupplier(): SelectFilter
    {
        return SelectFilter::make('supplier_id')
            ->label('Supplier')
            ->searchable()
            ->relationship('supplier', 'name');
    }

    /**
     * @return SelectFilter
     * @throws \Exception
     */
    public static function filterByProformaInvoice(): SelectFilter
    {
        return SelectFilter::make('proforma_invoice_id')
            ->label('Proforma Invoice')
            ->searchable()
            ->relationship('proformaInvoice', 'contract_number');
    }


    /**
     * @return Filter
     * @throws \Exception
     */

    public static function filterByAdjustment()
    {
        return Filter::make('has_adjustment')
            ->label('Show Previous  ➕ Credit / ➖ Debit')
            ->query(fn($query) => $query->where('type', 'adjustment'));
    }

    protected static function getSummarizers(): Summarizer
    {
        return Summarizer::make()->using(fn($query) => new HtmlString(
            $query->select('currency', DB::raw('SUM(diff) as total_diff'))
                ->groupBy('currency')
                ->get()
                ->map(function ($item) {
                    $totalDiff = (float)$item->total_diff;
                    [$status, $color] = match (true) {
                        $totalDiff < 0 => ['Underpaid', '#15803D'],
                        $totalDiff > 0 => ['Overpaid', '#EF4444'],
                        default => ['Settled', '#6B7280'],
                    };
                    return sprintf('<span style="color: %s">%s (%s): %s</span>',
                        $color,
                        $status,
                        strtoupper($item->currency),
                        number_format(abs($totalDiff), 2)
                    );
                })->implode('<br>'))
        );
    }
}
