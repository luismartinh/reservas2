<?php


use yii\bootstrap5\Html;
use yii\widgets\DetailView;
use yii\bootstrap5\Tabs;

use kartik\icons\FontAwesomeAsset;
FontAwesomeAsset::register($this);

/**
 * @var yii\web\View $this
 * @var app\models\Tarifa $model
 */

$this->title = Yii::t('models', 'Ver Tarifa');
$this->params['breadcrumbs'][] = ['label' => Yii::t('models.plural', 'Tarifas'), 'url' => ['index']];
$this->params['breadcrumbs'][] = Yii::t('cruds', 'Ver');

?>
<div class="giiant-crud tarifa-view">

    <h1>
        <?= $this->title ?>

        <small class="text-muted">
            <?= Html::encode($model->descr) ?>
        </small>
    </h1>


    <hr />

    <?php $this->beginBlock('app\models\Tarifa'); ?>


    <?php

    echo DetailView::widget([
        'model' => $model,
        'attributes' => [
            'descr',
            [
                'attribute' => 'activa',
                'value' => $model->activa == '1' ? 'Si' : 'No',
            ],
            [
                'attribute' => 'fecha',
                'value' => yii\helpers\Html::encode(date("d-M-Y H:i", strtotime($model->fecha))),
            ],
            [
                'attribute' => 'inicio',
                'value' => yii\helpers\Html::encode(date("d-M-Y H:i", strtotime($model->inicio))),
            ],
            [
                'attribute' => 'fin',
                'value' => yii\helpers\Html::encode(date("d-M-Y H:i", strtotime($model->fin))),
            ],
            'min_dias',
            [
                'attribute' => 'valor_dia',
                'value' => $model->valor_dia != null ? $model->valor_dia : '',
                'format' => ['decimal', 2],
            ],

        ],
    ]);
    ?>


    <hr />

    <?php $this->endBlock(); ?>



    <?php
    echo Tabs::widget(
        [
            'id' => 'relation-tabs',
            'encodeLabels' => false,
            'items' => [
                [
                    'label' => '<b>' . \Yii::t(
                        'cruds',
                        '# {primaryKey}',
                        ['primaryKey' => Html::encode($model->id)]
                    ) . '</b>',
                    'content' => $this->blocks['app\models\Tarifa'],
                    'active' => true,
                ],

            ]
        ]
    );
    ?>
</div>