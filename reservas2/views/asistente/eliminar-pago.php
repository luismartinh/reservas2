<?php
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
$this->title = 'Eliminar un pago';
$modal = $modal ?? false;
$action = ['asistente/eliminar-pago', 'borrador' => $draft['token'] ?? ''];
if ($modal) { $action['modal'] = '1'; }
$money = function ($cents) { return '$ ' . number_format($cents / 100, 2, ',', '.'); };
?>
<div class="<?= $modal ? 'asistente-pago-formulario' : 'container py-3' ?>">
<?php if (!$modal): ?><h1 class="h4"><?= Html::encode($this->title) ?></h1><?php endif ?>
<p class="small text-body-secondary">Elegí el pago cargado por error. Sus datos se muestran solo en este formulario privado. La propuesta vence a los 15 minutos.</p>
<?php if ($error): ?><p class="alert alert-danger" role="alert"><?= Html::encode($error) ?></p><?php endif ?>
<?php if (!empty($draft['completed'])): ?>
    <p class="alert alert-success">El pago seleccionado de la reserva #<?= (int) $draft['completed'] ?> fue eliminado. Se actualizó el importe cobrado.</p>
    <?= Html::a('Volver al chat', ['asistente/index'], ['class' => 'btn btn-primary', 'target' => '_top']) ?>
<?php elseif (!empty($draft['review'])): $review = $draft['review']; $payment = $review['pago']; ?>
    <h2 class="h5">Revisar eliminación del pago</h2>
    <p>Reserva #<?= (int) $review['id_reserva'] ?> · Titular: <?= Html::encode($review['titular']) ?></p>
    <table class="table table-sm"><tbody>
        <tr><th>Pago seleccionado</th><td>#<?= (int) $review['indice'] + 1 ?> · <?= Html::encode($payment['fecha']) ?></td></tr>
        <tr><th>Importe a eliminar</th><td><strong><?= Html::encode($money($payment['monto'])) ?></strong></td></tr>
        <tr><th>Medio</th><td><?= Html::encode($payment['medio']) ?></td></tr>
        <tr><th>Cobrado actual</th><td><?= Html::encode($money($review['pagado'])) ?></td></tr>
        <tr><th>Cobrado luego de eliminar</th><td><?= Html::encode($money($review['nuevo_pagado'])) ?></td></tr>
        <tr><th>Saldo registrado resultante</th><td><?= Html::encode($money($review['total'] - $review['nuevo_pagado'])) ?></td></tr>
    </tbody></table>
    <p><?= Html::encode($payment['nota']) ?></p>
    <p class="alert alert-warning">Se quitará este movimiento y se descontará su importe de lo cobrado. La reserva conservará su estado y fechas.
    <?= $payment['comprobante'] ? 'El comprobante asociado se elimina si no está referenciado por otro movimiento.' : '' ?></p>
    <?= Html::beginForm($action, 'post') ?>
    <?= Html::hiddenInput('fase', 'confirmar') ?>
    <?= Html::submitButton('Confirmar eliminación de este pago', ['class' => 'btn btn-danger']) ?>
    <?= Html::endForm() ?>
    <?= Html::beginForm($action, 'post', ['class' => 'mt-2']) ?>
    <?= Html::hiddenInput('fase', 'revisar') ?>
    <?= Html::submitButton('Volver a seleccionar el pago', ['class' => 'btn btn-outline-secondary']) ?>
    <?= Html::endForm() ?>
<?php elseif (!empty($draft['token']) && $request): ?>
    <p>Reserva #<?= (int) $request->id_reserva ?> · Solicitud #<?= (int) $request->id ?><br>
    Titular: <?= Html::encode($request->reserva->locador->denominacion ?? $request->denominacion) ?></p>
    <?php $active = ActiveForm::begin(['action' => $action, 'enableClientScript' => !$modal]); ?>
    <?= Html::hiddenInput('fase', 'revisar') ?>
    <?= $active->errorSummary($form) ?>
    <fieldset><legend class="h6">Seleccioná un pago para eliminar</legend>
    <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Elegir</th><th>Fecha</th><th>Importe</th><th>Medio / nota</th></tr></thead><tbody>
    <?php foreach ($payments as $payment): ?>
        <tr><td><?= Html::radio('AsistenteEliminarPagoForm[indice]', $form->indice !== null && (string) $form->indice === (string) $payment['indice'],
            ['value' => $payment['indice'], 'aria-label' => 'Elegir pago ' . ($payment['indice'] + 1), 'required' => true]) ?></td>
        <td><?= Html::encode($payment['fecha']) ?></td><td><?= Html::encode($money($payment['monto'])) ?></td>
        <td><?= Html::encode($payment['medio']) ?><small class="d-block"><?= Html::encode($payment['nota']) ?></small>
        <?= $payment['comprobante'] ? '<small class="d-block">Comprobante asociado</small>' : '' ?></td></tr>
    <?php endforeach ?></tbody></table></div></fieldset>
    <?= Html::submitButton('Revisar eliminación', ['class' => 'btn btn-danger']) ?>
    <?php ActiveForm::end(); ?>
<?php endif ?>
</div>
