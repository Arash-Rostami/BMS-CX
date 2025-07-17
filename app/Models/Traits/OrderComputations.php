<?php

namespace App\Models\Traits;

use App\Models\PaymentRequest;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;


trait OrderComputations
{
    public static function aggregateOrderGroupTotals($record)
    {
        $proformaInvoice = $record->proformaInvoice;
        $orders = $record->getRelatedOrdersByInvoiceNumber();

        [$payment, $quantity] = self::getTotalPaymentRequestedAmountAndQuantity($orders);
        $stageQuantities = self::calculateStageQuantities($orders);
        $averageLeadTime = self::calculateAverageLeadTime($orders);
        [$paymentRequestBalance, $quantityBalance] = self::getPaymentRequestBalance($proformaInvoice, $payment, $quantity);
        $paymentRequestsData = self::calculatePaymentRequestsData($orders);

        return [
            'payment' => number_format($payment, 2),
            'quantity' => number_format($quantity, 2),
            'totalQuantity' => number_format(optional($record->proformaInvoice)->quantity ?? 0, 2) ?? 'Undefined',
            'shippedQuantity' => number_format($stageQuantities['shipped'], 2),
            'releasedQuantity' => number_format($stageQuantities['released'], 2),
            'averageLeadTime' => $averageLeadTime,
            'totalPayment' => numberify(optional($record->proformaInvoice)->price ?? 1 * optional($record->proformaInvoice)->quantity) ?? 'Undefined',
            'shipmentPart' => optional($record->proformaInvoice)->part ?? 'Undefined',
            'daysPassed' => optional($record->proformaInvoice)->daysPassed ?? 'Undefined',
            'totalPaymentRequests' => $paymentRequestsData['count'],
            'totalPayments' => $paymentRequestsData['paymentsCount'],
            'quantityBalance' => $quantityBalance,
            'paymentRequestBalance' => $paymentRequestBalance,
            'totalOfOtherPaymentRequests' => $paymentRequestsData['otherFormatted']
        ];
    }

    public function getRelatedOrdersByInvoiceNumber()
    {
        $cacheKey = 'related_orders_' . $this->invoice_number ?? $this->proforma_invoice_id;

        return Cache::remember($cacheKey, 120, function () {
            return static::with('orderDetail', 'purchaseStatus', 'doc')
                ->where('invoice_number', $this->invoice_number)
                ->where('proforma_invoice_id', $this->proforma_invoice_id)
                ->whereNull('deleted_at')
                ->get();
        });
    }

    protected static function getTotalPaymentRequestedAmountAndQuantity(Collection $orders): array
    {
        $payment = $quantity = 0;

        foreach ($orders as $order) {
            if (!$order->orderDetail) continue;

            $payment += ($order->orderDetail->initial_payment ?? 0)
                + ($order->orderDetail->provisional_total ?? 0)
                + ($order->orderDetail->final_total ?? 0);

            $quantity += $order->orderDetail->final_quantity
                ?? $order->orderDetail->provisional_quantity
                ?? 0;
        }

        return [(float)$payment, (float)$quantity];
    }

    protected static function calculateStageQuantities(Collection $orders): array
    {
        $shipped = $released = 0;

        foreach ($orders as $order) {
            if (!$order->purchaseStatus) continue;

            $qty = $order->orderDetail->final_quantity
                ?? $order->orderDetail->provisional_quantity
                ?? 0;

            $statusName = $order->purchaseStatus->name;

            if (str_contains($statusName, 'Released')) {
                $released += $qty;
                $shipped += $qty;
            } elseif (str_contains($statusName, 'Shipped')) {
                $shipped += $qty;
            }
        }

        return [
            'shipped' => (float)$shipped,
            'released' => (float)$released
        ];
    }


    protected static function calculateAverageLeadTime(Collection $orders): int
    {
        $totalDays = 0;
        $validOrdersCount = 0;

        foreach ($orders as $order) {
            if ($order->proforma_date && $order->doc?->BL_date) {
                $proformaDate = Carbon::parse($order->proforma_date);
                $blDate = Carbon::parse($order->doc->BL_date);

                $totalDays += $blDate->diffInDays($proformaDate);
                $validOrdersCount++;
            }
        }

        if ($validOrdersCount === 0) {
            return 0;
        }

        return (int)round($totalDays / $validOrdersCount);
    }

