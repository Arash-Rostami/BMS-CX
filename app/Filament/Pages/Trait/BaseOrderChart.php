<?php

namespace App\Filament\Pages\Trait;


use App\Services\ColorTheme;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

trait BaseOrderChart
{

    protected static mixed $bgColorCache = [];

    public function getAllFilters()
    {
        return [
            'year' => $this->filters['yearlyOrders'] ?? 'all',
            'month' => $this->filters['monthlyOrders'] ?? 'all',
            'category_id' => $this->filters['category_id'] ?? null,
            'order_status' => $this->filters['order_status'] ?? null,
        ];
    }

// DEPRECATED as they belonged to single filter QUERY
//    protected function buildQuery($query, &$bindings)
//    {
//        $filters = $this->getAllFilters();
//        $query .= " AND o.deleted_at IS NULL";
//
//        if ($filters['year'] && $filters['year'] !== 'all') {
//            $query .= " AND YEAR(o.proforma_date) = ?";
//            $bindings[] = $filters['year'];
//        }
//        if ($filters['month'] && $filters['month'] !== 'all') {
//            $query .= " AND MONTH(o.proforma_date) = ?";
//            $bindings[] = $filters['month'];
//        }
//        if ($filters['category_id']) {
//            $query .= " AND o.category_id = ?";
//            $bindings[] = $filters['category_id'];
//        }
//        if ($filters['order_status']) {
//            $query .= " AND o.order_status = ?";
//            $bindings[] = $filters['order_status'];
//        }
//
//        return $query;
//    }

    protected function buildQuery($query, &$bindings)
    {
        $filters = $this->getAllFilters();
        $query .= " AND o.deleted_at IS NULL";

        if ($filters['year'] && $filters['year'] !== 'all') {
            $query .= " AND YEAR(o.proforma_date) = ?";
            $bindings[] = $filters['year'];
        }
        if ($filters['month'] && $filters['month'] !== 'all') {
            if (is_array($filters['month'])) {
                $placeholders = implode(',', array_fill(0, count($filters['month']), '?'));
                $query .= " AND MONTH(o.proforma_date) IN ({$placeholders})";
                $bindings = array_merge($bindings, $filters['month']);
            } else {
                $query .= " AND MONTH(o.proforma_date) = ?";
                $bindings[] = $filters['month'];
            }
        }
        if ($filters['category_id']) {
            if (is_array($filters['category_id'])) {
                $categoryIds = $filters['category_id'];
                $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
                $query .= " AND o.category_id IN ({$placeholders})";
                $bindings = array_merge($bindings, $categoryIds);
            } else {
                $query .= " AND o.category_id = ?";
                $bindings[] = $filters['category_id'];
            }
        }
        if ($filters['order_status']) {
            if (is_array($filters['order_status'])) {
                $orderStatuses = $filters['order_status'];
                $placeholders = implode(',', array_fill(0, count($orderStatuses), '?'));
                $query .= " AND o.order_status IN ({$placeholders})";
                $bindings = array_merge($bindings, $orderStatuses);
            } else {
                $query .= " AND o.order_status = ?";
                $bindings[] = $filters['order_status'];
            }
        }

        return $query;
    }

    protected function processOrders(array $orders, string $nameCol): array
    {
        $totalQuantity = 0;
        foreach ($orders as $order) {
            $totalQuantity += $order->quantity;
        }

        $result = [];
        foreach ($orders as $order) {
            $percentage = $totalQuantity > 0 ? ($order->quantity / $totalQuantity) * 100 : 0;
            $result[] = [
                $nameCol => $order->$nameCol ?? 'Unknown',
                'quantity' => $order->quantity,
                'percentage' => round($percentage, 2),
            ];
        }

        return $result;
    }

    protected function prepareChartData($data, $filterType, $nameCol, $chartTypeOne = 'bar', $chartTypeTwo = 'line')
    {
        $labels = array_column($data, $nameCol);
        $bgColor = $this->getBackgroundColor();
        $datasets = [];

        if ($filterType === 'quantity') {
            $quantities = array_column($data, 'quantity');
            $datasets[] = [
                'label' => 'Quantity',
                'data' => $quantities,
                'type' => $chartTypeOne,
                'backgroundColor' => $bgColor,
                'borderColor' => 'transparent',
                'hoverOffset' => 4,
            ];
        }
        if ($filterType === 'percentage') {
            $percentages = array_column($data, 'percentage');
            $datasets[] = [
                'label' => 'Percentage',
                'data' => $percentages,
                'type' => $chartTypeTwo,
                'borderColor' => '#4e73df',
                'fill' => true,
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets,
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
            ],
        ];
    }

    protected function prepareGapChartData($data, $filterType, $nameCol, $chartTypeOne = 'bar', $chartTypeTwo = 'line')
    {
        $labels = array_column($data, $nameCol);
        $bgColor = $this->getBackgroundColor();
        $datasets = [];


        if ($filterType === 'quantity') {
            $gaps = array_column($data, 'gap_days');
            $averageGap = count($gaps) > 0 ? round(array_sum($gaps) / count($gaps), 2) : 0;
            $label = 'Days 📈 Average: ' . $averageGap;
            $datasets[] = [
                'label' => $label,
                'data' => $gaps,
                'type' => $chartTypeOne,
                'backgroundColor' => $bgColor,
                'borderColor' => 'transparent',
                'hoverOffset' => 4,
            ];
        }
        if ($filterType === 'percentage') {
            $percentages = array_column($data, 'percentage');
            $datasets[] = [
                'label' => 'Percentage',
                'data' => $percentages,
                'type' => $chartTypeTwo,
                'borderColor' => '#4e73df',
                'fill' => true,
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets,
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
            ],
        ];
    }

    protected function processGapOrders(string $query, array $bindings): array
    {
        $query = $this->buildQuery($query, $bindings) . " GROUP BY s.name";
        $orders = DB::select($query, $bindings);

        $processedOrders = [];
        $totalOrders = 0;
        foreach ($orders as $order) {
            $orderCount = $order->order_count;
            $processedOrders[] = [
                'supplier_name' => $order->supplier_name ?? 'Unknown',
                'gap_days' => (int)$order->gap_days,
                'order_count' => $orderCount,
            ];
            $totalOrders += $orderCount;
        }

        foreach ($processedOrders as &$order) {
            $percentage = $totalOrders > 0 ? ($order['order_count'] / $totalOrders) * 100 : 0;
            $order['percentage'] = round($percentage, 2);
            unset($order['order_count']);
        }

        return $processedOrders;
    }

    protected function getBackgroundColor(): mixed
    {
        $key = str_replace('\\', '-', static::class);
        if (isset(self::$bgColorCache[$key])) {
            return self::$bgColorCache[$key];
        }
        $cacheKey = 'widget-bg-color-' . $key;
        self::$bgColorCache[$key] = Cache::remember($cacheKey, 300, function () {
            return ColorTheme::getRandomColorForWidget();
        });
        return self::$bgColorCache[$key];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'datalabels' => [
                    'formatter' => function ($value, $context) {
                        return $value . '%';
                    },
                    'padding' => 6,
                    'anchor' => 'end',
                    'align' => 'center',
                    'offset' => 10,
                ],
            ],
            'animation' => [
                'duration' => 1000,
                'easing' => 'easeInOutQuad',
            ],
        ];
    }
}
