<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

class ProjectNumberGenerator
{
    protected static array $modelMap = [
        'proforma-invoices' => ['model' => 'ProformaInvoice', 'prefix' => 'CT', 'field' => 'contract_number'],
        'orders' => ['model' => 'Order', 'prefix' => 'PN', 'field' => 'invoice_number'],
    ];

    public static function generate(): string
    {
        $now = Carbon::now();
        $baseNumber = $now->format('y') . $now->format('md');

        $modelData = self::getModelDataFromUrl(Request::url());
        if (!$modelData) {
            return "ERROR-{$baseNumber}-URL";
        }

        $modelClass = "App\\Models\\{$modelData['model']}";
        if (!class_exists($modelClass)) {
            return "ERROR-{$baseNumber}-MODEL";
        }

        $generatedNumber = "{$modelData['prefix']}-{$baseNumber}";
        $existingCount = DB::table((new $modelClass)->getTable())
            ->whereRaw("{$modelData['field']} LIKE ?", ["{$generatedNumber}%"])
            ->count();

        return $existingCount > 0 ? "{$generatedNumber}-" . ($existingCount + 1) : $generatedNumber;
    }

    private static function getModelDataFromUrl(string $url): ?array
    {
        foreach (self::$modelMap as $key => $data) {
            if (Str::contains($url, $key)) {
                return $data;
            }
        }
        return null;
    }
}
