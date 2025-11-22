<?php

use yii\bootstrap5\Html;

/** @var \yii\web\View $this */
/** @var \app\models\RequestReserva $model */

$items = is_array($model->requestCabanas) ? $model->requestCabanas : $model->requestCabanas;

if (empty($items)): ?>
    <div class="alert alert-info mb-0">
        <?= Yii::t('app', 'No hay cabañas asociadas a esta solicitud.') ?>
    </div>
    <?php return; ?>
<?php endif; ?>

<div class="table-responsive">
    <table class="table table-sm table-bordered mb-0">
        <thead class="table-light">
            <tr>
                <th><?= Yii::t('app', 'Cabaña') ?></th>
                <th class="text-end"><?= Yii::t('app', 'Valor') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $rc): ?>
                <?php
                $nombreCabana = $rc->cabana ? $rc->cabana->descr ?? ('#' . $rc->cabana->id) : Yii::t('app', 'Sin nombre');
                $valor = (float) $rc->valor;
                ?>
                <tr>
                    <td><?= Html::encode($nombreCabana) ?></td>
                    <td class="text-end">
                        <?= '$ ' . number_format($valor, 2, ',', '.') ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>