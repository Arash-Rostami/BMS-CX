<?php

namespace App\Livewire\ClientPortal;

use App\Models\LoanSummary;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Livewire\Component;
use App\Filament\Resources\Dashboard\LoanSummaryResource\Pages\Admin;
use App\Filament\Resources\Dashboard\LoanSummaryResource\Exports\LoanSummaryExport;

class LoanTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public $accountId;

    public function table(Table $table): Table
    {
        return $table
            ->query(LoanSummary::query()->where('account_id', $this->accountId))
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
            ->filters([Admin::filterSummary()], layout: FiltersLayout::Modal)
            ->filtersFormColumns(6)
            ->groups([
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

    public function render()
    {
        return view('livewire.client-portal.loan-table');
    }
}
