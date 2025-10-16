<?php

use yii\helpers\Url;
use yii\web\View;
use yii\bootstrap5\Html;
use kartik\grid\GridView;

use kartik\icons\FontAwesomeAsset;
FontAwesomeAsset::register($this);



/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var app\models\NotificacionesSearch $searchModel
 */

$this->title = Yii::t('models', 'Notificaciones');
$this->params['breadcrumbs'][] = $this->title;


?>
<div class="giiant-crud notificaciones-index">



    <?php \yii\widgets\Pjax::begin(['id' => 'pjax-main', 'enableReplaceState' => false, 'linkSelector' => '#pjax-main ul.pagination a, th a']) ?>

    <h1>
        <?= Yii::t('cruds', $this->title) ?>
        <small class="text-muted"><?= Yii::t('cruds', 'registradas:') ?>
        </small>
    </h1>
    <div class="clearfix crud-navigation">
        <div class="pull-left">
            <?= Html::a('<i class="bi bi-x-circle-fill"></i> ' . Yii::t('cruds', 'Eliminar leidos'), ['notificaciones/delete-leidos'], ['class' => 'btn btn-warning']) ?>

            <?= Html::a('<i class="bi bi-x-circle-fill"></i> ' . Yii::t('cruds', 'Eliminar todas'), ['notificaciones/delete-todas'], ['class' => 'btn btn-danger']) ?>
        </div>
    </div>


    <hr />

    <div class="table-responsive">
        <?php 
        
        $columns = [
            [
                'class' => 'kartik\grid\CheckboxColumn',
                'headerOptions' => ['class' => 'kartik-sheet-style'],
                'header' => 'Leida',
                'rowHighlight' => false,
                'rowSelectedClass' => 'table-success',
                'checkboxOptions' => function ($model, $key, $index, $column) {
                    return [
                        'checked' => $model->leida == "1" ? true : false
                    ];
                },
            ],
            [
                'class' => 'kartik\grid\ExpandRowColumn',
                'width' => '80px',
                'value' => function ($model, $key, $index, $column) {
                        return GridView::ROW_COLLAPSED;
                    },
                'enableCache' => false,
                'detailUrl' => Url::toRoute(['notificaciones/ver-detalle']),
                'headerOptions' => ['class' => 'kartik-sheet-style'],
                'expandOneOnly' => true
            ],

            [
                'vAlign' => 'middle',
                'hAlign' => 'middle',
                'label'=>'Fecha',
                'attribute' => 'msg.created_at',
                'headerOptions' => ['style' => 'text-align:center'],
                'contentOptions' => ['style' => 'text-align:center'],
                'width' => '25%',
                'value' => function ($model) {
                    if ($rel = $model->msg) {
                        if ($rel->created_at) {
                            return yii\helpers\Html::encode(date("d-M-Y h:i", strtotime($rel->created_at)));
                        }
                        return null;
                    } else {
                        return null;
                    }

                },

                'filter' => kartik\daterange\DateRangePicker::widget([
                    'id' => 'NotificaionesSearch-fecha',
                    'name' => 'NotificaionesSearch[fecha]',
                    'value'=>$searchModel->fecha,
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
                'label'=>'Tabla',
                'attribute' => 'tabla',
                'headerOptions' => ['style' => 'text-align:center'],
                'contentOptions' => ['style' => 'text-align:center'],
            ],


            [
                'class' => 'kartik\grid\BooleanColumn',
                'headerOptions' => ['style' => 'text-align:center'],
                'label' => 'Leida?',
                'trueLabel' => 'SI',
                'falseLabel' => 'NO',
                'trueIcon'=>'<i class="bi bi-eye-fill info" style="font-size: 2rem;"></i>',
                'falseIcon'=>'<i class="bi bi-eye-slash-fill" style="font-size: 2rem; color:green"></i>',
                'attribute' => 'leida',
                'vAlign' => 'middle',
                'width' => '25%',
                'value' => function ($model, $key, $index, $column) {
                    return $model->leida;
                },

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
            'id' => 'notificaciones_grid',
            'dataProvider' => $dataProvider,
            'pjax' => true,
            'filterModel' => $searchModel,
            'columns' => $columns,
            'panel' => [
                'before' => $ind,
                'heading' => '<i class="fas fa-book"></i>  Lista de notificaciones',
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


<?php


$url = Yii::$app->request->baseUrl . '/index.php?r=notificaciones/set-leidas';


$initScript2 = <<<JS2
         
        const GL_url="$url"; 

        
                
JS2;

$this->registerJs($initScript2, View::POS_HEAD);

$getSelect = <<<JS

$(document).on('change', '.kv-row-checkbox', function() {
    let row = $(this).closest('tr'); // Encuentra la fila del checkbox
    let id = row.data('key'); // Obtiene el ID desde data-id

    let estado = $(this).is(':checked') ? 1 : 0; // Estado: 1 si está marcado, 0 si está desmarcado

    console.log(id, estado, row.attr('class'));
    //1 1 'notificaciones_grid table-success'
    //1 0 'notificaciones_grid'

        // Enviar los datos por AJAX
        $.ajax({
            url: GL_url, 
            type: 'POST',
            data: {
                id: id,
                estado: estado,
                _csrf: yii.getCsrfToken() // Incluye el token CSRF para protección
            },
            success: function(response) {

                //console.log('Datos enviados con éxito:', response);
                //$.pjax.reload({container: '#pjax-main', timeout: false});
                location.reload(true);

            },
            error: function(xhr, status, error) {
                console.log('Error al enviar los datos:', error);
            }
        });
       


});	





JS;

$this->registerJs($getSelect);
