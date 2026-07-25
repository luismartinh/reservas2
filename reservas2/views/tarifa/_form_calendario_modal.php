<?php

use kartik\form\ActiveForm;
use yii\bootstrap5\Html;

/** @var yii\web\View $this */
/** @var app\models\Tarifa $model */
/** @var array $actionRoute */
/** @var string $submitLabel */
/** @var string $modalTitle */
?>

<div class="tarifa-calendario-modal-form">
    <h5 class="mb-3"><?= Html::encode($modalTitle ?? '') ?></h5>

    <?php $form = ActiveForm::begin([
        'id' => 'tarifa-calendario-modal-form',
        'action' => $actionRoute,
        'type' => ActiveForm::TYPE_VERTICAL,
        'enableClientValidation' => true,
        'errorSummaryCssClass' => 'error-summary alert alert-danger',
        'fieldConfig' => [
            'template' => "{label}\n{beginWrapper}\n{input}\n{hint}\n{error}\n{endWrapper}",
            'horizontalCssClasses' => [
                'label' => 'col-sm-2',
                'wrapper' => 'col-sm-8',
                'error' => '',
                'hint' => '',
            ],
        ],
    ]); ?>

    <div class="row">
        <div class="col-12">
            <?= $this->render('_form-fields', [
                'form' => $form,
                'model' => $model,
                'relAttributesHidden' => ['activa' => true],
            ]) ?>
        </div>
    </div>

    <?= $form->errorSummary($model) ?>

    <div class="d-flex justify-content-end gap-2 mt-3">
        <?= Html::button('Cancelar', [
            'class' => 'btn btn-outline-secondary',
            'data-bs-dismiss' => 'modal',
        ]) ?>
        <?= Html::submitButton(
            '<i class="bi bi-floppy-fill me-2"></i>' . Html::encode($submitLabel),
            ['class' => 'btn btn-success']
        ) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>
