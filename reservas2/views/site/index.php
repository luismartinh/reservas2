<?php

use yii\bootstrap5\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var \app\models\Cabana[] $cabanas */

$this->title = Yii::t('app', 'Cabañas Dina Huapi - Reservas online');

$baseImg = Yii::getAlias('@web') . '/images/cabanas';
$heroImg = $baseImg . '/exterior-deck.jpeg';

$actionBuscar = Url::to(['disponibilidad/buscar']);


/**
 * Imágenes del slider de cabaña (hero derecha)
 */
$heroDir = Yii::getAlias('@webroot') . '/images/slider-cabana';
$heroUrl = Yii::getAlias('@web') . '/images/slider-cabana';
$heroImages = [];
if (is_dir($heroDir)) {
    $heroImages = glob($heroDir . '/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE) ?: [];
    sort($heroImages);
}

$contact = Yii::$app->params['contact'] ?? [];
$whatsAppNumber = $contact['whatsapp_number_link'] ?? '5492944000000';
//$whatsAppNumber = '5492944000000'; // 👉 poné acá tu número con código de país, sin "+" ni espacios
$whatsAppMessage = Yii::t('app', 'Hola, quiero consultar por las cabañas en Dina Huapi.');
$whatsAppUrl = 'https://wa.me/' . $whatsAppNumber . '?text=' . urlencode($whatsAppMessage);


?>

<div class="site-index container py-5 py-lg-5">

    <?= $this->render('//partials/_dhBackground') ?>


    <!-- ======================= HERO (LOGO GRANDE + SLIDER + BOTÓN ABAJO DERECHA) ======================= -->
    <section class="dh-hero mb-5 py-4">
        <div class="row align-items-start gy-4">

            <!-- IZQUIERDA: Logo + textos -->
            <div class="col-lg-5 text-center d-flex flex-column align-items-center">

                <!-- Logo redondeado con fondo -->
                <div class="dh-logo-box mb-4">
                    <img src="<?= Yii::getAlias('@web') ?>/images/logos/logo1.png" alt="Cabañas Dina Huapi"
                        class="dh-hero-logo">
                </div>

                <!-- Título -->
                <h1 class="dh-hero-title mb-3 text-center">
                    <?= Yii::t('app', 'Viví la Patagonia en nuestras cabañas en Dina Huapi') ?>
                </h1>

                <!-- Subtítulo -->
                <p class="dh-hero-subtitle mb-0 text-center">
                    <?= Yii::t('app', 'Descansá frente al lago Nahuel Huapi, en cabañas totalmente equipadas rodeadas de naturaleza.') ?>
                </p>

            </div>

            <!-- DERECHA: Slider + botón grande debajo -->
            <div class="col-lg-7 d-flex flex-column">

                <!-- Slider -->
                <div class="dh-hero-slider rounded-4 shadow-lg overflow-hidden position-relative mb-3">
                    <div class="dh-slider-frame">
                        <?php if (!empty($heroImages)): ?>
                            <?php foreach ($heroImages as $path): ?>
                                <?php $fileName = basename($path); ?>
                                <img src="<?= Html::encode($heroUrl . '/' . $fileName) ?>" class="dh-slider-img">
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- Fallback: si no hay imágenes en la carpeta, uso la imagen por defecto -->
                            <img src="<?= Html::encode($heroImg) ?>" class="dh-slider-img">
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Botón centrado debajo del slider (estilo XXL compartido) -->
                <div class="mt-2 d-flex justify-content-center">
                    <a href="<?= $actionBuscar ?>"
                        class="btn btn-dh-primary dh-hero-btn-xl dh-hero-btn-pulse dh-hero-btn-shine">
                        <i class="bi bi-calendar-check me-2"></i>
                        <?= Yii::t('app', 'Buscar disponibilidad') ?>
                    </a>
                </div>

            </div>

        </div>
    </section>

    <!-- =================== NUESTRAS CABAÑAS =================== -->
    <section class="mb-5">
        <div class="d-flex flex-wrap justify-content-between align-items-end mb-3 gap-2">
            <div>
                <h2 class="dh-section-title mb-2 text-center text-lg-start">
                    <?= Yii::t('app', 'Nuestras cabañas') ?>
                </h2>
                <p class="dh-section-subtitle mb-0">
                    <?= Yii::t('app', 'Cabañas independientes, totalmente equipadas, en frente a la costa del lago.') ?>
                </p>
            </div>
        </div>

        <div class="row g-4">
            <?php if (!empty($cabanas)): ?>
                <?php foreach ($cabanas as $cabana): ?>
                    <?php
                    $numeroCabana = $cabana->numero ?? $cabana->id;

                    $webroot = Yii::getAlias('@webroot') . '/images/cabanas';
                    $webUrl = $baseImg;

                    $cabanaDir = Yii::getAlias('@webroot') . "/images/cabanas/cabana-{$numeroCabana}";
                    $cabanaUrl = Yii::getAlias('@web') . "/images/cabanas/cabana-{$numeroCabana}";

                    $files = [];
                    if (is_dir($cabanaDir)) {
                        $files = glob($cabanaDir . "/*.{jpg,jpeg,png,webp}", GLOB_BRACE);
                        sort($files);
                    }

                    $titulo = $cabana->descr ?? Yii::t('app', 'Cabaña {n}', ['n' => $numeroCabana]);
                    $descripcion = $cabana->descr
                        ?? Yii::t('app', 'Cabaña equipada para disfrutar de tu estadía en Dina Huapi.');
                    $capacidadTexto = !empty($cabana->max_pax)
                        ? Yii::t('app', 'Hasta {n} personas', ['n' => $cabana->max_pax])
                        : Yii::t('app', 'Capacidad cómoda para tu estadía');
                    ?>
                    <div class="col-md-6 col-lg-3">
                        <div class="card cabana-card h-100">
                            <div class="cabana-slider-wrapper position-relative mb-2">

                                <div class="cabana-slider-frame">
                                    <?php if (!empty($files)): ?>
                                        <?php foreach ($files as $path): ?>
                                            <?php $fileName = basename($path); ?>
                                            <img src="<?= Html::encode($cabanaUrl . '/' . $fileName) ?>" class="cabana-slider-img">
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <img src="<?= Html::encode($heroImg) ?>" class="cabana-slider-img active">
                                    <?php endif; ?>
                                </div>

                                <!-- Controles -->
                                <button class="cabana-slider-prev">&lsaquo;</button>
                                <button class="cabana-slider-next">&rsaquo;</button>
                            </div>
                            <div class="card-body d-flex flex-column">
                                <h3 class="dh-heading cabana-title mb-2 text-center">
                                    <?= Html::encode($titulo) ?>
                                </h3>

                                <div class="mb-3 cabana-tags text-center">
                                    <span class="badge-dh-soft cabana-badge-row">
                                        <i class="bi bi-people-fill me-1"></i>
                                        <?= Html::encode($capacidadTexto) ?>
                                    </span>
                                </div>

                                <div class="mb-3 cabana-features text-left">
                                    <span class="badge-dh-soft cabana-badge-row">
                                        <i class="bi bi-wifi me-1"></i>
                                        <?= Yii::t('app', 'Wi-Fi Internet') ?>
                                    </span>
                                    <span class="badge-dh-soft cabana-badge-row">
                                        <i class="bi bi-tv me-1"></i>
                                        <?= Yii::t('app', 'TV en cada cuarto') ?>
                                    </span>
                                    <span class="badge-dh-soft cabana-badge-row">
                                        <i class="bi bi-egg-fried me-1"></i>
                                        <?= Yii::t('app', 'Cocina completa') ?>
                                    </span>
                                    <span class="badge-dh-soft cabana-badge-row">
                                        <i class="bi bi-thermometer-half me-1"></i>
                                        <?= Yii::t('app', 'Calefacción central') ?>
                                    </span>
                                    <span class="badge-dh-soft cabana-badge-row">
                                        <i class="bi bi-stars me-1"></i>
                                        <?= Yii::t('app', 'Ropa de cama y toallas') ?>
                                    </span>
                                    <span class="badge-dh-soft cabana-badge-row">
                                        <i class="bi bi-fire me-1"></i>
                                        <?= Yii::t('app', 'Parrilla (fogón)') ?>
                                    </span>
                                    <span class="badge-dh-soft cabana-badge-row">
                                        <i class="bi bi-car-front me-1"></i>
                                        <?= Yii::t('app', 'Estacionamiento') ?>
                                    </span>
                                </div>

                                <div class="mt-auto d-grid gap-2">
                                    <?= Html::a(
                                        Yii::t('app', 'Ver detalles'),
                                        ['site/cabana', 'id' => $cabana->id],
                                        ['class' => 'btn btn-dh-primary dh-cabana-btn w-100']
                                    ) ?>
                                    <?php $actionBuscarCabana = ['disponibilidad/buscar-en-cabana', 'id_cabana' => $cabana->id] ?>
                                    <?= Html::a(
                                        Yii::t('app', 'Ver disponibilidad'),
                                        $actionBuscarCabana,
                                        ['class' => 'btn btn-outline-primary dh-cabana-availability-btn w-100']
                                    ) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Placeholder si aún no hay cabañas en BD -->
                <div class="col-12">
                    <div class="alert alert-info">
                        <?= Yii::t('app', 'Próximamente verás aquí el detalle de cada cabaña disponible para reservar.') ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- =================== EXPERIENCIA PATAGÓNICA =================== -->
    <section class="section-soft rounded-4 p-4 p-md-5 mb-5">
        <div class="row gy-4 align-items-center">
            <div class="col-lg-4">
                <h2 class="dh-heading h3 mb-3">
                    <?= Yii::t('app', 'Una experiencia patagónica frente al Nahuel Huapi') ?>
                </h2>
                <p class="text-muted mb-0">
                    <?= Yii::t('app', 'Dina Huapi combina la calma de la naturaleza con la cercanía a Bariloche. Desde nuestras cabañas vas a poder disfrutar del lago, la montaña y actividades durante todo el año.') ?>
                </p>
            </div>
            <div class="col-lg-8">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="bg-white rounded-4 p-3 h-100 shadow-sm">
                            <h3 class="dh-heading h6 mb-1">
                                <?= Yii::t('app', 'Frente al lago') ?>
                            </h3>
                            <p class="small text-muted mb-0">
                                <?= Yii::t('app', 'Acceso inmediato a la costa del Nahuel Huapi para caminar, descansar o simplemente disfrutar de la vista.') ?>
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="bg-white rounded-4 p-3 h-100 shadow-sm">
                            <h3 class="dh-heading h6 mb-1">
                                <?= Yii::t('app', 'Cabañas equipadas') ?>
                            </h3>
                            <p class="small text-muted mb-0">
                                <?= Yii::t('app', 'Cocina completa, calefacción, ropa blanca y todo lo necesario para que sólo te preocupes por descansar.') ?>
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="bg-white rounded-4 p-3 h-100 shadow-sm">
                            <h3 class="dh-heading h6 mb-1">
                                <?= Yii::t('app', 'Atención personalizada') ?>
                            </h3>
                            <p class="small text-muted mb-0">
                                <?= Yii::t('app', 'Te acompañamos durante el proceso de reserva y en tu estadía para que tu experiencia sea excelente.') ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- =================== CTA FINAL =================== -->
    <section class="text-center mb-2">
        <h2 class="dh-heading h4 mb-2">
            <?= Yii::t('app', '¿Listo para tu próxima escapada a la Patagonia?') ?>
        </h2>
        <p class="mb-3">
            <?= Yii::t('app', 'Consultá la disponibilidad de nuestras cabañas y empezá a planificar tu viaje.') ?>
        </p>
        <a href="<?= $actionBuscar ?>" class="btn btn-dh-primary btn-lg">
            <i class="bi bi-calendar-plus me-2"></i>
            <?= Yii::t('app', 'Buscar disponibilidad ahora') ?>
        </a>
    </section>

    <!-- BOTÓN FLOTANTE DE WHATSAPP -->
    <a href="<?= Html::encode($whatsAppUrl) ?>" class="dh-whatsapp-float" target="_blank" rel="noopener"
        aria-label="<?= Yii::t('app', 'Contactar por WhatsApp') ?>">
        <i class="bi bi-whatsapp"></i>
    </a>

</div>

<?php
// ================= CSS ESPECÍFICO DE HOME =================
$css = <<<CSS
/* ============== HOME / INDEX ESPECÍFICO ============== */

/* Cards de cabañas */
.cabana-card {
    border-radius: 1.25rem;
    border: none !important;
    background: rgba(255, 255, 255, 0.55) !important;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    box-shadow: 0 10px 24px rgba(0,0,0,0.10);
    overflow: hidden;
    transition: transform .18s ease, box-shadow .18s ease;
}

.cabana-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 18px 40px rgba(0,0,0,0.18);
}

