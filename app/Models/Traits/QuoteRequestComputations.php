<?php

namespace App\Models\Traits;

use App\Models\Quote;
use App\Models\QuoteToken;

trait QuoteRequestComputations
{
    public static function showQuoteResponseRate($id)
    {
        $tokenCount = QuoteToken::countNum($id);

        if ($tokenCount === 0) {
            return '✖️ 0/0 (No Received Quote)';
        }

        $responseCount = Quote::countNum($id);

        $percentage = number_format(($responseCount / $tokenCount) * 100, 2, '.', '');

        return "🖂 $responseCount/$tokenCount ({$percentage}%)";
    }
}
