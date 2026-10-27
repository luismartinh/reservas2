<?php
namespace app\components\asistente;

/** Data-only, bounded reader. Never executes a file, expands variables or writes values to logs. */
class EntornoPrivado
{
    const ARCHIVO = '/home/vaqxtmgw/reservas.env';

    public static function valor($name, $file = self::ARCHIVO)
    {
        if (!in_array($name, ['ASISTENTE_PROVEEDOR', 'ASISTENTE_OPENAI_MODELO', 'OPENAI_API_KEY', 'ASISTENTE_AUDIO_HABILITADO', 'ASISTENTE_AUDIO_MODELO'], true)) {
            throw new \InvalidArgumentException('unsupported_private_setting');
        }
        $env = getenv($name);
        if ($env !== false) { return $env; } // Explicitly empty/invalid values never fall back to a file.
        try {
            if (!is_string($file) || !@is_file($file) || !@is_readable($file)) { return null; }
            $contents = @file_get_contents($file, false, null, 0, 16385);
            if ($contents === false || strlen($contents) > 16384) { return ''; }
            $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents);
            $found = false;
            $value = null;
            foreach (preg_split('/\r\n|\n|\r/', $contents) as $line) {
                if (!preg_match('/^\s*' . preg_quote($name, '/') . '\s*=\s*(.*?)\s*$/', $line, $match)) { continue; }
                if ($found) { return ''; }
                $found = true;
                $value = $match[1];
                if (strlen($value) >= 2 && ($value[0] === '"' || $value[0] === "'")) {
                    if (substr($value, -1) !== $value[0]) { return ''; }
                    $value = substr($value, 1, -1);
                }
                if (strpbrk($value, "\"'`$#\\") !== false) { return ''; }
            }
            return $value;
        } catch (\Throwable $e) { return ''; }
    }
}
