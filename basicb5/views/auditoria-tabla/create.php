<?php

use yii\helpers\Html;

/**
* @var yii\web\View $this
* @var app\models\AuditoriaTabla $model
*/

$this->title = Yii::t('models', 'Auditoria Tabla');
$this->params['breadcrumbs'][] = ['label' => Yii::t('models', 'Auditoria Tablas'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="giiant-crud auditoria-tabla-create">

    <h1>
                <?= Html::encode($model->id) ?>
        <small>
            <?= Yii::t('models', 'Auditoria Tabla') ?>
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