    protected static function getPaymentRequestBalance($proformaInvoice, float $payment, float $quantity): array
    {
        $expectedPayment = ($proformaInvoice->price ?? 0) * ($proformaInvoice->quantity ?? 0);
        $expectedQuantity = $proformaInvoice->quantity ?? 0;

        return [
            $payment > $expectedPayment ? '🔺' : ($payment < $expectedPayment ? '🔻' : '✅'),
            $quantity > $expectedQuantity ? '🔺' : ($quantity < $expectedQuantity ? '🔻' : '✅')
        ];
    }

    protected static function calculatePaymentRequestsData(Collection $orders): array
    {
        $count = $payments_count = 0;
        $otherRequests = [];

        foreach ($orders as $order) {
            $count += $order->paymentRequests->count();

            foreach ($order->paymentRequests as $paymentRequest) {
                $payments_count += $paymentRequest->payments->count();

                if (!in_array($paymentRequest->type_of_payment, ['balance', 'full', 'advance'])) {
                    $currency = $paymentRequest->currency;
                    $otherRequests[$currency] = ($otherRequests[$currency] ?? 0) + $paymentRequest->requested_amount;
                }
            }
        }

        $formatted = collect($otherRequests)->map(
            fn($amount, $currency) => getCurrencySymbols($currency) . number_format($amount, 2)
        )->join(', ');

        return [
            'count' => $count,
            'paymentsCount' => $payments_count,
            'otherFormatted' => $formatted
        ];
    }

    public static function countOrdersByStatus($year)
    {
        return Cache::remember("orders_count_by_status_{$year}", 300, function () use ($year) {
            $query = static::when($year !== 'all', fn($q) => $q->whereYear('proforma_date', $year));

            return $query->selectRaw('order_status, COUNT(*) as total')
                ->groupBy('order_status')
                ->get()
                ->pluck('total', 'order_status');
        });
    }

    public static function countOrdersByMonth($year)
    {
        return Cache::remember("orders_count_by_month_{$year}", 300, function () use ($year) {
            $query = static::when($year !== 'all', fn($q) => $q->whereYear('proforma_date', $year));

            return $query->selectRaw('MONTH(proforma_date) as month, COUNT(*) as count')
                ->groupBy('month')
                ->orderBy('month')
                ->pluck('count', 'month');
        });
    }

    public static function countOrdersByBuyer($year)
    {
        return Cache::remember("orders_count_by_Buyer_{$year}", 300, function () use ($year) {
            $query = static::with('party.buyer')
                ->when($year !== 'all', fn($q) => $q->whereYear('proforma_date', $year));

            return $query->get()
                ->groupBy(fn($order) => $order->party->buyer->name ?? 'Unknown')
                ->map->count();
        });
    }

    public static function countOrdersBySupplier($year)
    {
        return Cache::remember("orders_count_by_Supplier_{$year}", 300, function () use ($year) {
            $query = static::with('party.supplier')
                ->when($year !== 'all', fn($q) => $q->whereYear('proforma_date', $year));

            return $query->get()
                ->groupBy(fn($order) => $order?->party?->supplier?->name ?? 'Unknown')
                ->map->count();
        });
    }

    public static function countOrdersByCategory($year)
    {
        return Cache::remember("orders_count_by_category_{$year}", 300, function () use ($year) {
            return static::with('category')
                ->when($year !== 'all', fn($q) => $q->whereYear('proforma_date', $year))
                ->get()
                ->groupBy('category_id')
                ->map(fn($orders) => [
                    'name' => $orders->first()?->category?->name ?? 'Unknown',
                    'count' => $orders->count()
                ]);
        });
    }

    public static function countOrdersByProduct($year)
    {
        return Cache::remember("orders_count_by_product_{$year}", 300, function () use ($year) {
            return static::with('product')
                ->when($year !== 'all', fn($q) => $q->whereYear('proforma_date', $year))
                ->get()
                ->groupBy('product_id')
                ->map(fn($orders) => [
                    'name' => $orders->first()?->product?->name ?? 'Unknown',
                    'count' => $orders->count(),
                ]);
        });
    }

