<?php

use yii\bootstrap5\Html;

/**
 * @var yii\web\View $this
 * @var app\models\Usuario $model
 */

$this->title = Yii::t('models', 'Modificar Usuario');
$this->params['breadcrumbs'][] = ['label' => Yii::t('models', 'Usuario'), 'url' => ['index']];
$this->params['breadcrumbs'][] = Yii::t('cruds', 'Editar');
?>
<div class="giiant-crud usuario-update">

    <h1>
        <?= Yii::t('models', 'Modificar:') ?>

        <small class="text-muted">
            <?= Html::encode($model->login) ?>
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
        'nivel' => $nivel,
        'relAttributes' => $relAttributes,
        'relAttributesHidden' => $relAttributesHidden,

    ]); ?>

</div>