<?php

use yii\helpers\Html;

/**
* @var yii\web\View $this
* @var app\models\ParametrosGenerales $model
*/

$this->title = Yii::t('models', 'Parametros Generales');
$this->params['breadcrumbs'][] = ['label' => Yii::t('models', 'Parametros Generales'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="giiant-crud parametros-generales-create">

    <h1>
                <?= Html::encode($model->id) ?>
        <small>
            <?= Yii::t('models', 'Parametros Generales') ?>
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