    public static function getOrdersCached(): array
    {
        return Cache::remember('orders_list', 300, function () {
            return static::pluck('reference_number', 'id')->toArray();
        });
    }

    public static function getStatusCounts()
    {
        return static::groupBy('purchase_status_id')
            ->selectRaw('purchase_status_id, count(*) as count')
            ->pluck('count', 'purchase_status_id');
    }

    public static function findByProformaInvoiceId($proformaInvoiceId)
    {
        return static::where('proforma_invoice_id', $proformaInvoiceId)
            ->whereNull('deleted_at')
            ->get();
    }

    public static function findByProjectNumber($proformaInvoiceId)
    {
        return static::where('invoice_number', $proformaInvoiceId)
            ->whereNull('deleted_at')
            ->get();
    }

    public static function makeOrderNumber($post): string
    {
        return implode('', [
            'C', $post->category_id,
            '-P', $post->product_id,
            '-PR', $post->proforma_number,
            '-PA', $post->party_id,
            '-OD', $post->order_detail_id,
            '-L', $post->logistic_id,
            '-D', $post->doc_id
        ]);
    }

    public static function getTabCounts(): array
    {
        $userId = auth()->id();

        return Cache::remember("order_tab_counts_{$userId}", 60, function () {
            return static::selectRaw('
                COUNT(*) as total,
                COUNT(CASE WHEN order_status = "accounting_review" THEN 1 END) as review_count,
                COUNT(CASE WHEN order_status = "accounting_approved" THEN 1 END) as approved_count,
                COUNT(CASE WHEN order_status = "accounting_rejected" THEN 1 END) as rejected_count,
                COUNT(CASE WHEN order_status = "closed" THEN 1 END) as closed_count
            ')->first()->toArray();
        });
    }

    public function getInvoiceNumberWithPartAttribute(): string
    {
        $booking = $this->logistic->booking_number ?? 'N/A';
        $ref = $this->reference_number ?? 'N/A';
        return "Booking# {$booking} 💢Ref: {$ref}";
    }

    public function getInvoiceNumberWithReferenceNumberAttribute(): string
    {
        $prj = $this->invoice_number;
        $ref = $this->reference_number ?? 'No Ref. No.';
        return "{$prj} (Ref: {$ref})";
    }

    public function getAllPaymentRequests()
    {
        return $this->hasMany(PaymentRequest::class)
            ->whereNull('deleted_at')
            ->orWhere(function ($query) {
//                $query->whereRaw("FIND_IN_SET(?, proforma_invoice_number) > 0", [$this->proforma_number])
                $query->whereRaw("proforma_invoice_number REGEXP CONCAT('(^|,\\s)', ?, '(\\s,|$)')", [addslashes($this->proforma_number)])
                    ->whereNull('deleted_at')
                    ->whereNull('order_id');
            });
    }

    public function hasCompletedBalancePayment()
    {
        $orderId = $this->id;
        $cacheKey = "hasCompletedBalancePayment_" . $orderId;

        return Cache::remember($cacheKey, 5 * 60, function () use ($orderId) {
            $sql = "
            SELECT 1
            FROM payment_requests pr
            INNER JOIN orders o ON pr.order_id = o.id
            INNER JOIN payment_payment_request ppr ON pr.id = ppr.payment_request_id
            INNER JOIN payments p ON ppr.payment_id = p.id
            WHERE pr.status = 'completed'
              AND pr.type_of_payment = 'balance'
              AND pr.deleted_at IS NULL
              AND p.deleted_at IS NULL
              AND p.date < CURDATE() - INTERVAL 3 DAY
              AND NOT EXISTS (
                  SELECT 1
                  FROM attachments a
                  WHERE a.order_id = o.id
                    AND LOWER(a.name) LIKE '%telex-release%'
              )
              AND o.id = ?
            GROUP BY pr.id, pr.requested_amount
            HAVING SUM(p.amount) >= pr.requested_amount
            LIMIT 1
        ";

            $results = DB::select($sql, [$orderId]);

            return !empty($results);
        });
    }
}
