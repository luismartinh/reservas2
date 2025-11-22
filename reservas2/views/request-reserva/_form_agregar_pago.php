<?php
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

/** @var \app\models\RequestReserva $model */
/** @var \yii\base\DynamicModel $formModel */
/** @var float $debe */

?>

<div class="agregar-pago-form">
    <p class="mb-2">
        <strong><?= Yii::t('app', 'Cliente') ?>:</strong>
        <?= Html::encode($model->denominacion) ?><br>
        <strong><?= Yii::t('app', 'Saldo pendiente') ?>:</strong>
        <?= '$ ' . number_format($debe, 2, ',', '.') ?>
    </p>

    <?php $form = ActiveForm::begin([
        'id' => 'form-agregar-pago',
        'action' => ['request-reserva/agregar-pago', 'id' => $model->id],
        'options' => ['enctype' => 'multipart/form-data'],
        'enableClientValidation' => false,
    ]); ?>

    <div class="mb-3">
        <?= $form->field($formModel, 'monto')
            ->input('number', [
                'step' => '0.01',
                'min' => '0.01',
                'max' => number_format($debe, 2, '.', ''),
                'required' => true,
            ])
            ->hint(Yii::t('app', 'El monto debe ser mayor que 0 y no puede superar {max}', [
                'max' => '$ ' . number_format($debe, 2, ',', '.'),
            ]))
            ->label(Yii::t('app', 'Monto del pago')) ?>
    </div>


    <div class="mb-3">
        <?= $form->field($formModel, 'notas')
            ->textarea([
                'rows' => 3,
                'maxlength' => 500,
            ])
            ->hint(Yii::t('app', 'Opcional. Máximo 500 caracteres.'))
            ->label(Yii::t('app', 'Notas')) ?>
    </div>



    <div class="mb-3">
        <?= $form->field($formModel, 'comprobante')
            ->fileInput([
                'accept' => '.png,.jpg,.jpeg,.pdf',
            ])
            ->hint(Yii::t('app', 'Opcional. Formatos permitidos: PNG, JPG, JPEG, PDF. Máx 5MB.'))
            ->label(Yii::t('app', 'Comprobante de pago (opcional)')) ?>
    </div>

    <div class="text-end">
        <?= Html::submitButton(
            '<i class="bi bi-plus-circle"></i> ' . Yii::t('app', 'Agregar pago'),
            ['class' => 'btn btn-success']
        ) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>