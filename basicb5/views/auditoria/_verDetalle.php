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
<div class="giiant-crud auditoria-view">



    <?php
    echo DetailView::widget([
        'model' => $model,
        'attributes' => [

            [
                'attribute' => 'pkId',
                'format' => 'raw',
                'value' => function ($model) {
                    return '<pre style="padding:10px; border-radius:5px;">' . 
                           json_encode($model->pkId, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . 
                           '</pre>';
                },
            ],            


            [
                'attribute' => 'changes',
                'format' => 'raw',
                'value' => function ($model) {
                    return '<pre style="padding:10px; border-radius:5px;">' . 
                           json_encode($model->changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . 
                           '</pre>';
                },
            ],            


        ],
    ]);
    ?>


</div>