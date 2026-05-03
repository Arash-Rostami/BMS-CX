<?php

namespace App\Filament\Resources\Dashboard\ContractOverviewResource\Widgets;

use App\Filament\Pages\Trait\ContractStats;
use App\Filament\Resources\Dashboard\ContractOverviewResource\Pages\ListContractOverviews;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ContractStatsWidget extends BaseWidget
{
    use ContractStats;
    use InteractsWithPageTable;

    protected static ?string $pollingInterval = null;

    protected ?string $heading = 'Statistics';

    protected ?string $description = 'Real-time insights into contract operations, shipment progress, and target achievement metrics.';

    protected function getStats(): array
    {
        $builder = $this->getPageTableQuery();
        $data = $this->prepareContractStats($builder);

        $m = $data['metrics'];
        $r = $data['rates'];
        $period = $data['period'];
        $targetTotal = $data['target_total'];

        $stats = [
            Stat::make('Total PI Qty', number_format($m['total_pi_quantity'], 2) . ' mt')
                ->description($m['contract_count'] . ' contracts')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary')
                ->extraAttributes([
                    'style' => 'width:280px;min-width:180px;max-width:200px;background:light-dark(linear-gradient(135deg, #fef2f2 0%, #ffffff 100%), linear-gradient(135deg, #7f1d1d 0%, #450a0a 100%));border:1px solid #bfdbfe;transition:all .3s ease;border-bottom:3px solid #3b82f6;',
                    'onmouseover' => 'this.style.transform="translateY(-2px)";this.style.boxShadow="0 10px 25px rgba(59,130,246,0.15)";',
                    'onmouseout' => 'this.style.transform="translateY(0)";this.style.boxShadow="";',
                ]),

            Stat::make('Rls/Shpd Qnty', number_format($m['released_shipped_q'], 2) . ' mt')
                ->description($m['released_shipped_count'] . ' orders')
                ->descriptionIcon('heroicon-m-truck')
                ->color('success')
                ->extraAttributes([
                    'style' => 'width:280px;min-width:180px;max-width:200px;background:light-dark(linear-gradient(135deg, #fef2f2 0%, #ffffff 100%), linear-gradient(135deg, #7f1d1d 0%, #450a0a 100%));border:1px solid #bbf7d0;transition:all .3s ease;border-bottom:3px solid #10b981;',
                    'onmouseover' => 'this.style.transform="translateY(-2px)";this.style.boxShadow="0 10px 25px rgba(16,185,129,0.15)";',
                    'onmouseout' => 'this.style.transform="translateY(0)";this.style.boxShadow="";',
                ]),

            Stat::make('Delv/Trns/Cstms Qty', number_format($m['delivered_in_transit_custom_q'], 2) . ' mt')
                ->description(
                    $m['delivered_in_transit_custom_count'] . ' orders' . ($m['pending_count'] > 0 ? ' ± ' . $m['pending_count'] . ' pending orders: ' . number_format($m['pending_q'], 2) . ' mt' : '')
                )
                ->descriptionIcon('heroicon-m-arrows-right-left')
                ->color('warning')
                ->extraAttributes([
                    'style' => 'width:280px;min-width:180px;max-width:200px;background:light-dark(linear-gradient(135deg, #fef2f2 0%, #ffffff 100%), linear-gradient(135deg, #7f1d1d 0%, #450a0a 100%));border:1px solid #fed7aa;transition:all .3s ease;border-bottom:3px solid #f59e0b;',
                    'onmouseover' => 'this.style.transform="translateY(-2px)";this.style.boxShadow="0 10px 25px rgba(245,158,11,0.15)";',
                    'onmouseout' => 'this.style.transform="translateY(0)";this.style.boxShadow="";',
                ]),

            Stat::make('Fulfillment Rate', number_format($r['contract_fulfillment_rate'], 1) . '%')
                ->description('Released/Shipped vs Total PI')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color($r['contract_fulfillment_rate'] > 70 ? 'success' : 'warning')
                ->extraAttributes([
                    'style' => 'width:280px;min-width:180px;max-width:200px;background:light-dark(linear-gradient(135deg, #fef2f2 0%, #ffffff 100%), linear-gradient(135deg, #7f1d1d 0%, #450a0a 100%));border:1px solid #e9d5ff;transition:all .3s ease;border-bottom:3px solid #8b5cf6;',
                    'onmouseover' => 'this.style.transform="translateY(-2px)";this.style.boxShadow="0 10px 25px rgba(139,92,246,0.15)";',
                    'onmouseout' => 'this.style.transform="translateY(0)";this.style.boxShadow="";',
                ])
        ];

        if (!empty($period['months_by_year'])) {
            $stats[] = Stat::make('Targets (' . implode(',', $period['years']) . ')', number_format($targetTotal, 2) . ' mt')
                ->description(isset($period['from'], $period['to']) ? ($period['from'] . ' to ' . $period['to']) : 'selected period')
                ->descriptionIcon('heroicon-o-flag')
                ->extraAttributes([
                    'style' => 'width:280px;min-width:180px;max-width:200px;background:light-dark(linear-gradient(135deg, #fef2f2 0%, #ffffff 100%), linear-gradient(135deg, #7f1d1d 0%, #450a0a 100%));border:1px solid #99f6e4;transition:all .3s ease;border-bottom:3px solid #14b8a6;',
                    'onmouseover' => 'this.style.transform="translateY(-2px)";this.style.boxShadow="0 10px 25px rgba(20,184,166,0.15)";',
                    'onmouseout' => 'this.style.transform="translateY(0)";this.style.boxShadow="";',
                ])
                ->color($targetTotal > 0 ? 'primary' : 'secondary');

            $stats[] = Stat::make('Purchase Target', number_format($r['pi_vs_target_rate'], 1) . '%')
                ->description('PI Qty vs Targets')
                ->descriptionIcon('heroicon-o-chart-pie')
                ->extraAttributes([
                    'style' => 'width: 280px; min-width: 180px; max-width: 200px; background: light-dark(linear-gradient(135deg, #fef7ee 0%, #ffffff 100%), linear-gradient(135deg, #9a3412 0%, #431407 100%)); border: 1px solid light-dark(#fed7aa, #ea580c); transition: all 0.3s ease; border-bottom: 3px solid #ea580c;',
                    'onmouseover' => 'this.style.transform = "translateY(-2px)"; this.style.boxShadow = light-dark("0 10px 25px rgba(234,88,12,0.15)", "0 10px 25px rgba(234,88,12,0.25)");',
                    'onmouseout' => 'this.style.transform = "translateY(0)"; this.style.boxShadow = "";'
                ])
                ->color('orange');

            $stats[] = Stat::make('Sales Target', number_format($r['target_fulfillment_rate'], 1) . '%')
                ->description('Rls/Shpd Qnty vs Targets')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->extraAttributes([
                    'style' => 'width: 280px; min-width: 180px; max-width: 200px; background: light-dark(linear-gradient(135deg, #fef2f2 0%, #ffffff 100%), linear-gradient(135deg, #7f1d1d 0%, #450a0a 100%)); border: 1px solid light-dark(#fecaca, #dc2626); transition: all 0.3s ease; border-bottom: 3px solid #dc2626;',
                    'onmouseover' => 'this.style.transform = "translateY(-2px)"; this.style.boxShadow = light-dark("0 10px 25px rgba(220,38,38,0.15)", "0 10px 25px rgba(220,38,38,0.25)");',
                    'onmouseout' => 'this.style.transform = "translateY(0)"; this.style.boxShadow = "";'
                ])
                ->color('red');
        }

        return $stats;
    }

    protected function getTablePage(): string
    {
        return ListContractOverviews::class;
    }
}
