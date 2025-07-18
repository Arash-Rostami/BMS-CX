<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ProformaInvoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class ProjectNumberGenerator
{
    protected static array $map = [
        'proforma-invoices' =>
            ['model' => ProformaInvoice::class, 'prefix' => 'CT', 'field' => 'contract_number'],
        'orders' =>
            ['model' => Order::class, 'prefix' => 'PN', 'field' => 'invoice_number'],
    ];

    public static function generate(): string
    {
        $dateCode = now()->format('ymd');
        $segment = Request::segment(1);
        $data = static::$map[$segment] ?? null;

        if (!$data) {
            return "ERROR-{$dateCode}-URL";
        }

        [$modelClass, $prefix, $field] = [
            $data['model'],
            $data['prefix'],
            $data['field'],
        ];

        if (!class_exists($modelClass)) {
            return "ERROR-{$dateCode}-MODEL";
        }

        $base = "{$prefix}-{$dateCode}";
        $table = (new $modelClass)->getTable();

        $last = DB::table($table)
            ->where($field, 'like', "{$base}%")
            ->orderByDesc($field)
            ->value($field);

        if (!$last) {
            return $base;
        }

        $parts = explode('-', $last);
        $seq = (count($parts) < 3) ? 1 : intval(end($parts)) + 1;

        return "{$base}-{$seq}";
    }
}
