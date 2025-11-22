<?php
use yii\bootstrap5\Html;

/** @var \app\models\RequestReserva $reservaReq */
/** @var string $trackingUrl */

$this->title = Yii::t('app', 'Reserva registrada');
?>
<h2 class="mb-4 text-center"><?= Yii::t('app', 'Reserva registrada') ?></h2>

<div class="alert alert-success">
    <h4 class="mb-2"><?= Yii::t('app', 'Se registró la reserva correctamente.') ?></h4>
</div>

<div class="card border-secondary">
    <div class="card-body">
        <h6 class="card-title mb-2">
            <i class="bi bi-link-45deg me-1"></i><?= Yii::t('app', 'URL de seguimiento') ?>
        </h6>
        <p class="mb-2">
            <?= Html::a(Html::encode($trackingUrl), $trackingUrl, ['target' => '_blank', 'rel' => 'noopener']) ?>
        </p>
        <?= Html::a(
            '<i class="bi bi-box-arrow-up-right me-1"></i>' . Yii::t('app', 'Abrir seguimiento'),
            $trackingUrl,
            ['class' => 'btn btn-outline-primary', 'target' => '_blank', 'rel' => 'noopener']
        ) ?>
    </div>
</div>