<?php
/**
 * @var yii\web\View $this
 * @var app\models\Cabana $model
 * @var yii\widgets\ActiveForm $form
 */
?>


<!-- attribute descr -->
<?php echo $form->field($model, 'descr')->textInput(['maxlength' => true]) ?>


<!-- attribute checkout -->
<?= $form->field($model, 'checkout')->textInput([
    'type' => 'time',
    'step' => 60,
    'value' => $model->checkout ? date('H:i', strtotime($model->checkout)) : null,
]) ?>



<!-- attribute checkin -->
<?= $form->field($model, 'checkin')->textInput([
    'type' => 'time',
    'step' => 60,
    'value' => $model->checkin ? date('H:i', strtotime($model->checkin)) : null,
]) ?>



<!-- attribute max_pax -->
<?php echo $form->field($model, 'max_pax')->textInput(['type' => 'number']) ?>

<!-- attribute activa -->
<?php
if (!(isset($relAttributesHidden) && isset($relAttributesHidden['activa']))) {
    echo $form->field($model, 'activa')->checkbox([
        'custom' => true,
        'switch' => true,
        'disabled' => (isset($relAttributes) && isset($relAttributes['activa'])),
    ]);
}
?>

<!-- attribute caracteristicas -->
<?= $form->field($model, 'caracteristicas')->textarea([
    'maxlength' => true,
    'disabled' => (isset($relAttributes) && isset($relAttributes['caracteristicas'])),
]) ?>