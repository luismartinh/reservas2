<?php
/**
 * @var yii\web\View $this
 * @var app\models\NotifTablas $model
 * @var yii\widgets\ActiveForm $form
 */
?>


<!-- attribute tabla -->
<?php echo $form->field($model, 'tabla')->textInput(['maxlength' => true]) ?>

<!-- attribute enabled -->
<?php echo $form->field($model, 'enabled')->textInput() ?>