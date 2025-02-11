<?php

use app\models\Auditoria;
use yii\bootstrap5\Html;
use yii\helpers\Url;
use kartik\grid\GridView;


use kartik\icons\FontAwesomeAsset;
FontAwesomeAsset::register($this);


/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var app\models\AuditoriaSearch $searchModel
 */

$this->title = Yii::t('models', 'Auditoria Ver');
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="giiant-crud auditoria-index">



    <?php \yii\widgets\Pjax::begin(['id' => 'pjax-main', 'enableReplaceState' => false, 'linkSelector' => '#pjax-main ul.pagination a, th a']) ?>

    <h1>
        <?= Yii::t('cruds', 'Auditorias') ?>
        <small class="text-muted"><?= Yii::t('cruds', 'registradas:') ?>
        </small>
    </h1>

    <div class="clearfix crud-navigation">
        <div class="pull-left">
            <?= Html::a('<i class="bi bi-toggles2"></i> ' . Yii::t('cruds', 'Activar auditorias'), ['auditoria-tabla/index'], ['class' => 'btn btn-primary']) ?>
        </div>
    </div>


    <hr />

    <div class="table-responsive">
        <?php 
        
        
        $columns=[
            [
                'class' => 'kartik\grid\ExpandRowColumn',
                'width' => '50px',
                'value' => function ($model, $key, $index, $column) {
                        return GridView::ROW_COLLAPSED;
                    },
                'enableCache' => false,
                'detailUrl' => Url::toRoute(['auditoria/ver-detalle']),
                'headerOptions' => ['class' => 'kartik-sheet-style'],
                'expandOneOnly' => true
            ],

            [
                'vAlign' => 'middle',
                'hAlign' => 'middle',
                'attribute' => 'created_at',
                'headerOptions' => ['style' => 'text-align:center'],
                'contentOptions' => ['style' => 'text-align:center'],
                'value' => function ($model) {
                    if ($rel = $model) {
                        if ($rel->created_at) {
                            return yii\helpers\Html::encode(date("d-M-Y h:i", strtotime($rel->created_at)));
                        }
                        return null;
                    } else {
                        return null;
                    }

                },

                'filter' => kartik\daterange\DateRangePicker::widget([
                    'id' => 'AuditoriaSearch-created_at',
                    'name' => 'AuditoriaSearch[created_at]',
                    'value'=>$searchModel->created_at,
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
                'attribute' => 'tabla',
                'headerOptions' => ['style' => 'text-align:center'],
                'contentOptions' => ['style' => 'text-align:center'],
            ],

            [
                'vAlign' => 'middle',
                'hAlign' => 'middle',
                'attribute' => 'user',
                'headerOptions' => ['style' => 'text-align:center'],
                'contentOptions' => ['style' => 'text-align:center'],
            ],
            [
                'vAlign' => 'middle',
                'hAlign' => 'middle',
                'attribute' => 'action',
                'headerOptions' => ['style' => 'text-align:center'],
                'contentOptions' => ['style' => 'text-align:center'],
                'filter' => Html::activeDropDownList($searchModel, 
                        'action', 
                        Auditoria::getDropdownOptions(),
                        ['class'=>'form-control','prompt' => 'Select']
                        ),        
                'format' => 'raw',


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
            'id' => 'auditoria_grid',            
            'dataProvider' => $dataProvider,
            'pjax' => true,
            'pager' => [
                'class' => yii\widgets\LinkPager::class,
                'firstPageLabel' => Yii::t('cruds', 'First'),
                'lastPageLabel' => Yii::t('cruds', 'Last'),
            ],
            'filterModel' => $searchModel,
            'columns' => $columns,
            'panel' => [
                'before' => $ind,
                'heading' => '<i class="fas fa-book"></i>  Listado de cambios',
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
$pjaxend = <<<JS




$(document).on('pjax:end', function() {
        // Re-inicializar el DateRangePicker después de que Pjax actualice el contenido

        if($('#AuditoriaSearch-created_at').data('daterangepicker')==undefined){

            const hoy = new Date();
            const hoystr=hoy.toLocaleDateString('es-ES', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric'
                }).replace(/\//g, "-");


            $('#AuditoriaSearch-created_at').daterangepicker({ 
                startDate: hoystr , 
                endDate: hoystr,
             });


             $('#AuditoriaSearch-created_at').val('')

             $('#AuditoriaSearch-created_at').data('daterangepicker').locale.format='DD-MM-YYYY';

            
        }
    });


JS;

$this->registerJs($pjaxend);
?>