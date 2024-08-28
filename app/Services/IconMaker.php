<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Vite;

class IconMaker
{
    protected static array $icons = [
        'pending' => 'resources/images/stats/pending.png',
        'review' => 'resources/images/stats/review.png',
        'processing' => 'resources/images/stats/review.png',
        'allowed' => 'resources/images/stats/allowed.png',
        'approved' => 'resources/images/stats/approve.png',
        'rejected' => 'resources/images/stats/reject.png',
        'completed' => 'resources/images/stats/fulfill.png',
        'in_transit' => 'resources/images/stats/transit.png',
        'delivered' => 'resources/images/stats/delivered.png',
        'shipped' => 'resources/images/stats/shipped.png',
        'customs' => 'resources/images/stats/customs.png',
        'cancelled' => 'resources/images/stats/cancel.png',
        'dollar' => 'resources/images/stats/dollar.png',
        'total' => 'resources/images/stats/total.png',
    ];

    public static function getIcon(string $iconName): string
    {
        if (!isset(self::$icons[$iconName])) {
            return '';
        }

        return Vite::asset(self::$icons[$iconName]);
    }
}
