<?php
/**
 * @var yii\web\View $this
 * @var app\models\ParametrosGenerales $model
 * @var yii\widgets\ActiveForm $form
 */
?>


<!-- attribute descr -->
<?php echo $form->field($model, 'descr')->textInput(['maxlength' => true]) ?>

<!-- attribute valor -->
<?php 

echo $form->field($model, 'valor')->widget(
    kdn\yii2\JsonEditor::class,
    [
        'clientOptions' => ['language'=>'es', 'mode' => 'code', 'modes' => ['code','text', 'tree'],],
        //'containerOptions' => ['class' => 'container bg-dark'],
        'decodedValue' => $model->valor, /* if attribute contains already decoded JSON,then you should pass it as shown, otherwise omit this line */
    ]
);

//echo $form->field($model, 'valor')->textInput() 

?>


