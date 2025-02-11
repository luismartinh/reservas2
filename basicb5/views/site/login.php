<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var app\models\LoginForm $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = 'Ingreso';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-login">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>Por favor, ingresa tus credenciales:</p>

    <div class="row">
        <div class="col-lg-5">

            <?php $form = ActiveForm::begin([
                'id' => 'login-form',
                'fieldConfig' => [
                    'template' => "{label}\n{input}\n{error}",
                    'labelOptions' => ['class' => 'col-lg-3 col-form-label mr-lg-3'],
                    'inputOptions' => ['class' => 'col-lg-3 form-control'],
                    'errorOptions' => ['class' => 'col-lg-7 invalid-feedback'],
                ],
            ]); ?>

            <?= $form->field($model, 'username')->textInput(['autocomplete' => 'off', 'autofocus' => true]) ?>

            <?= $form->field($model, 'password', [
                'template' => '{label}<div class="input-group">{input}<div class="input-group-append">
                                <span class="input-group-text toggle-password">
                                    <i class="bi bi-eye"></i></i>
                                </span></div></div>{error}{hint}',
            ])->passwordInput(['id' => 'password-field']) ?>

            <?= $form->field($model, 'rememberMe')->checkbox([
                'template' => "<div class=\"custom-control custom-checkbox\">{input} {label}</div>\n<div class=\"col-lg-8\">{error}</div>",
            ]) ?>

            <div class="row">

                <div class="form-group">
                    <div class="col-md-11">
                        <div style="color:#999;">
                            Olvide mi password <?= Html::a('Reestablecer', ['site/request-password-reset']) ?>.
                        </div>
                    </div>
                </div>

            </div>


            <div class="form-group">
                <div>
                    <?= Html::submitButton('<i class="bi bi-box-arrow-in-right me-2"></i></i>Ingresar', ['class' => 'btn btn-primary', 'name' => 'login-button']) ?>
                </div>
            </div>

            <?php ActiveForm::end(); ?>



        </div>
    </div>
</div>


<?php
$script = <<<JS






    $(document).ready(function() {
        $(".toggle-password").click(function() {
            let input = $("#password-field");
            let icon = $(this).find("i");

            if (input.attr("type") === "password") {
                input.attr("type", "text");
                icon.removeClass("bi bi-eye").addClass("bi bi-eye-slash"); // Cambia a ojo cerrado
            } else {
                input.attr("type", "password");
                icon.removeClass("bi bi-eye-slash").addClass("bi bi-eye"); // Cambia a ojo abierto
            }
        });
    });



JS;

$this->registerJs($script);

