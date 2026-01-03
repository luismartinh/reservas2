<?php

use app\models\RequestReserva;
use yii\bootstrap5\Html;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use kartik\grid\GridView;


use kartik\icons\FontAwesomeAsset;
FontAwesomeAsset::register($this);

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var app\models\ReservaSearch $searchModel
 */

$this->title = Yii::t('models', 'Administrar Reservas');
$this->params['breadcrumbs'][] = $this->title;


?>
<div class="giiant-crud reservas-index">



    <?php \yii\widgets\Pjax::begin(['id' => 'pjax-main', 'enableReplaceState' => false, 'linkSelector' => '#pjax-main ul.pagination a, th a']) ?>

    <h1>
        <?= $this->title ?>
    </h1>

    <?php echo $this->render('_search', ['model' => $searchModel]); ?>

    <hr />

    <div class="table-responsive">
        <?php

        $columns = [

            [
                'class' => 'kartik\grid\ActionColumn',
                'header' => '',
                'template' => "{view} {delete}",
                'buttons' => [
                    'view' => function ($url, $model, $key) {

                        if ($model->getRequestReservas()->count() == 0)
                            return "";


                        $url = Url::toRoute(['disponibilidad/seguimiento', 'hash' => $model->requestReservas[0]->hash]);

                        $options = [
                            'title' => Yii::t('cruds', 'Ver'),
                            'aria-label' => Yii::t('cruds', 'Ver'),
                            'style' => 'margin-right: 3px;',
                            'data-pjax' => '0',
                            'onclick' => "window.open('" . $url . "',"
                                . "'popup','width=1000,height=600,scrollbars=no,resizable=no'); "
                                . "return false;",


                        ];
                        return Html::a('<span class="fas fa-eye" aria-hidden="true"></span>', $url, $options);
                    },
                    'delete' => function ($url, $model, $key) {
                        $options = [
                            'title' => Yii::t('cruds', 'Eliminar'),
                            'aria-label' => Yii::t('cruds', 'Eliminar'),
                            'style' => 'margin-right: 3px;',
                            'data-pjax' => '0',
                            'data-confirm' => Yii::t('cruds', 'Esta seguro de eliminar? Se eliminaran los pagos asociados y la reserva correspomndiente.'),
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
                'headerOptions' => ['style' => 'text-align:center;with:50px'],
                'contentOptions' => ['class' => 'text-center align-middle'],
                'attribute' => 'id',
                'label' => '#',
                'value' => function ($model, $key, $index, $column) {

                    return $model->id ;
                },

                //'filter' => false,
                'filterInputOptions' => [
                    //'type' => 'number',
                    'class' => 'form-control',
                    'style' => 'width:60px;',
                ],

            ],

            [
                'class' => 'kartik\grid\ExpandRowColumn',
                'width' => '50px',
                'headerOptions' => ['style' => 'text-align:center'],
                'contentOptions' => ['class' => 'text-center align-middle'],
                'expandOneOnly' => true,
                'value' => function ($model, $key, $index, $column) {
                    // por defecto, filas colapsadas
                    return GridView::ROW_COLLAPSED;
                },
                'detail' => function ($model, $key, $index, $column) {
                    /** @var \app\models\Reserva $model */
                    return Yii::$app->controller->renderPartial('_reserva_cabanas', [
                        'model' => $model,
                    ]);
                },
                'expandIcon' => '<i class="bi bi-chevron-right"></i>',
                'collapseIcon' => '<i class="bi bi-chevron-down"></i>',
            ],

            [
                'attribute' => 'fecha',
                'headerOptions' => ['style' => 'text-align:center'],
                'contentOptions' => ['style' => 'text-align:center'],

                'value' => function ($model) {
                    if ($rel = $model) {
                        if ($rel->fecha) {
                            return yii\helpers\Html::encode(date("d-m-y H:i", strtotime($rel->fecha)));
                        }
                        return null;
                    } else {
                        return null;
                    }

                },
                'filter' => false,
            ],

            [
                'attribute' => 'desde',
                'headerOptions' => ['style' => 'text-align:center'],
                'contentOptions' => ['style' => 'text-align:center'],

                'value' => function ($model) {
                    if ($rel = $model) {
                        if ($rel->desde) {
                            return yii\helpers\Html::encode(date("d-m-y H:i", strtotime($rel->desde)));
                        }
                        return null;
                    } else {
                        return null;
                    }

                },

                'filter' => false,

            ],
            [
                'attribute' => 'hasta',
                'headerOptions' => ['style' => 'text-align:center'],
                'contentOptions' => ['style' => 'text-align:center'],

                'value' => function ($model) {
                    if ($rel = $model) {
                        if ($rel->hasta) {
                            return yii\helpers\Html::encode(date("d-m-y H:i", strtotime($rel->hasta)));
                        }
                        return null;
                    } else {
                        return null;
                    }

                },
                'filter' => false,
            ],
            [
                'attribute' => 'id_estado',
                'headerOptions' => ['style' => 'text-align:center'],
                'contentOptions' => ['style' => 'text-align:left'],
                'value' => function ($model) {
                    if ($rel = $model->estado) {
                        $cell = Html::encode($rel->descr);

                        // Reutilizamos la lógica de vencida
                        $res = RequestReserva::vencida($model->id, new \DateTime());
                        if ($res['status'] === 'vencida') {
                            $cell .= '<br><span class="text-danger">' . Html::encode($res['msg']) . '</span>';
                        }

                        return $cell;
                    }
                    return '';
                },
                'format' => 'raw',
                'filter' => false,


            ],
            [
                'vAlign' => 'middle',
                'hAlign' => 'left',
                'attribute' => 'id_locador',
                'headerOptions' => ['style' => 'text-align:center'],
                'contentOptions' => ['style' => 'text-align:left;min-width:400px'],
                'value' => function ($model) {


                    $datos="";

                    if ($model->locador) {
                        $datos = '<span class="text-muted">' . Yii::t('app', 'Locador: ') . '</span ><b>' . yii\helpers\Html::encode($model->locador->denominacion) . '<b>';
                        $datos .= '<br><span class="text-muted">' . Yii::t('app', 'Email: ') . ' </span ><b>' . yii\helpers\Html::encode($model->locador->email) . '<b>';
                        
                    }

                    if ($model->requestReservas != null) {
                        $datos .= '<br><span class="text-muted">' . Yii::t('app', 'Codigo: ') . ' </span ><b>' . yii\helpers\Html::encode($model->requestReservas[count($model->requestReservas)-1]->codigo_reserva) . '<b>';
                    }


                    return $datos;
                },
                'filter' => false,
                'format' => 'raw',

            ],

            [
                'vAlign' => 'middle',
                'hAlign' => 'middle',
                'header' => 'Pax',
                'attribute' => 'pax',
                'headerOptions' => ['style' => 'text-align:center'],
                'contentOptions' => ['style' => 'text-align:center;min-width:40px'],
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
            'id' => 'reservas_grid',
            'dataProvider' => $dataProvider,
            'pjax' => false,
            'filterModel' => $searchModel,
            'columns' => $columns,
            'panel' => [
                'before' => $ind,
                'heading' => '<i class="bi bi-house-lock-fill"></i>  ' . Yii::t('cruds', 'Reservas Registradas'),
                'type' => 'info',
            ],
            'export' => false,
            'rowOptions' => function ($model) {

                if ($model->estado->slug === 'confirmado-verificar-pago') {
                    return ['class' => 'table-warning', 'data-id' => $model->id];
                }

                return ['data-id' => $model->id]; // Agrega el ID del modelo a la fila
            },
        ]); ?>
    </div>

</div>


<?php \yii\widgets\Pjax::end() ?>