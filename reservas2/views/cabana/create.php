<?php

use yii\bootstrap5\Html;

/**
* @var yii\web\View $this
* @var app\models\Cabana $model
* @var array $coloresDisponibles
* @var array $numerosDisponibles
*/

$this->title = Yii::t('models', 'Nueva cabaña');
$this->params['breadcrumbs'][] = ['label' => Yii::t('models', 'Cabanas'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="giiant-crud cabana-create">

<h1>
        <small>
            <?= $this->title ?>
        </small>
    </h1>

    <div class="clearfix">
        <?= Html::a(
            '<i class="bi bi-arrow-counterclockwise me-2"></i>' . Yii::t('cruds', 'Cancelar'),
            ['index'],
            ['class' => 'btn btn-outline-secondary btn-default float-end']
        ) ?>

    </div>

    <hr />

    <?= $this->render('_form', [
    'model' => $model,
    'coloresDisponibles' => $coloresDisponibles,
     'numerosDisponibles' => $numerosDisponibles,
    ]); ?>

</div>
