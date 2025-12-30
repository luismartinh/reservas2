<?php

use app\models\RequestReserva;
use kartik\grid\GridView;
use yii\bootstrap5\Html;
use yii\data\ActiveDataProvider;

/** @var \yii\web\View $this */
/** @var \app\models\Locador $locador */
?>
<div class="table-responsive">
    <?php

    $dataProvider = new ActiveDataProvider([
        'query' => $locador->getReservas(),
        'sort' => ['defaultOrder' => ['id' => SORT_DESC]]
    ]);

    $columns = [

        [
            'vAlign' => 'middle',
            'hAlign' => 'middle',
            'headerOptions' => ['style' => 'text-align:center;with:50px'],
            'contentOptions' => ['class' => 'text-center align-middle'],
            'attribute' => 'id',
            'label' => '#',
            'value' => function ($model, $key, $index, $column) {

                return $model->id;
            },

            //'filter' => false,
            'filterInputOptions' => [
                //'type' => 'number',
                'class' => 'form-control',
                'style' => 'width:60px;',
            ],

        ],


        [
            'vAlign' => 'middle',
            'hAlign' => 'left',
            'attribute' => 'fecha',
            'header' => Yii::t('app', 'Periodo'),
            'headerOptions' => ['style' => 'text-align:center'],
            'contentOptions' => ['style' => 'text-align:left;min-width:400px'],
            'value' => function ($model) {


                $datos = "";
                $datos .= '<br><span class="text-muted">' .
                    Yii::t('app', 'Creada: ') . ' </span ><b>'
                    . yii\helpers\Html::encode(date("d-m-y H:i", strtotime($model->fecha))) . '<b>';

                $datos .= '<br><span class="text-muted">' .
                    Yii::t('app', 'Desde: ') . ' </span ><b>'
                    . yii\helpers\Html::encode(date("d-m-y H:i", strtotime($model->desde))) . '<b>';
                $datos .= '<br><span class="text-muted">' .
                    Yii::t('app', 'Hasta: ') . ' </span ><b>'
                    . yii\helpers\Html::encode(date("d-m-y H:i", strtotime($model->hasta))) . '<b>';

                return $datos;
            },
            'filter' => false,
            'format' => 'raw',

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


                $datos = "";


                if ($model->requestReservas != null) {
                    $datos .= '<br><span class="text-muted">' . Yii::t('app', 'Codigo: ') . ' </span ><b>' . yii\helpers\Html::encode($model->requestReservas[count($model->requestReservas) - 1]->codigo_reserva) . '<b>';
                }
                /** @var \app\models\Reserva $model */
                $datos .= Yii::$app->controller->renderPartial('@app/views/reserva/_reserva_cabanas', [
                    'model' => $model,
                ]);



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

    $gridId = 'reservas_grid_' . (int) $locador->id;
    echo GridView::widget([
        'id' => $gridId,
        'dataProvider' => $dataProvider,
        'pjax' => false,
        'columns' => $columns,
        'panel' => [
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