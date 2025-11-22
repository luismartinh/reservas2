<?php
use yii\base\DynamicModel;
use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;
use yii\captcha\Captcha;

/** @var \yii\web\View $this */
/** @var \app\models\Cabana[] $cabanas */
/** @var string $desde */
/** @var string $hasta */
/** @var DynamicModel $formModel */

$this->title = Yii::t('app', 'Solicitar reserva');
?>

<h2 class="mb-4 text-center"><?= Yii::t('app', 'Solicitud de Reserva') ?></h2>
<h3 class="mb-4 text-center"><?= Yii::t('app', 'Cabañas seleccionadas a reservar') ?></h3>
<!-- (warning) -->
<div class="alert alert-warning d-flex align-items-center" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-3"></i>
    <div>
        <?= Yii::t('app', 'Por favor, verifique los datos antes de continuar:') ?>
    </div>
</div>

<?php
// --- Calcular totales, fechas y resumen ---
$totales = \app\models\CabanaTarifa::calcularTotalesParaCabanas(
    array_map(fn($c) => $c->id, $cabanas),
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
    <?= $this->render('_cabana_card', [
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
<?= $this->render('_resumen_solicitud', [
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

<h2 class="mb-4 text-center"><?= Yii::t('app', 'Complete el formulario para enviar la solicitud:') ?></h2>

<div class="alert alert-primary d-flex align-items-center" role="alert">
    <i class="bi bi-info-circle me-3"></i>
    <div>
        <?= Yii::t('app', 'La respuesta de la solicitud se enviará a su correo:') ?>
    </div>
</div>


<?php $form = ActiveForm::begin(['action' => ['enviar-solicitud-reserva'], 'method' => 'post']); ?>

<?= Html::hiddenInput('desde', $desde) ?>
<?= Html::hiddenInput('hasta', $hasta) ?>

<?php foreach ($cabanas as $cabana): ?>
    <?= Html::hiddenInput('seleccionadas[]', $cabana->id) ?>
<?php endforeach; ?>

<div class="row">
    <div class="col-md-6">
        <?= $form->field($formModel, 'denominacion')
            ->textInput(['maxlength' => 100, 'required' => true])
            ->label(Yii::t('app', 'Nombre y Apellido')) ?>
    </div>
    <div class="col-md-6">
        <?= $form->field($formModel, 'email')
            ->input('email', ['maxlength' => 45, 'required' => true])
            ->label(Yii::t('app', 'Email')) ?>
    </div>
</div>

<?= $form->field($formModel, 'nota')
    ->textarea(['rows' => 4, 'maxlength' => 500])
    ->label(Yii::t('app', 'Nota (opcional, algo que quiera consultar)')) ?>

<div class="row justify-content-center mt-4">
    <div class="col-md-6">
        <div class="card border-info shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-2">
                    <i class="bi bi-shield-check me-2"></i>
                    <?= Yii::t('app', 'Verificación humana') ?>
                </h5>

                <p class="text-muted small mb-3">
                    <?= Yii::t('app', 'Para evitar envíos automáticos, por favor escriba los caracteres que ve en la imagen. Si no los distingue, haga clic sobre la imagen para generar una nueva.') ?>
                </p>

                <?= $form->field($formModel, 'verifyCode')->widget(Captcha::class, [
                    'captchaAction' => 'disponibilidad/captcha',
                    'template' => '
                        <div class="row align-items-center g-2 mb-1">
                            <div class="col-5 text-center">
                                {image}
                            </div>
                            <div class="col-7">
                                {input}
                            </div>
                        </div>
                    ',
                    'imageOptions' => [
                        'alt' => Yii::t('app', 'Código de verificación'),
                        'style' => 'cursor:pointer; border-radius:4px;',
                        'title' => Yii::t('app', 'Click para recargar la imagen'),
                    ],
                    'options' => [
                        'class' => 'form-control',
                        'placeholder' => Yii::t('app', 'Ingrese el código aquí'),
                    ],
                ])->label(false)
                    ->hint(Yii::t('app', 'Si el código no se entiende, haga clic en la imagen para cambiarlo.')) ?>
            </div>
        </div>
    </div>
</div>

<div class="text-center mt-4">
    <?= Html::submitButton('<i class="bi bi-send"></i> ' . Yii::t('app', 'Enviar solicitud'), [
        'class' => 'btn btn-primary btn-lg px-5'
    ]) ?>
</div>

<?php ActiveForm::end(); ?>