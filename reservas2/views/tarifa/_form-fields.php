<?php
/**
 * @var yii\web\View $this
 * @var app\models\Tarifa $model
 * @var yii\widgets\ActiveForm $form
 */
?>



<!-- attribute descr -->
<?php echo $form->field($model, 'descr')->textInput(['maxlength' => true]) ?>

<!-- attribute inicio -->
<?= $form->field($model, 'inicio')->input('date', [
    'value' => $model->inicio ? (new \DateTime($model->inicio))->format('Y-m-d') : '',
]) ?>


<!-- attribute fin -->
<?= $form->field($model, 'fin')->input('date', [
    'value' => $model->fin ? (new \DateTime($model->fin))->format('Y-m-d') : '',
]) ?>


<!-- attribute valor_dia -->
<?php echo $form->field($model, 'valor_dia')->textInput(['type' => 'number', 'step' => '0.01']) ?>

<!-- attribute min_dias -->
<?php echo $form->field($model, 'min_dias')->textInput(['type' => 'number']) ?>

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