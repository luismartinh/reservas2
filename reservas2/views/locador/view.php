<?php


use yii\bootstrap5\Html;
use yii\widgets\DetailView;
use yii\bootstrap5\Tabs;

use kartik\icons\FontAwesomeAsset;
FontAwesomeAsset::register($this);

/**
 * @var yii\web\View $this
 * @var app\models\Locador $model
 */

$this->title = Yii::t('models', 'Ver Pasajero');
$this->params['breadcrumbs'][] = ['label' => Yii::t('models.plural', 'Pasajeros'), 'url' => ['index']];
$this->params['breadcrumbs'][] = Yii::t('cruds', 'Ver');

?>
<div class="giiant-crud locador-view">

    <h1>
        <?= $this->title ?>

        <small class="text-muted">
            <?= Html::encode($model->denominacion) ?>
        </small>
    </h1>


    <hr />

    <?php $this->beginBlock('app\models\Locador'); ?>


    <?php

    echo DetailView::widget([
        'model' => $model,
        'attributes' => [
            [
                'attribute' => 'created_at',
                'value' => yii\helpers\Html::encode(date("d-M-Y H:i", strtotime($model->created_at))),
            ],
			'denominacion',
			'documento',
			'email:email',
			'telefono',
			'documentos',
			'domicilio',

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
                    'content' => $this->blocks['app\models\Locador'],
                    'active' => true,
                ],

            ]
        ]
    );
    ?>
</div>