<?php

use yii\bootstrap5\Html;

/** @var yii\web\View $this */
/** @var string $googleMapsUrl */
/** @var string $googleMapsEmbedUrl */

$this->title = Yii::t('app', 'Cómo llegar');
?>

<div class="site-como-llegar container py-5">

    <?= $this->render('//partials/_dhBackground') ?>

    <div class="row mb-4">
        <div class="col-12 text-center">
            <h1 class="dh-heading mb-2">
                <?= Yii::t('app', 'Cómo llegar a Cabañas Dina Huapi') ?>
            </h1>
            <p class="mb-0">
                <?= Yii::t('app', 'Te mostramos nuestra ubicación en Dina Huapi y cómo contactarnos.') ?>
            </p>
        </div>
    </div>

    <div class="row gy-4">
        <!-- MAPA -->
        <div class="col-lg-7">
            <div class="ratio ratio-16x9 shadow rounded-4 overflow-hidden">
                <iframe
                    src="<?= Html::encode($googleMapsEmbedUrl) ?>"
                    style="border:0;"
                    allowfullscreen
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
            <div class="mt-2 small text-muted text-end">
                <?= Yii::t('app', 'Ver en') ?> 
                <?= Html::a('Google Maps', $googleMapsUrl, [
                    'target' => '_blank',
                    'rel' => 'noopener',
                ]) ?>
            </div>
        </div>

        <!-- INFO DE CONTACTO / DIRECCIÓN -->
        <div class="col-lg-5">
            <div class="dh-glass-box p-4 h-100">
                <h2 class="dh-heading h4 mb-3">
                    Cabañas Dina Huapi
                </h2>

                <ul class="list-unstyled mb-3">
                    <li class="mb-3">
                        <i class="bi bi-geo-alt-fill me-2"></i>
                        <strong><?= Yii::t('app', 'Dirección') ?>:</strong><br>
                        Los Cohiues 375<br>
                        <span class="text-muted">
                            (Cohiues y Av. Patagonia Argentina)
                        </span>
                    </li>

                    <li class="mb-3">
                        <i class="bi bi-pin-map-fill me-2"></i>
                        (CP 8402) Dina Huapi, Río Negro – Argentina
                    </li>

                    <li class="mb-3">
                        <i class="bi bi-telephone-fill me-2"></i>
                        <strong>Tel/WhatsApp:</strong>
                        <?= Html::a('2944 557 891', 'https://wa.me/5492944557891', [
                            'target' => '_blank',
                            'rel' => 'noopener',
                        ]) ?>
                        <span class="text-muted">(Claudia)</span>
                    </li>
                </ul>

                <p class="small text-muted mb-0">
                    <?= Yii::t('app', 'Estamos a metros de la costa del lago Nahuel Huapi y a pocos minutos de Bariloche.') ?>
                </p>
            </div>
        </div>
    </div>
</div>

<?php
$this->registerCss(<<<CSS
.site-como-llegar {
    padding-top: 6rem;
}
@media (max-width: 576px) {
    .site-como-llegar {
        padding-top: 5rem;
    }
}
CSS);
?>