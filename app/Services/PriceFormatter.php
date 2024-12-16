<?php

namespace App\Services;

class PriceFormatter
{
    public static function condense($number, $precision = 1): string
    {
        if (!is_numeric($number)) {
            return '0';
        }

        foreach ([1_000_000_000 => 'B', 1_000_000 => 'M', 1_000 => 'K'] as $value => $suffix) {
            if (abs($number) >= $value) {
                return number_format($number / $value, $precision) . $suffix;
            }
        }
        return number_format($number, 2);
    }
}
