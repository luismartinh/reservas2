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


    /**
     * Genera un código de reserva amigable (7 caracteres, A-Z y dígitos),
     * verificando que la combinación (email + codigo_reserva) no exista.
     *
     * IMPORTANTE: Método PUBLIC y STATIC.
     *
     * @param string $email   Email para verificar unicidad
     * @param int    $length  Largo del código (7 por defecto)
     * @return string
     */
    public static function generateUniqueCodigoReserva(string $email, int $length = 7): string
    {
        // Conjunto de caracteres permitidos (sin O,0,I,1)
        $chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $maxAttempts = 50;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {

            $code = static::randomStringFromSet($chars, $length);

            $exists = static::find()
                ->where(['email' => $email, 'codigo_reserva' => $code])
                ->exists();

            if (!$exists) {
                return $code;
            }
        }

        throw new \RuntimeException('No se pudo generar un código de reserva único.');
    }

    /**
     * Helper estático para crear string aleatorio desde un set de caracteres.
     */
    protected static function randomStringFromSet(string $charSet, int $length): string
    {
        $result = '';
        $maxIndex = strlen($charSet) - 1;

        for ($i = 0; $i < $length; $i++) {
            $result .= $charSet[random_int(0, $maxIndex)];
        }

        return $result;
    }


    /**
     * Envía el mail de cambio de estado de la solicitud.
     */
    public static function enviarMailCambioEstado(RequestReserva $recReserva): void
    {

        $trackingUrl = Yii::$app->urlManager->createAbsoluteUrl(['disponibilidad/seguimiento', 'hash' => $recReserva->hash]);

        $body = Yii::$app->controller->renderPartial('@app/views/request-reserva/mail_cambio_estado', [
            'reqReserva' => $recReserva,
            'trackingUrl' => $trackingUrl,
        ]);

        $fromEmail = Yii::$app->params['senderEmail'] ?? null;
        $fromName = Yii::$app->params['senderName'] ?? 'Reservas';

        if (!$fromEmail) {
            Yii::warning('senderEmail no configurado; no se envía correo.', __METHOD__);
            return;
        }


        $bccEmail = Yii::$app->params['bccEmail'] ?? null;


        if (!$bccEmail) {
            $ok = Yii::$app->mailer->compose()
                ->setFrom([$fromEmail => $fromName])
                ->setTo($recReserva->email)
                ->setSubject(Yii::t('app', 'Estado de su Solicitud de Reserva a: ') . $recReserva->estado->descr . " " . $recReserva->codigo_reserva)
                ->setHtmlBody($body)
                ->send();
        } else {
            $ok = Yii::$app->mailer->compose()
                ->setFrom([$fromEmail => $fromName])
                ->setTo($recReserva->email)
                ->setBcc($bccEmail)
                ->setSubject(Yii::t('app', 'Estado de su Solicitud de Reserva a: ') . $recReserva->estado->descr . " " . $recReserva->codigo_reserva)
                ->setHtmlBody($body)
                ->send();

        }


        if (!$ok) {
            Yii::warning('Fallo al enviar email', __METHOD__);
        }
    }

}
