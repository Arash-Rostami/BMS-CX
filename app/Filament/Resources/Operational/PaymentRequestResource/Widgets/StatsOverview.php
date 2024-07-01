<?php

namespace App\Filament\Resources\Operational\PaymentRequestResource\Widgets;

use App\Models\PaymentRequest;
use App\Services\IconMaker;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $statuses = ['approved', 'rejected', 'completed', 'cancelled'];
        $statusCounts = PaymentRequest::getStatusCounts();
        $icons = $this->getIcons($statuses);

        return array_map(function ($status) use ($statusCounts, $icons) {
            return Stat::make($status, $statusCounts->get($status, 0))
                ->extraAttributes([
                    'class' => "hidden md:block" . ($status === 'cancelled' ? ' border-2 border-red-500' : ''),
                ])
                ->label(new HtmlString("<img class='inline-block' src='{$icons[$status]}' width='30' height='20' ><span class='grayscale'> " . ucfirst($status) . "</span>"))
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
