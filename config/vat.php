<?php

return [
    'enabled' => env('VIES_ENABLED', false),

    'endpoint' => env(
        'VIES_ENDPOINT',
        'https://ec.europa.eu/taxation_customs/vies/rest-api/check-vat-number'
    ),
    'connect_timeout_seconds' => 3,
    'timeout_seconds' => 8,

    'requester_country_code' => env('VIES_REQUESTER_COUNTRY', 'CZ'),
    'requester_vat_number' => env('VIES_REQUESTER_VAT_NUMBER', '63076331'),

    'cache' => [
        'valid_minutes' => 1440,
        'invalid_minutes' => 60,
        'unavailable_minutes' => 5,
    ],

    // All prefixes accepted by VIES validation, including Czechia and Northern Ireland.
    'vies_country_codes' => [
        'AT',
        'BE',
        'BG',
        'CY',
        'CZ',
        'DE',
        'DK',
        'EE',
        'EL',
        'ES',
        'FI',
        'FR',
        'HR',
        'HU',
        'IE',
        'IT',
        'LT',
        'LU',
        'LV',
        'MT',
        'NL',
        'PL',
        'PT',
        'RO',
        'SE',
        'SI',
        'SK',
        'XI',
    ],

    // Standard EU VAT territories supported for automatic B2B exemption.
    // Czechia is intentionally excluded and Greece uses the VIES prefix EL.
    'automatic_eu_country_codes' => [
        'AT',
        'BE',
        'BG',
        'CY',
        'DE',
        'DK',
        'EE',
        'EL',
        'ES',
        'FI',
        'FR',
        'HR',
        'HU',
        'IE',
        'IT',
        'LT',
        'LU',
        'LV',
        'MT',
        'NL',
        'PL',
        'PT',
        'RO',
        'SE',
        'SI',
        'SK',
    ],

    // Postal areas outside the standard EU VAT territory. Unknown or more
    // complicated special territories remain a manual accounting case.
    'excluded_tax_territory_postal_patterns' => [
        'DE' => [
            '/^(27498|78266)$/',
        ],
        'EL' => [
            '/^63086$/',
        ],
        'ES' => [
            '/^(35|38)\d{3}$/',
            '/^(51|52)\d{3}$/',
        ],
        'FI' => [
            '/^22\d{3}$/',
        ],
        'FR' => [
            '/^(97|98)\d{3}$/',
        ],
        'IT' => [
            '/^(22061|23041)$/',
        ],
    ],
];
