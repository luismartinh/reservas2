<?php
use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;
use yii\captcha\Captcha;

/** @var \app\models\RequestReserva $reservaReq */
/** @var \app\models\Cabana[] $cabanas */
/** @var array $totales */
/** @var \yii\base\DynamicModel $form */
/** @var array $banco */
/** @var DateTime $desdeDate */
/** @var DateTime $hastaDate */
/** @var int $dias */
/** @var DateTime $fechaIngreso */
/** @var DateTime $fechaEgreso */
/** @var int $paxAcumulado */
/** @var float $totalGeneral */
/** @var int                           $confirmar_pago_expira_hr */
/** @var Datetime                      $fecha_confirmar_pago_expira */


$this->title = Yii::t('app', 'Registrar Pago');
?>

<h2 class="mb-4 text-center"><?= Yii::t('app', 'Registrar Pago de Reserva o Seña') ?></h2>

<!-- Estado -->
<div class="alert alert-info">
    <strong><?= Yii::t('app', 'Estado') ?>:</strong>
    <?= Html::encode($reservaReq->estado->descr ?? Yii::t('app', 'Pendiente')) ?>
</div>

<?php if (isset($fecha_confirmar_pago_expira) && $fecha_confirmar_pago_expira instanceof DateTime): ?>
    <!-- ⚠️ Vencimiento para realizar el pago -->
    <div id="alert-pago-vencimiento" class="alert alert-warning"
        data-expira="<?= Html::encode($fecha_confirmar_pago_expira->format('c')) ?>">
        <strong><?= Yii::t('app', 'Plazo para realizar el pago de la reserva') ?>:</strong>
        <?= Yii::t('app', 'Hasta el ') ?>
        <?= $fecha_confirmar_pago_expira->format('d/m/Y H:i') ?>
        (<?= (int) $confirmar_pago_expira_hr ?> hs.)
        <br>
        <?= Yii::t('app', 'Tiempo restante para realizar el pago') ?>:
        <span id="pago-countdown" class="fw-bold"></span>
        <br>
        <?= Yii::t('app', 'Si no se registra el pago dentro de este plazo, la solicitud puede ser cancelada automáticamente.') ?>
    </div>
<?php endif; ?>

<!-- 🚨 Alerta cuando el plazo de pago ya está vencido (se muestra solo por JS) -->
<div id="alert-pago-vencido" class="alert alert-danger d-none">
    <strong><?= Yii::t('app', 'Plazo de pago vencido') ?>:</strong>
    <?= Yii::t('app', 'El tiempo para registrar el pago ha expirado. La solicitud puede haber sido cancelada o quedar sujeta a disponibilidad.') ?>
</div>


<!-- (warning) -->
<div class="alert alert-warning d-flex align-items-center" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-3"></i>
    <div>
        <?= Yii::t('app', 'Por favor, verifique los datos antes de continuar:') ?>
    </div>
</div>


<!-- Resumen -->
<?= $this->render('_resumen_solicitud', [
    'cabanas' => $cabanas,
    'desde' => $desdeDate->format('d-m-Y'),
    'hasta' => $hastaDate->format('d-m-Y'),
    'dias' => $dias,
    'fechaIngreso' => $fechaIngreso,
    'fechaEgreso' => $fechaEgreso,
    'paxAcumulado' => $paxAcumulado,
    'totalGeneral' => $totalGeneral,
]) ?>
<hr class="my-4">


<h2 class="mb-4 text-center"><?= Yii::t('app', 'Debe realizar el depósito / transferencia a: ') ?></h2>
<!-- Datos de la cuenta -->
<div class="card border-secondary mb-4">
    <div class="card-body">
        <h5 class="card-title"><?= Yii::t('app', 'datos de la cuenta') ?></h5>
        <div class="row">
            <div class="col-md-6">
                <ul class="list-unstyled mb-0">
                    <li><strong><?= Yii::t('app', 'Banco') ?>:</strong> <?= Html::encode($banco['banco'] ?? '') ?></li>
                    <li><strong><?= Yii::t('app', 'Titular') ?>:</strong> <?= Html::encode($banco['titular'] ?? '') ?>
                    </li>
                    <li><strong><?= Yii::t('app', 'CUIT') ?>:</strong> <?= Html::encode($banco['cuit'] ?? '') ?></li>
                </ul>
            </div>
            <div class="col-md-6">
                <ul class="list-unstyled mb-0">
                    <li><strong>CBU:</strong> <?= Html::encode($banco['cbu'] ?? '') ?></li>
                    <li><strong><?= Yii::t('app', 'Alias') ?>:</strong> <?= Html::encode($banco['alias'] ?? '') ?></li>
                    <li><strong><?= Yii::t('app', 'Cuenta') ?>:</strong>
                        <?= Html::encode(($banco['tipo'] ?? '') . ' ' . ($banco['nro'] ?? '')) ?></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<h3 class="mb-4 text-center">
    <?= Yii::t('app', 'Luego, complete el formulario para enviar los datos del pago de la seña/reserva:') ?>
