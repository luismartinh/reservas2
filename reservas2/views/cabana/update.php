<?php

use yii\helpers\Html;

/**
* @var yii\web\View $this
* @var app\models\Cabana $model
*/

$this->title = Yii::t('models', 'Modificar cabana');
$this->params['breadcrumbs'][] = ['label' => Yii::t('models', 'Cabañas'), 'url' => ['index']];
$this->params['breadcrumbs'][] = Yii::t('cruds', 'Editar');
?>
<div class="giiant-crud cabana-update">

    <h1>
        <?= Yii::t('models', 'Modificar:') ?>

        <small class="text-muted">
            <?= Html::encode($model->descr) ?>
        </small>
    </h1>

    <div class="clearfix">
        <?= Html::a(
            '<i class="bi bi-eye  me-2"></i>' . Yii::t('cruds', Yii::t('cruds', 'Ver')),
            ['view', 'id' => $model->id],
            ['class' => 'btn btn-outline-secondary btn-default  float-end']
        ) ?>


    </div>

    <hr />

    <?php echo $this->render('_form', [
    'model' => $model,
    ]); ?>

</div>
