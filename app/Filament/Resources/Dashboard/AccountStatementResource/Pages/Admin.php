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
            ->label('Transaction Date')
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
            ->color(fn($record): string => ($record?->accrued_interest ?? 0) < 0 ? 'success' : 'warning')
            ->tooltip(fn($record): string => match ($record?->event_type) {
                'disbursement' => 'Counter-interest earned on idle credit before this disbursement (banked for future offset).',
                'settlement' => 'Gross interest accrued in this period — before counter-interest offset.',
                default => '',
            })
            ->summarize([
                Sum::make()->numeric(decimalPlaces: 3)->label('Total Interest'),
            ]);
    }

    public static function showAppliedCredit(): TextColumn
    {
        return TextColumn::make('applied_credit')
            ->label('Applied Credit')
            ->numeric(decimalPlaces: 2)
            ->default(0)
            ->color('warning')
            ->placeholder('—')
            ->toggleable(isToggledHiddenByDefault: true)
            ->tooltip('Credit used at disbursement — reduces the amount owed.');
    }

    public static function showCounterInterest(): TextColumn
    {
        return TextColumn::make('counter_interest')
            ->label('Counter Interest')
            ->numeric(decimalPlaces: 3)
            ->badge()
            ->color('success')
            ->placeholder('—')
            ->toggleable(isToggledHiddenByDefault: true)
            ->tooltip('Counter-interest applied in this settlement — earned during the period a prior credit sat idle.');
    }

    public static function showCredit(): TextColumn
    {
        return TextColumn::make('credit')
            ->label('🟢 In')
            ->numeric(decimalPlaces: 3)
            ->sortable()
            ->badge()
            ->color('success')
            ->tooltip('Money coming into the account. This increases the balance and is counted in the In total.')
            ->summarize([
                Sum::make()->numeric(decimalPlaces: 3)->label('Total In'),
            ]);
    }

    public static function showDebit(): TextColumn
    {
        return TextColumn::make('debit')
            ->label('🔴 Out')
            ->numeric(decimalPlaces: 3)
            ->sortable()
            ->badge()
            ->color('danger')
            ->tooltip('Money going out of the account. This reduces the balance and is counted in the Out total.')
            ->summarize([
                Sum::make()->numeric(decimalPlaces: 3)->label('Total Out'),
            ]);
    }

    public static function showDescription(): TextColumn
    {
        return TextColumn::make('description')
            ->label('Description')
            ->wrap()
            ->searchable(isIndividual: true)
            ->tooltip('Narration or note explaining what this transaction is for.');
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
            ->tooltip(fn(?string $state): string => match ($state) {
                'disbursement' => 'Funds released from the account or loan side. Usually represents an outgoing principal movement.',
                'receipt' => 'Incoming payment or collection received into the account.',
                'settlement' => 'Closing or reconciling entry that nets debit, credit, and interest together.',
                default => 'Transaction category used to classify how this row affects the statement.',
            })
            ->searchable(query: fn(Builder $q, string $search): Builder => self::applyEventTypeSearch($q, $search));
    }

    public static function showNetMovement(): TextColumn
    {
        $net = fn($record): float => $record->event_type === 'settlement'
            ? (float)$record->debit - (float)$record->credit - (float)($record->counter_interest ?? 0)
            : (float)$record->debit - (float)$record->credit + (float)($record->accrued_interest ?? 0);

        return TextColumn::make('net')
            ->label('Net (DR−CR)')
            ->getStateUsing($net)
            ->numeric(decimalPlaces: 3)
            ->badge()
            ->color(fn($record): string => $net($record) >= 0 ? 'danger' : 'success')
            ->tooltip(fn($record): string => $record?->event_type === 'settlement'
                ? 'Settlement net = debit − credit − counter interest. This shows the final closing movement for the row.'
                : 'Net movement = debit − credit + accrued interest. Positive means outflow; negative means inflow.');
    }

    public static function showRunningBalance(): TextColumn
    {
        return TextColumn::make('running_balance')
            ->label('Running Balance')
            ->numeric(decimalPlaces: 3)
            ->sortable()
            ->badge()
            ->color(fn($record): string => ($record?->running_balance > 0) ? 'info' : 'success')
            ->tooltip('Cumulative balance after applying this row. It shows the account position at this exact point in time.');
    }

    public static function showTransactionDate(): TextColumn
    {
        return TextColumn::make('transaction_date')
            ->label('Transaction Date')
            ->date()
            ->sortable()
            ->badge()
            ->color('secondary')
            ->icon('heroicon-s-calendar-days')
            ->tooltip('The business date when this transaction was posted and included in the statement.');
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
