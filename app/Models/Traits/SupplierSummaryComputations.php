<?php

namespace App\Models\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

trait SupplierSummaryComputations
{
    public function scopeAdjustments($query)
    {
        return $query->where('type', 'adjustment')->whereNull('proforma_invoice_id');
    }

    public function scopeProformas($query)
    {
        return $query->where('type', 'proforma')->whereNotNull('proforma_invoice_id');
    }

    public static function getTabCounts(): array
    {
        return Cache::remember('supplier_balances_by_status', 60, function () {
            return collect(['Overpaid' => [], 'Underpaid' => [], 'Settled' => []])
                ->merge(
                    self::query()
                        ->select('supplier_id', DB::raw('SUM(diff) as balance'))
                        ->groupBy('supplier_id')
                        ->get()
                        ->mapToGroups(function ($row) {
                            $balance = (float)$row->balance;
                            $status = match (true) {
                                $balance < 0 => 'Underpaid',
                                $balance > 0 => 'Overpaid',
                                default => 'Settled',
                            };
                            return [$status => $row->supplier_id];
                        })
                )->all();
        });
    }
}