</h3>
<!-- Form de registro de pago -->
<?php $af = ActiveForm::begin([
    'action' => ['registrar-pago', 'hash' => $reservaReq->hash],
    'method' => 'post',
    'options' => ['enctype' => 'multipart/form-data']
]); ?>



<div class="row g-3">
    <div class="col-md-6">
        <?= $af->field($form, 'denominacion')
            ->textInput([
                'maxlength' => 100,
                'required' => true,
                'readonly' => true, // 🔒 solo lectura
                'value' => $form->denominacion,
            ])->label(Yii::t('app', 'Denominación')) ?>
    </div>
    <div class="col-md-6">
        <?= $af->field($form, 'documento')
            ->textInput(['maxlength' => 45, 'required' => true])
            ->label(Yii::t('app', 'Documento')) ?>
    </div>
    <div class="col-md-6">
        <?= $af->field($form, 'email')
            ->input('email', [
                'maxlength' => 45,
                'required' => true,
                'readonly' => true, // 🔒 solo lectura
                'value' => $form->email,
            ])->label(Yii::t('app', 'Email')) ?>
    </div>
    <div class="col-md-6">
        <?= $af->field($form, 'telefono')
            ->textInput(['maxlength' => 45, 'required' => true])
            ->label(Yii::t('app', 'Teléfono')) ?>
    </div>
    <div class="col-md-12">
        <?= $af->field($form, 'domicilio')
            ->textInput(['maxlength' => 100])
            ->label(Yii::t('app', 'Domicilio')) ?>
    </div>
</div>

<div class="row g-3 mt-2">
    <div class="col-md-6">
        <?php
        $minMonto = round($totalGeneral * 0.10, 2);
        $maxMonto = round($totalGeneral, 2);
        ?>
        <?= $af->field($form, 'monto')->input('number', [
            'step' => '0.01',
            'min' => number_format($minMonto, 2, '.', ''),
            'max' => number_format($maxMonto, 2, '.', ''),
            'required' => true
        ])->hint(
                Yii::t('app', 'Mínimo {min} / Máximo {max}', [
                    'min' => '$ ' . number_format($minMonto, 2, ',', '.'),
                    'max' => '$ ' . number_format($maxMonto, 2, ',', '.'),
                ])
            )->label(Yii::t('app', 'Monto depositado')) ?>
    </div>
    <div class="col-md-6">
        <?= $af->field($form, 'comprobante')
            ->fileInput(['accept' => '.png,.jpg,.jpeg,.pdf'])
            ->label(Yii::t('app', 'Adjuntar comprobante (png/jpg/pdf, máx 5MB)')) ?>
    </div>
</div>

<!-- 👇 NUEVO BLOQUE CAPTCHA -->
<div class="row g-3 mt-3">
    <div class="col-md-6 offset-md-3">
        <div class="card border-info">
            <div class="card-body">
                <h5 class="card-title text-info mb-2">
                    <i class="bi bi-shield-lock me-2"></i>
                    <?= Yii::t('app', 'Verificación humana') ?>
                </h5>
                <p class="text-muted small mb-3">
                    <?= Yii::t('app', 'Por favor copie los caracteres que ve en la imagen para confirmar que es una persona real y no un robot.') ?>
                </p>

                <?= $af->field($form, 'verifyCode')
                    ->widget(Captcha::class, [
                        'captchaAction' => 'disponibilidad/captcha',
                        'imageOptions' => [
                            'style' => 'cursor:pointer; border-radius:4px;',
                            'title' => Yii::t('app', 'Click para recargar la imagen'),
                        ],
                        'template' =>
                            '<div class="row align-items-center">' .
                            '<div class="col-5 mb-2 mb-sm-0">{image}</div>' .
                            '<div class="col-7">{input}</div>' .
                            '</div>',
                    ])
                    ->label(false)
                    ->hint(Yii::t('app', 'Si el código no se lee bien, haga click en la imagen para generar uno nuevo.')) ?>
            </div>
        </div>
    </div>
