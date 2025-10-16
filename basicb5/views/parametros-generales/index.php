<?php

use yii\bootstrap5\Html;
use yii\helpers\Url;
use kartik\grid\GridView;


use kartik\icons\FontAwesomeAsset;
FontAwesomeAsset::register($this);


/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var app\models\ParametrosGeneralesSearch $searchModel
 */

$this->title = Yii::t('models', 'Administrar Parametros Generales');
$this->params['breadcrumbs'][] = $this->title;



?>
<div class="giiant-crud parametros-generales-index">



    <?php \yii\widgets\Pjax::begin(['id' => 'pjax-main', 'enableReplaceState' => false, 'linkSelector' => '#pjax-main ul.pagination a, th a']) ?>

    <h1>
        <?= $this->title ?>
        <small class="text-muted"><?= Yii::t('cruds', 'registradas:') ?>
        </small>
    </h1>


    <hr />

    <div class="table-responsive">
        <?php


        $columns = [
            [
                'class' => 'kartik\grid\ActionColumn',
                'header' => '',
                'template' => "{update}",
                'buttons' => [
                    'update' => function ($url, $model, $key) {
                        $options = [
                            'title' => Yii::t('cruds', 'Editar'),
                            'aria-label' => Yii::t('cruds', 'Editar'),
                            'style' => 'margin-right: 3px;',
                            'data-pjax' => '0',
                        ];
                        return Html::a('<span class="fas fa-pencil-alt" aria-hidden="true"></span>', $url, $options);
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
                'class' => 'kartik\grid\ExpandRowColumn',
                'width' => '50px',
                'value' => function ($model, $key, $index, $column) {
                    return GridView::ROW_COLLAPSED;
                },
                'enableCache' => false,
                'detailUrl' => Url::toRoute(['parametros-generales/ver-detalle']),
                'headerOptions' => ['class' => 'kartik-sheet-style'],
                'expandOneOnly' => true
            ],


            [
                'vAlign' => 'middle',
                'hAlign' => 'middle',
                'attribute' => 'clave',
                'headerOptions' => ['style' => 'text-align:center'],
                'contentOptions' => ['style' => 'text-align:left'],
            ],

            [
                'vAlign' => 'middle',
                'hAlign' => 'middle',
                'label'=>'Descripcion',
                'attribute' => 'descr',
                'headerOptions' => ['style' => 'text-align:center'],
                'contentOptions' => ['style' => 'text-align:left'],
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
            'id' => 'parametros_grid',
            'dataProvider' => $dataProvider,
            'pjax' => true,
            'filterModel' => $searchModel,
            'columns' => $columns,
            'panel' => [
                'before' => $ind,
                'heading' => '<i class="fas fa-book"></i>  Parametros generales',
                'type' => 'info',
            ],
            'export' => false,
            'rowOptions' => function ($model) {
                return ['data-id' => $model->id]; // Agrega el ID del modelo a la fila
            },
        ]);



        ?>
    </div>

</div>


<?php \yii\widgets\Pjax::end() ?>