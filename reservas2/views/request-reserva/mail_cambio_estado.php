<?php
use yii\bootstrap5\Html;

/** @var \app\models\RequestReserva $reqReserva */
/** @var string $trackingUrl */

$fmt = clone Yii::$app->formatter;
$fmt->defaultTimeZone = $fmt->timeZone; // asumimos que lo guardado ya está en hora local

$desdeTxt = $fmt->asDatetime($reqReserva->desde, 'php:d/m/Y H:i');
$hastaTxt = $fmt->asDatetime($reqReserva->hasta, 'php:d/m/Y H:i');
?>

<h2 style="color:#2c3e50;"><?= Yii::t('app', 'Mensaje de solicitud de reserva') ?></h2>

<p><?= Yii::t('app', 'Datos y resumen de la reserva solicitada:') ?></p>

<table style="border-collapse:collapse; width:100%; max-width:600px;">
    <tbody>
        <tr>
            <td style="padding:6px 10px; color:#555;"><strong><?= Yii::t('app', 'Nombre') ?>:</strong></td>
            <td style="padding:6px 10px;"><?= Html::encode($reqReserva->denominacion) ?></td>
        </tr>
        <tr>
            <td style="padding:6px 10px; color:#555;"><strong><?= Yii::t('app', 'Email') ?>:</strong></td>
            <td style="padding:6px 10px;"><?= Html::encode($reqReserva->email) ?></td>
        </tr>
        <tr>
            <td style="padding:6px 10px; color:#555;"><strong><?= Yii::t('app', 'Período') ?>:</strong></td>
            <td style="padding:6px 10px;"><?= $desdeTxt ?> → <?= $hastaTxt ?></td>
        </tr>
        <tr>
            <td style="padding:6px 10px; color:#555;"><strong><?= Yii::t('app', 'Total estimado') ?>:</strong></td>
            <td style="padding:6px 10px;">$ <?= number_format((float) $reqReserva->total, 2, ',', '.') ?></td>
        </tr>
    </tbody>
</table>

<hr style="margin:20px 0; border:none; border-top:1px solid #ccc;">

<p>
    <?= Yii::t('app', 'El estado de su solicitud ha cambiado a:') ?><br>
    <strong><?= Html::encode($reqReserva->estado->descr) ?></strong>
</p>


<p style="margin-top:15px;">
    <?= Yii::t('app', 'También puede seguir el estado de su solicitud en el siguiente enlace:') ?><br>
    <a href="<?= Html::encode($trackingUrl) ?>"><?= Html::encode($trackingUrl) ?></a>
</p>

<p style="margin-top:25px; color:#777; font-size:13px;">
    <?= Yii::t('app', 'Este mensaje fue generado automáticamente, por favor no responda a este correo.') ?>
</p>