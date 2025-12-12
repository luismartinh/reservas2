<?php

use yii\bootstrap5\Html;
use yii\helpers\Url;
use yii\widgets\Pjax;
use yii\widgets\ActiveForm;
use kartik\grid\GridView;
use kartik\select2\Select2;
use kartik\icons\FontAwesomeAsset;
FontAwesomeAsset::register($this);


/** @var yii\web\View $this */
/** @var app\models\Cabana $cabana */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array $listaTarifas */
/** @var int $id_cabana */

$this->title = Yii::t('models', 'Vincular Tarifas a: ') . $cabana->descr;
$this->params['breadcrumbs'][] = ['label' => Yii::t('models', 'Cabañas'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="cabana-vincular-tarifas">

    <h1>
        <?= Html::encode($this->title) ?>

        <small class="text-muted">
            <?= Html::encode($model->descr) ?>
        </small>
    </h1>

    <div class="clearfix">

        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalVincular">
            <i class="bi bi-link-45deg me-1"></i><?= Yii::t('cruds', 'Asociar tarifas') ?>
        </button>

        <?= Html::a(
            '<i class="bi bi-arrow-counterclockwise me-2"></i>' . Yii::t('cruds', 'Cancelar'),
            ['cabana/index'],
            ['class' => 'btn btn-outline-secondary btn-default float-end']
        ) ?>


    </div>


    <?php Pjax::begin(['id' => 'pjax-cabana-tarifas', 'enablePushState' => false]); ?>

    <!-- GRILLA tarifas ya asociadas -->
    <div class="card mt-2">
        <div class="card-header"><?= Yii::t('cruds', 'Tarifas ya asociadas') ?></div>
        <div class="card-body">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'pjax' => false, // ya estamos dentro de un PJAX
                'columns' => [
                    [
                        'class' => 'kartik\grid\ActionColumn',
                        'template' => '{delete} {editar}',
                        'buttons' => [
                            'delete' => function ($url, $model) {
                                    return Html::a(
                                        '<span class="fas fa-trash-alt"></span>',
                                        ['eliminar-vinculacion', 'id' => $model->id],
                                        [
                                            'class' => 'btn btn-sm btn-outline-danger',
                                            'title' => Yii::t('cruds', 'Eliminar vinculación'),
                                            'data-method' => 'post',
                                            'data-confirm' => Yii::t('cruds', '¿Eliminar esta vinculación?'),
                                            'data-pjax' => '1',
                                        ]
                                    );
                                },
                            'editar' => function ($url, $model, $key) {
                                    $options = [
                                        'title' => Yii::t('cruds', 'Editar Tarifa'),
                                        'aria-label' => Yii::t('cruds', 'Editar Tarifa'),
                                        'style' => 'margin-left: 6px;',
                                        'data-pjax' => '0',
                                    ];

                                    $url = Url::toRoute(['tarifa/update', 'id' => $model->id_tarifa]);

                                    return Html::a('<span class="fas fa-pencil-alt" aria-hidden="true"></span>', $url, $options);
                                },

                        ],
                        'header' => '',
                        'contentOptions' => ['style' => 'width:80px'],
                    ],
                    [
                        'attribute' => 'tarifa.descr',
                        'label' => Yii::t('models', 'Tarifa'),
                        'headerOptions' => ['style' => 'text-align:center'],
                    ],
                    [
                        'class' => 'kartik\grid\BooleanColumn',
                        'attribute' => 'tarifa.activa',
                        'trueLabel' => 'SI',
                        'falseLabel' => 'NO',
                        'vAlign' => 'middle'
                    ],

                    [
                        'label' => Yii::t('models', 'Inicio'),
                        //'value' => fn($m) => $m->tarifa ? (new \DateTime($m->tarifa->inicio))->format('d-m-Y H:i') : null,
                        'value' => function ($m) {
                                return $m->tarifa
                                    ? (new \DateTime($m->tarifa->inicio))->format('d-m-Y H:i')
                                    : null;
                            },
                        'hAlign' => 'center',
                        'headerOptions' => ['style' => 'text-align:center'],
                    ],
                    [
                        'label' => Yii::t('models', 'Fin'),
                        //'value' => fn($m) => $m->tarifa ? (new \DateTime($m->tarifa->fin))->format('d-m-Y H:i') : null,
                        'value' => function ($m) {
                                return $m->tarifa
                                    ? (new \DateTime($m->tarifa->fin))->format('d-m-Y H:i')
                                    : null;
                            },
                        'hAlign' => 'center',
                    ],
                    [
                        'label' => Yii::t('models', 'Valor del Dia'),
                        //'value' => fn($m) => $m->tarifa ? $m->tarifa->valor_dia : null,
                        'value' => function ($m) {
                                return $m->tarifa ? $m->tarifa->valor_dia : null;
                            },
                        'hAlign' => 'right',
                        'format' => ['decimal', 2],
                        'headerOptions' => ['style' => 'text-align:center'],

                    ],
                    [
                        'label' => Yii::t('models', 'Min. Dias'),
                        //'value' => fn($m) => $m->tarifa ? $m->tarifa->min_dias : null,
                        'value' => function ($m) {
                                return $m->tarifa ? $m->tarifa->min_dias : null;
                            },
                        'hAlign' => 'center',
                        'headerOptions' => ['style' => 'text-align:center'],
                    ],
                ],
            ]); ?>
        </div>
    </div>

    <?php Pjax::end(); ?>
</div>

<!-- MODAL: seleccionar múltiples tarifas disponibles -->
<div class="modal fade" id="modalVincular" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= Yii::t('cruds', 'Seleccionar tarifas disponibles') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php $form = ActiveForm::begin([
                    'action' => ['vincular-cabanas-tarifas', 'id_cabana' => $id_cabana],
                    'method' => 'post',
                    'options' => ['data-pjax' => 1, 'id' => 'form-vincular-tarifas'],
                ]); ?>

                <?= Select2::widget([
                    'name' => 'tarifa_ids',
                    'data' => $listaTarifas,
                    'value' => [],
                    'options' => [
                        'multiple' => true,
                        'placeholder' => Yii::t('cruds', 'Seleccione una o más tarifas...'),
                    ],
                    'bsVersion' => '5.x',
                    //'theme' => Select2::THEME_KRAJEE,
                    'language' => Yii::$app->language,
                    'pluginOptions' => [
                        'allowClear' => true,
                        'width' => '100%',
                    ],
                ]); ?>

                <?php ActiveForm::end(); ?>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline-secondary"
                    data-bs-dismiss="modal"><?= Yii::t('cruds', 'Cancelar') ?></button>
                <button class="btn btn-primary" id="btn-submit-vincular">
                    <i class="bi bi-link-45deg me-1"></i><?= Yii::t('cruds', 'Vincular tarifas') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<?php
// JS: enviar el form por PJAX, cerrar modal y limpiar selector si hubo éxito.
$js = <<<JS
document.getElementById('btn-submit-vincular')
  .addEventListener('click', function() {
    document.getElementById('form-vincular-tarifas').submit();
  });

// Al terminar el PJAX, si hubo flash success, cierro modal y limpio selección
document.addEventListener('pjax:end', function() {
  var modalEl = document.getElementById('modalVincular');
  if (!modalEl) return;
  var hasSuccess = document.querySelector('.alert.alert-success');
  if (hasSuccess) {
    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.hide();
    // limpiar select2
    var s2 = $('select[name="tarifa_ids"]');
    if (s2.length) s2.val(null).trigger('change');
  }
});
JS;
$this->registerJs($js);
