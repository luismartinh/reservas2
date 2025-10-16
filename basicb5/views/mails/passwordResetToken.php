<?php

use yii\bootstrap5\Html;

//use yii\helpers\Html;

$resetLink = Yii::$app->urlManager->createAbsoluteUrl(['site/reset-password', 'token' => $user->password_reset_token]);
?>

<div class="password-reset">
    <p> <?= Html::encode('Hola') . ' ' . Html::encode($user->login) ?>,</p>
    <p><?= Yii::t('app', 'Siga el link de abajo para resetear su password:') ?> :</p>
    <p><?= Html::a(Html::encode($resetLink), $resetLink) ?></p>
</div>