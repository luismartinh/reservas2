<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;
use yii\widgets\DetailView;
use yii\widgets\Pjax;
use yii\bootstrap\Tabs;

/**
 * @var yii\web\View $this
 * @var app\models\Auditoria $model
 */

?>
<div class="giiant-crud parametros-view">



    <?php
    echo DetailView::widget([
        'model' => $model,
        'attributes' => [


            [
                'attribute' => 'valor',
                'format' => 'raw',
                'value' => function ($model) {
                    return '<pre style="padding:10px; border-radius:5px;">' . 
                           json_encode($model->valor, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . 
                           '</pre>';
                },
            ],            


        ],
    ]);
    ?>


</div>