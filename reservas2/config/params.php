<?php

return [
    'idAPP' => 'bt5',
    'bsVersion' => '5.x',
    'adminEmail' => 'test@dinasis.com.es',
    'senderEmail' => 'no-reply@tudominio.test',   // o el remitente que admita tu SMTP
    'senderName' => 'Sistema de Reservas',
    'sessionTimeoutSeconds' => '7200',
    'expireSessionMin' => 24 * 60,
    'user.passwordResetTokenExpire' => 3600,
    'supportedLanguages' => [
        'es' => 'Español',
        'en' => 'English',
        'pt-BR' => 'Português (Brasil)',
    ],
];
