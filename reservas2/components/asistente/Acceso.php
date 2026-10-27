<?php
namespace app\components\asistente;

use app\config\Niveles;
use app\models\Identificador;
use yii\web\ForbiddenHttpException;

class Acceso
{
    public static function permitido($identity)
    {
        return $identity !== null && (string) $identity->activo === '1'
            && $identity->nivel !== null
            && Identificador::autorizarPorNivel($identity, Niveles::ADMIN)['auth'];
    }

    public static function exigir($identity)
    {
        if (!self::permitido($identity)) {
            throw new ForbiddenHttpException('Acceso exclusivo para administradores.');
        }
    }
}
