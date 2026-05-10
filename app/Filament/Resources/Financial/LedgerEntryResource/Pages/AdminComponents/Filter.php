<?php

namespace App\Filament\Resources\Financial\LedgerEntryResource\Pages\AdminComponents;

use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Tables\Filters\Filter as FilamentFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group as Grouping;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

trait Filter
{
    public static function filterAccount(): SelectFilter
    {
        return SelectFilter::make('account_id')
            ->label('Account (Ledger Account)')
            ->relationship('account', 'name')
            ->preload()
            ->searchable();
    }

    public static function filterIsSettled(): FilamentFilter
    {
        return FilamentFilter::make('is_settled')
            ->label('Settled')
            ->toggle()
            ->query(fn(Builder $query): Builder => $query->where('is_settled', true));
    }

    public static function filterTransactionDate(): FilamentFilter
    {
        return FilamentFilter::make('transaction_date')
            ->label('Date (Transaction Date)')
            ->form([
                Grid::make(2)->schema([
                    DatePicker::make('created_from')
                        ->native(false)
                        ->placeholder('From'),
                    DatePicker::make('created_until')
                        ->native(false)
                        ->placeholder('Until'),
                ]),
            ])
            ->query(fn(Builder $query, array $data): Builder => $query
                ->when($data['created_from'] ?? null, fn($q, $d) => $q->whereDate('transaction_date', '>=', $d))
                ->when($data['created_until'] ?? null, fn($q, $d) => $q->whereDate('transaction_date', '<=', $d))
            )
            ->indicateUsing(function (array $data) {
                $indicators = [];

                if ($data['created_from'] ?? null) {
                    $indicators['created_from'] = 'From ' . Carbon::parse($data['created_from'])->toFormattedDateString();
                }

                if ($data['created_until'] ?? null) {
                    $indicators['created_until'] = 'Until ' . Carbon::parse($data['created_until'])->toFormattedDateString();
                }

                return $indicators;
            });
    }

    public static function filterType(): SelectFilter
    {
        return SelectFilter::make('type')
            ->label('Type')
            ->options([
                'disbursement' => 'Disbursement (Debit)',
                'receipt' => 'Receipt (Credit)',
            ]);
    }

    public static function filterUser(): SelectFilter
    {
        return SelectFilter::make('user_id')
            ->label('Loan Officer')
            ->relationship('user', 'first_name')
            ->getOptionLabelFromRecordUsing(fn(User $record) => $record->full_name)
            ->searchable()
            ->preload()
            ->visible(fn() => auth()->user()?->hasRole('admin') || auth()->user()?->hasRole('developer'));
    }

    public static function groupAccountRecords(): Grouping
    {
        return Grouping::make('account_id')
            ->label('Account (Ledger Account)')
            ->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => optional($record->account)->name ?? 'No Account');
    }

    public static function groupSettledRecords(): Grouping
    {
        return Grouping::make('is_settled')
            ->label('Settled')
            ->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => $record->is_settled ? 'Settled' : 'Pending Settlement');
    }

    public static function groupTransactionDateRecords(): Grouping
    {
        return Grouping::make('transaction_date')
            ->label('Date (Transaction Date)')
            ->collapsible()
            ->getKeyFromRecordUsing(fn(Model $record) => optional($record->transaction_date)->format('Y-m') ?? 'N/A')
            ->getTitleFromRecordUsing(fn(Model $record): string => optional($record->transaction_date)->format('F Y') ?? 'No Date');
    }

    public static function groupTypeRecords(): Grouping
    {
        return Grouping::make('type')
            ->label('Type')
            ->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => ucfirst($record->type ?? 'Unknown'));
    }

    public static function groupUserRecords(): Grouping
    {
        return Grouping::make('user_id')
            ->label('Loan Officer')
            ->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => optional($record->user)->full_name ?? 'Unknown');
    }
}
