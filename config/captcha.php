<?php

return [

    'disable' => env('CAPTCHA_DISABLE', false),

    'characters' => 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789',

    'fontsDirectory' => base_path('vendor/mews/captcha/assets/fonts'),

    'bgsDirectory' => storage_path('app/public/captcha/backgrounds'),

    'default' => [
        'length' => 6,
        'width' => 345,
        'height' => 65,
        'quality' => 90,
        'math' => false,
        'expire' => 60,
        'encrypt' => false,
        'bgImage' => false,
        'bgColor' => '#f4f6fb',
    ],

    'flat' => [
        'length' => 6,
        'width' => 345,
        'height' => 65,
        'quality' => 90,
        'math' => false,
        'expire' => 60,
        'encrypt' => false,
        'bgImage' => false,
        'bgColor' => '#f4f6fb',
    ],

];
