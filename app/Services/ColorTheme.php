<?php

namespace App\Services;

use Filament\Facades\Filament;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Auth;

class ColorTheme
{

    /**
     * @return array
     */
    public static function getRandomFontTheme(): array
    {
        $colors = [
//            'gold' => Color::Gold,
//            'orange' => Color::Orange,
//            'slate' => Color::Slate,
//            'zinc' => Color::Zinc,
            'indigo' => Color::Indigo,
        ];

        return $colors[array_rand($colors)];
    }


}
