<?php
/**
 * Tarjeta reutilizable para mostrar URL de seguimiento.
 *
 * @var string $hash   Hash único para generar la URL de seguimiento
 */

use yii\bootstrap5\Html;

// Generar URL absoluta dentro del partial
$trackingUrl = Yii::$app->urlManager->createAbsoluteUrl([
    'disponibilidad/seguimiento',
    'hash' => $hash,
]);
?>

<div class="card border-secondary mb-3">
    <div class="card-body">

        <h6 class="card-title mb-2">
            <i class="bi bi-link-45deg me-1"></i>
            <?= Yii::t('app', 'URL de seguimiento') ?>
        </h6>

        <p class="mb-2 small">
            <?= Html::a(Html::encode($trackingUrl), $trackingUrl, [
                'target' => '_blank',
                'rel' => 'noopener'
            ]) ?>
        </p>

        <div class="d-flex flex-wrap gap-2 align-items-center">

            <?= Html::a(
                '<i class="bi bi-box-arrow-up-right me-1"></i>' . Yii::t('app', 'Abrir seguimiento'),
                $trackingUrl,
                [
                    'class' => 'btn btn-outline-primary btn-sm',
                    'target' => '_blank',
                    'rel' => 'noopener'
                ]
            ) ?>

            <button type="button" class="btn btn-outline-secondary btn-sm btn-copy-tracking-url"
                data-url="<?= Html::encode($trackingUrl) ?>">
                <i class="bi bi-clipboard-check me-1"></i>
                <?= Yii::t('app', 'Copiar enlace') ?>
            </button>

            <span class="text-success small ms-2 d-none copy-feedback">
                <i class="bi bi-check2-circle me-1"></i>
                <?= Yii::t('app', 'Copiado') ?>
            </span>
        </div>

    </div>
</div>

<?php
// JS para copiar al portapapeles con feedback visual
$js = <<<JS
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-copy-tracking-url');
    if (!btn) return;

    const url = btn.getAttribute('data-url');
    if (!url) return;

    const feedback = btn.parentElement.querySelector('.copy-feedback');

    // Función para mostrar mensaje "Copiado"
    const showFeedback = () => {
        if (!feedback) return;
        feedback.classList.remove('d-none');
        setTimeout(() => feedback.classList.add('d-none'), 2000);
    };

    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url)
            .then(showFeedback)
            .catch(() => {
                // Fallback
                const tempInput = document.createElement('input');
                tempInput.value = url;
                document.body.appendChild(tempInput);
                tempInput.select();
                document.execCommand('copy');
                document.body.removeChild(tempInput);
                showFeedback();
            });
    } else {
        // Fallback viejo
        const tempInput = document.createElement('input');
        tempInput.value = url;
        document.body.appendChild(tempInput);
        tempInput.select();
        document.execCommand('copy');
        document.body.removeChild(tempInput);
        showFeedback();
    }
});
JS;

$this->registerJs($js);
?>