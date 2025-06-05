<?php

return [
	'mode'                  => 'utf-8',
	'format'                => 'A4',
	'author'                => '',
	'subject'               => '',
	'keywords'              => '',
	'creator'               => 'Laravel Pdf',
	'display_mode'          => 'fullpage',
	'pdf_a'                 => false,
	'pdf_a_auto'            => false,
	'icc_profile_path'      => '',
    'tempDir' => base_path('storage/temp'),
    'font_path' => base_path('resources/fonts/'),
    'font_data' => [
        'Kalameh' => [
            'R' => 'Kalameh-Light.ttf',
            'B' => 'Kalameh-Black.ttf',
            'useOTL' => 0xFF,
            'useKashida' => 75,
        ]
    ]
];
