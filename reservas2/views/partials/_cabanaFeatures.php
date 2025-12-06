<?php
use yii\bootstrap5\Html;

/**
 * Vista parcial reutilizable para mostrar características de una cabaña
 *
 * @var \app\models\Cabana $cabana
 */

// ================== CAPACIDAD ==================
$capacidadTexto = !empty($cabana->max_pax)
    ? Yii::t('app', 'Hasta {n} personas', ['n' => $cabana->max_pax])
    : Yii::t('app', 'Capacidad cómoda para tu estadía');

// ================== CARACTERÍSTICAS ==================
$featuresRaw = $cabana->caracteristicas ?? $cabana->descr ?? '';

if (!empty($featuresRaw)) {
    $decoded = json_decode($featuresRaw, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        if (is_string($decoded)) {
            $featuresRaw = $decoded;
        } elseif (is_array($decoded)) {
            $featuresRaw = reset($decoded);
        }
    }
}

$featuresLines = array_filter(
    array_map('trim', preg_split('/\R/', (string) $featuresRaw))
);

?>

<div class="row justify-content-center">
    <div class="col-lg-10 col-xl-9">
        <div class="dh-cabana-info dh-glass-box p-4 p-lg-4">

            <!-- Título -->
            <h2 class="dh-heading h4 mb-3 text-center">
                <?= Yii::t('app', 'Características de la cabaña') ?>
            </h2>

            <!-- Badge capacidad -->
            <div class="d-flex justify-content-center mb-3">
                <span class="dh-capacity-badge">
                    <i class="bi bi-people-fill me-2"></i>
                    <?= Html::encode($capacidadTexto) ?>
                </span>
            </div>

            <!-- Lista de características -->
            <?php if (!empty($featuresLines)): ?>
                <ul class="list-unstyled mb-0 dh-feature-list">
                    <?php foreach ($featuresLines as $line): ?>
                        <li class="d-flex mb-2">
                            <span class="me-2 text-primary">
                                <i class="bi bi-check2-circle"></i>
                            </span>
                            <span><?= Html::encode($line) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="text-muted mb-0 text-center">
                    <?= Yii::t('app', 'Pronto agregaremos más detalles sobre esta cabaña.') ?>
                </p>
            <?php endif; ?>

        </div>
    </div>
</div>