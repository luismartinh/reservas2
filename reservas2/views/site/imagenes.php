<?php

use yii\bootstrap5\Html;

/** @var yii\web\View $this */
/** @var string[] $images */

$this->title = Yii::t('app', 'Galería de imágenes');

// CSS específico para el fullscreen
$css = <<<CSS
.site-imagenes-fullscreen {
    margin-top: 4.5rem;        /* separa del navbar fixed-top */
    min-height: 100vh;
    background-color: #000;
}

/* El carrusel ocupa toda la altura disponible */
.site-imagenes-fullscreen .carousel,
.site-imagenes-fullscreen .carousel-inner {
    height: calc(100vh - 4.5rem);
}

/* NO tocamos display de .carousel-item para no romper Bootstrap */
.site-imagenes-fullscreen .carousel-item {
    height: 100%;
}

/* Wrapper interno que sí usa flex para centrar la imagen */
.site-imagenes-fullscreen .fullscreen-slide {
    height: 100%;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #000;
}

/* La imagen se ajusta sin deformarse y sin salirse del viewport */
.site-imagenes-fullscreen .fullscreen-slide > img {
    max-width: 100%;
    max-height: 100%;
    width: auto;
    height: auto;
    object-fit: contain;
}

/* Caption discreto */
.site-imagenes-fullscreen .carousel-caption {
    background: rgba(0,0,0,0.35);
    border-radius: 999px;
    padding: .4rem 1rem;
}

/* Indicadores redondos */
.site-imagenes-fullscreen .carousel-indicators [data-bs-target] {
    width: 10px;
    height: 10px;
    border-radius: 50%;
}
CSS;

$this->registerCss($css);
?>

<div class="site-imagenes-fullscreen">

    <?php if (empty($images)): ?>
        <div class="d-flex align-items-center justify-content-center text-white" style="height: calc(100vh - 4.5rem);">
            <div class="text-center">
                <h1 class="h4 mb-3"><?= Yii::t('app', 'Galería de imágenes') ?></h1>
                <p class="text-muted mb-0">
                    <?= Yii::t('app', 'Pronto vas a encontrar aquí imágenes de nuestras cabañas y alrededores.') ?>
                </p>
            </div>
        </div>
    <?php else: ?>
        <div id="fullGalleryCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="4500">

            <!-- Indicadores -->
            <div class="carousel-indicators">
                <?php foreach ($images as $i => $src): ?>
                    <button type="button" data-bs-target="#fullGalleryCarousel" data-bs-slide-to="<?= $i ?>"
                        class="<?= $i === 0 ? 'active' : '' ?>" aria-current="<?= $i === 0 ? 'true' : 'false' ?>"
                        aria-label="Slide <?= $i + 1 ?>"></button>
                <?php endforeach; ?>
            </div>

            <!-- Slides -->
            <div class="carousel-inner">
                <?php foreach ($images as $index => $src): ?>
                    <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                        <div class="fullscreen-slide">
                            <img src="<?= Html::encode($src) ?>" alt="">
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Controles -->
            <button class="carousel-control-prev" type="button" data-bs-target="#fullGalleryCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden"><?= Yii::t('app', 'Anterior') ?></span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#fullGalleryCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden"><?= Yii::t('app', 'Siguiente') ?></span>
            </button>
        </div>
    <?php endif; ?>

</div>