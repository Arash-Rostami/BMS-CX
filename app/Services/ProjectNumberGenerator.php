<?php

namespace App\Services;

use Carbon\Carbon;

class ProjectNumberGenerator
{
    public static function generate()
    {
        $now = Carbon::now();

        $yearLastTwoDigits = $now->format('y');
        $monthDay = $now->format('md');

        // Map hour to alphabet (A=0, B=1, ..., Z=25), wrapping every 24 hours
        $hourInAlphabet = chr(65 + ($now->hour % 24));

        $minuteSecond = $now->format('is');

        $url = request()->url();

        $prefix = str_contains($url, 'proforma') ? 'CT' : 'PN';

        return "{$prefix}-{$yearLastTwoDigits}{$monthDay}-{$hourInAlphabet}{$minuteSecond}";
    }
}
