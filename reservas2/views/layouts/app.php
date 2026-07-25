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

AppAsset::register($this);

$this->registerCsrfMetaTags();
$this->registerMetaTag(['charset' => Yii::$app->charset], 'charset');
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1, shrink-to-fit=no']);
$this->registerMetaTag(['name' => 'description', 'content' => $this->params['meta_description'] ?? '']);
$this->registerMetaTag(['name' => 'keywords', 'content' => $this->params['meta_keywords'] ?? '']);
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

        [data-bs-theme="dark"] .select2-container--krajee-bs5 .select2-selection,
        [data-bs-theme="dark"] .select2-container--krajee .select2-selection,
        [data-bs-theme="dark"] .select2-container--default .select2-selection {
            background-color: var(--bs-body-bg);
            border-color: var(--bs-border-color);
            color: var(--bs-body-color);
        }

        [data-bs-theme="dark"] .select2-container--krajee-bs5 .select2-selection__rendered,
        [data-bs-theme="dark"] .select2-container--krajee .select2-selection__rendered,
        [data-bs-theme="dark"] .select2-container--default .select2-selection__rendered {
            color: var(--bs-body-color);
        }

        [data-bs-theme="dark"] .select2-container--krajee-bs5 .select2-selection__placeholder,
        [data-bs-theme="dark"] .select2-container--krajee .select2-selection__placeholder,
        [data-bs-theme="dark"] .select2-container--default .select2-selection__placeholder {
            color: var(--bs-secondary-color);
        }

        [data-bs-theme="dark"] .select2-container--krajee-bs5 .select2-selection__choice,
        [data-bs-theme="dark"] .select2-container--krajee .select2-selection__choice,
        [data-bs-theme="dark"] .select2-container--default .select2-selection__choice {
            background-color: var(--bs-secondary-bg);
            border-color: var(--bs-border-color);
            color: var(--bs-body-color);
        }

        [data-bs-theme="dark"] .select2-container--krajee-bs5 .select2-selection__choice__remove,
        [data-bs-theme="dark"] .select2-container--krajee .select2-selection__choice__remove,
        [data-bs-theme="dark"] .select2-container--default .select2-selection__choice__remove {
            color: var(--bs-secondary-color);
        }

        [data-bs-theme="dark"] .select2-dropdown,
        [data-bs-theme="dark"] .select2-container--krajee-bs5 .select2-dropdown,
        [data-bs-theme="dark"] .select2-container--krajee .select2-dropdown,
        [data-bs-theme="dark"] .select2-container--default .select2-dropdown {
            background-color: var(--bs-body-bg) !important;
            border-color: var(--bs-border-color) !important;
            color: var(--bs-body-color) !important;
        }

        [data-bs-theme="dark"] .select2-search__field {
            background-color: var(--bs-body-bg) !important;
            border-color: var(--bs-border-color) !important;
            color: var(--bs-body-color) !important;
        }

        [data-bs-theme="dark"] .select2-results,
        [data-bs-theme="dark"] .select2-results__options,
        [data-bs-theme="dark"] .select2-container--krajee-bs5 .select2-results,
        [data-bs-theme="dark"] .select2-container--krajee-bs5 .select2-results__options,
        [data-bs-theme="dark"] .select2-container--krajee .select2-results,
        [data-bs-theme="dark"] .select2-container--krajee .select2-results__options,
        [data-bs-theme="dark"] .select2-container--default .select2-results,
        [data-bs-theme="dark"] .select2-container--default .select2-results__options {
            background-color: var(--bs-body-bg) !important;
            color: var(--bs-body-color) !important;
        }

        [data-bs-theme="dark"] .select2-results__option,
        [data-bs-theme="dark"] .select2-container--krajee-bs5 .select2-results__option,
        [data-bs-theme="dark"] .select2-container--krajee .select2-results__option,
        [data-bs-theme="dark"] .select2-container--default .select2-results__option {
            background-color: var(--bs-body-bg) !important;
            color: var(--bs-body-color) !important;
        }

        [data-bs-theme="dark"] .select2-results__option--selected,
        [data-bs-theme="dark"] .select2-container--krajee-bs5 .select2-results__option--selected,
        [data-bs-theme="dark"] .select2-container--krajee .select2-results__option--selected,
        [data-bs-theme="dark"] .select2-container--default .select2-results__option--selected {
            background-color: var(--bs-secondary-bg) !important;
            color: var(--bs-body-color) !important;
        }

        [data-bs-theme="dark"] .select2-results__option--highlighted,
        [data-bs-theme="dark"] .select2-container--default .select2-results__option--highlighted[aria-selected],
        [data-bs-theme="dark"] .select2-container--krajee .select2-results__option--highlighted[aria-selected],
        [data-bs-theme="dark"] .select2-container--krajee-bs5 .select2-results__option--highlighted[aria-selected] {
            background-color: var(--bs-primary) !important;
            color: #fff !important;
        }


        /* Logo pequeño + texto marca */
        .dh-navbar-logo {
            max-height: 40px;
            width: auto;
        }

        #header .dropdown-menu .dropend {
            position: relative;
        }

        #header .dropdown-menu .dropend>.dropdown-menu {
            top: 0;
            left: 100%;
            margin-top: 0;
            margin-left: var(--bs-dropdown-spacer);
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

            function closeNestedDropdowns(container, exceptDropend = null) {
                if (!container) {
                    return;
                }

                container.querySelectorAll(':scope > .dropend').forEach(function (dropend) {
                    if (exceptDropend && dropend === exceptDropend) {
                        return;
                    }

                    dropend.classList.remove('show');

                    const toggle = dropend.querySelector(':scope > .dropdown-toggle');
                    const submenu = dropend.querySelector(':scope > .dropdown-menu');

                    if (toggle) {
                        toggle.classList.remove('show');
                        toggle.setAttribute('aria-expanded', 'false');
                    }

                    if (submenu) {
                        submenu.classList.remove('show');
                    }
                });
            }

            document.querySelectorAll('#header .nav-item.dropdown').forEach(function (dropdown) {
                dropdown.addEventListener('show.bs.dropdown', function () {
                    document.querySelectorAll('#header .nav-item.dropdown.show').forEach(function (openDropdown) {
                        if (openDropdown === dropdown) {
                            return;
                        }

                        const toggle = openDropdown.querySelector(':scope > .dropdown-toggle, [data-bs-toggle="dropdown"]');
                        if (toggle) {
                            bootstrap.Dropdown.getOrCreateInstance(toggle).hide();
                        }
                    });
                });

                dropdown.addEventListener('hide.bs.dropdown', function () {
                    const rootMenu = dropdown.querySelector(':scope > .dropdown-menu');
                    closeNestedDropdowns(rootMenu);
                });
            });

            document.querySelectorAll('#header .dropdown-menu .dropend > .js-submenu-toggle').forEach(function (element) {
                element.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();

                    const currentDropend = this.closest('.dropend');
                    const parentMenu = currentDropend ? currentDropend.parentElement : null;
                    const currentMenu = currentDropend ? currentDropend.querySelector(':scope > .dropdown-menu') : null;

                    if (!currentDropend || !parentMenu || !currentMenu) {
                        return;
                    }

                    const shouldOpen = !currentMenu.classList.contains('show');

                    closeNestedDropdowns(parentMenu, shouldOpen ? currentDropend : null);

                    currentDropend.classList.toggle('show', shouldOpen);
                    currentMenu.classList.toggle('show', shouldOpen);
                    this.classList.toggle('show', shouldOpen);
                    this.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
                });
            });

            document.querySelectorAll('#header .dropdown-menu').forEach(function (menu) {
                menu.addEventListener('click', function (e) {
                    e.stopPropagation();
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
            'brandLabel' => Html::a(
                Html::img(Yii::getAlias('@web') . '/images/logos/logo1_transparente.png', [
                    'class' => 'dh-navbar-logo me-2',
                    'alt' => 'Cabañas Dina Huapi',
                ])
                ,
                Yii::$app->homeUrl,
                ['class' => 'navbar-brand d-flex align-items-center']
            ),
            'brandUrl' => Yii::$app->homeUrl,
            'options' => ['class' => 'navbar-expand-md navbar-dark bg-dark fixed-top']
        ]);



        if (Yii::$app->user->isGuest) {
            $items = [
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


