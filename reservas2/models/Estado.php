<?php

namespace app\models;

use \app\models\base\Estado as BaseEstado;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "estados".
 */
class Estado extends BaseEstado
{

    public static function getSegunEstado($requestReserva)
    {
        $resp = [];
        if (!$requestReserva) {
            return $resp;
        }

        switch ($requestReserva->estado->slug) {
            case 'pendiente-email-verificar':
                return $resp;
            case 'pendiente-email-verificado':
            case 'pendiente-email-contestado':
            case 'confirmado-verificar-pago':
                return ArrayHelper::map(
                    self::find()
                        ->where(['slug' => ['confirmado', 'rechazado']])
                        ->orderBy('descr')->asArray()->all(),
                    'id',
                    'descr'
                );
            case 'confirmado':
                return ArrayHelper::map(
                    self::find()
                        ->where(['slug' => ['confirmado-verificar-pago', 'rechazado']])
                        ->orderBy('descr')->asArray()->all(),
                    'id',
                    'descr'
                );
            case 'rechazado':
                return ArrayHelper::map(
                    self::find()
                        ->where(['slug' => ['confirmado-verificar-pago', 'confirmado']])
                        ->orderBy('descr')->asArray()->all(),
                    'id',
                    'descr'
                );
            default:
                return $resp;
        }
    }
}
