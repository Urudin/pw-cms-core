<?php

return [
    'forward_payment' => [
        'label' => 'Közvetlen Banki Átutalás',
        'info' => 'Fizetés közvetlenül a bankszámlánkra. A megjegyzés rovatban tüntessük fel a rendelésszámot. A számla kiállítás az összeg beérkezését követően történik.'
    ],
    'card' => [
        'label' => 'Bankkártyás fizetés',
        'info' => 'A megrendelést követően automatikusan átirányítjuk a Simplepay oldalára, ahol bankkártyával meg tudja fizetni a megrendelés ellenértékét.'
    ]
];
