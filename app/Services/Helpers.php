<?php

use App\Services\AvatarMaker;
use Illuminate\Support\HtmlString;

function capitalizeFirstLetters(string $text): string
{
    $words = explode(' ', strtolower($text));
    $processedWords = [];

    foreach ($words as $word) {
        $processedWords[] = ucfirst($word);
    }

    return implode(' ', $processedWords);
}

function getTableDesign()
{
    return data_get(optional(auth()->user()->info), 'tableDesign');
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


function isUserAccountant()
{
    return auth()->user()->role === 'accountant';
}

function showCurrencies()
{
    return [
        'USD' => new HtmlString('<span class="mr-2">🇺🇸</span> Dollar'),
        'EURO' => new HtmlString('<span class="mr-2">🇪🇺</span> Euro'),
        'Yuan' => new HtmlString('<span class="mr-2">🇨🇳</span> Yuan'),
        'Dirham' => new HtmlString('<span class="mr-2">🇦🇪</span> Dirham'),
        'Ruble' => new HtmlString('<span class="mr-2">🇷🇺</span> Ruble'),
        'Rial' => new HtmlString('<span class="mr-2">🇮🇷</span> Rial')
    ];
}

function showCurrencyWithoutHTMLTags($record)
{
    return strip_tags(showCurrencies()[$record]);
}
