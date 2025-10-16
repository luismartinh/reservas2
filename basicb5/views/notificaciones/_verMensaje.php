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
<div class="giiant-crud notificaciones-view">



    <?php
    echo DetailView::widget([
        'model' => $model,
        'attributes' => [

            [
                'attribute' => 'msg',
                'format' => 'raw',
            ],            


        ],
    ]);
    ?>


</div>