/* Título de cada cabaña */
.cabana-title {
    font-size: 1.35rem;
    font-weight: 700;
    color: #154CA6;
    text-shadow: 0 1px 6px rgba(0,0,0,0.12);
}

/* Tags de arriba (capacidad, etc.) centrados */
.cabana-tags {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
}

/* Features de la cabaña alineadas a la izquierda */
.cabana-features {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 0.25rem;
    text-align: left;
}

/* Cada fila de feature */
.cabana-badge-row {
    display: inline-flex;
    align-items: center;
    justify-content: flex-start;
    padding: 0.35rem 0.9rem;
    width: 100%;
    max-width: 260px;
    font-size: 0.8rem;
    margin-inline: auto;
}

.cabana-features .badge-dh-soft {
    font-size: 0.75rem;
}

/* Sección "suave" de experiencia patagónica */
.section-soft {
    background-color: #F5F7FF;
}

/* ================= HERO HOME ================= */

/* HERO semitransparente, deja ver el fondo animado */
.dh-hero {
    border-radius: 2rem;
    background: rgba(255, 255, 255, 0.65);
    border: 1px solid rgba(255,255,255,0.45);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.12);
}

/* Logo grande del hero */
.dh-hero-logo {
    max-width: 420px;
    height: auto;
}

/* Caja del logo: recuadro redondeado que recorta la imagen */
.dh-logo-box {
    background: rgba(255, 255, 255, 0.85);
    border-radius: 1.5rem;
    box-shadow: 0 8px 24px rgba(0,0,0,0.10);
    overflow: hidden;
    display: inline-block;
}

