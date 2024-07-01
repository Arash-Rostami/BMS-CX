<?php

namespace App\Filament\Resources\Operational\OrderResource\Widgets;

use App\Models\Order;
use App\Services\IconMaker;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\HtmlString;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $statuses = [3 => 'In Transit', 4 => 'Customs', 5 => 'Delivered', 6 => 'Shipped'];
        $statusCounts = Order::getStatusCounts();
        $icons = $this->getIcons($statuses);

        return array_map(function ($statusId, $statusLabel) use ($statusCounts, $icons) {
            return Stat::make($statusLabel, $statusCounts->get($statusId, 0))
                ->extraAttributes([
                    'class' => "hidden md:block" . ($statusId === 3 ? ' border-2 border-red-500' : ''),
                ])
                ->label(new HtmlString("<img class='inline-block' src='{$icons[$statusId]}' width='30' height='" . ($statusId === 6 ? '15' : '25') . "' > <span class='grayscale relative top-" . ($statusId === 4 || $statusId === 5 ? '2' : '1') . "'>{$statusLabel}</span>"))
                ->color('secondary');
        }, array_keys($statuses), $statuses);
    }

    private function getIcons(array $statuses): array
    {
        $icons = [];
        foreach ($statuses as $statusId => $statusLabel) {
            $iconName = strtolower(str_replace(' ', '_', $statusLabel));  //To match the name with in_transit in Icon class
            $icons[$statusId] = IconMaker::getIcon($iconName);
        }
        return $icons;
    }
}
