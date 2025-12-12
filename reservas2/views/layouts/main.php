<?php

/** @var yii\web\View $this */
/** @var string $content */


use app\assets\PublicPortalAsset;
use app\widgets\Alert;
use yii\bootstrap5\Html;
use yii\bootstrap5\Nav;
use yii\bootstrap5\NavBar;
use yii\captcha\Captcha;
use yii\helpers\Url;


PublicPortalAsset::register($this);


$this->registerCsrfMetaTags();
$this->registerMetaTag(['charset' => Yii::$app->charset], 'charset');
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1, shrink-to-fit=no']);
$this->registerLinkTag([
    'rel' => 'icon',
    'type' => 'image/x-icon',
    'href' => Yii::getAlias('@web/favicon.ico'),
]);

$this->registerLinkTag([
    'rel' => 'icon',
    'type' => 'image/png',
    'sizes' => '16x16',
    'href' => Yii::getAlias('@web/favicon-16x16.png'),
]);
$this->registerLinkTag([
    'rel' => 'icon',
    'type' => 'image/png',
    'sizes' => '32x32',
    'href' => Yii::getAlias('@web/favicon-32x32.png'),
]);
$this->registerLinkTag([
    'rel' => 'apple-touch-icon',
    'sizes' => '180x180',
    'href' => Yii::getAlias('@web/apple-touch-icon.png'),
]);
$this->registerLinkTag([
    'rel' => 'manifest',
    'href' => Yii::getAlias('@web/site.webmanifest'),
]);


// Fuentes del portal público
$this->registerCssFile(
    'https://fonts.googleapis.com/css2?family=League+Spartan:wght@400;600;700&family=Poppins:wght@300;400;500;600&display=swap',
    ['rel' => 'stylesheet']
);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="h-100" data-bs-theme="auto">

<head>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>

<body class="d-flex flex-column h-100">
    <?php $this->beginBody() ?>

    <header id="header">
        <?php

        NavBar::begin([
            'brandLabel' => Html::a(
                Html::img(Yii::getAlias('@web') . '/images/logos/logo1.png', [
                    'class' => 'dh-navbar-logo me-2',
                    'alt' => 'Cabañas Dina Huapi',
                ])
                ,
                Yii::$app->homeUrl,
                ['class' => 'navbar-brand d-flex align-items-center']
            ),
            'brandUrl' => Yii::$app->homeUrl,
            'options' => [
                // IMPORTANTE: nada de bg-dark ni navbar-dark
                'class' => 'navbar navbar-expand-md dh-navbar fixed-top',
            ],
        ]);

        // Menú izquierdo (público)
        echo Nav::widget([
            'options' => ['class' => 'navbar-nav me-auto'],
            'items' => [
                [
                    'label' => '<i class="bi bi-journal-check me-1"></i> ' . Yii::t('app', 'Mi reserva'),
                    'url' => '#',
                    'linkOptions' => [
                        'data-bs-toggle' => 'modal',
                        'data-bs-target' => '#miReservaModal', // ✅ mismo id que el modal
                    ],
                    'encode' => false,
                ],
                [
                    'label' => '<i class="bi bi-telephone me-1"></i> '.Yii::t('app', 'Contacto'),
                    'url' => ['/site/contact'],
                    'encode' => false,
                ],
                [
                    'label' => '<i class="bi bi-geo-alt-fill me-1"></i> ' . Yii::t('app', 'Cómo llegar'),
                    'url' => ['/site/como-llegar'],
                    'encode' => false,
                ],
                [
                    'label' => '<i class="bi bi-images me-1"></i> '.Yii::t('app', 'Imágenes'),
                    'url' => ['/site/imagenes'],
                    'encode' => false,
                ],
            ],
        ]);

        echo '<ul class="navbar-nav ms-auto align-items-center">';

        // Botón "Ingresar" (área admin / login)
        echo '<li class="nav-item me-2">'
            . Html::a(
                '<i class="bi bi-box-arrow-in-right me-1"></i><span>Ingresar</span>',
                ['/site/login'],
                ['class' => 'nav-link d-flex align-items-center', 'encode' => false]
            )
            . '</li>';

        // Language switcher
        $supported = Yii::$app->params['supportedLanguages'] ?? ['es' => 'Español', 'en' => 'English'];
        $currentLang = Yii::$app->language;

        echo '<li class="nav-item dropdown">';
        echo '<a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="langDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">';
        echo '<i class="bi bi-translate me-1"></i>';
        echo Html::encode($supported[$currentLang] ?? strtoupper($currentLang));
        echo '</a>';

        echo '<ul class="dropdown-menu dropdown-menu-end" aria-labelledby="langDropdown">';
        foreach ($supported as $code => $label) {
            $url = Url::current(['lang' => $code]);
            $active = $code === $currentLang ? ' active' : '';
            echo '<li>';
            echo '<a class="dropdown-item' . $active . '" href="' . Html::encode($url) . '">'
                . Html::encode($label)
                . ' <small class="text-muted ms-1">' . Html::encode($code) . '</small>'
                . '</a>';
            echo '</li>';
        }
        echo '</ul>';
        echo '</li>';

        echo '</ul>';

        NavBar::end();
        ?>
    </header>

    <main id="main" class="flex-shrink-0" role="main">
        <?= Alert::widget() ?>
        <?= $content ?>
    </main>

    <?= $this->render('//partials/_miReservaModal') ?>


    <footer id="footer" class="mt-auto py-4 dh-footer">
        <div class="container">

            <div class="row justify-content-between align-items-center text-muted">

                <!-- Izquierda -->
                <div class="col-md-4 text-center text-md-start mb-3 mb-md-0">
                    &copy; LMH <?= date('Y') ?>
                </div>

                <!-- Centro: Redes sociales -->
                <div class="col-md-4 text-center mb-3 mb-md-0">

                    <span class="me-2 fw-semibold">
                        <?= Yii::t('app', 'Seguinos en') ?>:
                    </span>

                    <?php
                    $social = Yii::$app->params['social'] ?? [];
                    ?>

                    <?php foreach ($social as $net): ?>
                        <a href="<?= Html::encode($net['url']) ?>" target="_blank" rel="noopener"
                            class="footer-social-link">
                            <i class="<?= Html::encode($net['icon']) ?>"></i>
                        </a>
                    <?php endforeach; ?>

                </div>
                <!-- Derecha -->
                <div class="col-md-4 text-center text-md-end">
                    <?= Yii::powered() ?>
                </div>

            </div>
        </div>
    </footer>


    <script>
        document.addEventListener("scroll", function () {

            const nav = document.querySelector(".dh-navbar");
            if (!nav) return;

            if (window.scrollY > 20) {
                nav.classList.add("scrolled");
            } else {
                nav.classList.remove("scrolled");
            }


            const footer = document.querySelector(".dh-footer");
            if (!footer) return;

            if (window.scrollY > 80) {
                footer.classList.add("scrolled");
            } else {
                footer.classList.remove("scrolled");
            }
        });
    </script>


    <?php $this->endBody() ?>

</body>

</html>
<?php $this->endPage() ?>