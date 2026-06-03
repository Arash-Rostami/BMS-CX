<?php

namespace App\Livewire\ClientPortal;

use App\Models\AccountStatement;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Livewire\Component;
use App\Filament\Resources\Dashboard\AccountStatementResource\Pages\Admin;

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
            ->actions([])
            ->bulkActions([]);
    }

    public function render()
    {
        return view('livewire.client-portal.statement-table');
    }
}
