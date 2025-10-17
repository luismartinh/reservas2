<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var yii\data\ArrayDataProvider $dataProvider */


$this->title = 'Consultas de datos';
?>

<h1><?= Html::encode($this->title) ?></h1>

<?php $form = ActiveForm::begin(); ?>

<div class="row">
    <div class="alert alert-warning d-flex align-items-center" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-3"></i>
        <div>
            <strong>Cuidado! solo uso experimental!</strong><spam class="ms-3">Las respuestas pueden ser incorrectas!</spam> 
        </div>
    </div>
</div>


<div class="row">
    <?= $form->field($model, 'question')
    ->textarea(['maxlength' => true])
    ->label("Pregunta:")->hint("Escriba su pregunta aqui, como por ejemplo: ¿Cuántos usuarios hay?") ?>
</div>

<div class="row">
    <?php echo $form->errorSummary($model); ?>
</div>

<div class="row">
    <div class="form-group">
        <?= Html::submitButton('Consultar', ['class' => 'btn btn-primary']) ?>
    </div>
</div>

<?php ActiveForm::end(); ?>

<?php if (isset($dataProvider)) { ?>

<div class="row">
    <?= $this->render('resultquery2', ['dataProvider' => $dataProvider]) ?>
</div>




<?php } ?>

</div>






