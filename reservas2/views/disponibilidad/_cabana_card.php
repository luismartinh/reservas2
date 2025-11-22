<?php
/** @var \app\models\Cabana $model */
/** @var array $totales */
/** @var string|null $desde */
/** @var string|null $hasta */

use yii\bootstrap5\Html;

$fmtDateTime = fn($dt) => Yii::$app->formatter->asDatetime($dt, 'php:d/m/Y H:i');

// --- Parseo seguro de hora (checkin / checkout) ---
$parseTime = function ($t) {
    if (!$t)
        return [0, 0];
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

// --- Características ---
$carac = $model->caracteristicas;
if (is_string($carac)) {
    $decoded = json_decode($carac, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $carac = $decoded;
    }
}
if (!is_array($carac)) {
    $carac = $carac ? [$carac] : [];
}

// --- Total y días del rango ---
$total = $totales[$model->id] ?? null;
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
        [$checkinH, $checkinM] = $parseTime($model->checkin);
        [$checkoutH, $checkoutM] = $parseTime($model->checkout);

        $fechaIngreso = (clone $d)->setTime($checkinH, $checkinM);
        $fechaEgreso = (clone $h)->modify('+1 day')->setTime($checkoutH, $checkoutM);
    }
}

$mostrarTotal = array_key_exists($model->id, $totales) && $dias !== null;

$mostrarSwitch = !isset($mostrarSwitch) ? true : (bool) $mostrarSwitch;
?>

<div class="card w-100 shadow-sm mb-3">
    <div class="card-body d-flex flex-column">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <h5 class="card-title mb-0">
                <i class="bi bi-house-door-fill me-1"></i>
                <?= Html::encode($model->descr) ?>
            </h5>
            <?php if ((int) $model->activa === 1): ?>
                <span class="badge bg-success"><?= Yii::t('app', 'Activa') ?></span>
            <?php else: ?>
                <span class="badge bg-secondary"><?= Yii::t('app', 'Inactiva') ?></span>
            <?php endif; ?>
        </div>

        <!-- 🔹 Checkbox de selección -->
        <?php if ($mostrarSwitch && $total > 0): ?>
            <div class="form-check form-switch mb-3">
                <input class="form-check-input cabana-switch" type="checkbox" id="select-cabana-<?= $model->id ?>"
                    name="seleccionadas[]" value="<?= $model->id ?>">
                <label class="form-check-label fw-semibold" for="select-cabana-<?= $model->id ?>">
                    <?= Yii::t('app', 'Seleccionar') ?>
                </label>
            </div>
        <?php endif; ?>
        <ul class="list-unstyled small mb-3 w-25">
            <li class="d-flex justify-content-between border-bottom py-1">
                <span class="text-muted"><?= Yii::t('models', 'Fecha de Ingreso') ?>:</span>
                <span class="fw-semibold"><?= $fechaIngreso ? $fmtDateTime($fechaIngreso) : '—' ?></span>
            </li>

            <li class="d-flex justify-content-between border-bottom py-1">
                <span class="text-muted"><?= Yii::t('models', 'Fecha de Egreso') ?>:</span>
                <span class="fw-semibold"><?= $fechaEgreso ? $fmtDateTime($fechaEgreso) : '—' ?></span>
            </li>

            <li class="d-flex justify-content-between border-bottom py-1">
                <span class="text-muted"><?= Yii::t('models', 'Capacidad Máxima') ?>:</span>
                <span class="fw-semibold">
                    <?= (int) $model->max_pax ?> <?= Yii::t('app', 'pasajeros') ?>
                </span>
            </li>

            <li class="d-flex justify-content-between py-1">
                <span class="text-muted"><?= Yii::t('models', 'Cantidad de dias') ?>:</span>
                <span class="fw-semibold">
                    <?= $dias ?> <?= Yii::t('app', 'dias') ?>
                </span>
            </li>

        </ul>

        <?php if ($carac): ?>
            <div class="mb-3">
                <div class="mb-1 fw-semibold"><?= Yii::t('models', 'Características') ?>:</div>
                <div class="d-flex flex-wrap gap-1">
                    <?php foreach ($carac as $c): ?>
                        <span class="badge bg-info text-dark">
                            <?= Html::encode(is_array($c) ? json_encode($c) : (string) $c) ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($mostrarTotal): ?>
            <hr class="my-2" />
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    <?= Yii::t('app', 'Período') ?>:
                    <strong><?= Html::encode($desde) ?></strong>
                    &rarr;
                    <strong><?= Html::encode($hasta) ?></strong>
                    <?= Yii::t('app', '(inclusive)') ?>
                    (<?= $dias ?>     <?= Yii::t('app', 'día(s)') ?>)
                </div>

                <div class="fs-5 fw-semibold">
                    <?php if ($total === -1 || (is_numeric($total) && $total < 0)): ?>
                        <span class="text-warning"
                            title="<?= Html::encode(Yii::t('app', 'No hay tarifas activas que cubran todo el período seleccionado.')) ?>">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                            <?= Yii::t('app', 'Sin tarifa para todo el período') ?>
                        </span>
                    <?php else: ?>
                        <?= '$ ' . number_format((float) $total, 2, ',', '.') ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>