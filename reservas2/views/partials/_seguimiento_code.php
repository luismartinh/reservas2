<?php
/**
 *
 * @var \app\models\RequestReserva $requestReserva
 */


?>



<!-- Mostrar codigo de seguimiento -->
<div class="alert alert-primary">
    <h2><?= Yii::t('app', 'Su codigo de seguimiento es:') ?>
        <strong><?= $requestReserva->codigo_reserva ?></strong>
    </h2>
</div>