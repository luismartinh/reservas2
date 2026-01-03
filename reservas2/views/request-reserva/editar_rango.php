<?php

use kartik\daterange\DateRangePicker;
use kartik\form\ActiveForm;
use yii\bootstrap5\Html;

/** @var \yii\web\View $this */
/** @var \app\models\RequestReserva $model */
/** @var \yii\base\DynamicModel $formModel */
/** @var \app\models\Cabana[] $cabanas */
/** @var string $desde  Y-m-d */
/** @var string $hasta  Y-m-d */

$this->title = Yii::t('app', 'Editar período');
?>

<div class="container py-4">

    <h2 class="mb-4 text-center"><?= Html::encode($this->title) ?></h2>

    <!-- Datos básicos del request -->
    <div class="card mb-3">
        <div class="card-body">
            <div><b>ID:</b> <?= Html::encode($model->id) ?></div>
            <div><b>Código:</b> <?= Html::encode($model->codigo_reserva ?? '') ?></div>
            <div><b>Estado:</b> <?= Html::encode($model->estado->descr ?? $model->estado->slug ?? '') ?></div>
            <div><b>Denominación:</b> <?= Html::encode($model->denominacion ?? '') ?></div>
            <div><b>Email:</b> <?= Html::encode($model->email ?? '') ?></div>
        </div>
    </div>

    <br class="my-4">

    <!-- Form para editar período -->
    <div class="card">
        <div class="card-body">

            <h5 class="mb-3"><?= Yii::t('app', 'Modificar período') ?></h5>

            <?php $form = ActiveForm::begin([
                'method' => 'post',
                'options' => ['autocomplete' => 'off'],
            ]); ?>

            <div class="row">
                <div class="col-md-6">


                    <?= $form->field($formModel, 'periodo', [
                        'addon' => ['prepend' => ['content' => '<i class="bi bi-calendar-range-fill"></i>']],
                        'options' => ['class' => 'drp-container mb-2']
                    ])->widget(DateRangePicker::class, [
                                'startAttribute' => 'desde',
                                'endAttribute' => 'hasta',
                                'useWithAddon' => true,
                                'convertFormat' => true,
                                'includeMonthsFilter' => true,
                                'bsVersion' => '5.x',
                                'pluginOptions' => [
                                    'locale' => ['format' => 'd-m-Y'],
                                    'minDate' => null, // admin permite pasado (si querés bloquear, poné moment().startOf("day"))
                                ],
                                'language' => Yii::$app->language,
                                'options' => [
                                    'autocomplete' => 'off',
                                    'placeholder' => Yii::t('app', 'seleccione rango...')
                                ]
                            ])->label(Yii::t('app', 'Período actual'))->hint(Yii::t('app', 'Seleccione el nuevo período')) ?>

                </div>

                <div class="col-md-6">
                    <div class="form-group d-flex align-items-end" style="margin-top:32px;">
                        <?= Html::submitButton('<i class="bi bi-send"></i> ' . Yii::t('app', 'Modificar Reserva'), [
                            'class' => 'btn btn-primary me-2'
                        ]) ?>

                        <?= Html::a(
                            '<i class="bi bi-arrow-counterclockwise me-2"></i>' . Yii::t('cruds', 'Cancelar'),
                            ['request-reserva/index'],
                            ['class' => 'btn btn-outline-secondary']
                        ) ?>
                    </div>

                </div>
            </div>

            <?php ActiveForm::end(); ?>

        </div>
    </div>

    <?php
    // --- Calcular totales, fechas y resumen (igual que crear_reserva.php) ---
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

    $d = \DateTime::createFromFormat('d/m/Y', $desde)
        ?: \DateTime::createFromFormat('d-m-Y', $desde)
        ?: \DateTime::createFromFormat('Y-m-d', $desde);

    $h = \DateTime::createFromFormat('d/m/Y', $hasta)
        ?: \DateTime::createFromFormat('d-m-Y', $hasta)
        ?: \DateTime::createFromFormat('Y-m-d', $hasta);

    if ($d && $h) {
        $dias = $d->diff($h)->days + 1;
        $fechaIngreso = (clone $d)->setTime(14, 0);          // mismo criterio que crear_reserva.php
        $fechaEgreso = (clone $h)->modify('+1 day')->setTime(11, 0);
    }
    ?>
<br class="my-4">

<h2 class="mb-4 text-center"><?= Yii::t('cruds', 'Datos guardados:') ?></h2>
    <!-- Mostrar cabañas actuales (igual que crear_reserva.php) -->
    <?php foreach ($cabanas as $cabana): ?>
            <?= $this->render('@app/views/disponibilidad/_cabana_card', [
                'model' => $cabana,
                'totales' => $totales,
                'desde' => $desde,
                'hasta' => $hasta,
                'mostrarSwitch' => false,
            ]) ?>

            <?php
            $valor = $totales[$cabana->id] ?? 0;
            $totalGeneral += $valor;
            $paxAcumulado += (int) $cabana->max_pax;
            ?>
    <?php endforeach; ?>

    <!-- Resumen (igual que crear_reserva.php) -->
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


</div>