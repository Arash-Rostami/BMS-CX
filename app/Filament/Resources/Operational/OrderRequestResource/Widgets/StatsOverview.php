<?php

namespace App\Filament\Resources\Operational\OrderRequestResource\Widgets;

use App\Models\OrderRequest;
use App\Services\IconMaker;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\HtmlString;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $statuses = ['review', 'approved', 'rejected', 'completed'];
        $statusCounts = OrderRequest::getStatusCounts();
        $icons = $this->getIcons($statuses);

        return array_map(function ($status) use ($statusCounts, $icons) {
            $statusLabel = ucfirst($status) === 'Review' ? 'Under Review' : ucfirst($status);
            return Stat::make($status, $statusCounts->get($status, 0))
                ->extraAttributes([
                    'class' => "hidden md:block" . ($status === 'review' ? ' border-2 border-red-500' : ''),
                ])
                ->label(new HtmlString("<img class='inline-block' src='{$icons[$status]}' width='30' height='20' ><span class='grayscale'> {$statusLabel}</span>"))
                ->color('secondary');
        }, $statuses);
    }

    private function getIcons(array $statuses): array
    {
        $icons = [];
        foreach ($statuses as $status) {
            $icons[$status] = IconMaker::getIcon($status);
        }
        return $icons;
    }
}
