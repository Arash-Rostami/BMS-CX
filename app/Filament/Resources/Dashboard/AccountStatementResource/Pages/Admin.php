<?php

namespace App\Filament\Resources\Dashboard\AccountStatementResource\Pages;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter as FilamentFilter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Admin
{
    public static function filterAccount(): SelectFilter
    {
        return SelectFilter::make('account_id')
            ->label('Account (Ledger Account)')
            ->relationship('account', 'name')
            ->searchable()
            ->preload();
    }

    public static function filterLedger(): SelectFilter
    {
        return SelectFilter::make('original_id')
            ->label('Ledger (Loan Ledger)')
            ->relationship('ledgerEntry', 'description')
            ->searchable()
            ->preload();
    }

    public static function filterTransactionDate(): FilamentFilter
    {
        return FilamentFilter::make('transaction_date')
            ->label('Date (Transaction Date)')
            ->form([
                Grid::make(2)->schema([
                    DatePicker::make('created_from')->native(false)->placeholder('From'),
                    DatePicker::make('created_until')->native(false)->placeholder('Until'),
                ])->columnSpanFull(),
            ])
            ->query(function (Builder $query, array $data): Builder {
                return $query
                    ->when($data['created_from'] ?? null, fn($q, $d) => $q->whereDate('transaction_date', '>=', $d))
                    ->when($data['created_until'] ?? null, fn($q, $d) => $q->whereDate('transaction_date', '<=', $d));
            })
            ->indicateUsing(function (array $data): array {
                return array_filter([
                    'created_from' => !empty($data['created_from'] ?? null)
                        ? 'From ' . Carbon::parse($data['created_from'])->toFormattedDateString()
                        : null,
                    'created_until' => !empty($data['created_until'] ?? null)
                        ? 'Until ' . Carbon::parse($data['created_until'])->toFormattedDateString()
                        : null,
                ]);
            })
            ->columnSpanFull();
    }

    public static function showAccruedInterest(): TextColumn
    {
        return TextColumn::make('accrued_interest')
            ->label('Interest (Accrued)')
            ->numeric(decimalPlaces: 3)
            ->sortable()
            ->badge()
            ->color('warning')
            ->summarize([
                Sum::make()->numeric(decimalPlaces: 3)->label('Total Interest'),
            ]);
    }

    public static function showAppliedCredit(): TextColumn
    {
        return TextColumn::make('applied_credit')
            ->label('Adjustment (Applied Credit)')
            ->numeric(decimalPlaces: 2)
            ->default(0)
            ->color('warning')
            ->placeholder('—')
            ->toggleable()
            ->tooltip('Credit used at disbursement — reduces the amount owed.');
    }

    public static function showCredit(): TextColumn
    {
        return TextColumn::make('credit')
            ->label('In (Credit Received)')
            ->numeric(decimalPlaces: 3)
            ->sortable()
            ->badge()
            ->color('success')
            ->summarize([
                Sum::make()->numeric(decimalPlaces: 3)->label('Total In'),
            ]);
    }

    public static function showDebit(): TextColumn
    {
        return TextColumn::make('debit')
            ->label('Out (Debit Sent)')
            ->numeric(decimalPlaces: 3)
            ->sortable()
            ->badge()
            ->color('danger')
            ->summarize([
                Sum::make()->numeric(decimalPlaces: 3)->label('Total Out'),
            ]);
    }

    public static function showDescription(): TextColumn
    {
        return TextColumn::make('description')
            ->label('Description')
            ->wrap()
            ->searchable(isIndividual: true);
    }

    public static function showEventType(): TextColumn
    {
        return TextColumn::make('event_type')
            ->label('Type')
            ->badge()
            ->color(fn(string $state): string => match ($state) {
                'disbursement' => 'danger',
                'receipt' => 'success',
                default => 'secondary',
            })
            ->searchable(query: fn(Builder $q, string $search): Builder => self::applyEventTypeSearch($q, $search));
    }

    public static function showNetMovement(): TextColumn
    {
        return TextColumn::make('net')
            ->label('Net (DR−CR)')
            ->getStateUsing(fn($record) => $record->debit - $record->credit + $record->accrued_interest)
            ->numeric(decimalPlaces: 3)
            ->badge()
            ->color(fn($record): string => ($record->debit - $record->credit + $record->accrued_interest) >= 0 ? 'danger' : 'success');
    }

    public static function showRunningBalance(): TextColumn
    {
        return TextColumn::make('running_balance')
            ->label('Balance (Running Balance)')
            ->numeric(decimalPlaces: 3)
            ->sortable()
            ->badge()
            ->color(fn($record): string => ($record?->running_balance > 0) ? 'info' : 'success');
    }

    public static function showTransactionDate(): TextColumn
    {
        return TextColumn::make('transaction_date')
            ->label('Date (Transaction Date)')
            ->date()
            ->sortable()
            ->badge()
            ->color('secondary')
            ->icon('heroicon-s-calendar-days');
    }

    private static function applyEventTypeSearch(Builder $query, string $search): Builder
    {
        $normalized = strtolower(trim($search));
        $matches = [];

        foreach (
            [
                'disbursement' => ['disb', 'loan', 'payout', 'out', 'funding', 'transfer out', 'withdrawal', 'expense', 'payment sent', 'release', 'advance', 'issue', 'outgoing'],
                'receipt' => ['receipt', 'pay', 'collection', 'in', 'deposit', 'remit', 'payment received', 'incoming', 'credit', 'cash in', 'settlement in', 'receive', 'inflow'],
                'settlement' => ['settle', 'clear', 'reconcile', 'balance', 'closure', 'adjust', 'net', 'final', 'resolution', 'adjustment'],
            ] as $type => $keywords) {
            if (str_contains($type, $normalized) || Str::contains($normalized, $keywords)) {
                $matches[] = $type;
                continue;
            }

            foreach ($keywords as $keyword) {
                if (str_contains($keyword, $normalized)) {
                    $matches[] = $type;
                    break;
                }
            }
        }

        return $matches
            ? $query->orWhereIn('event_type', $matches)
            : $query->orWhere('event_type', 'like', "%{$search}%");
    }
}
