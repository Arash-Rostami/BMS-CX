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


    public const SmokyQuartz = [
        50 => '240, 235, 230',   // Pale quartz
        100 => '220, 215, 210',  // Soft fog
        200 => '200, 195, 190',  // Polished stone
        300 => '180, 175, 170',  // Misty taupe
        400 => '160, 155, 150',  // Subtle smoke
        500 => '140, 135, 130',  // Cool quartz
        600 => '120, 115, 110',  // Soft slate
        700 => '100, 95, 90',    // Rich taupe
        800 => '80, 75, 70',     // Deep quartz
        900 => '60, 55, 50',     // Smoky shadow
        950 => '40, 35, 30',     // Dark gemstone
    ];


    public const RadiantCoral = [
        50 => '250, 225, 220',   // Soft blush
        100 => '240, 200, 195',  // Warm peach
        200 => '230, 175, 170',  // Subtle coral
        300 => '220, 150, 145',  // Elegant salmon
        400 => '200, 120, 115',  // Polished coral
        500 => '180, 100, 95',   // Striking crimson
        600 => '160, 80, 75',    // Deep coral
        700 => '140, 60, 55',    // Intense vermilion
        800 => '120, 40, 35',    // Dark red clay
        900 => '100, 30, 25',    // Bold brick
        950 => '80, 20, 20',     // Deep garnet
    ];


    public const CoolGraphite = [
        50 => '220, 227, 229',   // Pale graphite
        100 => '200, 213, 217',  // Light slate
        200 => '180, 200, 205',  // Muted silver
        300 => '160, 185, 190',  // Polished steel
        400 => '140, 170, 175',  // Modern graphite
        500 => '120, 150, 155',  // Cool pewter
        600 => '100, 130, 135',  // Shadowed gray
        700 => '80, 110, 115',   // Deep steel blue
        800 => '60, 90, 95',     // Bold slate
        900 => '40, 70, 75',     // Charcoal tint
        950 => '20, 50, 55',     // Dark graphite
    ];


    public const VelvetAubergine = [
        50 => '240, 230, 235',   // Pale lilac
        100 => '225, 210, 220',  // Soft plum
        200 => '210, 190, 205',  // Gentle mauve
        300 => '195, 170, 190',  // Subtle eggplant
        400 => '180, 150, 175',  // Elegant amethyst
        500 => '160, 130, 155',  // Rich violet
        600 => '140, 110, 135',  // Deep plum
        700 => '120, 90, 115',   // Dusky purple
        800 => '100, 70, 95',    // Bold aubergine
        900 => '80, 50, 75',     // Dark blackberry
        950 => '60, 30, 55',     // Blackened violet
    ];


    public const GoldenDusk = [
        50 => '245, 240, 225',   // Pale cream
        100 => '235, 225, 200',  // Soft beige
        200 => '220, 210, 180',  // Muted gold
        300 => '205, 190, 160',  // Dusky amber
        400 => '190, 170, 140',  // Warm ochre
        500 => '175, 150, 120',  // Rich gold
        600 => '155, 130, 100',  // Deep bronze
        700 => '135, 110, 80',   // Dark golden brown
        800 => '115, 90, 60',    // Burnished gold
        900 => '95, 70, 40',     // Antique brass
        950 => '75, 50, 20',     // Deep ochre
    ];

    public const MidnightTeal = [
        50 => '225, 235, 235',   // Misty aqua
        100 => '200, 220, 220',  // Pale teal
        200 => '175, 205, 205',  // Light turquoise
        300 => '150, 185, 185',  // Cool blue-green
        400 => '125, 165, 165',  // Polished teal
        500 => '100, 145, 145',  // Rich aqua
        600 => '80, 125, 125',   // Deep teal
        700 => '60, 105, 105',   // Bold seafoam
        800 => '40, 85, 85',     // Shadowed blue-green
        900 => '20, 65, 65',     // Dark ocean
        950 => '10, 45, 45',     // Blackened teal
    ];

    public const BlushRose = [
        50 => '250, 240, 240',   // Pale blush
        100 => '240, 220, 220',  // Soft rose
        200 => '230, 200, 200',  // Light peachy pink
        300 => '220, 180, 180',  // Subtle coral rose
        400 => '200, 160, 160',  // Gentle pink
        500 => '180, 140, 140',  // Elegant rose
        600 => '160, 120, 120',  // Muted mauve
        700 => '140, 100, 100',  // Dusky pink
        800 => '120, 80, 80',    // Deep blush
        900 => '100, 60, 60',    // Burnt rose
        950 => '80, 40, 40',     // Blackened pink
    ];

    public const SlateBlue = [
        50 => '230, 235, 240',   // Frosted blue
        100 => '210, 220, 230',  // Light slate
        200 => '190, 200, 220',  // Muted gray-blue
        300 => '170, 180, 210',  // Soft steel blue
        400 => '150, 160, 200',  // Elegant slate
        500 => '130, 140, 190',  // Rich gray-blue
        600 => '110, 120, 170',  // Deep periwinkle
        700 => '90, 100, 150',   // Bold slate
        800 => '70, 80, 130',    // Dark indigo-gray
        900 => '50, 60, 110',    // Deep navy
        950 => '30, 40, 90',     // Blackened blue
    ];

    public const White = [
        50 => '255, 255, 255',
        100 => '255, 255, 255',
        200 => '255, 255, 255',
        300 => '255, 255, 255',
        400 => '255, 255, 255',
        500 => '255, 255, 255',
        600 => '255, 255, 255',
        700 => '255, 255, 255',
        800 => '255, 255, 255',
        900 => '255, 255, 255',
        950 => '255, 255, 255',
    ];

    public const Gray = [
        50 => '120, 120, 120',
        100 => '130, 130, 130',
        200 => '140, 140, 140',
        300 => '150, 150, 150',
        400 => '160, 160, 160',
        500 => '110, 110, 110',
        600 => '100, 100, 100',
        700 => '90, 90, 90',
        800 => '80, 80, 80',
        900 => '70, 70, 70',
        950 => '60, 60, 60',
    ];

    public const DarkBlueGray = [
        50 => '210, 212, 230',
        100 => '190, 193, 220',
        200 => '170, 173, 210',
        300 => '140, 144, 195',
        400 => '110, 115, 180',
        500 => '80, 85, 124',
        600 => '70, 75, 110',
        700 => '60, 65, 95',
        800 => '50, 55, 80',
        900 => '40, 45, 65',
        950 => '30, 35, 50',
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
//            'maroon' => self::DarkMaroon,
//            'coral' => self::RadiantCoral,
//            'orange' => Color::Orange,
//            'slate' => Color::Slate,
            'zinc' => Color::Zinc,
//            'indigo' => Color::Indigo,
//            'darkMaroon' => self::DarkMaroon,
//            'goldenDusk' => self::GoldenDusk,
//            'smokyQuartz' => self::SmokyQuartz,
//            'radiantCoral' => self::RadiantCoral,
//            'velvetAubergine' => self::VelvetAubergine,
//            'midnightTeal' => self::MidnightTeal,
//            'blushRose' => self::BlushRose,
//            'gray' => self::Gray,
//            'darkBlueGray' => self::DarkBlueGray,
//            'coolGraphite' => self::CoolGraphite,
//            'slateBlue' => self::SlateBlue,
        ];

        return $colors[array_rand($colors)];
    }
}
