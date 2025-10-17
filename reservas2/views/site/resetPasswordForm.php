<?php

use kartik\form\ActiveForm;
use kartik\password\PasswordInput;
use yii\bootstrap5\Html;

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var app\models\LoginForm $model */


$this->title = 'Reseteo de password';
?>

<div class="site-reset-password">
    <h1><?= Html::encode($this->title) ?></h1>
    <p>Ingrese su nueva password:</p>
    <div class="row">
        <div class="col-lg-5">

            <?php $form = ActiveForm::begin(['id' => 'reset-password-form']); ?>


            <?= $form->field($model, 'password', ['inputOptions' => ['placeholder' => Yii::t('app', 'Ingrese la contraseña')]])->widget(
                PasswordInput::classname(),
                ['pluginOptions' => ['toggleMask' => true]]
            )->hint(Yii::t('app', 'password'))
                ?>
            <?= $form->field($model, 'repeatpassword', ['inputOptions' => ['placeholder' => Yii::t('app', 'repita la contraseña')]])->passwordInput()->label(Yii::t('app', 'Repita')) ?>


            <div class="form-group">
                <?= Html::submitButton('<i class="bi bi-floppy-fill me-2"></i>Guardar', ['class' => 'btn btn-primary']) ?>
            </div>
            <?php ActiveForm::end(); ?>

        </div>
    </div>
</div>