<?php

namespace App\Models\Traits;

trait QuoteRequestComputations
{
    public static function formatResponseRate(int $responseCount, int $tokenCount): string
    {
        if ($tokenCount === 0) return '✖️ 0/0 (No Received Quote)';

        $percentage = number_format(($responseCount / $tokenCount) * 100, 2, '.', '');

        return "🖂 {$responseCount}/{$tokenCount} ({$percentage}%)";
    }
}
