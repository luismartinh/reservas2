<?php
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
$this->title = 'Cargar un pago';
$modal = $modal ?? false;
$paymentAction = ['asistente/pago', 'borrador' => $draft['token'] ?? ''];
if ($modal) { $paymentAction['modal'] = '1'; }
$money = function ($cents) { return '$ ' . number_format($cents / 100, 2, ',', '.'); };
?>
<div class="<?= $modal ? 'asistente-pago-formulario' : 'container py-3' ?>">
<?php if (!$modal): ?><h1 class="h4"><?= Html::encode($this->title) ?></h1><?php endif ?>
<p class="small text-body-secondary">Formulario privado. Monto, nota y comprobante no se envían a la IA. La propuesta vence a los 15 minutos.</p>
<?php if ($error): ?><p class="alert alert-danger" role="alert"><?= Html::encode($error) ?></p><?php endif ?>
<?php if (!empty($draft['completed'])): ?>
    <p class="alert alert-success">Pago registrado para la reserva #<?= (int) $draft['completed'] ?>. Repetir la confirmación no vuelve a cargarlo.</p>
    <?= Html::a('Volver al chat', ['asistente/index'], ['target' => '_top', 'class' => 'btn btn-primary']) ?>
<?php elseif (!empty($draft['review'])): $review = $draft['review']; ?>
    <h2 class="h5">Revisar antes de confirmar el pago</h2>
    <p>Reserva #<?= (int) $review['id_reserva'] ?> · Solicitud #<?= (int) $review['id_request'] ?><br>
    Titular: <?= Html::encode($review['titular']) ?></p>
    <table class="table table-sm"><tbody>
        <tr><th>Total de la reserva</th><td><?= Html::encode($money($review['total'])) ?></td></tr>
        <tr><th>Pagado registrado</th><td><?= Html::encode($money($review['pagado'])) ?></td></tr>
        <tr><th>Medio de pago</th><td><?= Html::encode(\app\models\AsistentePagoForm::medios()[$review['medio_pago'] ?? ''] ?? 'No especificado') ?></td></tr>
        <tr><th>Nuevo pago</th><td><strong><?= Html::encode($money($review['monto_centavos'])) ?></strong></td></tr>
        <tr><th>Saldo luego de confirmar</th><td><?= Html::encode($money($review['total'] - $review['pagado'] - $review['monto_centavos'])) ?></td></tr>
    </tbody></table>
    <p><?= Html::encode($review['nota']) ?></p>
    <p><?= $review['archivo'] ? 'Comprobante privado adjunto.' : 'Sin comprobante adjunto.' ?></p>
    <p class="alert alert-warning">Al confirmar, declarás que verificaste la acreditación. Se utiliza el estado Confirmado para la reserva y la solicitud.
        La verificación es global sobre todos los importes registrados; no existe verificación por movimiento.
        <?= $review['verificacion_global'] ? 'Esto también dará por verificado el acumulado registrado anteriormente.' : '' ?></p>
    <?= Html::beginForm($paymentAction, 'post') ?>
    <?= Html::hiddenInput('fase', 'confirmar') ?>
    <?= Html::submitButton('Confirmar y registrar pago verificado', ['class' => 'btn btn-primary']) ?>
    <?= Html::endForm() ?>
    <?= Html::beginForm($paymentAction, 'post', ['class' => 'mt-2']) ?>
    <?= Html::hiddenInput('fase', 'revisar') ?>
    <?= Html::submitButton('Volver a completar el pago', ['class' => 'btn btn-outline-secondary']) ?>
    <?= Html::endForm() ?>
<?php elseif (!empty($draft['token'])): ?>
    <p>Reserva #<?= (int) $draft['proposal']['id_reserva'] ?> · Solicitud #<?= (int) $draft['proposal']['id_request'] ?></p>
    <?php if ($request): ?>
        <p>Titular: <?= Html::encode($request->reserva->locador->denominacion ?? $request->denominacion) ?><br>
        Saldo registrado: <?= Html::encode($money((int) round(((float) $request->total - (float) $request->pagado) * 100))) ?></p>
    <?php endif ?>
    <?php $active = ActiveForm::begin(['action' => $paymentAction, 'enableClientScript' => !$modal, 'options' => ['enctype' => 'multipart/form-data']]); ?>
    <?= Html::hiddenInput('fase', 'revisar') ?>
    <?= $active->field($form, 'monto')->textInput(['inputmode' => 'decimal', 'placeholder' => 'Ejemplo: 15000,50'])->hint('Sin separadores de miles. Se admite coma o punto decimal. No puede superar el saldo registrado.') ?>
    <?= $active->field($form, 'medio_pago')->dropDownList(\app\models\AsistentePagoForm::medios(), ['prompt' => 'Seleccionar']) ?>
    <?= $active->field($form, 'nota')->textarea(['maxlength' => 500]) ?>
    <?= $active->field($form, 'comprobante')->fileInput(['accept' => '.png,.jpg,.jpeg,.pdf'])->hint('Opcional: imagen o PDF de hasta 5 MB. Adjuntar un comprobante no acredita el pago.') ?>
    <?= $active->field($form, 'acreditado')->checkbox() ?>
    <?= Html::submitButton('Revisar pago', ['class' => 'btn btn-primary']) ?>
    <?php ActiveForm::end(); ?>
<?php endif ?>
</div>
