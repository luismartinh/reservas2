<?php

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\helpers\Url;

/** @var \yii\web\View $this */
/** @var \app\models\RequestReserva $model */

$tabsId = 'request-tabs-' . (int) $model->id;
$obsPaneId = $tabsId . '-obs';
$solPaneId = $tabsId . '-solicitado';
$formId = 'form-request-obs-' . (int) $model->id;
$msgId = 'request-obs-msg-' . (int) $model->id;
?>
<ul class="nav nav-tabs mb-3" id="<?= Html::encode($tabsId) ?>" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="<?= Html::encode($obsPaneId) ?>-tab" data-bs-toggle="tab"
            data-bs-target="#<?= Html::encode($obsPaneId) ?>" type="button" role="tab"
            aria-controls="<?= Html::encode($obsPaneId) ?>" aria-selected="true">
            <?= Html::encode(Yii::t('app', 'Observaciones')) ?>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="<?= Html::encode($solPaneId) ?>-tab" data-bs-toggle="tab"
            data-bs-target="#<?= Html::encode($solPaneId) ?>" type="button" role="tab"
            aria-controls="<?= Html::encode($solPaneId) ?>" aria-selected="false">
            <?= Html::encode(Yii::t('app', 'Solicitado')) ?>
        </button>
    </li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="<?= Html::encode($obsPaneId) ?>" role="tabpanel"
        aria-labelledby="<?= Html::encode($obsPaneId) ?>-tab">
        <?php $form = ActiveForm::begin([
            'id' => $formId,
            'action' => Url::to(['request-reserva/guardar-obs', 'id' => $model->id]),
            'options' => [
                'class' => 'form-request-obs',
                'data-msg-id' => $msgId,
            ],
        ]); ?>

        <?= Html::textarea('obs', (string) ($model->obs ?? ''), [
            'class' => 'form-control mb-3',
            'rows' => 6,
            'maxlength' => 500,
        ]) ?>

        <div id="<?= Html::encode($msgId) ?>" class="small mb-3"></div>

        <div class="d-flex justify-content-end">
            <?= Html::submitButton(
                '<i class="bi bi-floppy me-2"></i>' . Yii::t('app', 'Guardar observaciones'),
                ['class' => 'btn btn-primary']
            ) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>

    <div class="tab-pane fade" id="<?= Html::encode($solPaneId) ?>" role="tabpanel"
        aria-labelledby="<?= Html::encode($solPaneId) ?>-tab">
        <?= Yii::$app->controller->renderPartial('_request_cabanas', [
            'model' => $model,
        ]) ?>
    </div>
</div>
