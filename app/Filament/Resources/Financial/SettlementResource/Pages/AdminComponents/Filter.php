<?php

namespace App\Filament\Resources\Financial\SettlementResource\Pages\AdminComponents;

use App\Models\Account;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\Filter as FilamentFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group as Grouping;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait Filter
{
    public static function filterAccount(): SelectFilter
    {
        return SelectFilter::make('account_id')
            ->label('Account (Ledger Account)')
            ->options(Account::pluck('name', 'id'))
            ->searchable()
            ->query(fn (Builder $query, array $data): Builder => $query
                ->when($data['value'] ?? null, fn ($q, $v) => $q->whereHas('ledgerEntry', fn ($q) => $q->where('account_id', $v)))
            );
    }

    public static function filterTransactionDate(): FilamentFilter
    {
        return FilamentFilter::make('transaction_date')
            ->label('Date (Transaction Date)')
            ->form([
                DatePicker::make('created_from')->native(false)->placeholder('From'),
                DatePicker::make('created_until')->native(false)->placeholder('Until'),
            ])
            ->query(fn (Builder $query, array $data): Builder => $query
                ->when($data['created_from'] ?? null, fn ($q, $d) => $q->whereDate('transaction_date', '>=', $d))
                ->when($data['created_until'] ?? null, fn ($q, $d) => $q->whereDate('transaction_date', '<=', $d))
            )
            ->indicateUsing(function (array $data): array {
                $indicators = [];

                if ($data['created_from'] ?? null) {
                    $indicators['created_from'] = 'From ' . $data['created_from'];
                }

                if ($data['created_until'] ?? null) {
                    $indicators['created_until'] = 'Until ' . $data['created_until'];
                }

                return $indicators;
            });
    }

    public static function groupAccountRecords(): Grouping
    {
        return Grouping::make('ledgerEntry.account_id')
            ->label('Account (Ledger Account)')
            ->collapsible()
            ->getTitleFromRecordUsing(fn (Model $record): string => optional($record->ledgerEntry?->account)->name ?? 'No Account');
    }

    public static function groupCurrencyType(): Grouping
    {
        return Grouping::make('currency_type')
            ->label('Currency')
            ->collapsible()
            ->getTitleFromRecordUsing(fn (Model $record): string => $record->currency_type ?? 'Unknown');
    }

    public static function groupTransactionDateRecords(): Grouping
    {
        return Grouping::make('transaction_date')
            ->label('Date (Transaction Date)')
            ->collapsible()
            ->getKeyFromRecordUsing(fn (Model $record) => optional($record->transaction_date)->format('Y-m') ?? 'N/A')
            ->getTitleFromRecordUsing(fn (Model $record): string => optional($record->transaction_date)->format('F Y') ?? 'No Date');
    }
}
