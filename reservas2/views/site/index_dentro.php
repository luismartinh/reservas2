<?php

use app\components\asistente\Acceso;
use yii\bootstrap5\Html;

/** @var yii\web\View $this */

$this->title = 'reservas v2';

$url_images = Yii::getAlias('@web') . '/images/';


?>
<div class="site-index">



    <div class="jumbotron text-center bg-transparent mt-5 mb-5">

        <div class="row">
            <div class="col-md-3">
            </div>

            <div class="col-md-6">

                

            </div>
        </div>

    </div>

    <div class="body-content">

        <div class="row">
            <div class="col-12 text-start"> <!-- Asegura que ocupe toda la fila y alinee a la izquierda -->
                <?= Html::a('<i class="bi bi-patch-question"></i> '
                 . Yii::t('cruds', 'Buscar disponibilidad'),
                  ['reserva/reservar'], ['class' => 'btn btn-link']) ?>
            </div>
        </div>

        <?php if (Acceso::permitido(Yii::$app->user->identity)): ?>
            <div class="row">
                <div class="col-12 text-start">
                    <?= Html::a('<i class="bi bi-chat-dots"></i> ' . Yii::t('cruds', 'Asistente'), ['asistente/index'], ['class' => 'btn btn-link']) ?>
                </div>
            </div>
        <?php endif; ?>

    </div>


</div>
