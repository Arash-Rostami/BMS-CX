<?php

namespace App\Filament\Resources\Dashboard\LoanSummaryResource\Pages;

use App\Filament\Resources\LedgerEntryResource;
use App\Models\Account;
use App\Models\LoanSummary;
use Filament\Tables\Columns\Summarizers\Average;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\QueryBuilder\Constraints\BooleanConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\DateConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\NumberConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\SelectConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\TextConstraint;
use Illuminate\Support\Str;

class Admin
{
    public static function filterSummary(): QueryBuilder
    {
        return QueryBuilder::make()
            ->constraintPickerColumns(2)
            ->constraintPickerWidth('3xl')
            ->constraints([
                SelectConstraint::make('account_id')
                    ->label('Account')
                    ->options(fn(): array => Account::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all()),

                SelectConstraint::make('entry_type')
                    ->label('Type')
                    ->options([
                        'disbursement' => 'Disbursement',
                        'receipt' => 'Receipt',
                    ]),

                BooleanConstraint::make('is_settled')
                    ->label('Settlement Status'),

                DateConstraint::make('disbursement_date')
                    ->label('Disbursement Date'),

                TextConstraint::make('description')
                    ->label('Description'),

                NumberConstraint::make('total_disbursed_base')
                    ->label('Disbursed Amount'),

                NumberConstraint::make('applied_credit_amount')
                    ->label('Applied Credit'),

                NumberConstraint::make('avg_duration_days')
                    ->label('Avg Duration'),

                NumberConstraint::make('avg_settlement_amount')
                    ->label('Avg Settlement Amount'),

                NumberConstraint::make('counter_interests')
                    ->label('Counter Interest'),

                NumberConstraint::make('duration_days')
                    ->label('Duration Days'),

                NumberConstraint::make('remaining_principal')
                    ->label('Remaining Principal'),
            ])
            ->columnSpanFull();
    }

    public static function showAccruedInterests(): TextColumn
    {
        return TextColumn::make('accrued_interests')
            ->label('Interest')
            ->placeholder(fn($record): string => $record->entry_type === 'receipt' ? 'N/A' : '—')
            ->formatStateUsing(self::formatList(2))
            ->html()
            ->wrap()
            ->summarize(self::sumSummary(2, 'Total Interest'))
            ->color('warning')
            ->tooltip('Gross interest accrued per settlement period.');
    }

    public static function showAppliedCredit(): TextColumn
    {
        return TextColumn::make('applied_credit_amount')
            ->label('Applied Credit')
            ->numeric(decimalPlaces: 2)
            ->placeholder('—')
            ->color('warning')
            ->summarize(self::sumSummary(2, 'Total Credit'))
            ->tooltip('Credit applied at disbursement — reduces the amount owed.');
    }

    public static function showAvgDuration(): TextColumn
    {
        return TextColumn::make('avg_duration_days')
            ->label('Avg Duration')
            ->numeric(decimalPlaces: 2)
            ->placeholder(fn($record): string => $record->entry_type === 'receipt' ? 'N/A' : '—')
            ->badge()
            ->color('info')
            ->summarize(self::averageSummary(2, 'Avg Duration'))
            ->tooltip('Average settlement period duration across all settlements.');
    }

    public static function showAvgSettlement(): TextColumn
    {
        return TextColumn::make('avg_settlement_amount')
            ->label('Avg Amount')
            ->numeric(decimalPlaces: 3)
            ->placeholder(fn($record): string => $record->entry_type === 'receipt' ? 'N/A' : '—')
            ->badge()
            ->color('info')
            ->summarize(self::averageSummary(3, 'Avg Settlement'))
            ->tooltip('Average settlement amount across all settlements.');
    }

    public static function showCounterInterests(): TextColumn
    {
        return TextColumn::make('counter_interests')
            ->label('Counter Interest')
            ->placeholder(fn($record): string => $record->entry_type === 'receipt' ? 'N/A' : '—')
            ->numeric(decimalPlaces: 2)
            ->formatStateUsing(self::formatList(2))
            ->html()
            ->wrap()
            ->summarize(self::sumSummary(2, 'Total Counter'))
            ->color(fn($state): string => ((float)$state) > 0 ? 'danger' : 'gray')
            ->tooltip('Counter-interest applied per settlement — offsets gross accrued interest.');
    }

    public static function showDeductedFromPrincipal(): TextColumn
    {
        return TextColumn::make('deducted_from_principals')
            ->label('Deducted')
            ->placeholder(fn($record): string => $record->entry_type === 'receipt' ? 'N/A' : '—')
            ->formatStateUsing(self::formatList(2))
            ->html()
            ->wrap()
            ->tooltip('Amount deducted from principal in each settlement.');
    }

