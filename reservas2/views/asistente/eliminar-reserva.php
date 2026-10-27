<?php
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
$this->title = 'Eliminar reserva';
$modal = $modal ?? false;
$action = ['asistente/eliminar-reserva', 'borrador' => $draft['token'] ?? ''];
if ($modal) { $action['modal'] = '1'; }
$money = function ($value) { return $value === null ? 'No informado' : '$ ' . number_format($value, 2, ',', '.'); };
$date = function ($value) { return (new DateTimeImmutable($value))->format('d/m/Y H:i'); };
$impact = $draft['review'] ?? $quote;
?>
<div class="<?= $modal ? 'asistente-pago-formulario' : 'container py-3' ?>">
<?php if (!$modal): ?><h1 class="h4"><?= Html::encode($this->title) ?></h1><?php endif ?>
<?php if ($error): ?><p class="alert alert-danger" role="alert"><?= Html::encode($error) ?></p><?php endif ?>
<?php if (!empty($draft['completed'])): ?>
    <p class="alert alert-success">Reserva #<?= (int) $draft['completed'] ?>: <?= !empty($draft['eliminada']) ? 'eliminada; las solicitudes y sus pagos se conservaron desvinculados.' : 'ya no está registrada. No se realizaron cambios adicionales.' ?></p>
    <?= Html::a('Volver al chat', ['asistente/index'], ['class' => 'btn btn-primary', 'target' => '_top']) ?>
<?php elseif ($impact): ?>
    <h2 class="h5">Revisar eliminación de la reserva #<?= (int) $impact['id_reserva'] ?></h2>
    <p><strong>Titular:</strong> <?= Html::encode($impact['titular']) ?><br>
        <strong>Ingreso:</strong> <?= Html::encode($date($impact['desde'])) ?><br>
        <strong>Egreso:</strong> <?= Html::encode($date($impact['hasta'])) ?><br>
        <strong>Estado:</strong> <?= Html::encode($impact['estado']) ?></p>
    <p><strong>Cabañas:</strong> <?= $impact['cabanas'] ? Html::encode(implode(', ', array_map(function ($cabin) { return 'Cabaña ' . $cabin['numero']; }, $impact['cabanas']))) : 'Sin cabañas vinculadas' ?></p>
    <p class="alert alert-warning">Se eliminarán esta reserva y su ocupación de cabañas. Se conservarán el titular, las solicitudes, conversaciones, observaciones, pagos y comprobantes. Las solicitudes quedarán sin reserva asociada.</p>
    <?php if ($impact['requests']): ?>
        <h3 class="h6">Solicitudes y pagos que se conservarán</h3>
        <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Solicitud</th><th>Total</th><th>Pagado registrado</th><th>Movimientos</th></tr></thead><tbody>
        <?php foreach ($impact['requests'] as $request): ?>
            <tr><td>#<?= (int) $request['id'] ?></td><td><?= Html::encode($money($request['total'])) ?></td><td><?= Html::encode($money($request['pagado'])) ?></td><td><?= $request['movimientos'] === null ? 'No informado' : (int) $request['movimientos'] ?></td></tr>
        <?php endforeach ?></tbody></table></div>
    <?php else: ?><p>Sin solicitudes asociadas.</p><?php endif ?>
    <?php if (!empty($draft['review'])): ?>
        <?= Html::beginForm($action, 'post') ?>
        <?= Html::hiddenInput('fase', 'confirmar') ?>
        <?= Html::submitButton('Confirmar eliminación de esta reserva', ['class' => 'btn btn-danger']) ?>
        <?= Html::endForm() ?>
        <?= Html::beginForm($action, 'post', ['class' => 'mt-2']) ?>
        <?= Html::hiddenInput('fase', 'revisar') ?>
        <?= Html::submitButton('Volver a revisar el impacto', ['class' => 'btn btn-outline-secondary']) ?>
        <?= Html::endForm() ?>
    <?php else: ?>
        <?php $active = ActiveForm::begin(['action' => $action, 'enableClientScript' => !$modal]); ?>
        <?= Html::hiddenInput('fase', 'revisar') ?>
        <?= $active->field($form, 'revisado')->checkbox() ?>
        <?= Html::submitButton('Revisar y continuar', ['class' => 'btn btn-danger']) ?>
        <?php ActiveForm::end(); ?>
    <?php endif ?>
<?php endif ?>
</div>
