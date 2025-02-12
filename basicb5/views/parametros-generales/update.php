<?php

use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var app\models\ParametrosGenerales $model
 */

$this->title = Yii::t('models', 'Modificar Parametro General');
$this->params['breadcrumbs'][] = ['label' => Yii::t('models', 'Parametros Generales'), 'url' => ['index']];
$this->params['breadcrumbs'][] = Yii::t('cruds', 'Modificar');
?>
<div class="giiant-crud parametros-generales-update">

    <h1>
        <?= Html::encode($model->clave) ?>

        <small class="text-muted">
            <?= Html::encode($model->descr) ?>
            <?= Yii::t('models', ' parametro') ?>
        </small>
    </h1>

    <hr />

    <?php echo $this->render('_form', [
        'model' => $model,
    ]); ?>

</div>