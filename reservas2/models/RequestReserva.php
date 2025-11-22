<?php

namespace app\models;

use \app\models\base\RequestReserva as BaseRequestReserva;
use Yii;

/**
 * This is the model class for table "request_reservas".
 */
class RequestReserva extends BaseRequestReserva
{
    public function getRequestResponses()
    {
        return $this->hasMany(\app\models\RequestResponse::class, ['id_request' => 'id'])
            ->orderBy(['fecha' => SORT_ASC, 'id' => SORT_ASC]);
    }


    public static function vencida($id, $now)
    {
        $rr = self::findOne($id);

        $estado = $rr->estado;

        // 👉 Configuración general RESERVA_CFG
        $cfg = ParametrosGenerales::getParametro('RESERVA_CFG')->valor ?? [];
        $email_token_expira = (int) ($cfg['max_reintentos']['email_token_expira'] ?? 48);
        $fecha_expira = (new \DateTime($rr->fecha))->modify("+{$email_token_expira} hours");
        $confirmar_pago_expira = (int) ($cfg['max_horas_venc']['confirmar_pago'] ?? 48);
        $fecha_confirmar_pago_expira = (new \DateTime($rr->fecha))->modify("+{$confirmar_pago_expira} hours");

        if (!$now) {
            $now = new \DateTime();
        }


        if ($estado->slug == 'pendiente-email-verificar') {

            if ($now > $fecha_expira) {
                return [
                    'status' => 'vencida',
                    'estado' => $estado->slug,
                    'msg' => Yii::t('app', 'La verificacion del email ha expirado'),
                ];
            }

        }

        if ($estado->slug == 'pendiente-email-contestado' || $estado->slug == 'pendiente-email-verificado') {

            if ($now > $fecha_confirmar_pago_expira) {
                return [
                    'status' => 'vencida',
                    'estado' => $estado->slug,
                    'msg' => Yii::t('app', 'El plazo para confirmar el pago ha expirado'),
                ];
            }

        }


        return [
            'status' => 'OK',
            'estado' => $estado->slug,
            'msg' => '',
        ];


    }
}
