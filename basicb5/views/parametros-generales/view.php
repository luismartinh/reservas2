<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;
use yii\widgets\DetailView;
use yii\widgets\Pjax;
use yii\bootstrap\Tabs;

/**
* @var yii\web\View $this
* @var app\models\ParametrosGenerales $model
*/

$this->title = Yii::t('models', 'Parametros Generales');
$this->params['breadcrumbs'][] = ['label' => Yii::t('models.plural', 'Parametros Generales'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => (string)$model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('cruds', 'View');
?>
<div class="giiant-crud parametros-generales-view">

    <h1>
        <?= Html::encode($model->id) ?>
        <small><?= Yii::t('cruds', 'Parametros Generales') ?></small>
    </h1>

    <div class="clearfix crud-navigation">

        <!-- menu buttons -->
        <div class='pull-left'>

            <?php if(\Yii::$app->getUser()->can('app_parametros-generales_update')): ?>            <?php 
 echo Html::a(
            '<span class="glyphicon glyphicon-pencil"></span> ' . Yii::t('cruds', 'Edit Parametros Generales'),
            [ 'update', 'id' => $model->id],
            ['class' => 'btn btn-info'])
            ?>
            <?php endif ?>
                        <?php if(\Yii::$app->getUser()->can('app_parametros-generales_update')): ?>            <?php 
 echo Html::a(
            '<span class="glyphicon glyphicon-copy"></span> ' . Yii::t('cruds', 'Copy Parametros Generales'),
            ['create', 'id' => $model->id, 'ParametrosGenerales'=> $model->hasMethod('getCopyParams') ? $model->getCopyParams() : $model->attributes],
            ['class' => 'btn btn-success'])
            ?>
            <?php endif ?>            
            <?php if(\Yii::$app->getUser()->can('app_parametros-generales_create')): ?>            <?php 
 echo Html::a(
            '<span class="glyphicon glyphicon-plus"></span> ' . Yii::t('cruds', 'New Parametros Generales'),
            ['create'],
            ['class' => 'btn btn-success'])
            ?>
            <?php endif ?>        </div>

        <div class="pull-right">
            <?= Html::a('<span class="glyphicon glyphicon-list"></span> '
            . Yii::t('cruds', 'Full list'), ['index'], ['class'=>'btn btn-default']) ?>
        </div>

    </div>

    <hr/>

    <?php $this->beginBlock('app\models\ParametrosGenerales'); ?>

    
    <?php 
 echo DetailView::widget([
    'model' => $model,
    'attributes' => [
            'clave',
        'valor',
        'descr',
    ],
    ]);
    ?>

    
    <hr/>

    <?php if(\Yii::$app->getUser()->can('app_parametros-generales_delete')): ?>    <?php 
 echo Html::a('<span class="glyphicon glyphicon-trash"></span> '
    . Yii::t('cruds', 'Delete Parametros Generales'), ['delete', 'id' => $model->id],
    [
    'class' => 'btn btn-danger',
    'data-confirm' => '' . Yii::t('cruds', 'Are you sure to delete this item?') . '',
    'data-method' => 'post',
    ]);
    ?>
    <?php endif ?>    <?php $this->endBlock(); ?>


    
    <?php 
        echo Tabs::widget(
                 [
                     'id' => 'relation-tabs',
                     'encodeLabels' => false,
                     'items' => [
 [
    'label'   => '<b>' . \Yii::t('cruds', '# {primaryKey}', ['primaryKey' => Html::encode($model->id)]) . '</b>',
    'content' => $this->blocks['app\models\ParametrosGenerales'],
    'active'  => true,
],
 ]
                 ]
    );
    ?>
</div>
