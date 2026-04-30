<?php

return [
    'interest_tiers' => [
        [
            'min_days' => 1,
            'max_days' => 30,
            'rate' => 0.015,
        ],
        [
            'min_days' => 31,
            'max_days' => 60,
            'rate' => 0.02,
        ],
        [
            'min_days' => 61,
            'max_days' => 90,
            'rate' => 0.025,
        ],
        [
            'min_days' => 91,
            'max_days' => 120,
            'rate' => 0.03,
        ],
        [
            'min_days' => 121,
            'max_days' => 150,
            'rate' => 0.035,
        ],
        [
            'min_days' => 151,
            'max_days' => null, // null represents no upper limit (151+)
            'rate' => 0.04,
        ],
    ],
];
