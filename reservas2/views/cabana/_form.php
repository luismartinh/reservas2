<?php

use yii\bootstrap5\Html;
use yii\bootstrap5\Tabs;
use kartik\form\ActiveForm;

/**
 * @var yii\web\View $this
 * @var app\models\Cabana $model
 * @var yii\widgets\ActiveForm $form
 */

$trait = new app\traits\FormTraitClass();

$trait->init($this);


?>

<?= $trait->getModal() ?>

<div class="cabana-form">

    <?php $form = ActiveForm::begin(
        [
            'id' => 'cabana-form-id',
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
        ]
    );
    ?>

    <?=
        Tabs::widget(
            [
                'encodeLabels' => false,
                'items' => [
                    [
                        'label' => Yii::t('cruds', 'Ingrese los datos:'),
                        'content' => '<div class="row mt-3 ps-3" style="width: 90%">' .
                            $this->render('_form-fields', ['form' => $form, 'model' => $model]) . '</div>',
                        'active' => true,
                    ]
                ]
            ]
        );
    ?>
    <hr />

    <?php echo $form->errorSummary($model); ?>

    <div class="form-group mb-3">
        <?= Html::submitButton(
            '<i class="bi bi-floppy-fill me-2"></i>' .
            ($model->isNewRecord ? Yii::t('cruds', 'Crear') : Yii::t('cruds', 'Guardar')),
            [
                'id' => 'save-' . $model->formName(),
                'class' => 'btn btn-success'
            ]
        );
        ?>

        <?= Html::resetButton(
            '<i class="bi bi-x-circle-fill me-2"></i>' . Yii::t('cruds', 'Reset'),
            ['class' => 'btn btn-outline-secondary btn-default ms-2']
        ) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>