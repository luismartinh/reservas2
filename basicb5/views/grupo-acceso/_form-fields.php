<?php
/**
 * @var yii\web\View $this
 * @var app\models\GrupoAcceso $model
 * @var yii\widgets\ActiveForm $form
 */
?>


<!-- attribute descr -->
<?php echo $form->field($model, 'descr')->textInput(['maxlength' => true]) ?>

<?php 
echo $form->field($model, 'nivel')->dropDownList(app\config\Niveles::getNivelesDesde($nivel)); ;
?>
