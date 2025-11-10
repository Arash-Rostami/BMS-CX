<?php

use App\Services\Traits\BpCredentials;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Number;
use Illuminate\Support\Str;


function cachedUser(): ?Authenticatable
{
    static $user = null;

    if ($user === null) {
        $user = auth()->user();
    }

    return $user;
}

function capitalizeFirstLetters(string $text): string
{
    return ucwords(strtolower($text));
}

function configureIfAppIsOnline()
{
    return config('app.storage') === 'production';
}

function formatNumber(int $number)
{
    if ($number < 1_000) {
        return Number::format($number, 0);
    }

    $suffixes = [
        1_000_000 => 'm',
        1_000 => 'k',
    ];

    foreach ($suffixes as $limit => $suffix) {
        if ($number >= $limit) {
            return Number::format($number / $limit, 2) . $suffix;
        }
    }
}

function formatCurrency($amount): string
{
    $units = [
        1_000_000_000_000 => 'T',
        1_000_000_000 => 'B',
        1_000_000 => 'M',
        1_000 => 'K',
    ];

    foreach ($units as $divisor => $suffix) {
        if ($amount >= $divisor) {
            return round($amount / $divisor, 2) . $suffix;
        }
    }

    return number_format($amount, 2, '.', ',');
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
        'Rial' => 'R',
    ][$currency] ?? '﷼';
}

function getTableDesign()
{
    return data_get(optional(cachedUser()->info), 'tableDesign');
}

function getMenuDesign()
{
    return data_get(optional(cachedUser()->info), 'menuDesign');
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
    $user = cachedUser();

    return $user && (($user->info['sideBarItems'] ?? 'show') === 'hide');
}

function isFilterSelected()
{
    $user = cachedUser();

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
    $user = cachedUser();

    return ($user && ($user->info['shadeDesign'] ?? 'hide') === 'show');
}

function isShadeSelected($bg)
{
    return (isColorSelected() ? $bg : '') ?? null;
}

function isUserAdmin()
{
    return optional(cachedUser())->role === 'admin';
}

function isUserManager()
{
    return optional(cachedUser())->role === 'manager';
}

function isUserAgent()
{
    return optional(cachedUser())->role === 'agent';
}

function isUserJnrAccountant()
{
    $user = cachedUser();
    return $user && $user->role === 'accountant' && ($user->info['position'] ?? null) == 'jnr';
}

function isUserSnrAccountant()
{
    $user = cachedUser();
    return $user && $user->role === 'accountant' && ($user->info['position'] ?? null) == 'snr';
}

function isUserAccountant()
{
    return optional(cachedUser())->role === 'accountant';
}

function isUserPartner()
{
    return optional(cachedUser())->role === 'partner';
}

function isUserMdr()
{
    $user = cachedUser();

    if (!$user || !isset($user->role, $user->info['position'])) {
        return false;
    }

    return $user->role == 'agent' && $user->info['position'] == 'mdr';
}

function isUserCXHead()
{
    $user = cachedUser();

    if (!$user || !isset($user->role, $user->info['department'], $user->info['position'])) {
        return false;
    }

    return $user->role === 'agent' && $user->info['department'] == 6 && $user->info['position'] == 'mdr';
}

function isUserPersoreHead()
{
    $user = cachedUser();

    if (!$user || !isset($user->role, $user->info['department'], $user->info['position'])) {
        return false;
    }

    return $user->role === 'agent' && $user->info['department'] == 10 && $user->info['position'] == 'mdr';
}

function isUserMKHead()
{
    $user = cachedUser();

    if (!$user || !isset($user->role, $user->info['department'], $user->info['position'])) {
        return false;
    }

    return $user->role === 'agent' && $user->info['department'] == 9 && $user->info['position'] == 'mdr';
}

function numberify($number)
{
    return number_format((float)$number, 2, '.', ',');
}

function persistReferenceNumber($record, string $prefix): void
{
    $record->update([
        'reference_number' => sprintf(
            '%s-%s%04d',
            $prefix,
            date('y'),
            $record->id
        )
    ]);
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

function showDelimiter(float|int $number, ?string $currency = null)
{
    $decimalPlaces = (int)str_contains((string)$number, '.')
        ? strlen(explode('.', (string)$number)[1])
        : 0;

    return trim(sprintf('%s %s',
        $currency,
        number_format($number, $decimalPlaces, '.', ',')
    ));
}

function slugify(string $text)
{
    return Str::slug($text);
}
