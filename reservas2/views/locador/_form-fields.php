<?php
/**
 * @var yii\web\View $this
 * @var app\models\Locador $model
 * @var yii\widgets\ActiveForm $form
 */
?>


<!-- attribute denominacion -->
<?php echo $form->field($model, 'denominacion')->textInput(['maxlength' => true]) ?>

<!-- attribute documento -->
<?php echo $form->field($model, 'documento')->textInput(['maxlength' => true]) ?>

<!-- attribute email -->
<?php echo $form->field($model, 'email')->textInput(['type' => 'email', 'maxlength' => true]) ?>

<!-- attribute telefono -->
<?php echo $form->field($model, 'telefono')->textInput(['maxlength' => true]) ?>

<!-- attribute domicilio -->
<?php echo $form->field($model, 'domicilio')->textInput(['maxlength' => true]) ?>