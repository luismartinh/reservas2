<?php

use yii\bootstrap5\Html;



/**
 * @var yii\web\View $this
 * @var app\models\UpdateDBForm $model
 * @var array $processed
 */

$this->title = Yii::t('models', 'Actualizar la base de datos');
$this->params['breadcrumbs'][] = Yii::t('cruds', 'Actualizar');
?>
<div class="giiant-crud dbversion-update">

    <h1>
        <?= Yii::t('models', 'Actualizar la base de datos:') ?>

        <small class="text-muted">
            Ultima actualizacion: <?= Html::encode($model->last_update) ?>
        </small>
    </h1>


    <div class="clearfix crud-navigation">
        <div class="pull-left">
            <?= Html::a('<i class="bi bi-arrow-counterclockwise"></i> ' . 
            Yii::t('cruds', 'Rollback'), ['auditoria/rollback-update'], ['class' => 'btn btn-danger']) ?>

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
                Se actualizo la base de datos con la version actual del sistema.

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