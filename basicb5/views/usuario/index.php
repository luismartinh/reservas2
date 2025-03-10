<?php

use yii\bootstrap5\Html;
use yii\helpers\Url;
use kartik\grid\GridView;


use kartik\icons\FontAwesomeAsset;
FontAwesomeAsset::register($this);


/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var app\models\UsuarioSearch $searchModel
 */

$this->title = Yii::t('models', 'Administrar Usuarios');
$this->params['breadcrumbs'][] = $this->title;


?>
<div class="giiant-crud usuario-index">



    <?php \yii\widgets\Pjax::begin([
        'id' => 'pjax-main', 
        'enableReplaceState' => false, 
        'linkSelector' => '#pjax-main ul.pagination a, th a',
        ]) ?>

    <h1>
        <?= $this->title ?>
        <small class="text-muted"><?= Yii::t('cruds', 'registrados:') ?>
        </small>
    </h1>
    <div class="clearfix crud-navigation">
        <div class="pull-left">
            <?= Html::a('<i class="bi bi-person-plus"></i> ' . Yii::t('cruds', 'Nuevo Usuario'), ['create'], ['class' => 'btn btn-success']) ?>
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
                'vAlign' => 'middle',
                'hAlign' => 'middle',
                'attribute' => 'last_login_time',
                'headerOptions' => ['style' => 'text-align:center'],
                'contentOptions' => ['style' => 'text-align:center'],
                'value' => function ($model) {
                    if ($rel = $model) {
                        if ($rel->last_login_time) {
                            return yii\helpers\Html::encode(date("d-M-Y h:i", strtotime($rel->last_login_time)));
                        }
                        return null;
                    } else {
                        return null;
                    }

                },

                'filter' => kartik\daterange\DateRangePicker::widget([
                    'id' => 'UsuarioSearch-last_login_time',
                    'name' => 'UsuarioSearch[last_login_time]',
                    'value'=>$searchModel->last_login_time,
                    'convertFormat' => true,
                    'includeMonthsFilter' => true,
                    'bsVersion'=>'5.x',
                    'pluginOptions' => ['locale' => ['format' => 'd-m-Y']],
                    'options' => [
                        'data-pjax' => '0',
                         'autocomplete' => 'off',
                        'placeholder' => 'Select rango...']
                ]),

                'format' => 'raw',
            ],

            [
                'vAlign' => 'middle',
                'hAlign' => 'middle',
                'attribute' => 'login',
                'headerOptions' => ['style' => 'text-align:center'],
                'contentOptions' => ['style' => 'text-align:center'],
            ],
            [
                'vAlign' => 'middle',
                'hAlign' => 'middle',
                'attribute' => 'nivel',
                'headerOptions' => ['style' => 'text-align:center'],
                'contentOptions' => ['style' => 'text-align:center'],
                'value' => function ($model) {
                    if ($rel = $model) {
                        $niveles = app\config\Niveles::getNiveles();
                        return yii\helpers\Html::encode( $niveles[$rel->nivel] );
                    } else {
                        return '';
                    }

                },
                'filter' => Html::activeDropDownList($searchModel, 
                        'nivel', 
                        app\config\Niveles::getNiveles(),
                        ['class'=>'form-control','prompt' => 'Select']
                        ),        
                'format' => 'raw',

            ],

            [
                'vAlign' => 'middle',
                'hAlign' => 'middle',
                'attribute' => 'nombre',
                'headerOptions' => ['style' => 'text-align:center'],
                'contentOptions' => ['style' => 'text-align:center'],
            ],
            [
                'vAlign' => 'middle',
                'hAlign' => 'middle',
                'attribute' => 'apellido',
                'headerOptions' => ['style' => 'text-align:center'],
                'contentOptions' => ['style' => 'text-align:center'],
            ],
            [
                'class' => 'kartik\grid\BooleanColumn',
                'attribute' => 'activo',
                'vAlign' => 'middle'
            ],
            [
                'vAlign' => 'middle',
                'hAlign' => 'middle',
                'attribute' => 'email',
                'headerOptions' => ['style' => 'text-align:center'],
                'contentOptions' => ['style' => 'text-align:center'],
            ],
            [
                'vAlign' => 'middle',
                'hAlign' => 'middle',
                'attribute' => 'last_login_ip',
                'headerOptions' => ['style' => 'text-align:center'],
                'contentOptions' => ['style' => 'text-align:center'],
            ],
            [
                'vAlign' => 'middle',
                'hAlign' => 'middle',
                'attribute' => 'codigo',
                'headerOptions' => ['style' => 'text-align:center'],
                'contentOptions' => ['style' => 'text-align:center'],
            ],

        ];

        $ind=Html::a(
            '<i class="bi bi-arrow-counterclockwise me-2"></i>' . Yii::t('cruds', 'Reset'),
            ['index'],
            [
                'class' => 'btn btn-outline-secondary btn-default float-end me-2',
                'data-pjax' => '0',
                ]
        );


        echo GridView::widget([
            'id' => 'usuarios_grid',            
            'dataProvider' => $dataProvider,
            'pjax' => true,
            'filterModel' => $searchModel,
            'columns' => $columns,
            'panel' => [
                'before' => $ind,
                'heading' => '<i class="fas fa-book"></i>  Usuarios Registrados',
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


<?php
$pjaxend = <<<JS




$(document).on('pjax:end', function() {
        // Re-inicializar el DateRangePicker después de que Pjax actualice el contenido

        if($('#UsuarioSearch-last_login_time').data('daterangepicker')==undefined){

            const hoy = new Date();
            const hoystr=hoy.toLocaleDateString('es-ES', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric'
                }).replace(/\//g, "-");


            $('#UsuarioSearch-last_login_time').daterangepicker({ 
                startDate: hoystr , 
                endDate: hoystr,
             });


             $('#UsuarioSearch-last_login_time').val('')

             $('#UsuarioSearch-last_login_time').data('daterangepicker').locale.format='DD-MM-YYYY';

            
        }
    });


JS;

$this->registerJs($pjaxend);
?>