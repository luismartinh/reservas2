<?php
use yii\bootstrap5\Html;

/** @var \app\models\RequestReserva $reserva */
/** @var \app\models\Cabana[]       $cabanas */
/** @var string                      $hash */
/** @var int           $email_token_expira_hr */
/** @var Datetime     $fecha_expira */

$this->registerCssFile('@web/css/cabana.css', [
    'depends' => [\yii\bootstrap5\BootstrapAsset::class]
]);


// Mapear totales por id_cabana a partir de request_cabanas
$totales = [];
if (!empty($reserva->requestCabanas)) {
    foreach ($reserva->requestCabanas as $rc) {
        $totales[(int) $rc->id_cabana] = (float) $rc->valor;
    }
}

// Normalizar período (solo fecha para el rango, y datetime para ingreso/egreso)
$desdeDate = new \DateTime(substr($reserva->desde, 0, 10));
$hastaDate = new \DateTime(substr($reserva->hasta, 0, 10));
$dias = (int) $desdeDate->diff($hastaDate)->days + 1;

// Estas ya vienen con hora ajustada desde el controller (min checkin / 23:56:59)
$fechaIngreso = new \DateTime($reserva->desde);
$fechaEgreso = new \DateTime($reserva->hasta);

// Capacidad total
$paxTotal = 0;
foreach ($cabanas as $c) {
    $paxTotal += (int) $c->max_pax;
}

// Total general desde request_cabanas
$totalGeneral = 0.0;
foreach ($cabanas as $c) {
    $totalGeneral += $totales[$c->id] ?? 0.0;
}

$this->title = Yii::t('app', 'Solicitud de Reserva');


?>

<div class="site-index container py-5 py-lg-5">

    <?= $this->render('//partials/_dhBackground') ?>

    <section class="dh-hero mb-5">
        <h2 class="mb-4 text-center"><?= Yii::t('app', 'Solicitud de Reserva') ?></h2>

        <!-- ✅ Fecha (success) -->
        <div class="alert alert-success">

            <h3><?= Yii::t('app', 'Solicitud de Reserva recibida') ?></h3>
            <h4><?= Yii::t('app', 'Enviaremos un correo de confirmación a su email.') ?></h4>
            <h4><strong><?= Yii::t('app', 'Por favor, revise su bandeja de entrada para continuar..') ?></strong></h4>
            <hr>
            <strong><?= Yii::t('app', 'Fecha de la solicitud:') ?>:</strong>
            <?= Yii::$app->formatter->asDatetime($reserva->fecha, 'php:d/m/Y H:i') ?>
        </div>

        <!-- ⚠️ Estado (warning) -->
        <div class="alert alert-warning">
            <strong><?= Yii::t('app', 'Estado') ?>:</strong>
            <?= Html::encode($reserva->estado->descr ?? Yii::t('app', 'Pendiente')) ?>
            <strong><?= Yii::t('app', 'Vencimiento de esta solicitud') ?>:</strong>
            <?= Yii::t('app', 'El ') ?><?= $fecha_expira->format('d/m/Y H:i') ?> (<?= $email_token_expira_hr ?> hs.)
            <br>
            <?= Yii::t('app', 'Una vez vencida, si el email no fue confiramdo, se elimina automáticamente') ?>
        </div>


        <h4><?= count($cabanas) > 1 ? Yii::t('app', 'Cabañas seleccionadas') . ' (' . count($cabanas) . ')' :
            Yii::t('app', 'Cabañas seleccionadas') . ' (' . count($cabanas) . ')' ?> </h4>

        <?php foreach ($cabanas as $cabana): ?>
            <?= $this->render('_cabana_card', [
                'model' => $cabana,
                'totales' => $totales,                           // muestra precio en la card
                'desde' => $desdeDate->format('d-m-Y'),        // para el pie de la card
                'hasta' => $hastaDate->format('d-m-Y'),
                'mostrarSwitch' => false,                               // no mostrar switch en esta vista
            ]) ?>
        <?php endforeach; ?>

        <!-- 🔹 Resumen destacado (partial reutilizable) -->
        <?= $this->render('_resumen_solicitud', [
            'cabanas' => $cabanas,
            'desde' => $desdeDate->format('d-m-Y'),
            'hasta' => $hastaDate->format('d-m-Y'),
            'dias' => $dias,
            'fechaIngreso' => $fechaIngreso,
            'fechaEgreso' => $fechaEgreso,
            'paxAcumulado' => $paxTotal,          // el partial espera 'paxAcumulado'
            'totalGeneral' => $totalGeneral,
        ]) ?>

        <!-- 🔗 URL de seguimiento -->
        <?= $this->render('//partials/_trackingUrlCard', [
            'hash' => $hash
        ]) ?>

    </section>
</div>