</div>


<div class="text-center mt-4">
    <?= Html::submitButton('<i class="bi bi-cash-coin"></i> ' . Yii::t('app', 'Registrar Pago'), [
        'class' => 'btn btn-success btn-lg px-5'
    ]) ?>
</div>

<?php ActiveForm::end(); ?>

<!-- (warning) -->
<div class="alert alert-warning d-flex align-items-center mt-4" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-3"></i>
    <div>
        <?= Yii::t('app', 'Cuando verifiquemos el pago, se confirmará la reserva:') ?>
    </div>
</div>

<!-- Cabañas -->
<h4 class="mt-3 mb-2"><?= Yii::t('app', 'Cabañas seleccionadas') ?></h4>
<?php foreach ($cabanas as $cabana): ?>
    <?= $this->render('_cabana_card', [
        'model' => $cabana,
        'totales' => $totales,
        'desde' => $desdeDate->format('d-m-Y'),
        'hasta' => $hastaDate->format('d-m-Y'),
        'mostrarSwitch' => false
    ]) ?>
<?php endforeach; ?>


<?php
$js = <<<JS
(function() {

    function formatDiff(ms) {
        if (ms <= 0) {
            return '00:00:00';
        }
        var totalSeconds = Math.floor(ms / 1000);
        var hours = Math.floor(totalSeconds / 3600);
        var minutes = Math.floor((totalSeconds % 3600) / 60);
        var seconds = totalSeconds % 60;

        function pad(n) { return n < 10 ? '0' + n : n; }
        return pad(hours) + ':' + pad(minutes) + ':' + pad(seconds);
    }

    /**
     * Inicializa un countdown genérico.
     * opts = {
     *   alertId: id del alert de "en curso" (tiene data-expira),
     *   countdownId: span donde se muestra el tiempo,
     *   expiredAlertId: id del alert que se muestra al vencer,
     *   hideOnExpireIds: [ids de elementos que se ocultan al vencer]
     * }
     */
    function setupCountdown(opts) {
        var alertEl = document.getElementById(opts.alertId);
        if (!alertEl) {
            return;
        }

        var countdownEl = document.getElementById(opts.countdownId);
        var expiredAlertEl = opts.expiredAlertId
            ? document.getElementById(opts.expiredAlertId)
            : null;

        var hideOnExpireEls = [];
        if (Array.isArray(opts.hideOnExpireIds)) {
            hideOnExpireEls = opts.hideOnExpireIds
                .map(function(id) { return document.getElementById(id); })
                .filter(function(el) { return el !== null; });
        }

        var expiraStr = alertEl.getAttribute('data-expira');
        if (!expiraStr) {
            return;
        }
        var expiraDate = new Date(expiraStr); // ISO 8601

        function tick() {
            var now = new Date();
            var diff = expiraDate - now;

            if (diff <= 0) {
                if (countdownEl) {
                    countdownEl.textContent = '00:00:00';
                }

                // Ocultamos el alert de "vencimiento en curso"
                alertEl.classList.add('d-none');

                // Mostramos el alert de vencido
                if (expiredAlertEl) {
                    expiredAlertEl.classList.remove('d-none');
                }

                // Ocultamos elementos extra (por ejemplo, el botón de pago)
                hideOnExpireEls.forEach(function(el) {
                    el.classList.add('d-none');
                });

                clearInterval(timerId);
                return;
            }

            if (countdownEl) {
                countdownEl.textContent = formatDiff(diff);
            }
        }

        // Primera ejecución
        tick();
        // Luego cada segundo
        var timerId = setInterval(tick, 1000);
    }

    // ⏳ 1) Countdown para confirmar email
    setupCountdown({
        alertId: 'alert-vencimiento',
        countdownId: 'vencimiento-countdown',
        expiredAlertId: 'alert-vencida',
        hideOnExpireIds: [] // acá no hay botón que ocultar
    });

    // ⏳ 2) Countdown para realizar el pago
    setupCountdown({
        alertId: 'alert-pago-vencimiento',
        countdownId: 'pago-countdown',
        expiredAlertId: 'alert-pago-vencido',
        hideOnExpireIds: ['pago-boton-wrapper'] // ocultamos el botón de pago al vencer
    });

})();
JS;

$this->registerJs($js);
?>