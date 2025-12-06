<?php

use yii\bootstrap5\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var \app\models\Cabana $cabana */
/** @var string[] $images */
/** @var string[] $featuresLines */

$numeroCabana = $cabana->numero ?? $cabana->id;

// Título de la página: usamos descr si está cargado
$pageTitle = $cabana->descr
    ? $cabana->descr
    : Yii::t('app', 'Cabaña {n}', ['n' => $numeroCabana]);

$this->title = $pageTitle;

// No usamos breadcrumbs
$actionBuscar = Url::to(['disponibilidad/buscar']);

/**
 * Imágenes de fondo animado (mismo mecanismo que index.php)
 */
$bgDir = Yii::getAlias('@webroot') . '/images/slider-background';
$bgUrl = Yii::getAlias('@web') . '/images/slider-background';
$bgImages = [];
if (is_dir($bgDir)) {
    $bgImages = glob($bgDir . '/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE) ?: [];
    sort($bgImages);
}

// Texto de capacidad para el badge destacado
$capacidadTexto = !empty($cabana->max_pax)
    ? Yii::t('app', 'Hasta {n} personas', ['n' => $cabana->max_pax])
    : Yii::t('app', 'Capacidad cómoda para tu estadía');

?>

<div class="site-cabana-detail">

    <!-- Fondo animado de la página -->
    <div class="dh-bg-rotator">
        <?php if (!empty($bgImages)): ?>
            <?php foreach ($bgImages as $path): ?>
                <?php $fileName = basename($path); ?>
                <img src="<?= Html::encode($bgUrl . '/' . $fileName) ?>" class="dh-bg-img">
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="container py-3">

        <!-- Título -->
        <div class="row mb-2">
            <div class="col-12 text-center">
                <h1 class="dh-heading dh-cabana-main-title mb-1">
                    <?= Html::encode($pageTitle) ?>
                </h1>
            </div>
        </div>

        <!-- SLIDER GRANDE -->
        <div class="row justify-content-center mb-2">
            <div class="col-12 d-flex justify-content-center">
                <div id="cabanaCarousel" class="carousel slide cabana-hero-slider shadow-lg" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        <?php foreach ($images as $i => $src): ?>
                            <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                                <img src="<?= Html::encode($src) ?>" class="d-block w-100 cabana-hero-img cabana-zoomable"
                                    data-img="<?= Html::encode($src) ?>" data-index="<?= $i ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (count($images) > 1): ?>
                        <button class="carousel-control-prev" type="button" data-bs-target="#cabanaCarousel"
                            data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden"><?= Yii::t('app', 'Anterior') ?></span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#cabanaCarousel"
                            data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden"><?= Yii::t('app', 'Siguiente') ?></span>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- BOTÓN VER DISPONIBILIDAD (GRANDE) -->
        <div class="row mb-4">
            <div class="col-12 d-flex justify-content-end justify-content-md-end">
                <?= Html::a(
                    '<i class="bi bi-calendar-week me-1"></i>' . Yii::t('app', 'Ver disponibilidad'),
                    $actionBuscar,
                    ['class' => 'btn btn-dh-primary dh-cabana-cta-btn']
                ) ?>
            </div>
        </div>

        <!-- CARACTERÍSTICAS -->
        <div class="row justify-content-center">
            <div class="col-lg-10 col-xl-9">
                <div class="dh-cabana-info glass-box p-4 p-lg-4">
                    <h2 class="dh-heading h4 mb-3 text-center">
                        <?= Yii::t('app', 'Características de la cabaña') ?>
                    </h2>

                    <!-- Badge de capacidad -->
                    <div class="d-flex justify-content-center mb-3">
                        <span class="dh-capacity-badge">
                            <i class="bi bi-people-fill me-2"></i>
                            <?= Html::encode($capacidadTexto) ?>
                        </span>
                    </div>

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

    </div>
</div>

<!-- MODAL FULLSCREEN PARA VER IMÁGENES -->
<div class="modal fade" id="imageModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen">
        <div class="modal-content bg-dark text-white">

            <div class="modal-header border-0">
                <h5 class="modal-title">
                    <?= Html::encode($pageTitle) ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="<?= Yii::t('app', 'Cerrar') ?>"></button>
            </div>

            <div class="modal-body d-flex flex-column align-items-center">

                <!-- Contador arriba de la imagen -->
                <div class="position-relative w-100 text-center mb-3">
                    <span id="modalCounter" class="px-3 py-1 small fw-semibold">
                        1 / <?= count($images) ?>
                    </span>
                    <img id="imageModalImg" src="" class="w-100" style="max-height: 85vh; object-fit: contain;">
                </div>

                <!-- Thumbnails -->
                <div class="modal-thumbs-wrapper text-center">
                    <?php foreach ($images as $i => $src): ?>
                        <img src="<?= Html::encode($src) ?>" class="dh-modal-thumb me-2 mb-2" data-index="<?= $i ?>">
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<?php
// CSS específico para esta vista
$css = <<<CSS
.site-cabana-detail {
    min-height: 80vh;
}

/* Reducimos paddings para ganar alto de slider */
.site-cabana-detail .container {
    padding-top: 1.2rem;
}

/* Título principal de la cabaña */
.dh-cabana-main-title {
    font-size: 2.2rem;
    font-weight: 700;
    color: #154CA6;
    text-shadow: 0 2px 10px rgba(255,255,255,0.8);
}

/* SLIDER HERO: ocupa 90% de ancho y buen alto */
.cabana-hero-slider {
    width: 90%;
    max-width: 90vw;
    margin: 0 auto;
    border-radius: 1.75rem;
    overflow: hidden;
}

.cabana-hero-img {
    width: 100%;
    height: 70vh;
    min-height: 420px;
    max-height: 760px;
    object-fit: cover;
    cursor: zoom-in;
    transition: opacity .2s ease;
}
.cabana-hero-img:hover {
    opacity: .88;
}

/* Caja de características, semi translúcida */
.dh-cabana-info.glass-box {
    background: rgba(255, 255, 255, 0.70);
    border-radius: 1.5rem;
    border: 1px solid rgba(255,255,255,0.6);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    box-shadow: 0 18px 40px rgba(0,0,0,0.18);
}

/* Lista de características */
.dh-feature-list li {
    font-size: 0.98rem;
}
.dh-feature-list i {
    font-size: 1.1rem;
}

/* Badge de capacidad */
.dh-capacity-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.45rem 1.2rem;
    border-radius: 999px;
    background: rgba(21, 76, 166, 0.10);
    color: #154CA6;
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
}

