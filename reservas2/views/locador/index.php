<?php

use yii\bootstrap5\Html;
use yii\helpers\Url;
use kartik\grid\GridView;


use kartik\icons\FontAwesomeAsset;
FontAwesomeAsset::register($this);

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var app\models\TarifaSearch $searchModel
 */

$this->title = Yii::t('models', 'Administrar Pasajeros');
$this->params['breadcrumbs'][] = $this->title;


?>
<div class="giiant-crud locador-index">



    <?php \yii\widgets\Pjax::begin(['id' => 'pjax-main', 'enableReplaceState' => false, 'linkSelector' => '#pjax-main ul.pagination a, th a']) ?>

    <h1>
        <?= $this->title ?>
    </h1>
    <div class="clearfix crud-navigation">
        <div class="pull-left">
            <?= Html::a('<i class="bi bi-person-add"></i> ' . Yii::t('cruds', 'Nuevo Pasajero'), ['create'], ['class' => 'btn btn-success']) ?>
        </div>
    </div>

    <hr />

    <div class="table-responsive">
        <?php

        $columns = [

            [
                'class' => 'kartik\grid\ActionColumn',
                'header' => '',
                'template' => "{view} {update} {delete}",
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
                'attribute' => 'created_at',
                'value' => function ($model) {
                    return $model->created_at
                        ? date('d-m-Y H:i', strtotime($model->created_at))
                        : null;
                },
                'filterInputOptions' => [
                    'type' => 'date',
                    'class' => 'form-control',
                    'style' => 'min-width:120px;',
                ],
            ],

            [
                'vAlign' => 'middle',
                'hAlign' => 'middle',
                'attribute' => 'denominacion',
                'headerOptions' => ['style' => 'text-align:center'],
                'contentOptions' => ['style' => 'text-align:left'],
            ],
            [
                'vAlign' => 'middle',
                'hAlign' => 'middle',
                'attribute' => 'documento',
                'headerOptions' => ['style' => 'text-align:center'],
                'contentOptions' => ['style' => 'text-align:center'],
            ],
            'email:email',
            [
                'vAlign' => 'middle',
                'hAlign' => 'middle',
                'attribute' => 'telefono',
                'headerOptions' => ['style' => 'text-align:center'],
                'contentOptions' => ['style' => 'text-align:center'],
            ],
            [
                'vAlign' => 'middle',
                'hAlign' => 'middle',
                'attribute' => 'domicilio',
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
            'id' => 'locador_grid',
            'dataProvider' => $dataProvider,
            'pjax' => true,
            'filterModel' => $searchModel,
            'columns' => $columns,
            'panel' => [
                'before' => $ind,
                'heading' => '<i class="bi bi-people-fill"></i>  ' . Yii::t('cruds', 'Pasajeros Registrados'),
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