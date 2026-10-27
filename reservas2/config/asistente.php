<?php

// Non-secret settings. params-local.php can override the "asistente" array for FTP installs.
$readInt = function ($name, $default, $min, $max) {
    $value = getenv($name);
    return $value !== false && ctype_digit($value)
        ? max($min, min($max, (int) $value)) : $default;
};
return [
    'proveedor' => getenv('ASISTENTE_PROVEEDOR') === false ? 'gemini' : getenv('ASISTENTE_PROVEEDOR'),
    'openaiModelo' => getenv('ASISTENTE_OPENAI_MODELO') === false ? 'gpt-5.4-mini' : getenv('ASISTENTE_OPENAI_MODELO'),
    'turnos' => $readInt('ASISTENTE_TURNOS', 6, 1, 6),
    'bytes' => $readInt('ASISTENTE_BYTES', 24576, 4096, 24576),
    'ttl' => $readInt('ASISTENTE_TTL', 1800, 60, 1800),
    'porMinuto' => $readInt('ASISTENTE_POR_MINUTO', 5, 1, 100),
    'porDia' => $readInt('ASISTENTE_POR_DIA', 60, 1, 10000),
    'timeout' => $readInt('ASISTENTE_TIMEOUT', 60, 1, 60),
];
