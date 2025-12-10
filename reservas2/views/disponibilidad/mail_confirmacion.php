<?php
use yii\bootstrap5\Html;

/** @var \app\models\RequestReserva $reserva */
/** @var string $confirmUrl */
/** @var string $trackingUrl */

$fmt = clone Yii::$app->formatter;
$fmt->defaultTimeZone = $fmt->timeZone; // asumimos que lo guardado ya está en hora local

$desdeTxt = $fmt->asDatetime($reserva->desde, 'php:d/m/Y H:i');
$hastaTxt = $fmt->asDatetime($reserva->hasta, 'php:d/m/Y H:i');
?>

<h2 style="color:#2c3e50;"><?= Yii::t('app', 'Mensaje de solicitud de reserva') ?></h2>

<p><?= Yii::t('app', 'Datos y resumen de la reserva solicitada:') ?></p>

<table style="border-collapse:collapse; width:100%; max-width:600px;">
    <tbody>
        <tr>
            <td style="padding:6px 10px; color:#555;"><strong><?= Yii::t('app', 'Nombre') ?>:</strong></td>
            <td style="padding:6px 10px;"><?= Html::encode($reserva->denominacion) ?></td>
        </tr>
        <tr>
            <td style="padding:6px 10px; color:#555;"><strong><?= Yii::t('app', 'Codigo de reserva') ?>:</strong></td>
            <td style="padding:6px 10px;"><?= Html::encode($reserva->codigo_reserva) ?></td>
        </tr>
        <tr>
            <td style="padding:6px 10px; color:#555;"><strong><?= Yii::t('app', 'Período') ?>:</strong></td>
            <td style="padding:6px 10px;"><?= $desdeTxt ?> → <?= $hastaTxt ?></td>
        </tr>
        <tr>
            <td style="padding:6px 10px; color:#555;"><strong><?= Yii::t('app', 'Total estimado') ?>:</strong></td>
            <td style="padding:6px 10px;">$ <?= number_format((float) $reserva->total, 2, ',', '.') ?></td>
        </tr>
    </tbody>
</table>

<hr style="margin:20px 0; border:none; border-top:1px solid #ccc;">

<p>
    <strong><?= Yii::t('app', 'Confirme su dirección de correo electrónico:') ?></strong><br>
    <a href="<?= Html::encode($confirmUrl) ?>"
        style="display:inline-block; background:#28a745; color:#fff; padding:10px 16px; border-radius:4px; text-decoration:none;">
        <?= Yii::t('app', 'Haga clic aquí para confirmar su email') ?>
    </a>
</p>

<p style="margin-top:15px;">
    <?= Yii::t('app', 'También puede seguir el estado de su solicitud en el siguiente enlace:') ?><br>
    <a href="<?= Html::encode($trackingUrl) ?>"><?= Html::encode($trackingUrl) ?></a>
</p>

<p style="margin-top:25px; color:#777; font-size:13px;">
    <?= Yii::t('app', 'Este mensaje fue generado automáticamente, por favor no responda a este correo.') ?>
</p>