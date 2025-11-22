<?php
use yii\bootstrap5\Html;

/** @var bool   $ok */
/** @var string $msg */
/** @var string|null $trackingUrl */

$this->title = Yii::t('app', 'Confirmación de Email');
?>
<h2 class="mb-4 text-center"><?= Html::encode($this->title) ?></h2>

<?php if ($ok): ?>
    <div class="alert alert-success">
        <strong><?= Yii::t('app', 'Su email fue verificado') ?>.</strong><br>
        <?= Html::encode($msg) ?>
    </div>
    <?php if ($trackingUrl): ?>
        <p>
            <?= Yii::t('app', 'Puede seguir el estado de su solicitud en el siguiente enlace') ?>:
            <br>
            <?= Html::a(Html::encode($trackingUrl), $trackingUrl, ['target' => '_blank', 'rel' => 'noopener']) ?>
        </p>
    <?php endif; ?>
<?php else: ?>
    <div class="alert alert-danger">
        <strong><?= Yii::t('app', 'No se pudo verificar el email') ?>.</strong><br>
        <?= Html::encode($msg) ?>
    </div>
<?php endif; ?>