    public static function showDescription(): TextColumn
    {
        return TextColumn::make('description')
            ->label('Description')
            ->wrap()
            ->searchable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function showDisbursementDate(): TextColumn
    {
        return TextColumn::make('disbursement_date')
            ->label('Disbursement Date')
            ->date('Y-m-d')
            ->sortable()
            ->badge()
            ->color('secondary')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function showDurationDays(): TextColumn
    {
        return TextColumn::make('duration_days')
            ->label('Duration')
            ->placeholder(fn($record): string => $record->entry_type === 'receipt' ? 'N/A' : '—')
            ->formatStateUsing(self::formatList())
            ->html()
            ->wrap()
            ->tooltip('Number of days in each settlement period.');
    }

    public static function showEntryType(): TextColumn
    {
        return TextColumn::make('entry_type')
            ->label('Type')
            ->badge()
            ->icon('heroicon-o-arrow-top-right-on-square')
            ->iconPosition('after')
            ->formatStateUsing(fn($state, LoanSummary $record): string => $record->entryTypeLabel())
            ->color(fn($state, LoanSummary $record): string => $record->entryTypeColor())
            ->tooltip(fn($state, LoanSummary $record): string => $record->entryTypeTooltip())
            ->url(fn($record) => $record->id ? LedgerEntryResource::getUrl('view', ['record' => $record->id]) : null)
            ->openUrlInNewTab();
    }

    public static function showIsSettled(): TextColumn
    {
        return TextColumn::make('is_settled')
            ->label('Settled')
            ->badge()
            ->formatStateUsing(fn($state, LoanSummary $record): string => $record->settledLabel())
            ->color(fn($state, LoanSummary $record): string => $record->settledColor())
            ->tooltip('Whether this ledger entry has been fully settled.');
    }

    public static function showLoanSummary(): TextColumn
    {
        return TextColumn::make('id')
            ->label('Ledger Summary')
            ->html()
            ->alignCenter()
            ->extraAttributes(['style' => 'cursor: help', 'dir' => 'rtl'])
            ->toggleable()
            ->tooltip(fn(LoanSummary $record): string => $record->description ?: '—')
            ->formatStateUsing(function ($state, LoanSummary $record): string {
                $fullDescription = $record->summaryHeadline();
                $truncated = Str::limit($fullDescription, 35, '…');

                $date = $record->disbursement_date?->format('Y-m-d') ?? '—';
                $amount = number_format((float)($record->total_disbursed_base ?? 0), 3);

                $secondLine = sprintf(
                    '<span class="text-xs font-medium">Date:</span> %s  '
                    . '<span class="text-gray-300 dark:text-gray-600 mx-1">|</span>  '
                    . '<span class="text-xs font-medium">Amount:</span> %s',
                    e($date),
                    e($amount)
                );

                return '<div>' . e($truncated) . '</div>'
                    . '<div class="mt-1 text-gray-600 dark:text-gray-300">' . $secondLine . '</div>';
            });
    }

    public static function showRemainingPrincipal(): TextColumn
    {
        return TextColumn::make('remaining_principal')
            ->label('Remaining')
            ->numeric(decimalPlaces: 3)
            ->placeholder('—')
            ->sortable()
            ->summarize(self::sumSummary(3, 'Total Remaining'))
            ->color(fn($record): string => ($record?->remaining_principal > 0) ? 'warning' : 'gray')
            ->tooltip('Outstanding principal balance after last settlement.');
    }

    public static function showSettlementAmounts(): TextColumn
    {
        return TextColumn::make('settlement_amounts')
            ->label('Settlement Amounts')
            ->placeholder(fn($record): string => $record->entry_type === 'receipt' ? 'N/A' : '—')
            ->formatStateUsing(self::formatList())
            ->html()
            ->wrap()
            ->tooltip('Settlement amounts per period.')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function showSettlementDates(): TextColumn
    {
        return TextColumn::make('settlement_dates')
            ->label('Settlement Dates')
            ->placeholder(fn($record): string => $record->entry_type === 'receipt' ? 'N/A' : '—')
            ->formatStateUsing(self::formatList())
            ->html()
            ->wrap()
            ->toggleable(isToggledHiddenByDefault: true)
            ->tooltip('All settlement dates for this ledger entry.');
    }

    public static function showSettlementSummary(): TextColumn
    {
        return TextColumn::make('account_id')
            ->label('Settlement Summary')
            ->placeholder(fn($record): string => $record->entry_type === 'receipt' ? 'N/A' : '—')
            ->html()
            ->formatStateUsing(function ($state, LoanSummary $record): string {
                $pairs = $record->settlementPairs();

                if (blank($pairs)) {
                    return '—';
                }

                $rows = collect($pairs)->map(static function (array $pair): string {
                    return sprintf(
                        '<span class="text-xs font-medium">Date:</span> %s  '
                        . '<span class="text-gray-300 dark:text-gray-600 mx-1">|</span>  '
                        . '<span class="text-xs font-medium">Amount:</span> %s',
                        e($pair['date']),
                        e($pair['amount'])
                    );
                })->all();

                return implode(
                    '<hr style="margin:2px 0;border:0;border-top:1px solid #d1d5db;">',
                    $rows
                );
            })
            ->tooltip('Settlement dates paired with their corresponding amounts.');
    }

    public static function showTotalDisbursed(): TextColumn
    {
        return TextColumn::make('total_disbursed_base')
            ->label('Disbursed Amount')
            ->numeric(decimalPlaces: 3)
            ->sortable()
            ->badge()
            ->color('danger')
            ->summarize(self::averageSummary(3, 'Avg Disbursed'))
            ->toggleable(isToggledHiddenByDefault: true);
    }

    private static function averageSummary(int $decimalPlaces = 2, string $label = 'Average'): array
    {
        return [
            Average::make()
                ->numeric(decimalPlaces: $decimalPlaces)
                ->label($label),
        ];
    }

    private static function formatList(int $decimalPlaces = 0): \Closure
    {
        return static fn($state): ?string => filled($state)
            ? collect(preg_split('/\r\n|\r|\n|\|/', trim((string)$state)))
                ->filter()
                ->map(static fn($line): string => e(
                    is_numeric(trim($line))
                        ? number_format((float)trim($line), $decimalPlaces)
                        : trim($line)
                ))
                ->implode('<hr style="margin:2px 0;border:0;border-top:1px solid #d1d5db;">')
            : null;
    }

    private static function sumSummary(int $decimalPlaces = 2, string $label = 'Total'): array
    {
        return [
            Sum::make()
                ->numeric(decimalPlaces: $decimalPlaces)
                ->label($label),
        ];
    }
}
