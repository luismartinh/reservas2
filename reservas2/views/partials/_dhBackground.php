<?php
use yii\bootstrap5\Html;

/**
 * Fondo animado de la página
 * Reutilizable en cualquier vista: solo llamar $this->render('//partials/_dhBackground')
 */

$bgDir = Yii::getAlias('@webroot') . '/images/slider-background';
$bgUrl = Yii::getAlias('@web') . '/images/slider-background';

$bgImages = [];
if (is_dir($bgDir)) {
    $bgImages = glob($bgDir . '/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE) ?: [];
    sort($bgImages);
}
?>

<div class="dh-bg-rotator">
    <?php if (!empty($bgImages)): ?>
        <?php foreach ($bgImages as $path): ?>
            <?php $fileName = basename($path); ?>
            <img src="<?= Html::encode($bgUrl . '/' . $fileName) ?>" class="dh-bg-img">
        <?php endforeach; ?>
    <?php endif; ?>
</div>