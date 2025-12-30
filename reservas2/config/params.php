<?php

return [
    'idAPP' => 'bt5',
    'bsVersion' => '5.x',
    'adminEmail' => 'cabanas@dinasis.com.es',
    'bccEmail' => 'smartin@bariloche.com.ar',
    'senderEmail' => 'no-reply@dinasis.com.es',   // o el remitente que admita tu SMTP
    'senderName' => 'Sistema de Reservas',
    'sessionTimeoutSeconds' => '7200',
    'expireSessionMin' => 24 * 60,
    'user.passwordResetTokenExpire' => 3600,
    'supportedLanguages' => [
        'es' => 'Español',
        'en' => 'English',
        'pt-BR' => 'Português (Brasil)',
    ],
    'contact' => [
        'name' => 'Cabañas Dina Huapi',
        'address_line1' => 'Los Cohiues 375',
        'address_extra' => '(Cohiues y Av. Patagonia Argentina)',
        'location_line' => '(CP 8402) Dina Huapi, Río Negro – Argentina',

        'whatsapp_number_human' => '54 9 2944 59-7081',
        // solo números, con código país/área, sin espacios
        'whatsapp_number_link' => '5492944597081',
        'whatsapp_contact_name' => 'Claudia Barragan',
        'email'      => 'cabanas@dinasis.com.es',

        // texto extra debajo
        'extra_text' => 'Estamos a metros de la costa del lago Nahuel Huapi y a pocos minutos de Bariloche.',
    ],

    'social' => [
        'facebook' => [
            'url' => 'https://www.facebook.com/cabanasDinaHuapi?rdid=HWamukUW73oj15xE&share_url=https%3A%2F%2Fwww.facebook.com%2Fshare%2F1BnLmD8aGM%2F#',
            'icon' => 'bi bi-facebook',
        ],
        'instagram' => [
            'url' => 'https://www.instagram.com/alojamientosdinahuapi/?igsh=MWExajAzaGxzZmxjYQ%3D%3D#',
            'icon' => 'bi bi-instagram',
        ],
        'whatsapp' => [
            'url' => 'https://wa.me/5492944597081',
            'icon' => 'bi bi-whatsapp',
        ],
    ],
];
