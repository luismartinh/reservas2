<?php
/** @var \yii\web\View $this */
/** @var \app\models\ContactForm $model */

use yii\helpers\Html;
?>

<h2><?= Yii::t('app', 'Nueva consulta desde el formulario de contacto') ?></h2>

<p><strong><?= Yii::t('app', 'Nombre') ?>:</strong> <?= Html::encode($model->name) ?></p>
<p><strong><?= Yii::t('app', 'Email') ?>:</strong> <?= Html::encode($model->email) ?></p>
<p><strong><?= Yii::t('app', 'Asunto') ?>:</strong> <?= Html::encode($model->subject) ?></p>

<hr>

<p><strong><?= Yii::t('app', 'Mensaje') ?>:</strong></p>
<p style="white-space: pre-line;"><?= Html::encode($model->body) ?></p>

<hr>

<p style="font-size: 0.85rem; color: #666;">
    <?= Yii::t('app', 'Enviado el {fecha}', [
        'fecha' => Yii::$app->formatter->asDatetime('now', 'php:d/m/Y H:i')
    ]) ?><br>
    IP: <?= Html::encode(Yii::$app->request->userIP) ?>
</p>
