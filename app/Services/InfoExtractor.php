<?php

namespace App\Services;

class InfoExtractor
{

    public static function getPortInfo($proformaInvoice, ?string $state)
    {
        return collect($proformaInvoice->extra['port'] ?? [])
            ->map(function ($portInfo) {
                $tokens = preg_split('/\s+/', $portInfo);

                $partPatternCombined = '/^(?:[pP](?:[aA][rR][tT])?)\s*(\d+)$/';
                $partPatternSeparate = '/^[pP](?:[aA][rR][tT])?$/i';
                $quantityPattern = '/^\d+(?:\.\d+)?$/';

                $partPrefix = null;
                $partNumber = null;
                $quantity = null;
                $cityTokens = [];

                for ($i = 0; $i < count($tokens); $i++) {
                    $token = $tokens[$i];

                    // Check if the token matches the combined part pattern (e.g., 'P1', 'Part1')
                    if (preg_match($partPatternCombined, $token, $partMatches)) {
                        $partPrefix = ucfirst(strtolower(substr($token, 0, -strlen($partMatches[1]))));
                        $partNumber = $partMatches[1];
                    } elseif (preg_match($partPatternSeparate, $token)) {
                        // Look ahead to the next token
                        if (isset($tokens[$i + 1]) && preg_match('/^\d+$/', $tokens[$i + 1])) {
                            $partPrefix = ucfirst(strtolower($token));
                            $partNumber = $tokens[$i + 1];
                            // Skip the next token since it's part of the part number
                            $i++;
                        } else {
                            // 'P' or 'Part' without a following number, treat as city
                            $cityTokens[] = $token;
                        }
                    } elseif (preg_match($quantityPattern, $token)) {
                        $quantity = $token;
                    } else {
                        $cityTokens[] = $token;
                    }
                }

                $city = implode(' ', $cityTokens);

                if ($partNumber !== null && $quantity !== null && !empty($city)) {
                    return [
                        'city' => $city,
                        'quantity' => $quantity,
                        'partPrefix' => $partPrefix,
                        'partNumber' => $partNumber,
                    ];
                }

                return null;
            })
            ->filter()
            ->firstWhere('partNumber', $state);
    }
}
