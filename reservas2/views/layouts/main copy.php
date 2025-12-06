<?php

/** @var yii\web\View $this */
/** @var string $content */

use app\assets\AppAsset;
use app\config\RootMenu;
use app\widgets\Alert;
use yii\bootstrap5\Breadcrumbs;
use yii\bootstrap5\Html;
use yii\bootstrap5\Nav;
use yii\bootstrap5\NavBar;
use yii\helpers\Url;

//use app\assets\PublicPortalAsset;


AppAsset::register($this);
//PublicPortalAsset::register($this);

$this->registerCsrfMetaTags();
$this->registerMetaTag(['charset' => Yii::$app->charset], 'charset');
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1, shrink-to-fit=no']);
$this->registerMetaTag(['name' => 'description', 'content' => $this->params['meta_description'] ?? '']);
$this->registerMetaTag(['name' => 'keywords', 'content' => $this->params['meta_keywords'] ?? '']);
$this->registerLinkTag(['rel' => 'icon', 'type' => 'image/x-icon', 'href' => Yii::getAlias('@web/favicon.ico')]);

// 👉 Agregar fuentes de marca
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
    <!-- Bootstrap 5.3 con soporte para data-bs-theme -->
    <link href="<?= Yii::getAlias('@web') ?>/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN"
          crossorigin="anonymous">

    <!-- Agregar el favicon -->

    <style>
        [data-bs-theme="dark"] {
            --bs-primary: #001199;
            --bs-primary-bg-subtle: #001199;
            --bs-primary-bg-subtle-dark: #001199;
        }

        [data-bs-theme="dark"] .btn-primary {
            --bs-btn-bg: #001199;
        }
    </style>


    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const themeButtons = document.querySelectorAll("[data-bs-theme-value]");
            const rootElement = document.documentElement;
            const themeToggleButton = document.querySelector("#bd-theme .theme-icon-active");
            const savedTheme = localStorage.getItem("theme") || "auto";
            const buttons = document.querySelectorAll(".theme-menu-item button");

            // Mapeo de iconos según el tema
            const themeIcons = {
                light: "bi-sun-fill", // ☀️ Icono para claro
                dark: "bi-moon-stars-fill", // 🌙 Icono para oscuro
                auto: "bi-circle-half" // ⚡ Icono para automático
            };

            // Función para cambiar el tema, el icono y resaltar el ítem del menú
            function setTheme(theme) {
                rootElement.setAttribute("data-bs-theme", theme);
                localStorage.setItem("theme", theme);

                // Cambiar el icono del botón
                if (themeToggleButton) {
                    themeToggleButton.className = `bi ${themeIcons[theme]} my-1 theme-icon-active`;
                }

                // Remover clase active de todos los botones
                buttons.forEach(button => {
                    button.classList.remove("active");
                });

                // Agregar la clase active solo al botón seleccionado
                const selectedButton = document.querySelector(`[data-bs-theme-value="${theme}"] button`);
                if (selectedButton) {
                    selectedButton.classList.add("active");
                }
            }

            // Aplicar el tema guardado y actualizar el icono
            setTheme(savedTheme);

            // Agregar evento a los botones del menú
            themeButtons.forEach(button => {
                button.addEventListener("click", function () {
                    const selectedTheme = this.getAttribute("data-bs-theme-value");
                    setTheme(selectedTheme);
                });
            });

            // Seleccionar todos los dropdown-toggle dentro de un dropdown
            document.querySelectorAll('.dropdown-menu .dropdown-toggle').forEach(function (element) {
                element.addEventListener('click', function (e) {
                    e.stopPropagation(); // Evita que se cierre el menú al hacer clic
                });
            });
        });




    </script>





</head>

<body class="d-flex flex-column h-100">
    <?php $this->beginBody() ?>

    <header id="header">
        <?php



        NavBar::begin([
            /*
            'brandLabel' => Html::img(Empresas::getMyConfig(
                'images',
                'brandLabel'
            ), ["width" => "70px"]),
            */
            'brandUrl' => Yii::$app->homeUrl,
            'options' => ['class' => 'navbar-expand-md navbar-dark bg-dark fixed-top']
        ]);



        if (Yii::$app->user->isGuest) {
            $items = [
                ['label' => 'Home', 'url' => ['/site/index']],
                ['label' => 'Acerca de', 'url' => ['/site/about']],
                ['label' => 'Contacto', 'url' => ['/site/contact']],
            ];



            echo Nav::widget([
                'options' => ['class' => 'navbar-nav'],
                'items' => $items,
            ]);


        } else {


            echo app\models\Menu::getMenu([RootMenu::OTHER], Yii::$app->user->identity);



        }


        echo '<ul class="navbar-nav ms-auto">'; // 'ms-auto' alinea a la derecha
        if (Yii::$app->user->isGuest) {
            echo app\models\Menu::getcustomMenu("Ingresar", '<i class="bi bi-box-arrow-in-right"></i>', '/site/login');
        } else {
            $items = [
                Yii::$app->user->isGuest ?
                ['label' => 'Login', 'url' => ['/site/login']] :
                [
                    'label' => 'Salir (' . Yii::$app->user->identity->username() . ')<i class="bi bi-box-arrow-right ms-2"></i>',
                    'encode' => false,
                    'url' => ['/site/logout'],
                    'active' => true,
                    'linkOptions' => ['data-method' => 'post']
                ]
            ];

            echo Nav::widget([
                'options' => ['class' => 'navbar-nav'],
                'items' => $items,
            ]);

            echo app\models\Menu::getNotificacionesMenu('/notificaciones/index', Yii::$app->user->identity);

        }


        //echo app\models\Menu::getColSeparator();
        echo app\models\Menu::getDarkModeMenu();

        // ---- Language Switcher (dropdown) ----
        $supported = Yii::$app->params['supportedLanguages'] ?? ['es' => 'Español', 'en' => 'English'];
        $currentLang = Yii::$app->language;

        // Item dropdown al final del navbar-right
        echo '<li class="nav-item dropdown">';

        echo '<a class="nav-link dropdown-toggle" href="#" id="langDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">';
        echo '<i class="bi bi-translate me-1"></i>' . Html::encode($supported[$currentLang] ?? strtoupper($currentLang));
        echo '</a>';

        echo '<ul class="dropdown-menu dropdown-menu-end" aria-labelledby="langDropdown">';

        foreach ($supported as $code => $label) {
            // Mantiene la ruta actual y agrega/reemplaza ?lang=<code>
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
        // ---- /Language Switcher ----
        


        echo '</ul>';

        NavBar::end();


        ?>
    </header>

    <main id="main" class="flex-shrink-0" role="main">
        <div class="container">
            <?php if (!empty($this->params['breadcrumbs'])): ?>
                <?= Breadcrumbs::widget(['links' => $this->params['breadcrumbs']]) ?>
            <?php endif ?>
            <?= Alert::widget() ?>


            <?= $content ?>
        </div>
    </main>


    <footer id="footer" class="mt-auto py-3 bg-dark">
        <div class="container">
            <div class="row text-muted">
                <div class="col-md-6 text-center text-md-start">&copy; LMH <?= date('Y') ?></div>
                <div class="col-md-6 text-center text-md-end"><?= Yii::powered() ?></div>
            </div>
        </div>
    </footer>
    <?php $this->endBody() ?>
</body>

</html>
<?php $this->endPage() ?>