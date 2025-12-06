<?php
/**
 * @var \app\models\Cabana[] $cabanas
 * @var array $cabanaColors [cabana_id => color_hex]
 */

use yii\bootstrap5\Html;

if (empty($cabanaColors)) {
    return;
}

// Indexar cabañas por id para evitar doble foreach
$cabanasById = [];
foreach ($cabanas as $c) {
    $cabanasById[(int) $c->id] = $c;
}
?>

<div class="card mb-3">
    <div class="card-body py-2">
        <div class="d-flex flex-wrap align-items-center gap-3">
            <span class="fw-bold small text-muted">
                <?= Yii::t('app', 'Leyenda de colores por cabaña') ?>:
            </span>

            <?php foreach ($cabanaColors as $cabId => $color): ?>
                <?php
                $cabId = (int) $cabId;
                $cabana = $cabanasById[$cabId] ?? null;
                if ($cabana === null) {
                    continue;
                }

                $label = $cabana->descr ?: ('Cabana #' . $cabana->id);
                ?>
                <span class="badge rounded-pill" style="color: black; background-color: <?= Html::encode($color) ?>;">
                    <?= Html::encode($label) ?>
                </span>
            <?php endforeach; ?>
        </div>
    </div>
</div>