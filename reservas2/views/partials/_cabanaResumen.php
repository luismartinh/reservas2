<?php

use yii\bootstrap5\Html;

/**
 * Resumen de reserva para una cabaña
 *
 * @var \app\models\Cabana $cabana
 * @var array $totales            // total por id de cabaña: [id_cabana => total]
 * @var string|null $desde        // fecha inicio (string)
 * @var string|null $hasta        // fecha fin (string)
 */

//$fmtDateTime = fn($dt) => Yii::$app->formatter->asDatetime($dt, 'php:d/m/Y H:i');
$fmtDateTime = function ($dt) {
    return Yii::$app->formatter->asDatetime($dt, 'php:d/m/Y H:i');
};


// --- Parseo seguro de hora (checkin / checkout) ---
$parseTime = function ($t) {
    if (!$t) {
        return [0, 0];
    }
    $t = trim(str_ireplace(['a. m.', 'p. m.', 'a.m.', 'p.m.'], ['am', 'pm', 'am', 'pm'], $t));
    foreach (['H:i', 'H:i:s', 'g:i a', 'g:i A', 'h:i a', 'h:i A'] as $f) {
        $dt = \DateTime::createFromFormat($f, $t);
        if ($dt) {
            return [(int) $dt->format('H'), (int) $dt->format('i')];
        }
    }
    $ts = strtotime($t);
    return $ts ? [(int) date('H', $ts), (int) date('i', $ts)] : [0, 0];
};

// --- Total y días del rango ---
$total = $totales[$cabana->id] ?? null;
$dias = null;
$fechaIngreso = $fechaEgreso = null;

if (!empty($desde) && !empty($hasta)) {
    $d = \DateTime::createFromFormat('d/m/Y', $desde)
        ?: \DateTime::createFromFormat('d-m-Y', $desde)
        ?: \DateTime::createFromFormat('Y-m-d', $desde);

    $h = \DateTime::createFromFormat('d/m/Y', $hasta)
        ?: \DateTime::createFromFormat('d-m-Y', $hasta)
        ?: \DateTime::createFromFormat('Y-m-d', $hasta);

    if ($d && $h) {
        $d->setTime(0, 0, 0);
        $h->setTime(0, 0, 0);

        // Días inclusivos
        $dias = (int) $d->diff($h)->days + 1;

        // --- Calcular fechas de ingreso y egreso ---
        [$checkinH, $checkinM] = $parseTime($cabana->checkin);
        [$checkoutH, $checkoutM] = $parseTime($cabana->checkout);

        $fechaIngreso = (clone $d)->setTime($checkinH, $checkinM);
        $fechaEgreso = (clone $h)->modify('+1 day')->setTime($checkoutH, $checkoutM);
    }
}

// Mostrar total sólo si viene alguno
$mostrarTotal = $total !== null;

?>

<div class="row justify-content-center">
    <div class="col-lg-10 col-xl-9">

        <div class="dh-cabana-info dh-glass-box p-4 p-lg-4">
            <h4>
                <?= Yii::t('app', 'Resumen de '), $cabana->descr ?>
            </h4>

            <ul class="list-unstyled small mb-3">

                <li class="d-flex justify-content-between border-bottom py-1">
                    <span class="text-muted"><?= Yii::t('models', 'Fecha de Ingreso') ?>:</span>
                    <span class="fw-semibold">
                        <?= $fechaIngreso ? $fmtDateTime($fechaIngreso) : '—' ?>
                    </span>
                </li>

                <li class="d-flex justify-content-between border-bottom py-1">
                    <span class="text-muted"><?= Yii::t('models', 'Fecha de Egreso') ?>:</span>
                    <span class="fw-semibold">
                        <?= $fechaEgreso ? $fmtDateTime($fechaEgreso) : '—' ?>
                    </span>
                </li>

                <li class="d-flex justify-content-between border-bottom py-1">
                    <span class="text-muted"><?= Yii::t('models', 'Capacidad Máxima') ?>:</span>
                    <span class="fw-semibold">
                        <?= (int) $cabana->max_pax ?> <?= Yii::t('app', 'pasajeros') ?>
                    </span>
                </li>

                <li class="d-flex justify-content-between py-1">
                    <span class="text-muted"><?= Yii::t('models', 'Cantidad de dias') ?>:</span>
                    <span class="fw-semibold">
                        <?= $dias !== null ? $dias : '—' ?> <?= Yii::t('app', 'dias') ?>
                    </span>
                </li>

            </ul>

            <?php if ($mostrarTotal): ?>
                <hr class="my-2" />
                <div class="d-flex justify-content-between align-items-center">

                    <div>
                        <?= Yii::t('app', 'Período') ?>:
                        <strong><?= Html::encode($desde) ?></strong>
                        &rarr;
                        <strong><?= Html::encode($hasta) ?></strong>
                        <?= Yii::t('app', '(inclusive)') ?>
                        (<?= $dias ?>     <?= Yii::t('app', 'día(s)') ?>)
                    </div>

                    <div class="fs-4 fw-semibold">
                        <?php if ($total === -1 || (is_numeric($total) && $total < 0)): ?>
                            <span class="text-warning"
                                title="<?= Html::encode(Yii::t('app', 'No hay tarifas activas que cubran todo el período seleccionado.')) ?>">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                <?= Yii::t('app', 'Sin tarifa para todo el período') ?>
                            </span>
                        <?php else: ?>
                            <?= 'TOTAL: $ ' . number_format((float) $total, 2, ',', '.') ?>
                        <?php endif; ?>
                    </div>

                </div>
            <?php endif; ?>
        </div>

    </div>
</div>