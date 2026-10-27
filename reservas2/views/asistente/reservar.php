<?php
use yii\helpers\Html;
use yii\helpers\Url;
use yii\bootstrap5\ActiveForm;
$this->title = 'Completar reserva';
$money = function ($value) { return '$ ' . number_format((float) $value, 2, ',', '.'); };
$date = function ($value) { return (new DateTimeImmutable($value))->format('d/m/Y H:i'); };
?>
<div class="container py-3">
<h1 class="h4"><?= Html::encode($this->title) ?></h1>
<p class="small text-body-secondary">Formulario privado. Estos datos no se envían a la IA. La propuesta vence a los 15 minutos y no bloquea cabañas hasta confirmar.</p>
<?php if ($error): ?><p class="alert alert-danger" role="alert"><?= Html::encode($error) ?></p><?php endif ?>
<?php if (!empty($draft['completed'])): ?>
    <p class="alert alert-success">Reserva #<?= (int) $draft['completed'] ?> registrada sin pago inicial. No se registraron pagos ni comprobantes.</p>
    <?= Html::a('Volver al chat', ['asistente/index'], ['target' => '_top', 'class' => 'btn btn-primary']) ?>
<?php elseif (!empty($draft['review'])): $review = $draft['review']; ?>
    <h2 class="h5">Revisar antes de confirmar</h2>
    <p><strong><?= Html::encode($review['customer']['denominacion']) ?></strong> · Documento <?= Html::encode($review['customer']['documento']) ?><br>
        <?= Html::encode($review['customer']['email']) ?> · <?= Html::encode($review['customer']['telefono']) ?></p>
    <?php if ($review['existing']): ?><p class="small">Se utilizará el pasajero existente sin modificar sus datos.</p><?php endif ?>
    <?php if (!empty($review['location'])): ?><p>Ubicación: <?= Html::encode($review['location']) ?></p><?php endif ?>
    <?php if (!empty($review['referencia'])): ?><p>Referencia de la solicitud: <?= Html::encode($review['referencia']) ?></p><?php endif ?>
    <div class="table-responsive"><table class="table table-sm">
    <thead><tr><th>Cabaña</th><th>Ingreso</th><th>Egreso</th><th>Días</th><th>Total</th></tr></thead><tbody>
    <?php foreach ($review['quote']['rows'] as $row): ?>
        <tr><th><?= Html::encode('Cabaña ' . $row['numero'] . ' · cap. ' . $row['max_pax']) ?></th>
        <td><?= Html::encode($date($row['ingreso'])) ?></td><td><?= Html::encode($date($row['egreso'])) ?></td>
        <td><?= (int) $review['quote']['dias'] ?></td><td><?= Html::encode($money($row['total'])) ?></td></tr>
    <?php endforeach ?></tbody></table></div>
    <p><strong>Total: <?= Html::encode($money($review['quote']['total'])) ?></strong> · Pago inicial: <?= Html::encode($money(0)) ?></p>
    <p><?= Html::encode($review['obs']) ?></p>
    <p>Estado: Confirmado (reserva activa). Sin pago registrado. Se volverán a comprobar disponibilidad, horarios e importes al guardar.</p>
    <?= Html::beginForm(Url::to(['asistente/reservar', 'borrador' => $draft['token']]), 'post') ?>
    <?= Html::hiddenInput('fase', 'confirmar') ?>
    <?= Html::submitButton('Confirmar y guardar reserva sin pago inicial', ['class' => 'btn btn-primary']) ?>
    <?= Html::endForm() ?>
    <?= Html::beginForm(Url::to(['asistente/reservar', 'borrador' => $draft['token']]), 'post', ['class' => 'mt-2']) ?>
    <?= Html::hiddenInput('fase', 'revisar') ?>
    <?= Html::submitButton('Volver a completar los datos', ['class' => 'btn btn-outline-secondary']) ?>
    <?= Html::endForm() ?>
<?php elseif (!empty($draft['token'])): ?>
    <p>Ingreso: <?= Html::encode((new DateTimeImmutable($draft['proposal']['desde']))->format('d/m/Y')) ?> · Salida: <?= Html::encode((new DateTimeImmutable($draft['proposal']['hasta']))->format('d/m/Y')) ?> (horarios según cada cabaña).</p>
    <p>Cabañas seleccionadas: <?= Html::encode(implode(', ', $draft['proposal']['numeros_cabanas'])) ?></p>
    <p>Si el documento ya existe, se usarán sus datos actuales. Para un pasajero nuevo, completá nombre, documento, email y teléfono.</p>
    <?php $active = ActiveForm::begin(['action' => ['asistente/reservar', 'borrador' => $draft['token']]]); ?>
    <?= Html::hiddenInput('fase', 'revisar') ?>
    <?= $active->field($form, 'documento') ?>
    <?= $active->field($form, 'denominacion') ?>
    <?= $active->field($form, 'email')->input('email') ?>
    <?= $active->field($form, 'telefono') ?>
    <?= $active->field($form, 'domicilio') ?>
    <?= $this->render('@app/views/locador/_ubicacion', ['form' => $active, 'model' => $form]) ?>
    <?= $active->field($form, 'referencia')->textInput(['maxlength' => 45]) ?>
    <?= $active->field($form, 'obs')->textarea(['maxlength' => 500]) ?>
    <?= Html::submitButton('Revisar reserva', ['class' => 'btn btn-primary']) ?>
    <?php ActiveForm::end(); ?>
<?php endif ?>
</div>
