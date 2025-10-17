<?php

use yii\bootstrap5\Html;

/**
 * @var yii\web\View $this
 * @var app\models\Usuario $model
 * @var app\models\Usuario $user
 */

$this->title = Yii::t('models', 'Crear usuario');
$this->params['breadcrumbs'][] = ['label' => Yii::t('models', 'Usuarios'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="giiant-crud usuario-create">

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
        'nivel' => $nivel,
        'user' => $user
    ]); ?>


</div>