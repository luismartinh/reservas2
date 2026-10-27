<?php
namespace app\components\asistente;

use Yii;

class Configuracion
{
    public static function leer()
    {
        $defaults = require Yii::getAlias('@app/config/asistente.php');
        $values = array_merge($defaults, Yii::$app->params['asistente'] ?? []);
        $bounds = ['turnos' => [1, 6], 'bytes' => [4096, 24576], 'ttl' => [60, 1800],
            'porMinuto' => [1, 100], 'porDia' => [1, 10000], 'timeout' => [1, 60]];
        foreach ($bounds as $name => $range) {
            $values[$name] = is_numeric($values[$name])
                ? max($range[0], min($range[1], (int) $values[$name])) : $defaults[$name];
        }
        foreach (['ASISTENTE_PROVEEDOR' => 'proveedor', 'ASISTENTE_OPENAI_MODELO' => 'openaiModelo'] as $env => $setting) {
            $private = EntornoPrivado::valor($env);
            if ($private !== null) { $values[$setting] = $private; }
        }
        if (!is_string($values['proveedor'])) { throw new \InvalidArgumentException('invalid_provider'); }
        $values['proveedor'] = strtolower(trim($values['proveedor']));
        ProveedorFactory::metadata($values); // Unknown provider/model fails closed; no silent fallback.
        return $values;
    }
}
