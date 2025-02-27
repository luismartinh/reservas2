<?php

use yii\bootstrap5\Html;

/** @var yii\web\View $this */

$this->title = 'My Yii Application';



?>
<div class="site-index">



    <div class="jumbotron text-center bg-transparent mt-5 mb-5">
        <h1 class="display-4">Congratulations!</h1>

        <p class="lead">You have successfully created your Yii-powered application.</p>


    </div>

    <div class="body-content">
        <div class="row">
            <div class="col-12 text-start"> <!-- Asegura que ocupe toda la fila y alinee a la izquierda -->
                <?= Html::a('<i class="bi bi-patch-question"></i> ' . Yii::t('cruds', 'Asistente de ayuda para mis funciones'), ['agent/ask-gemini'], ['class' => 'btn btn-link']) ?>
            </div>
        </div>

        <div class="row">
            <div class="col-12 text-start"> <!-- Asegura que ocupe toda la fila y alinee a la izquierda -->
                <?= Html::a('<i class="bi bi-patch-question"></i> ' . Yii::t('cruds', 'Asistente de ayuda para obtener información de mi base de datos'), ['agent/aOPENAI_API_KEY_ENV'], ['class' => 'btn btn-link']) ?>
            </div>
        </div>

    </div>


</div>