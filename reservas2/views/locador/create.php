<?php

use yii\bootstrap5\Html;

/**
* @var yii\web\View $this
* @var app\models\Locador $model
*/

$this->title = Yii::t('models', 'Nuevo pasajero');
$this->params['breadcrumbs'][] = ['label' => Yii::t('models', 'Pasajeros'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="giiant-crud locado-create">

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
    ]); ?>

</div>
