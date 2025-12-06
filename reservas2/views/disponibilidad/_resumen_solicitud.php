<?php
/**
 * @var \app\models\Cabana[] $cabanas
 * @var string $desde           // d-m-Y o d/m/Y
 * @var string $hasta           // d-m-Y o d/m/Y
 * @var int    $dias
 * @var \DateTimeInterface|null $fechaIngreso
 * @var \DateTimeInterface|null $fechaEgreso
 * @var int    $paxAcumulado
 * @var float  $totalGeneral
 */

use yii\bootstrap5\Html;
?>
<div class="card border-info shadow-lg mt-4 mb-5">
    <div class="card-body">
        <h3 class="card-title text-info fw-bold mb-3">
            <i class="bi bi-info-circle-fill me-2"></i>
            <?= Yii::t('app', 'Resumen TOTAL de Solicitud') ?>
        </h3>

        <!-- Contenedor de 50% del ancho, centrado -->
        <ul class="list-unstyled fs-6 w-50 mx-auto">
            <li class="d-flex justify-content-between align-items-baseline border-bottom py-2">
                <span class="text-muted me-3"><?= Yii::t('app', 'Cantidad de cabañas seleccionadas') ?>:</span>
                <span class="fw-semibold text-end"><?= count($cabanas) ?></span>
            </li>

            <li class="d-flex justify-content-between align-items-baseline border-bottom py-2">
                <span class="text-muted me-3"><?= Yii::t('app', 'Período') ?>:</span>
                <span class="fw-semibold text-end">
                    <?= Html::encode($desde) ?> &rarr; <?= Html::encode($hasta) ?>
                    (<?= (int) $dias ?> <?= Yii::t('app', 'día(s)') ?>)
                </span>
            </li>

            <li class="d-flex justify-content-between align-items-baseline border-bottom py-2">
                <span class="text-muted me-3"><?= Yii::t('app', 'Fecha de ingreso') ?>:</span>
                <span class="fw-semibold text-end">
                    <?= $fechaIngreso ? Yii::$app->formatter->asDatetime($fechaIngreso, 'php:d/m/Y H:i') : '—' ?>
                </span>
            </li>

            <li class="d-flex justify-content-between align-items-baseline border-bottom py-2">
                <span class="text-muted me-3"><?= Yii::t('app', 'Ultima noche') ?>:</span>
                <span class="fw-semibold text-end">
                    <?= $hasta ? Yii::$app->formatter->asDatetime($hasta, 'php:d/m/Y') : '—' ?>
                </span>
            </li>

            <li class="d-flex justify-content-between align-items-baseline border-bottom py-2">
                <span class="text-muted me-3"><?= Yii::t('app', 'Salida') ?>:</span>
                <span class="fw-semibold text-end">
                    <?= $fechaEgreso ? Yii::$app->formatter->asDatetime($fechaEgreso, 'php:d/m/Y H:i') : '—' ?>
                </span>
            </li>

            <li class="d-flex justify-content-between align-items-baseline border-bottom py-2">
                <span class="text-muted me-3"><?= Yii::t('app', 'Capacidad total') ?>:</span>
                <span class="fw-semibold text-end">
                    <?= (int) $paxAcumulado ?> <?= Yii::t('app', 'pasajeros') ?>
                </span>
            </li>

            <!-- Total más destacado -->
            <li class="d-flex justify-content-between align-items-baseline pt-3">
                <span class="fw-bold fs-2 me-3"><?= Yii::t('app', 'Total estimado') ?>:</span>
                <span class="fw-bold fs-2 text-success px-3 py-1 rounded border border-success">
                    <?= '$ ' . number_format((float) $totalGeneral, 2, ',', '.') ?>
                </span>
            </li>
        </ul>
    </div>
</div>