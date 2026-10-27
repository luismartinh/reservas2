<?php
namespace app\components\asistente;

class ConfiguracionAudio
{
    const MAX_BYTES = 2097152;
    const MAX_SEGUNDOS = 60;

    public static function leer()
    {
        $enabled = EntornoPrivado::valor('ASISTENTE_AUDIO_HABILITADO');
        $model = EntornoPrivado::valor('ASISTENTE_AUDIO_MODELO');
        $model = $model === null ? 'gpt-transcribe' : trim($model);
        return ['habilitado' => $enabled === null || $enabled === '1', 'modelo' => $model];
    }
}
