<?php

use kartik\grid\GridView;
use yii\bootstrap5\Html;
use yii\data\ArrayDataProvider;

/** @var yii\web\View $this */
/** @var yii\data\ArrayDataProvider $dataProvider */

$this->title = 'Resultados de la Consulta SQL';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="query-result">
    <h1><?= Html::encode($this->title) ?></h1>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'summary' => "Mostrando {begin} - {end} de {totalCount} registros",
        'pager' => ['maxButtonCount' => 5],
    ]); ?>
</div>