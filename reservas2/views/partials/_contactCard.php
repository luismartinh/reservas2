<?php

use yii\bootstrap5\Html;

/** @var yii\web\View $this */

// Leemos desde params
$contact = Yii::$app->params['contact'] ?? [];

$name = $contact['name'] ?? 'Cabañas Dina Huapi';
$addressLine1 = $contact['address_line1'] ?? 'Los Cohiues 375';
$addressExtra = $contact['address_extra'] ?? '(Cohiues y Av. Patagonia Argentina)';
$locationLine = $contact['location_line'] ?? '(CP 8402) Dina Huapi, Río Negro – Argentina';
$whNumberHuman = $contact['whatsapp_number_human'] ?? '2944 557 891';
$whNumberLink = $contact['whatsapp_number_link'] ?? '5492944557891';
$whContactName = $contact['whatsapp_contact_name'] ?? 'Claudia';
$extraText = $contact['extra_text'] ?? Yii::t(
    'app',
    'Estamos a metros de la costa del lago Nahuel Huapi y a pocos minutos de Bariloche.'
);
?>

<div class="dh-glass-box p-4 h-100">
    <h2 class="dh-heading h4 mb-3">
        <?= Html::encode($name) ?>
    </h2>

    <ul class="list-unstyled mb-3">
        <li class="mb-3">
            <i class="bi bi-geo-alt-fill me-2"></i>
            <strong><?= Yii::t('app', 'Dirección') ?>:</strong><br>
            <?= Html::encode($addressLine1) ?><br>
            <?php if (!empty($addressExtra)): ?>
                <span class="text-muted">
                    <?= Html::encode($addressExtra) ?>
                </span>
            <?php endif; ?>
        </li>

        <li class="mb-3">
            <i class="bi bi-pin-map-fill me-2"></i>
            <?= Html::encode($locationLine) ?>
        </li>

        <li class="mb-3">
            <i class="bi bi-telephone-fill me-2"></i>
            <strong><?= Yii::t('app', 'Tel/WhatsApp') ?>:</strong>
            <?= Html::a(
                Html::encode($whNumberHuman),
                'https://wa.me/' . rawurlencode($whNumberLink),
                [
                    'target' => '_blank',
                    'rel' => 'noopener',
                ]
            ) ?>
            <?php if (!empty($whContactName)): ?>
                <span class="text-muted">(<?= Html::encode($whContactName) ?>)</span>
            <?php endif; ?>
        </li>

        <!-- 📧 Email -->
        <li class="mb-3">
            <i class="bi bi-envelope-fill me-2"></i>
            <strong><?= Yii::t('app', 'Email') ?>:</strong>
            <?= Html::a(
                Html::encode($contact['email']),
                'mailto:' . Html::encode($contact['email'])
            ) ?>
        </li>        
    </ul>

    <p class="small text-muted mb-0">
        <?= Html::encode($extraText) ?>
    </p>
</div>