<?php

namespace App\Livewire\ClientPortal;

use App\Filament\Resources\Dashboard\AccountStatementResource\Exports\AccountStatementExport;
use App\Filament\Resources\Dashboard\AccountStatementResource\Pages\Admin;
use App\Models\AccountStatement;
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
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class StatementTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public $accountId;

    public function table(Table $table): Table
    {
        return $table
            ->query(AccountStatement::query()->where('account_id', $this->accountId))
            ->columns([
                Admin::showTransactionDate(),
                Admin::showDescription(),
                Admin::showDebit(),
                Admin::showCredit(),
                Admin::showAppliedCredit(),
                Admin::showAccruedInterest(),
                Admin::showCounterInterest(),
                Admin::showNetMovement(),
                Admin::showEventType(),
                Admin::showRunningBalance(),
            ])
            ->defaultSort('transaction_date', 'asc')
            ->filtersFormWidth(MaxWidth::TwoExtraLarge)
            ->filtersFormColumns(2)
            ->filters([
                Admin::filterLedger(),
                Admin::filterTransactionDate()
            ], layout: FiltersLayout::Modal)
            ->groups([
                Group::make('transaction_date')
                    ->label('Month')
                    ->getTitleFromRecordUsing(fn($record) => Carbon::parse($record->transaction_date)->format('F Y'))
                    ->orderQueryUsing(fn($query, $direction) => $query->orderBy('transaction_date', $direction)),
            ])
            ->defaultGroup(null)
            ->actions([])
            ->bulkActions([
                BulkAction::make('export')
                    ->label('Export to Excel')
                    ->extraAttributes(['style' => 'background-color: rgba(16, 185, 129); color: white; border: 1px solid rgba(16, 185, 129, 0.6); font-weight: 600; padding: 0.5rem 1rem; border-radius: 0.5rem;'])
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn(Collection $records) => Excel::download(
                        new AccountStatementExport($records), 'account-statement-' . date('Y-m-d') . '.xlsx'))
            ]);
    }

    public function render()
    {
        return view('livewire.client-portal.statement-table');
    }
}
