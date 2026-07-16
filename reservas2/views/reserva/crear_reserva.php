<?php
use yii\base\DynamicModel;
use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;

/** @var \yii\web\View $this */
/** @var \app\models\Cabana[] $cabanas */
/** @var string $desde */
/** @var string $hasta */
/** @var DynamicModel $formModel */

$this->title = Yii::t('app', 'Crear reserva');
?>

<h2 class="mb-4 text-center"><?= Yii::t('app', 'Nueva Reserva') ?></h2>
<h3 class="mb-4 text-center"><?= Yii::t('app', 'Cabañas seleccionadas a reservar') ?></h3>
<!-- (warning) -->
<div class="alert alert-warning d-flex align-items-center" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-3"></i>
    <div>
        <?= Yii::t('app', 'Por favor, verifique los datos antes de continuar:') ?>
    </div>
</div>

<?php
/*
$totales = \app\models\CabanaTarifa::calcularTotalesParaCabanas(
    array_map(fn($c) => $c->id, $cabanas),
    $desde,
    $hasta
);
*/
// --- Calcular totales, fechas y resumen ---
$totales = \app\models\CabanaTarifa::calcularTotalesParaCabanas(
    array_map(function ($c) {
        return $c->id;
    }, $cabanas),
    $desde,
    $hasta
);

$totalGeneral = 0;
$paxAcumulado = 0;
$dias = 0;
$fechaIngreso = null;
$fechaEgreso = null;

// --- Calcular días y fechas de ingreso/egreso ---
$d = \DateTime::createFromFormat('d/m/Y', $desde)
    ?: \DateTime::createFromFormat('d-m-Y', $desde)
    ?: \DateTime::createFromFormat('Y-m-d', $desde);

$h = \DateTime::createFromFormat('d/m/Y', $hasta)
    ?: \DateTime::createFromFormat('d-m-Y', $hasta)
    ?: \DateTime::createFromFormat('Y-m-d', $hasta);

if ($d && $h) {
    $dias = $d->diff($h)->days + 1;
    $fechaIngreso = (clone $d)->setTime(14, 0); // ejemplo checkin
    $fechaEgreso = (clone $h)->modify('+1 day')->setTime(11, 0); // ejemplo checkout
}
?>

<?php foreach ($cabanas as $cabana): ?>
    <?= $this->render('@app/views/disponibilidad/_cabana_card', [
        'model' => $cabana,
        'totales' => $totales,
        'desde' => $desde,
        'hasta' => $hasta,
        'mostrarSwitch' => false, // no mostrar switch
    ]) ?>

    <?php
    $valor = $totales[$cabana->id] ?? 0;
    $totalGeneral += $valor;
    $paxAcumulado += (int) $cabana->max_pax;
?>
<?php endforeach; ?>

<!-- 🔹 Renderizar el partial reutilizable -->
<?= $this->render('@app/views/disponibilidad/_resumen_solicitud', [
    'cabanas' => $cabanas,
    'desde' => $desde,
    'hasta' => $hasta,
    'dias' => $dias,
    'fechaIngreso' => $fechaIngreso,
    'fechaEgreso' => $fechaEgreso,
    'paxAcumulado' => $paxAcumulado,
    'totalGeneral' => $totalGeneral,
]) ?>

<hr class="my-4">

<h3 class="mb-4 text-center">
    <?= Yii::t('app', 'Luego, complete el formulario con los datos del cliente:') ?>
</h3>
<!-- Form de registro del cliente y pago -->
<?php $af = ActiveForm::begin([
    'action' => ['solicitar-reserva','first_post' => "0"],
    'method' => 'post',
    'options' => ['enctype' => 'multipart/form-data']
]); ?>

<?= Html::hiddenInput('desde', $desde) ?>
<?= Html::hiddenInput('hasta', $hasta) ?>

