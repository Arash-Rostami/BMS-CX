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
//            'orange' => Color::Orange,
//            'slate' => Color::Slate,
//            'zinc' => Color::Zinc,
            'indigo' => Color::Indigo,
//            'indigo-second' => [400 => '242, 217, 159']
        ];

        return $colors[array_rand($colors)];
    }


}
