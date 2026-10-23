<?php

return [
    'smtp.host' => getenv('SMTP_HOST') ?: '',
    'smtp.user' => getenv('SMTP_USER') ?: '',
    'smtp.pass' => getenv('SMTP_PASSWORD') ?: '',
    'smtp.port' => (int) (getenv('SMTP_PORT') ?: 465),
    'smtp.encryption' => getenv('SMTP_ENCRYPTION') ?: 'ssl',
    'bank' => [
        'banco' => '',
        'titular' => '',
        'cbu' => '',
        'alias' => '',
        'cuit' => '',
        'tipo' => '',
        'nro' => '',
    ],
];
