<?php

namespace app\models;

use \app\models\base\RequestReserva as BaseRequestReserva;
use Yii;
use yii\helpers\FileHelper;

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
     * Devuelve true si $texto contiene "@emailFalso" (sin importar mayúsculas/minúsculas).
     */
    public static function contieneEmailFalso(?string $texto): bool
    {
        if ($texto === null || $texto === '') {
            return false;
        }

        return stripos($texto, '@emailFalso') !== false;
    }


    /**
     * Envía el mail de cambio de estado de la solicitud.
     *
     * @throws \RuntimeException si falla el envío del correo
     */
    public static function enviarMailCambioEstado(RequestReserva $recReserva): void
    {

        if (self::contieneEmailFalso($recReserva->email)) {
            return;
        }

        $trackingUrl = Yii::$app->urlManager->createAbsoluteUrl([
            'disponibilidad/seguimiento',
            'hash' => $recReserva->hash,
        ]);

        $body = Yii::$app->controller->renderPartial(
            '@app/views/request-reserva/mail_cambio_estado',
            [
                'reqReserva' => $recReserva,
                'trackingUrl' => $trackingUrl,
            ]
        );

        $fromEmail = Yii::$app->params['senderEmail'] ?? null;
        $fromName = Yii::$app->params['senderName'] ?? 'Reservas';

        if (!$fromEmail) {
            Yii::warning('senderEmail no configurado; no se envía correo de cambio de estado.', __METHOD__);
            throw new \RuntimeException('Email del remitente no configurado.');
        }

        $bccEmail = Yii::$app->params['bccEmail'] ?? null;

        // Armamos el mensaje una sola vez
        $message = Yii::$app->mailer->compose()
            ->setFrom([$fromEmail => $fromName])
            ->setTo($recReserva->email)
            ->setSubject(
                Yii::t('app', 'Estado de su Solicitud de Reserva a: ')
                . $recReserva->estado->descr
                . ' '
                . $recReserva->codigo_reserva
            )
            ->setHtmlBody($body);

        if ($bccEmail) {
            $message->setBcc($bccEmail);
        }

        try {
            $ok = $message->send();
        } catch (\Throwable $e) {
            Yii::error(
                'Error técnico al enviar mail de cambio de estado: ' . $e->getMessage(),
                __METHOD__
            );
            throw new \RuntimeException(Yii::t('app', 'No se pudo enviar el email de cambio de estado.'));
        }

        if (!$ok) {
            Yii::error('send() devolvió false al enviar mail de cambio de estado.', __METHOD__);
            throw new \RuntimeException(Yii::t('app', 'No se pudo enviar el email de cambio de estado.'));
        }
    }


    public function beforeDelete()
    {
        if (!parent::beforeDelete()) {
            return false;
        }

        $this->eliminarComprobantesDePago();

        return true;
    }

    private function eliminarComprobantesDePago(): void
    {
        $registros = is_array($this->registro_pagos) ? $this->registro_pagos : [];
        if (empty($registros)) {
            return;
        }

        // Directorios permitidos (seguridad)
        $allowedPrivateDir = FileHelper::normalizePath(Yii::getAlias('@runtime/priv_comprobantes'));
        $allowedTmpDir = FileHelper::normalizePath(Yii::getAlias('@webroot/uploads_tmp/comprobantes'));

        foreach ($registros as $pago) {
            $archivo = $pago['archivo'] ?? null;
            if (!$archivo || !is_string($archivo)) {
                continue;
            }

            // Si quedó como URL pública temporal
            if (str_starts_with($archivo, '/uploads_tmp/comprobantes/')) {
                $pathFs = Yii::getAlias('@webroot') . $archivo;
            } else {
                // En tu actionAgregarPago lo terminás guardando como ruta FS privada
                $pathFs = $archivo;
            }

            if (!$pathFs) {
                continue;
            }

            $pathFsNorm = FileHelper::normalizePath($pathFs);

            // Seguridad: solo borramos dentro de los dirs esperados
            $isAllowed =
                str_starts_with($pathFsNorm, $allowedPrivateDir . DIRECTORY_SEPARATOR) ||
                str_starts_with($pathFsNorm, $allowedTmpDir . DIRECTORY_SEPARATOR);

            if (!$isAllowed) {
                Yii::warning("Se evitó borrar archivo fuera de rutas permitidas: {$pathFsNorm}", __METHOD__);
                continue;
            }

            if (is_file($pathFsNorm)) {
                @unlink($pathFsNorm);
            }
        }
    }

}
