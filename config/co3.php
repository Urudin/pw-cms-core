<?php

return [
    'base_url' => env('CO3_BASE_URL', 'https://eventrix.hu/apitest'),

    'api_key' => env('CO3_API_KEY'),
    'username' => env('CO3_USERNAME'),
    'password_hash' => env('CO3_PASSWORD_HASH', env('EVENTRIX_PASSWORD_HASH')),

    'contact_owner' => env('CO3_CONTACT_OWNER'),
    'contact_category' => env('CO3_CONTACT_CATEGORY', 'None'),
    'selected_account' => env('CO3_SELECTED_ACCOUNT'),

    'language' => env('CO3_LANGUAGE', 'hu_HU'),
    'currency' => env('CO3_CURRENCY', 'HUF'),
    'esignature' => (int) env('CO3_ESIGNATURE', 0),
    'proforma' => (int) env('CO3_PROFORMA', 0),
];
