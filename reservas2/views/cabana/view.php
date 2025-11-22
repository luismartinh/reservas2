<?php


use yii\bootstrap5\Html;
use yii\widgets\DetailView;
use yii\bootstrap5\Tabs;

use kartik\icons\FontAwesomeAsset;
FontAwesomeAsset::register($this);

/**
 * @var yii\web\View $this
 * @var app\models\Cabana $model
 */

$this->title = Yii::t('models', 'Ver Cabaña');
$this->params['breadcrumbs'][] = ['label' => Yii::t('models.plural', 'Cabañas'), 'url' => ['index']];
$this->params['breadcrumbs'][] = Yii::t('cruds', 'Ver');

?>
<div class="giiant-crud cabana-view">

    <h1>
        <?= $this->title ?>

        <small class="text-muted">
            <?= Html::encode($model->descr) ?>
        </small>
    </h1>


    <hr />

    <?php $this->beginBlock('app\models\Cabana'); ?>


    <?php

    echo DetailView::widget([
        'model' => $model,
        'attributes' => [
            'descr',
            [
                'attribute' => 'activa',
                'value' => $model->activa == '1' ? 'Si' : 'No',
            ],
            'checkout',
            'checkin',
            'max_pax',
            'caracteristicas',


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
                    'content' => $this->blocks['app\models\Cabana'],
                    'active' => true,
                ],

            ]
        ]
    );
    ?>
</div>