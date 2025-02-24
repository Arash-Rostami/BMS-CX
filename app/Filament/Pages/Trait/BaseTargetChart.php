<?php

namespace App\Filament\Pages\Trait;

use App\Services\ColorTheme;
use Illuminate\Support\Facades\Cache;

trait BaseTargetChart
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
//    protected function buildQuery(string $query, array &$bindings, $order = false)
//    {
//        $filters = $this->getAllFilters();
//
//        $query .= " WHERE t.year = ?";
//        $bindings[] = $filters['year'];
//
//
//        if ($filters['category_id']) {
//            $query .= " AND t.category_id = ?";
//            $bindings[] = $filters['category_id'];
//        }
//
//        if ($order && $filters['order_status']) {
//            $query .= " AND o.order_status = ?";
//            $bindings[] = $filters['order_status'];
//        } elseif ($filters['order_status']) {
//            $query .= " AND pi.status = ?";
//            $bindings[] = ($filters['order_status'] == 'processing')
//                ? 'approved'
//                : ($filters['order_status'] == 'cancelled' ? 'rejected' : 'fulfilled');
//        }
//        return $query;
//    }
//    protected function addMonthFilter(string &$query, array &$bindings, string $dateColumn): void
//    {
//        $filters = $this->getAllFilters();
//        if (!empty($filters['month']) && $filters['month'] !== 'all') {
//            $monthNumber = (int)$filters['month'];
//            $monthName = $this->generateMonthName($monthNumber);
//
//            $query .= " AND MONTH($dateColumn) = ?";
//            $bindings[] = $monthNumber;
//
//            $query .= " AND JSON_UNQUOTE(JSON_EXTRACT(t.month, '$.\"{$monthName}\"')) IS NOT NULL";
//        }
//    }
//    protected function processChartData($orders, $monthFilter)
//    {
//        $processedData = [];
//
//        foreach ($orders as $order) {
//            $targetQuantity = $order->modified_target_quantity ?? $order->target_quantity;
//
//            if ($monthFilter !== 'all') {
//                $monthName = $this->generateMonthName((int)$monthFilter);
//                $monthData = json_decode($order->month, true);
//                $targetQuantity = $monthData[$monthName] ?? 0;
//            }
//
//            $gapQuantity = ($order->realized_quantity ?? 0) - $targetQuantity;
//
//            $processedData[] = [
//                'category_name' => $order->category_name,
//                'realized_quantity' => $order->realized_quantity,
//                'target_quantity' => $targetQuantity,
//                'gap_quantity' => $gapQuantity,
//            ];
//        }
//
//        return $processedData;
//    }

    protected function buildQuery(string $query, array &$bindings, $order = false)
    {
        $filters = $this->getAllFilters();

        $query .= " WHERE t.year = ?";
        $bindings[] = $filters['year'];


        if ($filters['category_id']) {
            if (is_array($filters['category_id'])) {
                $categoryIds = $filters['category_id'];
                $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
                $query .= " AND t.category_id IN ({$placeholders})";
                $bindings = array_merge($bindings, $categoryIds);
            } else {
                $query .= " AND t.category_id = ?";
                $bindings[] = $filters['category_id'];
            }
        }

        if ($order && $filters['order_status']) {
            // Modified order_status filter for orders table (if needed in other charts)
            if (is_array($filters['order_status'])) {
                $orderStatuses = $filters['order_status'];
                $placeholders = implode(',', array_fill(0, count($orderStatuses), '?'));
                $query .= " AND o.order_status IN ({$placeholders})"; // Assuming 'o' is the alias for orders table
                $bindings = array_merge($bindings, $orderStatuses);
            } else {
                $query .= " AND o.order_status = ?";
                $bindings[] = $filters['order_status'];
            }
        } elseif ($filters['order_status']) {
            // Modified order_status filter for proforma_invoices table
            if (is_array($filters['order_status'])) {
                $proformaStatuses = [];
                foreach ($filters['order_status'] as $status) {
                    if ($status == 'processing') {
                        $proformaStatuses[] = 'approved';
                    } elseif ($status == 'cancelled') {
                        $proformaStatuses[] = 'rejected';
                    } else {
                        $proformaStatuses[] = 'fulfilled'; // Assuming 'closed' maps to 'fulfilled'
                    }
                }
                if (!empty($proformaStatuses)) {
                    $placeholders = implode(',', array_fill(0, count($proformaStatuses), '?'));
                    $query .= " AND pi.status IN ({$placeholders})";
                    $bindings = array_merge($bindings, $proformaStatuses);
                }
            } else {
                $query .= " AND pi.status = ?";
                $bindings[] = ($filters['order_status'] == 'processing')
                    ? 'approved'
                    : ($filters['order_status'] == 'cancelled' ? 'rejected' : 'fulfilled');
            }
        }

        return $query;
    }

    protected function addMonthFilter(string &$query, array &$bindings, string $dateColumn): void
    {
        $filters = $this->getAllFilters();
        if (!empty($filters['month']) && $filters['month'] !== 'all') {
            $monthNumbers = is_array($filters['month']) ? $filters['month'] : [$filters['month']];

            if (count($monthNumbers) > 0) {
                $placeholders = implode(',', array_fill(0, count($monthNumbers), '?'));
                $query .= " AND MONTH($dateColumn) IN ({$placeholders})";
                $bindings = array_merge($bindings, $monthNumbers);

                $monthNamesConditions = [];
                foreach ($monthNumbers as $monthNumber) {
                    $monthName = $this->generateMonthName((int)$monthNumber);
                    $monthNamesConditions[] = "JSON_UNQUOTE(JSON_EXTRACT(t.month, '$.\"{$monthName}\"')) IS NOT NULL";
                }
                $query .= " AND (" . implode(" OR ", $monthNamesConditions) . ")";
            }
        }
    }

    protected function processChartData($orders, $monthFilter)
    {
        $processedData = [];

        foreach ($orders as $order) {
            $targetQuantity = $order->modified_target_quantity ?? $order->target_quantity;

            if ($monthFilter !== 'all') {
                $monthFilterArray = is_array($monthFilter) ? $monthFilter : [$monthFilter];
                $monthlyTargetQuantity = 0;
                foreach ($monthFilterArray as $month) {
                    $monthName = $this->generateMonthName((int)$month);
                    $monthData = json_decode($order->month, true);
                    $monthlyTargetQuantity += $monthData[$monthName] ?? 0;
                }
                $targetQuantity = $monthlyTargetQuantity;
            }

            $gapQuantity = ($order->realized_quantity ?? 0) - $targetQuantity;

            $processedData[] = [
                'category_name' => $order->category_name,
                'realized_quantity' => $order->realized_quantity,
                'target_quantity' => $targetQuantity,
                'gap_quantity' => $gapQuantity,
            ];
        }

        return $processedData;
    }

    protected function prepareChartData($data, $filterType, $nameCol)
    {
        $labels = array_column($data, $nameCol);
        $commonDatasetProps = [
            'type' => 'bar',
            'borderColor' => 'transparent',
            'hoverOffset' => 4,
            'barPercentage' => 0.4,
            'categoryPercentage' => 1.0,
        ];

        $datasets = [];

        $realizedQuantities = array_column($data, 'realized_quantity');
        $targetQuantities = array_column($data, 'target_quantity');
        $gapQuantities = array_column($data, 'gap_quantity');

        if ($filterType === 'quantity') {
            $datasets[] = array_merge($commonDatasetProps, [
                'label' => 'Realized Quantity',
                'data' => $realizedQuantities,
                'backgroundColor' => $this->getBackgroundColor(),
            ]);

            $datasets[] = array_merge($commonDatasetProps, [
                'label' => 'Target Quantity',
                'data' => $targetQuantities,
                'backgroundColor' => 'lightblue',
            ]);
        }

        if ($filterType === 'gap') {
            $backgroundColors = array_map(function ($gap) {
                return $gap < 0 ? 'lightcoral' : ($gap > 0 ? 'lightgreen' : '#4e73df');
            }, $gapQuantities);

            $datasets[] = array_merge($commonDatasetProps, [
                'label' => 'Gap Quantity',
                'data' => $gapQuantities,
                'backgroundColor' => $backgroundColors,
            ]);
        }

        if ($filterType === 'percentage') {
            $percentages = [];
            $totalRealized = array_sum($realizedQuantities);
            $totalTarget = array_sum($targetQuantities);
            $overallPercentage = $totalTarget > 0 ? round(($totalRealized / $totalTarget) * 100, 1) : 0;

            foreach ($data as $item) {
                $percentages[] = $item['target_quantity'] > 0 ? round(($item['realized_quantity'] / $item['target_quantity']) * 100, 1) : 0;
            }

            $datasets[] = array_merge($commonDatasetProps, [
                'label' => 'Percentage of Realization (Total: ' . $overallPercentage . '%) ',
                'data' => $percentages,
                'backgroundColor' => '#4e73df',
            ]);
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

    protected function generateMonthName(int $monthNumber): string
    {
        return strtolower(trim(date('F', mktime(0, 0, 0, $monthNumber, 1))));
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
