<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;
use yii\widgets\DetailView;
use yii\widgets\Pjax;
use yii\bootstrap\Tabs;

/**
* @var yii\web\View $this
* @var app\models\AuditoriaTabla $model
*/

$this->title = Yii::t('models', 'Auditoria Tabla');
$this->params['breadcrumbs'][] = ['label' => Yii::t('models.plural', 'Auditoria Tabla'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => (string)$model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('cruds', 'View');
?>
<div class="giiant-crud auditoria-tabla-view">

    <h1>
        <?= Html::encode($model->id) ?>
        <small><?= Yii::t('cruds', 'Auditoria Tabla') ?></small>
    </h1>

    <div class="clearfix crud-navigation">

        <!-- menu buttons -->
        <div class='pull-left'>

            <?php if(\Yii::$app->getUser()->can('app_auditoria-tabla_update')): ?>            <?php 
 echo Html::a(
            '<span class="glyphicon glyphicon-pencil"></span> ' . Yii::t('cruds', 'Edit Auditoria Tabla'),
            [ 'update', 'id' => $model->id],
            ['class' => 'btn btn-info'])
            ?>
            <?php endif ?>
                        <?php if(\Yii::$app->getUser()->can('app_auditoria-tabla_update')): ?>            <?php 
 echo Html::a(
            '<span class="glyphicon glyphicon-copy"></span> ' . Yii::t('cruds', 'Copy Auditoria Tabla'),
            ['create', 'id' => $model->id, 'AuditoriaTabla'=> $model->hasMethod('getCopyParams') ? $model->getCopyParams() : $model->attributes],
            ['class' => 'btn btn-success'])
            ?>
            <?php endif ?>            
            <?php if(\Yii::$app->getUser()->can('app_auditoria-tabla_create')): ?>            <?php 
 echo Html::a(
            '<span class="glyphicon glyphicon-plus"></span> ' . Yii::t('cruds', 'New Auditoria Tabla'),
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

    <?php $this->beginBlock('app\models\AuditoriaTabla'); ?>

    
    <?php 
 echo DetailView::widget([
    'model' => $model,
    'attributes' => [
            'tabla',
        'enabled',
    ],
    ]);
    ?>

    
    <hr/>

    <?php if(\Yii::$app->getUser()->can('app_auditoria-tabla_delete')): ?>    <?php 
 echo Html::a('<span class="glyphicon glyphicon-trash"></span> '
    . Yii::t('cruds', 'Delete Auditoria Tabla'), ['delete', 'id' => $model->id],
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
    'content' => $this->blocks['app\models\AuditoriaTabla'],
    'active'  => true,
],
 ]
                 ]
    );
    ?>
</div>