/* BOTÓN VER DISPONIBILIDAD GRANDE */
.dh-cabana-cta-btn {
    position: relative;
    overflow: hidden;
    font-size: 1.8rem !important;
    padding: 1.4rem 4rem !important;
    border-radius: 999px;
    font-weight: 900;
    letter-spacing: .02em;
    background-color: #154CA6 !important;
    color: #ffffff !important;
    border: none;
    box-shadow: 0 22px 60px rgba(21,76,166,0.55);
    cursor: pointer;
    animation: dh-cabana-pulse 2.8s infinite;
    transition: all .25s ease;
}

.dh-cabana-cta-btn:hover,
.dh-cabana-cta-btn:focus {
    transform: translateY(-3px) scale(1.03);
    box-shadow: 0 26px 65px rgba(21,76,166,0.70);
}

/* Pulse suave */
@keyframes dh-cabana-pulse {
    0%   { box-shadow: 0 0 0 0 rgba(21,76,166,0.50); }
    70%  { box-shadow: 0 0 0 20px rgba(21,76,166,0); }
    100% { box-shadow: 0 0 0 0 rgba(21,76,166,0); }
}

/* Shine que cruza el botón */
.dh-cabana-cta-btn::after {
    content: "";
    position: absolute;
    top: -40%;
    left: -60%;
    width: 40%;
    height: 180%;
    background: rgba(255, 255, 255, 0.55);
    transform: skewX(-25deg);
    opacity: 0;
    animation: dh-cabana-shine 3.5s infinite;
}

@keyframes dh-cabana-shine {
    0%   { left: -60%; opacity: 0; }
    10%  { opacity: 1; }
    35%  { left: 130%; opacity: 0; }
    100% { opacity: 0; }
}

/* MODAL FULLSCREEN */
#imageModal .modal-content {
    background: rgba(0,0,0,0.95);
    border-radius: 0 !important;
    padding: 0;
}