/* Título del hero */
.dh-hero-title {
    font-family: 'League Spartan', sans-serif;
    font-weight: 700;
    font-size: 3.4rem;
    line-height: 1.05;
    color: #1D1D1D;
}

/* Subtítulo del hero */
.dh-hero-subtitle {
    font-family: 'Poppins', sans-serif;
    font-size: 1.25rem;
    font-weight: 300;
    color: #4c4c4c;
    max-width: 520px;
}

/* Slider grande a la derecha */
.dh-hero-slider {
    width: 100%;
    height: 420px;
}

/* Frame del slider */
.dh-slider-frame {
    width: 100%;
    height: 100%;
    position: relative;
}

/* Imágenes del slider principal */
.dh-slider-img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    opacity: 0;
    transition: opacity 1.2s ease-in-out;
}

.dh-slider-img.active {
    opacity: 1;
}

/* Títulos secciones principales */
.dh-section-title {
    font-family: 'League Spartan', sans-serif;
    font-size: 2.6rem;
    font-weight: 700;
    color: #154CA6;
    text-shadow: 0 2px 10px rgba(0,0,0,0.12);
    letter-spacing: 0.5px;
}

.dh-section-subtitle {
    font-family: 'Poppins', sans-serif;
    font-size: 1rem;
    font-weight: 400;
    color: #1f2933;
    text-shadow: 0 1px 4px rgba(255,255,255,0.6);
}

