<?php

use yii\bootstrap5\Html;



/**
 * @var yii\web\View $this
 * @var app\models\UpdateDBForm $model
 * @var array $processed
 */

$this->title = Yii::t('models', 'Rollback version en la base de datos');
$this->params['breadcrumbs'][] = Yii::t('cruds', 'RollBack');
?>
<div class="giiant-crud dbversion-rollback">

    <h1>
        <?= Yii::t('models', 'Deshacer la actualizacion en la base de datos:') ?>
        <br>
        <small class="text-muted">
            Ultima actualizacion: <?= Html::encode($model->last_update) ?>
        </small>
    </h1>


    <div class="clearfix crud-navigation">
        <div class="pull-left">
            <?= Html::a('<i class="bi bi-arrow-clockwise"></i> ' . 
            Yii::t('cruds', 'Update db version'), ['auditoria/update-db'], ['class' => 'btn btn-primary']) ?>

        </div>

    </div>



    <hr />

    <?php

    if (count($processed) > 0) {
        ?>
        <div class="alert alert-success">
            <p>
                <span class="glyphicon glyphicon-info-sign"></span>
                <strong>Nota:</strong>
                Se volvieron para atras, las actualizaciones en la base de datos a la version seleccionada.

                <?php echo Html::ul($processed, [
                    'class' => 'list-group list-group-numbered',
                    'itemOptions' => ['class' => 'list-group-item list-group-item-success']
                ]); ?>

            </p>
        </div>

    <?php } ?>

    <?php echo $this->render('_form', [
        'model' => $model
    ]); ?>

</div>