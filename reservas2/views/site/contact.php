<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var app\models\ContactForm $model */

use app\assets\SubmitOverlayAsset;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\captcha\Captcha;

SubmitOverlayAsset::register($this);


$this->registerCssFile('@web/css/cabana.css', [
    'depends' => [\yii\bootstrap5\BootstrapAsset::class]
]);


$this->title = Yii::t('app', 'Contacto');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-contact">
    <h1><?= Html::encode($this->title) ?></h1>

    <?php if (Yii::$app->session->hasFlash('contactFormSubmitted')): ?>

        <div class="alert alert-success">
            <?= Yii::t('app', 'Gracias por contactarnos. Te responderemos a la brevedad.') ?>
        </div>

        <p>
            <?= Yii::t('app', 'Si tenés habilitado el Yii Debugger, podés ver el mensaje enviado en el panel de emails del debugger.') ?>
            <?php if (Yii::$app->mailer->useFileTransport): ?>
                <br>
                <?= Yii::t('app', 'Como la aplicación está en modo desarrollo, el email no se envía realmente, sino que se guarda como un archivo en') ?>
                <code><?= Yii::getAlias(Yii::$app->mailer->fileTransportPath) ?></code>.
                <br>
                <?= Yii::t('app', 'Configurá la propiedad') ?>
                <code>useFileTransport</code>
                <?= Yii::t('app', 'del componente') ?>
                <code>mail</code>
                <?= Yii::t('app', 'para habilitar el envío real de correos.') ?>
            <?php endif; ?>
        </p>

    <?php else: ?>

        <p>
            <?= Yii::t('app', 'Si tenés consultas o preguntas, completá el siguiente formulario y nos pondremos en contacto con vos. ¡Gracias!') ?>
        </p>

        <div class="row">
            <div class="col-lg-5">


                <?php $form = ActiveForm::begin([
                    'id' => 'contact-form',
                    'options' => [
                        'data-submit-overlay' => 'true',
                        'data-overlay-text' => Yii::t('app', 'Enviando su solicitud, por favor espere...'),
                    ],
                ]); ?>



                <?= $form->field($model, 'name')
                    ->textInput(['autofocus' => true])
                    ->label(Yii::t('app', 'Nombre')) ?>

                <?= $form->field($model, 'email')
                    ->label(Yii::t('app', 'Email')) ?>

                <?= $form->field($model, 'subject')
                    ->label(Yii::t('app', 'Asunto')) ?>

                <?= $form->field($model, 'body')->textarea([
                    'rows' => 6,
                    'maxlength' => 500,
                    'placeholder' => Yii::t('app', 'Escribí tu consulta (máx. 500 caracteres)...'),
                ])
                    ->label(Yii::t('app', 'Mensaje')) ?>


                <div class="row justify-content-center mt-4">
                    <div class="col-md-12">
                        <div class="card border-info shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title mb-2">
                                    <i class="bi bi-shield-check me-2"></i>
                                    <?= Yii::t('app', 'Verificación humana') ?>
                                </h5>

                                <p class="text-muted small mb-3">
                                    <?= Yii::t('app', 'Para evitar envíos automáticos, por favor escriba los caracteres que ve en la imagen. Si no los distingue, haga clic sobre la imagen para generar una nueva.') ?>
                                </p>

                                <?= $form->field($model, 'verifyCode')->widget(Captcha::class, [
                                    //'captchaAction' => 'disponibilidad/captcha',
                                    'template' => '
                        <div class="row align-items-center g-2 mb-1">
                            <div class="col-5 text-center">
                                {image}
                            </div>
                            <div class="col-7">
                                {input}
                            </div>
                        </div>
                    ',
                                    'imageOptions' => [
                                        'alt' => Yii::t('app', 'Código de verificación'),
                                        'style' => 'cursor:pointer; border-radius:4px;',
                                        'title' => Yii::t('app', 'Click para recargar la imagen'),
                                    ],
                                    'options' => [
                                        'class' => 'form-control',
                                        'placeholder' => Yii::t('app', 'Ingrese el código aquí'),
                                    ],
                                ])->label(false)
                                    ->hint(Yii::t('app', 'Si el código no se entiende, haga clic en la imagen para cambiarlo.')) ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <?= Html::submitButton(
                        '<i class="bi bi-send"></i> ' . Yii::t('app', 'Enviar solicitud'),
                        [
                            'class' => 'btn btn-primary btn-lg px-5',
                            'name' => 'contact-button',
                            'data-submit-overlay-btn' => 'true',
                            'data-loading-text' => Yii::t('app', 'Enviando...'),
                        ]
                    ) ?>
                </div>


                <?php ActiveForm::end(); ?>

            </div>
        </div>

    <?php endif; ?>
</div>