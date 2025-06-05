<?php

use App\Services\Traits\BpCredentials;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Number;

function capitalizeFirstLetters(string $text): string
{
    $words = explode(' ', strtolower($text));
    $processedWords = [];

    foreach ($words as $word) {
        $processedWords[] = ucfirst($word);
    }

    return implode(' ', $processedWords);
}

function configureIfAppIsOnline()
{
    return config('app.storage') === 'production';
}

function formatNumber(int $number)
{
    if ($number < 1000) {
        return (string)Number::format($number, 0);
    }

    if ($number < 1000000) {
        return Number::format($number / 1000, 2) . 'k';
    }

    return Number::format($number / 1000000, 2) . 'm';
}

function formatCurrency($amount): string
{
    if ($amount >= 1_000_000_000_000) {
        return round($amount / 1_000_000_000_000, 2) . 'T';
    } elseif ($amount >= 1_000_000_000) {
        return round($amount / 1_000_000_000, 2) . 'B';
    } elseif ($amount >= 1_000_000) {
        return round($amount / 1_000_000, 2) . 'M';
    } elseif ($amount >= 1_000) {
        return round($amount / 1_000, 2) . 'K';
    }

    return number_format($amount, 2);
}

function formatHTML(string $text, array $classes = [])
{
    $classString = implode(' ', $classes);

    return new HtmlString("<span class='{$classString}'>{$text}</span>");
}

function getCurrencySymbols($currency)
{
    return [
        'USD' => '$',
        'EURO' => '€',
        'Yuan' => '¥',
        'Dirham' => 'D',
        'Ruble' => '₽',
        'Rial' => 'R'
    ][$currency] ?? ['Rial'];
}

function getTableDesign()
{
    return data_get(optional(auth()->user()->info), 'tableDesign');
}

function getMenuDesign()
{
    return data_get(optional(auth()->user()->info), 'menuDesign');
}

function isModernDesign()
{
    return getTableDesign() == 'modern';
}

function isMenuTop()
{
    return getMenuDesign() == 'top';
}

function isSimpleSidebar()
{
    $user = auth()->user();

    return $user && (($user->info['sideBarItems'] ?? 'show') === 'hide');
}

function isFilterSelected()
{
    $user = auth()->user();

    return ($user && ($user->info['filterDesign'] ?? 'hide') == 'show');
}

function initializeBp($key)
{
    $bpHelper = new class {
        use BpCredentials;
    };

    $bpHelper->initializeBpCredentials();

    return [
        'client_id' => $bpHelper->clientId,
        'bot_id' => $bpHelper->botId,
    ][$key];
}

function isColorSelected()
{
    $user = auth()->user();

    return ($user && ($user->info['shadeDesign'] ?? 'hide') === 'show');

}

function isShadeSelected($bg)
{
    return isColorSelected() ? $bg : '' ?? null;
}


function isUserAdmin()
{
    return auth()->user()->role === 'admin';
}


function isUserManager()
{
    return auth()->user()->role === 'manager';
}


function isUserAgent()
{
    return auth()->user()->role === 'agent';
}

function isUserJnrAccountant()
{
    $user = auth()->user();
    return $user->role === 'accountant' && ($user->info['position'] ?? null) == 'jnr';
}

function isUserSnrAccountant()
{
    $user = auth()->user();
    return $user->role === 'accountant' && ($user->info['position'] ?? null) == 'snr';
}


function isUserAccountant()
{
    $user = auth()->user();

    return $user->role === 'accountant' && ($user->info['position'] ?? null) == 'jnr';
}


function isUserPartner()
{
    return auth()->user()->role === 'partner';
}

function isUserCXHead()
{
    $user = auth()->user();

    if (!$user || !isset($user->role, $user->info['department'], $user->info['position'])) {
        return false;
    }

    return $user->role === 'agent' && $user->info['department'] == 6 && $user->info['position'] == 'mdr';
}


function numberify($number)
{
    return number_format((float)$number, 2, '.', ',');
}


function persistReferenceNumber($record, $prefix): void
{
    $yearSuffix = date('y');
    $recordIndex = $record->id;
    $referenceNumber = sprintf('%s-%s%04d', $prefix, $yearSuffix, $recordIndex);
    $record->reference_number = $referenceNumber;
    $record->save();
}

function showCurrencies()
{
    return [
        'Rial' => new HtmlString('<span class="mr-2">🇮🇷</span> Rial'),
        'USD' => new HtmlString('<span class="mr-2">🇺🇸</span> Dollar'),
        'EURO' => new HtmlString('<span class="mr-2">🇪🇺</span> Euro'),
        'Yuan' => new HtmlString('<span class="mr-2">🇨🇳</span> Yuan'),
        'Dirham' => new HtmlString('<span class="mr-2">🇦🇪</span> Dirham'),
        'Ruble' => new HtmlString('<span class="mr-2">🇷🇺</span> Ruble')
    ];
}

function showCurrencyWithoutHTMLTags($record)
{
    return strip_tags(showCurrencies()[$record]);
}


function showDelimiter($number, $currency = null)
{
    $decimalPlaces = 0;
    $numberString = strval($number);

    $decimalPosition = strpos($numberString, '.');

    if ($decimalPosition !== false) {
        $decimalPlaces = strlen(substr($numberString, $decimalPosition + 1));
    }

    return $currency . ' ' . number_format($number, $decimalPlaces, '.', ',');
}

function slugify($string)
{
    return strtolower(str_replace(' ', '-', trim($string)));
}