/* Slider de mini-galerías en cada card */
.cabana-slider-wrapper {
    border-radius: 1.2rem;
    overflow: hidden;
    height: 200px;
    position: relative;
    box-shadow: 0 10px 25px rgba(0,0,0,0.12);
}

.cabana-slider-frame {
    width: 100%;
    height: 100%;
    position: relative;
}

.cabana-slider-img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    opacity: 0;
    transition: opacity .9s ease-in-out;
}

.cabana-slider-img.active {
    opacity: 1;
}

/* Botones slider */
.cabana-slider-prev,
.cabana-slider-next {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(255,255,255,0.75);
    border: none;
    font-size: 2rem;
    line-height: 1;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    cursor: pointer;
    color: #154CA6;
    box-shadow: 0 2px 10px rgba(0,0,0,0.20);
    transition: background .2s;
}

.cabana-slider-prev:hover,
.cabana-slider-next:hover {
    background: rgba(255,255,255,1);
}

.cabana-slider-prev {
    left: 10px;
}

.cabana-slider-next {
    right: 10px;
}

/* Botón principal en la card: VER DETALLES */
.dh-cabana-btn {
    padding: 0.9rem 1.4rem;
    font-weight: 600;
    border-radius: 1.5rem;
    font-size: 1.05rem;
    letter-spacing: .02em;

    background-color: #154CA6 !important;
    border-color: #154CA6 !important;
    color: #ffffff !important;
    box-shadow: 0 6px 20px rgba(21, 76, 166, 0.35);
}

