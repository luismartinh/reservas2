<?php
/**
 * @var yii\web\View $this
 * @var app\models\Auditoria $model
 * @var yii\widgets\ActiveForm $form
 */
?>


<!-- attribute tabla -->
<?php echo $form->field($model, 'tabla')->textInput(['maxlength' => true]) ?>

<!-- attribute changes -->
<?php echo $form->field($model, 'changes')->textInput() ?>

<!-- attribute user -->
<?php echo $form->field($model, 'user')->textInput(['maxlength' => true]) ?>

<!-- attribute action -->
<?php echo $form->field($model, 'action')->textInput(['maxlength' => true]) ?>

<!-- attribute pkId -->
<?php echo $form->field($model, 'pkId')->textInput() ?>