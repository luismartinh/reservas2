<?php

use app\models\Cabana;
use yii\bootstrap5\Html;
use yii\helpers\Url;
use kartik\grid\GridView;


use kartik\icons\FontAwesomeAsset;
FontAwesomeAsset::register($this);

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var app\models\CabanaSearch $searchModel
 */

$this->title = Yii::t('models', 'Administrar Cabañas');
$this->params['breadcrumbs'][] = $this->title;


?>
<div class="giiant-crud cabana-index">



    <?php \yii\widgets\Pjax::begin(['id' => 'pjax-main', 'enableReplaceState' => false, 'linkSelector' => '#pjax-main ul.pagination a, th a']) ?>

    <h1>
        <?= $this->title ?>
    </h1>
    <div class="clearfix crud-navigation">
        <div class="pull-left">
            <?= Html::a('<i class="bi bi-house-add"></i> ' . Yii::t('cruds', 'Nueva Cabaña'), ['create'], ['class' => 'btn btn-success']) ?>
        </div>
    </div>

    <hr />

    <div class="table-responsive">
        <?php

        $columns = [

            [
                'class' => 'kartik\grid\ActionColumn',
                'header' => '',
                'template' => "{view} {update} {delete} {vincular-cabanas-tarifas}",
                'buttons' => [
                    'view' => function ($url, $model, $key) {
                        $options = [
                            'title' => Yii::t('cruds', 'Ver'),
                            'aria-label' => Yii::t('cruds', 'Ver'),
                            'style' => 'margin-right: 3px;',
                            'data-pjax' => '0',
                        ];
                        return Html::a('<span class="fas fa-eye" aria-hidden="true"></span>', $url, $options);
                    },

                    'update' => function ($url, $model, $key) {
                        $options = [
                            'title' => Yii::t('cruds', 'Editar'),
                            'aria-label' => Yii::t('cruds', 'Editar'),
                            'style' => 'margin-right: 3px;',
                            'data-pjax' => '0',
                        ];
                        return Html::a('<span class="fas fa-pencil-alt" aria-hidden="true"></span>', $url, $options);
                    },
                    'vincular-cabanas-tarifas' => function ($url, $model, $key) {
                        $options = [
                            'title' => Yii::t('cruds', 'Tarifas vigentes'),
                            'aria-label' => Yii::t('cruds', 'Tarifas vigentes'),
                            'style' => 'margin-right: 3px;',
                            'data-pjax' => '0',
                        ];

                        $url = Url::toRoute(['vincular-cabanas-tarifas', 'id_cabana' => $model->id]);

                        return Html::a('<span class="fas fa-dollar-sign" aria-hidden="true"></span>', $url, $options);
                    },

                    'delete' => function ($url, $model, $key) {
                        $options = [
                            'title' => Yii::t('cruds', 'Eliminar'),
                            'aria-label' => Yii::t('cruds', 'Eliminar'),
                            'style' => 'margin-right: 3px;',
                            'data-pjax' => '0',
                            'data-confirm' => Yii::t('cruds', 'Esta seguro de eliminar?'),
                        ];
                        return Html::a('<span class="fas fa-trash-alt" aria-hidden="true"></span>', $url, $options);
                    },

                ],
                'urlCreator' => function ($action, $model, $key, $index) {
                    // using the column name as key, not mapping to 'id' like the standard generator
                    $params = is_array($key) ? $key : [$model->primaryKey()[0] => (string) $key];
                    $params[0] = \Yii::$app->controller->id ? \Yii::$app->controller->id . '/' . $action : $action;
                    return Url::toRoute($params);
                },
                'contentOptions' => ['nowrap' => 'nowrap']
            ],
            [
                'vAlign' => 'middle',
                'hAlign' => 'middle',
                'attribute' => 'descr',
                'format' => 'raw',
                'headerOptions' => ['style' => 'text-align:center'],
                'contentOptions' => ['style' => 'text-align:left'],
                'value' => function ($model) {
                    /** @var Cabana $model */
                    $texto = Html::encode($model->descr);

                    $hex = $model->color_cabana; // viene del getter que lee config['color_cabana']
                    if (!$hex) {
                        // Si no tiene color configurado, solo mostramos el nombre
                        return $texto;
                    }

                    // Nombre “humano” del color desde la paleta
                    $nombreColor = Cabana::$PALETA[$hex] ?? $hex;

                    // Píldora de color
                    $pill = Html::tag('span', $nombreColor, [
                        'class' => 'badge rounded-pill me-2',
                        'style' => "background: {$hex}; color: #000; font-weight:bold;",
                        'title' => $hex,
                    ]);

                    // Píldora + texto de la cabaña
                    return $pill . $texto;
                },
            ],

            [
                'class' => 'kartik\grid\BooleanColumn',
                'attribute' => 'activa',
                //'label' => 'Activa?',
                'trueLabel' => 'SI',
                'falseLabel' => 'NO',
                'vAlign' => 'middle'
            ],
            [
                'vAlign' => 'middle',
                'hAlign' => 'middle',
                'attribute' => 'numero',
                'headerOptions' => ['style' => 'text-align:center'],
                'contentOptions' => ['style' => 'text-align:center'],
            ],


            [
                'vAlign' => 'middle',
                'hAlign' => 'middle',
                'attribute' => 'max_pax',
                'headerOptions' => ['style' => 'text-align:center'],
                'contentOptions' => ['style' => 'text-align:center'],
            ],
            [
                'attribute' => 'checkin',
                //'value' => fn($model) => $model->checkin ? date('H:i', strtotime($model->checkin)) : null,
                'value' => function ($model) {
                    return $model->checkin
                        ? date('H:i', strtotime($model->checkin))
                        : null;
                },

                'filterInputOptions' => [
                    'type' => 'time',
                    'class' => 'form-control',
                    'step' => 60,
                    'style' => 'min-width:120px;',
                ],
            ],
            [
                'attribute' => 'checkout',
                //'value' => fn($model) => $model->checkout ? date('H:i', strtotime($model->checkout)) : null,
                'value' => function ($model) {
                    return $model->checkout ? date('H:i', strtotime($model->checkout)) : null;
                },
                'filterInputOptions' => [
                    'type' => 'time',
                    'class' => 'form-control',
                    'step' => 60,
                    'style' => 'min-width:120px;',
                ],
            ],

            [

                'header' => Yii::t('cruds', 'Tarifas asociadas'),
                'vAlign' => 'middle',
                'hAlign' => 'middle',
                'headerOptions' => ['style' => 'text-align:center'],
                'contentOptions' => ['style' => 'text-align:center'],
                'value' => function ($model) {
                    return count($model->tarifas);
                },
                'filter' => false
            ],

        ];

        $ind = Html::a(
            '<i class="bi bi-arrow-counterclockwise me-2"></i>' . Yii::t('cruds', 'Reset'),
            ['index'],
            [
                'class' => 'btn btn-outline-secondary btn-default float-end me-2',
                'data-pjax' => '0',
            ]
        );


        echo GridView::widget([
            'id' => 'cabana_grid',
            'dataProvider' => $dataProvider,
            'pjax' => true,
            'filterModel' => $searchModel,
            'columns' => $columns,
            'panel' => [
                'before' => $ind,
                'heading' => '<i class="bi bi-houses-fill"></i>  ' . Yii::t('cruds', 'Cabañas Registradas'),
                'type' => 'info',
            ],
            'export' => false,
            'rowOptions' => function ($model) {
                return ['data-id' => $model->id]; // Agrega el ID del modelo a la fila
            },
        ]); ?>
    </div>

</div>


<?php \yii\widgets\Pjax::end() ?>