.dh-cabana-btn:hover,
.dh-cabana-btn:focus {
    background-color: #0f3a7c !important;
    border-color: #0f3a7c !important;
    color: #ffffff !important;
    transform: translateY(-2px);
    box-shadow: 0 12px 28px rgba(21, 76, 166, 0.45);
}

/* Botón secundario: VER DISPONIBILIDAD */
.dh-cabana-availability-btn {
    border-radius: 1.5rem;
    font-weight: 600;
    font-size: 0.98rem;
    padding: 0.85rem 1.4rem;
    color: #154CA6 !important;
    border-width: 2px;
    background-color: rgba(255,255,255,0.85);
}

.dh-cabana-availability-btn:hover {
    background-color: #154CA6;
    color: #ffffff !important;
}


/* ================= BOTÓN FLOTANTE WHATSAPP ================= */
.dh-whatsapp-float {
    position: fixed;
    right: 1.5rem;
    bottom: 1.5rem;
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background-color: #25D366; /* verde WhatsApp */
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.35);
    z-index: 1080;
    border: 3px solid rgba(255,255,255,0.9);
    text-decoration: none;
    transition: transform .2s ease, box-shadow .2s ease, background-color .2s ease;
}

.dh-whatsapp-float:hover,
.dh-whatsapp-float:focus {
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 8px 20px rgba(0,0,0,0.45);
    background-color: #1ebe57;
    color: #fff;
}

/* Un poquito más chico en pantallas muy pequeñas */
@media (max-width: 576px) {
    .dh-whatsapp-float {
        width: 52px;
        height: 52px;
        font-size: 1.6rem;
        right: 1rem;
        bottom: 1rem;
    }
}


/* Responsive */
@media (max-width: 991.98px) {
    .dh-hero-slider {
        height: 280px;
        margin-top: 1rem;
    }

    .dh-hero-title {
        font-size: 2.4rem;
    }

    .dh-hero-logo {
        max-width: 320px;
    }
}
CSS;

$this->registerCss($css);




// ================= JS ESPECÍFICO DE HOME =================
$js = <<<JS
/* ===================== HERO SLIDER ===================== */
let sliderIndex = 0;
const sliderImages = document.querySelectorAll('.dh-slider-img');

if (sliderImages.length > 0) {
    function showSlide() {
        sliderImages.forEach(img => img.classList.remove('active'));
        sliderImages[sliderIndex].classList.add('active');
        sliderIndex = (sliderIndex + 1) % sliderImages.length;
    }
    showSlide();
    setInterval(showSlide, 4500);
}

/* ===================== SLIDERS DE CADA CABAÑA ===================== */
document.querySelectorAll('.cabana-slider-wrapper').forEach(wrapper => {
    const images = wrapper.querySelectorAll('.cabana-slider-img');
    const prevBtn = wrapper.querySelector('.cabana-slider-prev');
    const nextBtn = wrapper.querySelector('.cabana-slider-next');

    if (!images.length || !prevBtn || !nextBtn) {
        return;
    }

    let index = 0;
    images[0].classList.add('active');

    const show = (i) => {
        images.forEach(img => img.classList.remove('active'));
        images[i].classList.add('active');
    };

    prevBtn.addEventListener('click', () => {
        index = (index - 1 + images.length) % images.length;
        show(index);
    });

    nextBtn.addEventListener('click', () => {
        index = (index + 1) % images.length;
        show(index);
    });

    // Cambio automático cada 5s
    setInterval(() => {
        index = (index + 1) % images.length;
        show(index);
    }, 5000);
});
JS;

$this->registerJs($js);
?>