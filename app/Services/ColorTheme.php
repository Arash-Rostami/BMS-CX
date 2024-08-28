<?php

namespace App\Services;

use Filament\Facades\Filament;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Auth;

class ColorTheme
{
    public const DarkMaroon = [
        50 => '223, 207, 212',
        100 => '213, 192, 199',
        200 => '203, 177, 186',
        300 => '193, 162, 173',
        400 => '183, 147, 160',
        500 => '173, 132, 147',
        600 => '163, 117, 134',
        700 => '153, 102, 121',
        800 => '143, 87, 108',
        900 => '133, 72, 95',
        950 => '123, 57, 82',
    ];

    public static function getRandomColorForWidget()
    {
        $colorPalette = [
            'rgba(255, 99, 132, 0.5)', 'rgba(255, 159, 64, 0.5)', 'rgba(255, 205, 86, 0.5)',
            'rgba(75, 192, 192, 0.5)', 'rgba(54, 162, 235, 0.5)', 'rgba(153, 102, 255, 0.5)',
            'rgba(201, 203, 207, 0.5)', 'rgba(255, 120, 80, 0.5)', 'rgba(255, 179, 71, 0.5)',
            'rgba(100, 100, 200, 0.5)', 'rgba(50, 205, 50, 0.5)', 'rgba(235, 200, 255, 0.5)',
            'rgba(255, 99, 164, 0.5)', 'rgba(255, 217, 102, 0.5)', 'rgba(135, 206, 235, 0.5)',
            'rgba(255, 165, 0, 0.5)', 'rgba(30, 144, 255, 0.5)', 'rgba(220, 20, 60, 0.5)',
            'rgba(0, 255, 127, 0.5)', 'rgba(148, 0, 211, 0.5)'
        ];

        shuffle($colorPalette);

        return $colorPalette;
    }

    /**
     * @return array
     */
    public static function getRandomFontTheme(): array
    {

        $colors = [
//            'blue' => Color::Blue,
            'maroon' => self::DarkMaroon,
//            'orange' => Color::Orange,
//            'slate' => Color::Slate,
//            'zinc' => Color::Zinc,
//            'indigo' => Color::Indigo,
        ];

        return $colors[array_rand($colors)];
    }
}
