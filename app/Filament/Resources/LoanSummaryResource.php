<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Dashboard\LoanSummaryResource\Pages\ListLoanSummaries;
use App\Filament\Resources\Dashboard\LoanSummaryResource\Exports\LoanSummaryExport;
use App\Filament\Resources\Dashboard\LoanSummaryResource\Pages\Admin;
use App\Models\LoanSummary;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;


class LoanSummaryResource extends Resource
{
    protected static ?string $model = LoanSummary::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Loan Summary';
    protected static ?int $navigationSort = 3;

    protected static bool $isGloballySearchable = false;
    protected static bool $canCreate = false;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLoanSummaries::route('/'),
        ];
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Admin::showEntryType(),
                Admin::showLoanSummary(),
                Admin::showSettlementSummary(),
                Admin::showDescription(),
                Admin::showDisbursementDate(),
                Admin::showTotalDisbursed(),
                Admin::showSettlementDates(),
                Admin::showSettlementAmounts(),
                Admin::showDurationDays(),
                Admin::showAccruedInterests(),
                Admin::showDeductedFromPrincipal(),
                Admin::showCounterInterests(),
                Admin::showAppliedCredit(),
                Admin::showAvgDuration(),
                Admin::showAvgSettlement(),
                Admin::showIsSettled(),
                Admin::showRemainingPrincipal(),
            ])
            ->defaultSort('disbursement_date', 'asc')
            ->filtersFormWidth(MaxWidth::ThreeExtraLarge)
            ->filtersFormColumns(2)
            ->filters([Admin::filterSummary()], layout: FiltersLayout::Modal)
            ->filtersFormColumns(6)
            ->groups([
                Group::make('account.name')->label('Account'),
                Group::make('entry_type')->label('Type'),
                Group::make('disbursement_date')
                    ->label('Month')
                    ->getTitleFromRecordUsing(fn($record) => Carbon::parse($record->disbursement_date)->format('F Y'))
                    ->orderQueryUsing(fn($query, $direction) => $query->orderBy('disbursement_date', $direction)),
                Group::make('is_settled')
                    ->label('Settlement')
                    ->getTitleFromRecordUsing(fn($record) => $record->is_settled ? 'Settled' : 'Unsettled'),
            ])
            ->defaultGroup(null)
            ->bulkActions([
                BulkAction::make('export')
                    ->label('Export to Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn(Collection $records) => Excel::download(
                        new LoanSummaryExport($records), 'loan-summary-' . date('Y-m-d') . '.xlsx'))
            ])
            ->actions([]);
    }
}
