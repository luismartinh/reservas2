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

$this->title = Yii::t('models', 'Auditoria');
$this->params['breadcrumbs'][] = ['label' => Yii::t('models.plural', 'Auditoria'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => (string)$model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('cruds', 'View');
?>
<div class="giiant-crud auditoria-view">

    <h1>
        <?= Html::encode($model->id) ?>
        <small><?= Yii::t('cruds', 'Auditoria') ?></small>
    </h1>

    <div class="clearfix crud-navigation">

        <!-- menu buttons -->
        <div class='pull-left'>

            <?php if(\Yii::$app->getUser()->can('app_auditoria_update')): ?>            <?php 
 echo Html::a(
            '<span class="glyphicon glyphicon-pencil"></span> ' . Yii::t('cruds', 'Edit Auditoria'),
            [ 'update', 'id' => $model->id],
            ['class' => 'btn btn-info'])
            ?>
            <?php endif ?>
                        <?php if(\Yii::$app->getUser()->can('app_auditoria_update')): ?>            <?php 
 echo Html::a(
            '<span class="glyphicon glyphicon-copy"></span> ' . Yii::t('cruds', 'Copy Auditoria'),
            ['create', 'id' => $model->id, 'Auditoria'=> $model->hasMethod('getCopyParams') ? $model->getCopyParams() : $model->attributes],
            ['class' => 'btn btn-success'])
            ?>
            <?php endif ?>            
            <?php if(\Yii::$app->getUser()->can('app_auditoria_create')): ?>            <?php 
 echo Html::a(
            '<span class="glyphicon glyphicon-plus"></span> ' . Yii::t('cruds', 'New Auditoria'),
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

    <?php $this->beginBlock('app\models\Auditoria'); ?>

    
    <?php 
 echo DetailView::widget([
    'model' => $model,
    'attributes' => [
            'tabla',
        'changes',
        'user',
        'action',
        'pkId',
    ],
    ]);
    ?>

    
    <hr/>

    <?php if(\Yii::$app->getUser()->can('app_auditoria_delete')): ?>    <?php 
 echo Html::a('<span class="glyphicon glyphicon-trash"></span> '
    . Yii::t('cruds', 'Delete Auditoria'), ['delete', 'id' => $model->id],
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
    'content' => $this->blocks['app\models\Auditoria'],
    'active'  => true,
],
 ]
                 ]
    );
    ?>
</div>
