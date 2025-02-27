<?php

use kartik\grid\GridView;
use yii\bootstrap5\Html;
use yii\data\ArrayDataProvider;

use kartik\icons\FontAwesomeAsset;
FontAwesomeAsset::register($this);


/** @var yii\web\View $this */
/** @var yii\data\ArrayDataProvider $dataProvider */

?>

<div class="query-result">
    <?php \yii\widgets\Pjax::begin(['id' => 'pjax-main', 'enableReplaceState' => false, 'linkSelector' => '#pjax-main ul.pagination a, th a']) ?>

    <h3>Respuesta:</h3>

    <div class="table-responsive">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'pjax' => true,
            //'summary' => "Mostrando {begin} - {end} de {totalCount} registros",
            /*
            'pager' => [
                'class' => yii\widgets\LinkPager::class,
                'firstPageLabel' => Yii::t('cruds', 'First'),
                'lastPageLabel' => Yii::t('cruds', 'Last'),
            ],
            */
            'panel' => [
                'before' => $ind,
                'heading' => '<i class="fas fa-book"></i>  Resultado',
                'type' => 'info',
            ],
        ]); ?>
    </div>

    <?php \yii\widgets\Pjax::end() ?>
</div>