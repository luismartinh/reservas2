<?php

use app\config\Empresas;
use yii\bootstrap5\Html;

/** @var yii\web\View $this */

$this->title = 'Reservas v2';

$url_images = Yii::getAlias('@web') . '/';

?>
<div class="site-index">



    <div class="jumbotron text-center bg-transparent mt-5 mb-5">



        <div class="row">
            <div class="col-12 text-start"> <!-- Asegura que ocupe toda la fila y alinee a la izquierda -->
                <?= Html::a('<i class="bi bi-patch-question"></i> ' . Yii::t('cruds', 'Buscar disponibilidad'), ['disponibilidad/buscar'], ['class' => 'btn btn-link']) ?>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3">
            </div>

            <div class="col-md-6">



            </div>
        </div>


    </div>

    <div class="body-content">

    </div>
</div>