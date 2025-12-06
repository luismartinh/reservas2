<?php
/** @var \app\models\Cabana $model */
/** @var array $totales */
/** @var string|null $desde */
/** @var string|null $hasta */
/** @var int|null $cabana_selected */

use yii\bootstrap5\Html;


// --- Total y días del rango ---
$total = $totales[$model->id] ?? null;

$mostrarTotal = array_key_exists($model->id, $totales) && $dias !== null;

$mostrarSwitch = !isset($mostrarSwitch) ? true : (bool) $mostrarSwitch;




?>

<div class="card w-100 shadow-sm mb-3">
    <div class="card-body d-flex flex-column">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <h4 class="card-title mb-0">
                <i class="bi bi-house-door-fill me-1"></i>
                <?= Html::encode($model->descr) ?>
            </h4>
            <?php if ((int) $model->activa === 1): ?>
                <span class="badge bg-success"><?= Yii::t('app', 'Activa') ?></span>
            <?php else: ?>
                <span class="badge bg-secondary"><?= Yii::t('app', 'Inactiva') ?></span>
            <?php endif; ?>
        </div>

        <!-- 🔹 Checkbox de selección -->
        <?php if ($mostrarSwitch && $total > 0): ?>
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="col-2"></div>
                <div class="col-10 form-check form-switch mb-3">
                    <input class="form-check-input cabana-switch" type="checkbox" id="select-cabana-<?= $model->id ?>"
                        name="seleccionadas[]" value="<?= $model->id ?>" <?= $cabana_selected ? 'checked' : '' ?>>>
                    <label class="form-check-label fw-semibold" for="select-cabana-<?= $model->id ?>">
                        <?= Yii::t('app', 'Seleccionar') ?>
                    </label>
                </div>
            </div>
        <?php endif; ?>

        <?= $this->render('//partials/_cabanaResumen', [
            'cabana' => $model,
            'totales' => $totales,
            'desde' => $desde,
            'hasta' => $hasta,
        ]) ?>


        <br>

        <!-- CARACTERÍSTICAS -->
        <!-- 🔽 Accordion: Características -->
        <div class="accordion mt-3" id="accordionCabana<?= $model->id ?>">

            <div class="accordion-item">
                <h2 class="accordion-header" id="headingFeatures<?= $model->id ?>">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                        data-bs-target="#collapseFeatures<?= $model->id ?>" aria-expanded="false"
                        aria-controls="collapseFeatures<?= $model->id ?>">
                        <i class="bi bi-list-check me-2"></i>
                        <?= Yii::t('app', 'Ver características de la cabaña') ?>
                    </button>
                </h2>

                <div id="collapseFeatures<?= $model->id ?>" class="accordion-collapse collapse"
                    aria-labelledby="headingFeatures<?= $model->id ?>"
                    data-bs-parent="#accordionCabana<?= $model->id ?>">

                    <div class="accordion-body p-0">
                        <?= $this->render('//partials/_cabanaFeatures', [
                            'cabana' => $model
                        ]) ?>
                    </div>

                </div>
            </div>

        </div>

    </div>
</div>