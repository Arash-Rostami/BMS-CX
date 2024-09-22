<?php

namespace App\Services;

use Illuminate\Support\Str;

class HTMLCrawler
{

    public static function extractFirstLinkUsingStr($html)
    {
        $pattern = '/<a\s+href="([^"]+)">/i';

        if (preg_match($pattern, $html, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
