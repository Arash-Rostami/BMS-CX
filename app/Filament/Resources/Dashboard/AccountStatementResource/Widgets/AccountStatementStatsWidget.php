<?php

namespace App\Filament\Resources\Dashboard\AccountStatementResource\Widgets;

use App\Filament\Pages\Trait\AccountStatementStats;
use App\Filament\Resources\Dashboard\AccountStatementResource\Pages\ListAccountStatements;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AccountStatementStatsWidget extends BaseWidget
{
    use AccountStatementStats;
    use InteractsWithPageTable;

//    protected static ?string $pollingInterval = null;
    public array $tableColumnSearches = [];
    protected ?string $heading = 'Statistics';
    protected ?string $description = 'Real-time insight into aggregated ledger statements and net movements.';

    protected function getStats(): array
    {
        $stats = $this->prepareAccountStatementStats($this->getPageTableQuery());

        return [
            Stat::make('Total Disbursed (Credit)', number_format($stats['totalDebit'], 3))
                ->color('danger')
                ->icon('heroicon-m-arrow-trending-up')
                ->extraAttributes([
                    'style' => 'min-width: 220px; background: light-dark(linear-gradient(135deg, #fef2f2 0%, #ffffff 100%), linear-gradient(135deg, #7f1d1d 0%, #450a0a 100%)); border: 1px solid light-dark(#fecaca, #dc2626); transition: all 0.3s ease; border-bottom: 3px solid #dc2626;',
                    'onmouseover' => 'this.style.transform="translateY(-2px)"; this.style.boxShadow=light-dark("0 10px 25px rgba(220,38,38,0.15)", "0 10px 25px rgba(220,38,38,0.25)");',
                    'onmouseout' => 'this.style.transform="translateY(0)"; this.style.boxShadow="";',
                ]),

            Stat::make('Total Received (Debit)', number_format($stats['totalCredit'], 3))
                ->color('success')
                ->icon('heroicon-m-arrow-trending-down')
                ->extraAttributes([
                    'style' => 'min-width: 220px; background: light-dark(linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%), linear-gradient(135deg, #14532d 0%, #052e16 100%)); border: 1px solid light-dark(#bbf7d0, #16a34a); transition: all 0.3s ease; border-bottom: 3px solid #16a34a;',
                    'onmouseover' => 'this.style.transform="translateY(-2px)"; this.style.boxShadow=light-dark("0 10px 25px rgba(22,163,74,0.15)", "0 10px 25px rgba(22,163,74,0.25)");',
                    'onmouseout' => 'this.style.transform="translateY(0)"; this.style.boxShadow="";',
                ]),

            Stat::make('Accrued Interest', number_format($stats['totalAccrued'], 3))
                ->color('warning')
                ->icon('heroicon-m-banknotes')
                ->extraAttributes([
                    'style' => 'min-width: 220px; background: light-dark(linear-gradient(135deg, #fffbeb 0%, #ffffff 100%), linear-gradient(135deg, #78350f 0%, #451a03 100%)); border: 1px solid light-dark(#fde68a, #d97706); transition: all 0.3s ease; border-bottom: 3px solid #d97706;',
                    'onmouseover' => 'this.style.transform="translateY(-2px)"; this.style.boxShadow=light-dark("0 10px 25px rgba(217,119,6,0.15)", "0 10px 25px rgba(217,119,6,0.25)");',
                    'onmouseout' => 'this.style.transform="translateY(0)"; this.style.boxShadow="";',
                ]),

            Stat::make('Net Movement (DR−CR)', number_format($stats['netMovement'], 3))
                ->color($stats['isNetPositive'] ? 'danger' : 'success')
                ->icon($stats['isNetPositive'] ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->extraAttributes([
                    'style' => "min-width: 220px; background: {$stats['netBg']}; border: 1px solid {$stats['netBorder']}; transition: all 0.3s ease; border-bottom: 3px solid {$stats['netBorderBottom']};",
                    'onmouseover' => "this.style.transform='translateY(-2px)'; this.style.boxShadow={$stats['netShadow']};",
                    'onmouseout' => "this.style.transform='translateY(0)'; this.style.boxShadow='';",
                ]),
        ];
    }

    protected function getTablePage(): string
    {
        return ListAccountStatements::class;
    }
}
