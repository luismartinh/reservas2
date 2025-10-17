<?php

use yii\helpers\Html;

/**
* @var yii\web\View $this
* @var app\models\NotifTablas $model
*/

$this->title = Yii::t('models', 'Notif Tablas');
$this->params['breadcrumbs'][] = ['label' => Yii::t('models', 'Notif Tablas'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="giiant-crud notif-tablas-create">

    <h1>
                <?= Html::encode($model->id) ?>
        <small>
            <?= Yii::t('models', 'Notif Tablas') ?>
        </small>
    </h1>

    <div class="clearfix crud-navigation">
        <div class="pull-left">
            <?=             Html::a(
            Yii::t('cruds', 'Cancel'),
            ['index'],
            ['class' => 'btn btn-default']) ?>
        </div>
    </div>

    <hr />

    <?= $this->render('_form', [
    'model' => $model,
    ]); ?>

</div>
