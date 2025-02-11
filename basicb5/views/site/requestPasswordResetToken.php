<?php


use kartik\form\ActiveForm;
use yii\bootstrap5\Html;

/**
 * @var yii\web\View $this
 * @var app\models\Usuario $model
 */


$this->title = 'Reseteo de password';

?>

<div class="site-request-password-reset" id="particles-js">
    <br>
    <h3><?= Html::encode($this->title) ?></h3>
    <p>Por favor complete su correo electrónico. Un enlace para restablecer la password será enviado allí.</p>
    <div class="row">
        <div class="col-lg-5">

            <?php $form = ActiveForm::begin(['id' => 'request-password-reset-form']); ?>
            <?= $form->field($model, 'email')->textInput(['autofocus' => true]) ?>
            <div class="form-group">
                <?= Html::submitButton('Enviar<i class="bi bi-forward-fill ms-2"></i>', ['class' => 'btn btn-primary']) ?>
            </div>
            <?php ActiveForm::end(); ?>

        </div>
    </div>
</div>