/* Imagen principal dentro del modal */
#imageModalImg {
    max-height: 85vh !important;
    object-fit: contain !important;
    width: auto;
    display: block;
    margin: 0 auto;
}

/* Contador arriba de la imagen */
#modalCounter {
    position: absolute;
    top: 15px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 100;
    font-weight: 600;
    background: rgba(255,255,255,0.9);
    color: #000;
    border-radius: 999px;
}

/* Thumbnails del modal */
.modal-thumbs-wrapper {
    max-width: 100%;
    overflow-x: auto;
    white-space: nowrap;
    padding: 10px 15px 5px;
}

.dh-modal-thumb {
    height: 90px;
    border-radius: 10px;
    opacity: 0.65;
    transition: all .2s ease;
    cursor: pointer;
}

.dh-modal-thumb.active {
    opacity: 1;
    transform: scale(1.05);
    box-shadow: 0 0 0 3px #ffffff;
}

/* FONDO ANIMADO */
.dh-bg-rotator {
    position: fixed;
    inset: 0;
    z-index: -1;
    overflow: hidden;
}
.dh-bg-rotator::after {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(
        to bottom,
        rgba(255,255,255,0.35),
        rgba(255,255,255,0.55)
    );
}
.dh-bg-img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    opacity: 0;
    transform: scale(1.05);
    transition: opacity 1.8s ease-in-out, transform 1.8s ease-in-out;
}
.dh-bg-img.active {
    opacity: 1;
    transform: scale(1);
}

/* Responsive */
@media (max-width: 991.98px) {
    .cabana-hero-img {
        height: 50vh;
        min-height: 260px;
    }
    .dh-cabana-cta-btn {
        width: 100%;
        text-align: center;
    }
}
CSS;

$this->registerCss($css);

// JS para fondo animado + modal con thumbnails
$js = <<<JS
// ===== Fondo animado =====
let bgIndex = 0;
const bgImages = document.querySelectorAll('.dh-bg-img');

if (bgImages.length > 0) {
    bgImages[0].classList.add('active');
    setInterval(() => {
        bgImages[bgIndex].classList.remove('active');
        bgIndex = (bgIndex + 1) % bgImages.length;
        bgImages[bgIndex].classList.add('active');
    }, 8000);
}

// ===== Modal de imágenes =====
const modalElement   = document.getElementById('imageModal');
const modalImage     = document.getElementById('imageModalImg');
const modalCounter   = document.getElementById('modalCounter');
const modalThumbs    = Array.from(document.querySelectorAll('.dh-modal-thumb'));
const zoomables      = document.querySelectorAll('.cabana-zoomable');
const totalImages    = modalThumbs.length;
let currentIndex     = 0;

if (modalElement && modalImage && modalCounter && totalImages > 0) {
    const bootstrapModal = new bootstrap.Modal(modalElement);

    function updateModal(index) {
        if (index < 0 || index >= totalImages) return;
        currentIndex = index;

        // imagen principal
        const src = modalThumbs[currentIndex].getAttribute('src');
        modalImage.setAttribute('src', src);

        // contador
        modalCounter.textContent = (currentIndex + 1) + ' / ' + totalImages;

        // thumbnails activos
        modalThumbs.forEach(t => t.classList.remove('active'));
        modalThumbs[currentIndex].classList.add('active');
    }

    // click en imagen grande del slider
    zoomables.forEach(img => {
        img.addEventListener('click', () => {
            const idx = parseInt(img.dataset.index || '0', 10);
            updateModal(idx);
            bootstrapModal.show();
        });
    });

    // click en thumbnails del modal
    modalThumbs.forEach((thumb, idx) => {
        thumb.addEventListener('click', () => {
            updateModal(idx);
        });
    });

    // navegación con teclado dentro del modal
    modalElement.addEventListener('shown.bs.modal', () => {
        document.addEventListener('keydown', keyHandler);
    });
    modalElement.addEventListener('hidden.bs.modal', () => {
        document.removeEventListener('keydown', keyHandler);
    });

    function keyHandler(e) {
        if (e.key === 'ArrowRight') {
            updateModal((currentIndex + 1) % totalImages);
        } else if (e.key === 'ArrowLeft') {
            updateModal((currentIndex - 1 + totalImages) % totalImages);
        }
    }
}
JS;

$this->registerJs($js);
?>