<?php

use yii\bootstrap5\Html;
use yii\helpers\Url;

$this->registerCssFile('@web/css/cabana.css', [
    'depends' => [\yii\bootstrap5\BootstrapAsset::class]
]);


/** @var yii\web\View $this */
/** @var \app\models\Cabana $cabana */
/** @var string[] $images */

$numeroCabana = $cabana->numero ?? $cabana->id;

// Título de la página: usamos descr si está cargado
$pageTitle = $cabana->descr
    ? $cabana->descr
    : Yii::t('app', 'Cabaña {n}', ['n' => $numeroCabana]);

$this->title = $pageTitle;

// No usamos breadcrumbs
$actionBuscar = Url::to(['disponibilidad/buscar-en-cabana', 'id_cabana' => $cabana->id]);

?>
<div class="site-cabana-detail container py-3">

    <?= $this->render('//partials/_dhBackground') ?>


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
                    <!-- 🔹 Leyenda discreta -->
                    <div class="cabana-slider-hint">
                        <?= Yii::t('app', 'Hacer clic para ver') ?>
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

        <!-- BOTÓN VER DISPONIBILIDAD (GRANDE, reutiliza estilos del CTA global) -->
        <div class="row mb-4">
            <div class="col-12 d-flex justify-content-center justify-content-md-center">
                <?= Html::a(
                    '<i class="bi bi-calendar-week me-1"></i>' . Yii::t('app', 'Ver disponibilidad'),
                    $actionBuscar,
                    ['class' => 'btn btn-dh-primary dh-cabana-cta-btn']
                ) ?>
            </div>
        </div>

        <!-- CARACTERÍSTICAS -->

        <?= $this->render('//partials/_cabanaFeatures', [
            'cabana' => $cabana
        ]) ?>


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




// ============== JS ESPECÍFICO DEL DETALLE: MODAL DE IMÁGENES ==============
$js = <<<JS
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
    function keyHandler(e) {
        if (e.key === 'ArrowRight') {
            updateModal((currentIndex + 1) % totalImages);
        } else if (e.key === 'ArrowLeft') {
            updateModal((currentIndex - 1 + totalImages) % totalImages);
        }
    }

    modalElement.addEventListener('shown.bs.modal', () => {
        document.addEventListener('keydown', keyHandler);
    });
    modalElement.addEventListener('hidden.bs.modal', () => {
        document.removeEventListener('keydown', keyHandler);
    });
}
JS;

$this->registerJs($js);
?>