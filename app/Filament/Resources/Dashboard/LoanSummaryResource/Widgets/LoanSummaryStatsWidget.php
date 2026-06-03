<?php

namespace App\Filament\Resources\Dashboard\LoanSummaryResource\Widgets;


use App\Filament\Pages\Trait\LoanSummaryStats;
use App\Filament\Resources\Dashboard\LoanSummaryResource\Pages\ListLoanSummaries;
use App\Models\LoanSummary;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LoanSummaryStatsWidget extends BaseWidget
{
    use LoanSummaryStats;
    use InteractsWithPageTable;

    public array $tableColumnSearches = [];
    public $accountId = null;
    protected ?string $heading = 'Loan Summary Statistics';
    protected ?string $description = 'Real-time overview of disbursements, settlements, and outstanding principal.';

    protected function getStats(): array
    {
        $stats = $this->prepareLoanSummaryStats(
            !empty($this->accountId)
                ? LoanSummary::query()->where('account_id', $this->accountId)
                : $this->getPageTableQuery()
        );

        return [
            Stat::make('Total Disbursed', number_format($stats['totalDisbursed'], 3))
                ->description(number_format($stats['totalDisbursements']) . ' disbursement(s)')
                ->color('danger')
                ->icon('heroicon-m-banknotes')
                ->extraAttributes([
                    'style' => 'min-width: 220px; background: light-dark(linear-gradient(135deg, #fef2f2 0%, #ffffff 100%), linear-gradient(135deg, #7f1d1d 0%, #450a0a 100%)); border: 1px solid light-dark(#fecaca, #dc2626); transition: all 0.3s ease; border-bottom: 3px solid #dc2626;',
                    'onmouseover' => 'this.style.transform="translateY(-2px)"; this.style.boxShadow="0 10px 25px rgba(220,38,38,0.15)";',
                    'onmouseout' => 'this.style.transform="translateY(0)"; this.style.boxShadow="";',
                ]),

            Stat::make('Applied Credit', number_format($stats['appliedCredit'], 3))
                ->description('Credit offset at disbursement')
                ->color('warning')
                ->icon('heroicon-m-receipt-percent')
                ->extraAttributes([
                    'style' => 'min-width: 220px; background: light-dark(linear-gradient(135deg, #fffbeb 0%, #ffffff 100%), linear-gradient(135deg, #78350f 0%, #451a03 100%)); border: 1px solid light-dark(#fde68a, #d97706); transition: all 0.3s ease; border-bottom: 3px solid #d97706;',
                    'onmouseover' => 'this.style.transform="translateY(-2px)"; this.style.boxShadow="0 10px 25px rgba(217,119,6,0.15)";',
                    'onmouseout' => 'this.style.transform="translateY(0)"; this.style.boxShadow="";',
                ]),

            Stat::make('Outstanding Principal', number_format($stats['remainingPrincipal'], 3))
                ->description($stats['totalUnsettled'] . ' unsettled loan(s)')
                ->color($stats['outstandingColor'])
                ->icon($stats['outstandingIcon'])
                ->extraAttributes([
                    'style' => "min-width: 220px; background: {$stats['outstandingBg']}; border: 1px solid {$stats['outstandingBorder']}; transition: all 0.3s ease; border-bottom: 3px solid {$stats['outstandingBorderBottom']};",
                    'onmouseover' => 'this.style.transform="translateY(-2px)"; this.style.boxShadow="0 10px 25px rgba(217,119,6,0.15)";',
                    'onmouseout' => 'this.style.transform="translateY(0)"; this.style.boxShadow="";',
                ]),

            Stat::make('Settlement Rate', $stats['settlementRate'] . '%')
                ->description($stats['totalSettled'] . ' of ' . $stats['totalDisbursements'] . ' settled')
                ->color($stats['settlementRate'] >= 75 ? 'success' : ($stats['settlementRate'] >= 40 ? 'warning' : 'danger'))
                ->icon('heroicon-m-chart-bar')
                ->extraAttributes([
                    'style' => 'min-width: 220px; background: light-dark(linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%), linear-gradient(135deg, #14532d 0%, #052e16 100%)); border: 1px solid light-dark(#bbf7d0, #16a34a); transition: all 0.3s ease; border-bottom: 3px solid #16a34a;',
                    'onmouseover' => 'this.style.transform="translateY(-2px)"; this.style.boxShadow="0 10px 25px rgba(22,163,74,0.15)";',
                    'onmouseout' => 'this.style.transform="translateY(0)"; this.style.boxShadow="";',
                ]),

            Stat::make('Avg Settlement Amount', number_format($stats['avgSettlement'], 3))
                ->description('Avg ' . number_format($stats['avgDuration'], 0) . ' days per period')
                ->color('info')
                ->icon('heroicon-m-calculator')
                ->extraAttributes([
                    'style' => 'min-width: 220px; background: light-dark(linear-gradient(135deg, #eff6ff 0%, #ffffff 100%), linear-gradient(135deg, #1e3a5f 0%, #0c1a2e 100%)); border: 1px solid light-dark(#bfdbfe, #3b82f6); transition: all 0.3s ease; border-bottom: 3px solid #3b82f6;',
                    'onmouseover' => 'this.style.transform="translateY(-2px)"; this.style.boxShadow="0 10px 25px rgba(59,130,246,0.15)";',
                    'onmouseout' => 'this.style.transform="translateY(0)"; this.style.boxShadow="";',
                ]),
        ];
    }

    protected function getTablePage(): string
    {
        return ListLoanSummaries::class;
    }
}
