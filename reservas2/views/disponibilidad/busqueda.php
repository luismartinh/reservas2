<?php

use app\widgets\Alert;
use yii\bootstrap5\Html;
use yii\widgets\ListView;
use yii\widgets\Pjax;

$this->registerCssFile('@web/css/cabana.css', [
    'depends' => [\yii\bootstrap5\BootstrapAsset::class]
]);


/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var app\models\DisponibilidadSearch $searchModel
 * @var array $totales
 * @var bool $esAdmin
 * @var app\models\Cabana $cabana
 */

if ($cabana) {
    $this->title = Yii::t('app', 'Buscar disponibilidad de ') . $cabana->descr;
    $cabana_selected = $cabana->id;
} else {
    $this->title = Yii::t('app', 'Buscar disponibilidad');
    $cabana_selected = null;
}


// hubo búsqueda si ambos extremos del rango vienen cargados
$buscado = !empty($searchModel->desde) && !empty($searchModel->hasta);

// Determinar si hay un rango seleccionado:
$mostrarPeriodo = $buscado;

// Texto para JS (ya traducido)
$mustSelectMsg = Yii::t('app', 'Debe seleccionar al menos una cabaña para continuar.');


$frmAction = $esAdmin ? ['reserva/solicitar-reserva'] : ['disponibilidad/solicitar-reserva'];



?>
<div class="site-index container py-5 py-lg-5">

    <?php if (!$esAdmin) {
        echo $this->render('//partials/_dhBackground');
    }
    ?>



    <section class="dh-hero mb-5">

        <div class="mb-3">
            <?= Alert::widget() ?>
        </div>
        <h1 class="dh-heading dh-cabana-main-title mb-1"><?= $this->title ?></h1>

        <?php
        Pjax::begin([
            'id' => 'pjax-disponibilidad',
            'enablePushState' => false,
            // Solo este form será manejado por PJAX
            'formSelector' => '#form-busqueda',
            'linkSelector' => false,
        ]);
        ?>

        <div class="dh-cabana-info dh-glass-box card w-80 p-4 mt-5">
            <?= $this->render('_search', ['model' => $searchModel, 'esAdmin' => $esAdmin, 'cabana' => $cabana]); ?>
        </div>

        <?php if ($mostrarPeriodo): ?>
            <div class="card border-primary shadow-sm mb-4 bg-dark-subtle mt-3">
                <div class="card-body text-center">
                    <h6 class="card-title text-primary fw-bold mb-2">
                        <i class="bi bi-calendar-range-fill me-2"></i>
                        <?= Yii::t('app', 'Período de búsqueda') ?>
                    </h6>

                    <div class="fs-5">
                        <span class="text-light bg-primary px-3 py-1 rounded-pill">
                            <i class="bi bi-calendar-event me-1"></i>
                            <?= Html::encode($searchModel->desde) ?>
                        </span>
                        <span class="mx-2 text-muted">→</span>
                        <span class="text-light bg-primary px-3 py-1 rounded-pill">
                            <i class="bi bi-calendar-check me-1"></i>
                            <?= Html::encode($searchModel->hasta) ?>
                        </span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="dh-cabana-info dh-glass-box card w-80 p-4 mt-3">
            <?php if ($buscado): ?>

                <?php
                // 👉 Abrimos el form “real” de Yii (incluye CSRF automáticamente)
                //     Lo marcamos con data-pjax=0 para que NO lo intercepte el PJAX.
                echo Html::beginForm($frmAction, 'post', [
                    'id' => 'frm-solicitar',
                    'data-pjax' => 0,
                ]);


                // Hidden con el período buscado
                echo Html::hiddenInput('desde', $searchModel->desde ?? '');
                echo Html::hiddenInput('hasta', $searchModel->hasta ?? '');
                ?>

                <?= ListView::widget([
                    'dataProvider' => $dataProvider,
                    'emptyText' => $buscado
                        ? '<div class="alert alert-warning">' . Yii::t('app', 'No se encontraron cabañas disponibles para el período indicado.') . '</div>'
                        : '',
                    'itemView' => '_cabana_card',
                    'viewParams' => [
                        'totales' => $totales,
                        'desde' => $searchModel->desde ?? null,
                        'hasta' => $searchModel->hasta ?? null,
                        'cabana_selected' => $cabana_selected
                    ],
                    'options' => ['class' => 'list-unstyled'],
                    'itemOptions' => ['class' => 'mb-3'],
                    'layout' => "{items}\n<div class='mt-3'>{pager}</div>",
                    'pager' => [
                        'maxButtonCount' => 7,
                        'options' => ['class' => 'pagination justify-content-center'],
                    ],
                ]); ?>

                <?php if ($dataProvider->getCount() > 0): ?>
                    <div class="text-center mt-4">
                        <button type="submit" id="btn-solicitar-reserva" class="btn btn-success btn-lg px-5">
                            <i class="bi bi-send"></i>
                            <?= $esAdmin ? Yii::t('app', 'Crear reserva') :
                                Yii::t('app', 'Solicitar reserva') ?>
                        </button>


                    </div>
                <?php endif; ?>

                <?= Html::endForm(); ?>

                <script>
                    (function () {
                        const form = document.getElementById('frm-solicitar');
                        if (!form) return;

                        form.addEventListener('submit', function (e) {
                            // sólo contamos los switches visibles (las cards con precio los muestran)
                            const anyChecked = document.querySelectorAll('.cabana-switch:checked').length > 0;
                            if (!anyChecked) {
                                e.preventDefault();
                                alert(<?= json_encode($mustSelectMsg) ?>);
                                return false;
                            }
                            // deja enviar normalmente (fuera de PJAX)
                            return true;
                        });
                    })();
                </script>

            <?php endif; ?>
        </div>

        <?php Pjax::end(); ?>
    </section>
</div>