<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var yii\data\ArrayDataProvider $dataProvider */
/** @var string $response */


$this->title = 'Consultas dobre la aplicación';
?>

<h1><?= Html::encode($this->title) ?></h1>

<?php $form = ActiveForm::begin(); ?>

<div class="row">
    <?= $form->field($model, 'question')->textarea(['maxlength' => true])->label("Pregunta:") ?>
</div>

<div class="row">
    <?php echo $form->errorSummary($model); ?>
</div>


<?php if (isset($response)): ?>
    <div class="alert alert-success">
        <strong>Respuesta:</strong>
        <p><?= nl2br(Html::encode($response)) ?></p>

    </div>
<?php endif; ?>

<div class="row">
    <div class="form-group">
        <?= Html::submitButton('Consultar', ['class' => 'btn btn-primary']) ?>
    </div>
</div>

<?php ActiveForm::end(); ?>




</div>