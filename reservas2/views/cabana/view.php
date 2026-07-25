<?php


use app\models\Cabana;
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
            'numero',
            [
                'attribute' => 'activa',
                'value' => $model->activa == '1' ? 'Si' : 'No',
            ],
            'checkout',
            'checkin',
            'max_pax',
            [
                'attribute' => 'caracteristicas',
                'format' => 'raw',
                'value' => function ($model) {
                    $lines = $model->getFeaturesLines();

                    if (empty($lines)) {
                        return '';
                    }

                    return nl2br(Html::encode(implode("\n", $lines)));
                },
            ],
            // 🔹 Nuevo campo: color de la cabaña tomado de config[color_cabana]
            [
                'attribute' => 'color_cabana',
                'label' => Yii::t('app', 'Color de la cabaña'),
                'format' => 'raw',
                'value' => function ($model) {
                    /** @var \app\models\Cabana $model */
                    $hex = $model->color_cabana;     // viene del getter que lee config['color_cabana']
                    if (!$hex) {
                        return Html::tag('span', Yii::t('app', 'No definido'), [
                            'class' => 'badge bg-secondary',
                        ]);
                    }

                    $nombre = Cabana::$PALETA[$hex] ?? $hex;

                    return Html::tag('span', $nombre, [
                        'class' => 'badge rounded-pill',
                        'style' => "background: {$hex}; color: #000; font-weight:bold;",
                        'title' => $hex,
                    ]);
                },
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
                    'content' => $this->blocks['app\models\Cabana'],
                    'active' => true,
                ],

            ]
        ]
    );
    ?>
</div>