<?php foreach ($cabanas as $cabana): ?>
    <?= Html::hiddenInput('seleccionadas[]', $cabana->id) ?>
<?php endforeach; ?>


<div class="row g-3">
    <div class="col-md-6">
        <?= $af->field($formModel, 'denominacion')
            ->textInput([
                'maxlength' => 100,
                'required' => true,
                'value' => $formModel->denominacion,
            ])->label(Yii::t('app', 'Denominación')) ?>
    </div>
    <div class="col-md-6">
        <?= $af->field($formModel, 'documento')
            ->textInput(['maxlength' => 45, 'required' => true])
            ->label(Yii::t('app', 'Documento')) ?>
    </div>
    <div class="col-md-6">
        <?= $af->field($formModel, 'email')
            ->input('email', [
                'maxlength' => 45,
                //'required' => true,
                'value' => $formModel->email,
            ])->label(Yii::t('app', 'Email')) ?>
    </div>
    <div class="col-md-6">
        <?= $af->field($formModel, 'telefono')
            ->textInput(['maxlength' => 45, 'required' => true])
            ->label(Yii::t('app', 'Teléfono')) ?>
    </div>
    <div class="col-md-12">
        <?= $af->field($formModel, 'domicilio')
            ->textInput(['maxlength' => 100])
            ->label(Yii::t('app', 'Domicilio')) ?>
    </div>
    <div class="col-md-12">
        <?= $af->field($formModel, 'obs')
            ->textarea(['rows' => 4])
            ->hint(Yii::t('app', 'Lo que se escriba aqui es una nota que solo vera el administrador.'))
            ->label(Yii::t('app', 'Observaciones')) ?>
    </div>
</div>

<div class="row g-3 mt-2">
    <div class="col-md-6">
        <?php
        $minMonto = round($totalGeneral * 0.10, 2);
        $maxMonto = round($totalGeneral, 2);
        ?>
        <?= $af->field($formModel, 'monto')->input('number', [
            'step' => '0.01',
            //'min' => number_format($minMonto, 2, '.', ''),
            //'max' => number_format($maxMonto, 2, '.', ''),
            'required' => true
        ])->hint(
                Yii::t('app', 'Mínimo {min} / Máximo {max}', [
                    'min' => '$ ' . number_format($minMonto, 2, ',', '.'),
                    'max' => '$ ' . number_format($maxMonto, 2, ',', '.'),
                ])
            )->label(Yii::t('app', 'Monto depositado')) ?>
    </div>
    <div class="col-md-6">
        <?= $af->field($formModel, 'comprobante')
            ->fileInput(['accept' => '.png,.jpg,.jpeg,.pdf'])
            ->label(Yii::t('app', 'Adjuntar comprobante (png/jpg/pdf, máx 5MB)')) ?>
    </div>
</div>

<div class="row">
    <?= $af->field($formModel, 'nota')
        ->textarea(['rows' => 4, 'maxlength' => 500])
        ->hint(Yii::t('app', 'Lo que escriba aqui sera visto por el pasajero cuando vea el estado de su reserva.'))
        ->label(Yii::t('app', 'Enviar mensaje al pasajero:')) ?>

</div>

<div class="row mt-3">
    <div class="col-12">
        <?= $af->field($formModel, 'send_email', [
            'options' => ['class' => 'mb-0'],
        ])->checkbox([
            'label' => Yii::t('app', 'Enviar email al pasajero:'),
            'class' => 'form-check-input',
            'labelOptions' => ['class' => 'form-check-label fs-4 fw-semibold'],
            'uncheck' => 0,
            'value' => 1,
        ]) ?>
    </div>
</div>


<div class="text-center mt-4">
    <?= Html::submitButton('<i class="bi bi-send"></i> ' . Yii::t('app', 'Crear Reserva'), [
        'class' => 'btn btn-primary btn-lg px-5'
    ]) ?>
</div>

<?php ActiveForm::end(); ?>
