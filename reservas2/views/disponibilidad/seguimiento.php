<?php
use yii\bootstrap5\Html;
use yii\bootstrap5\Modal;
use yii\helpers\Url;

/** @var \yii\web\View                 $this */
/** @var \app\models\RequestReserva    $reserva */
/** @var \app\models\Cabana[]          $cabanas */
/** @var array                         $totales */
/** @var DateTime                      $desdeDate */
/** @var DateTime                      $hastaDate */
/** @var int                           $dias */
/** @var DateTime                      $fechaIngreso */
/** @var DateTime                      $fechaEgreso */
/** @var int                           $paxAcumulado */
/** @var float                         $totalGeneral */
/** @var string                        $trackingUrl */
/** @var int                           $email_token_expira_hr */
/** @var Datetime                      $fecha_expira */
/** @var int                           $confirmar_pago_expira_hr */
/** @var Datetime                      $fecha_confirmar_pago_expira */
/** @var boolean                      $showChatButton */

$this->registerCssFile('@web/css/cabana.css', [
    'depends' => [\yii\bootstrap5\BootstrapAsset::class]
]);



$this->title = Yii::t('app', 'Seguimiento de Solicitud');

$pago_pendiente = (float) $reserva->pagado < (float) $reserva->total;

?>

<div class="site-index container py-5 py-lg-5">

    <?= $this->render('//partials/_dhBackground') ?>

    <section class="dh-hero mb-5">


        <h2 class="mb-4 text-center"><?= Yii::t('app', 'Seguimiento de Solicitud') ?></h2>

        <!-- Datos principales -->
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="card border-success">
                    <div class="card-body">
                        <div class="text-muted small"><?= Yii::t('app', 'Fecha de creación') ?></div>
                        <div class="fw-semibold">
                            <?= Yii::$app->formatter->asDatetime($reserva->fecha, 'php:d/m/Y H:i') ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-info">
                    <div class="card-body">
                        <div class="text-muted small"><?= Yii::t('app', 'Denominación') ?></div>
                        <div class="fw-semibold"><?= Html::encode($reserva->denominacion) ?></div>
                        <div class="text-muted small mt-1"><?= Yii::t('app', 'Email') ?></div>
                        <div class="fw-semibold"><?= Html::encode($reserva->email) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <!-- Estado (warning) -->
                <div class="alert alert-warning mb-0 h-100 d-flex flex-column justify-content-center">
                    <div>
                        <strong><?= Yii::t('app', 'Estado') ?>:</strong>
                        <?= Html::encode($reserva->estado->descr ?? Yii::t('app', 'Pendiente')) ?>
                    </div>
                </div>
            </div>


        </div>


        <?php
        $slug = $reserva->estado->slug ?? null;


        if ($slug === 'pendiente-email-contestado' || $slug === 'pendiente-email-verificado'): ?>

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

            <!-- 🚨 Alerta cuando el plazo de pago ya está vencido (se muestra solo por JS) -->
            <div id="alert-pago-vencido" class="alert alert-danger d-none">
                <strong><?= Yii::t('app', 'Plazo de pago vencido') ?>:</strong>
                <?= Yii::t('app', 'El tiempo para registrar el pago ha expirado. La solicitud puede haber sido cancelada o quedar sujeta a disponibilidad.') ?>
            </div>

            <!-- Contenedor del botón de pago (se ocultará cuando venza el plazo) -->
            <div id="pago-boton-wrapper" class="text-start my-4">
                <?= Html::a(
                    '<i class="bi bi-cash-coin"></i> ' . Yii::t('app', 'Hacer el pago de reserva'),
                    ['disponibilidad/registrar-pago', 'hash' => $reserva->hash],
                    ['class' => 'btn btn-success btn-lg']
                ) ?>
            </div>
        <?php endif; ?>


        <?php if ($slug === 'pendiente-email-verificar'): ?>
            <!-- ⚠️ Estado (warning) -->
            <div id="alert-vencimiento" class="alert alert-warning"
                data-expira="<?= Html::encode($fecha_expira->format('c')) ?>">
                <strong><?= Yii::t('app', 'Vencimiento de esta solicitud') ?>:</strong>
                <?= Yii::t('app', 'El ') ?>     <?= $fecha_expira->format('d/m/Y H:i') ?> (<?= $email_token_expira_hr ?> hs.)
                <br>
                <?= Yii::t('app', 'Tiempo restante') ?>:
                <span id="vencimiento-countdown" class="fw-bold"></span>
                <br>
                <?= Yii::t('app', 'Una vez vencida, si el email no fue confirmado, se elimina automáticamente') ?>
            </div>

            <!-- 🚨 Alerta cuando ya está vencida (se muestra sólo por JS) -->
            <div id="alert-vencida" class="alert alert-danger d-none">
                <strong><?= Yii::t('app', 'Solicitud vencida') ?>:</strong>
                <?= Yii::t('app', 'El tiempo para confirmar el email ha expirado. Si la solicitud aún no fue procesada, puede haber sido eliminada automáticamente.') ?>
            </div>
        <?php endif; ?>

        <?php if ($slug !== 'pendiente-email-verificar' && $showChatButton): ?>
            <div class="my-3">
                <?= Html::button(
                    '<i class="bi bi-chat-dots"></i> ' . Yii::t('app', 'Ver / realizar consulta'),
                    [
                        'class' => 'btn btn-primary',
                        'id' => 'btn-consulta-chat',
                        'data-url' => Url::to(['disponibilidad/consulta-chat', 'hash' => $reserva->hash]),
                    ]
                ) ?>
            </div>
        <?php endif; ?>


        <?php if ($slug === 'confirmado-verificar-pago'): ?>

            <!-- (warning) -->
            <div class="alert alert-warning d-flex align-items-center mt-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-3"></i>
                <div>
                    <?= Yii::t('app', 'Cuando verifiquemos el pago, se confirmará la reserva:') ?>
                </div>
            </div>
        <?php endif; ?>


        <?php if ($slug === 'confirmado'): ?>
            <div class="alert alert-success mt-3" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <strong><?= Yii::t('app', 'Su reserva está confirmada.') ?></strong>
            </div>
        <?php endif; ?>

        <?php if ($slug === 'confirmado-verificar-pago' || $slug === 'confirmado'): ?>
            <!-- resumen de pago -->
            <div class="card my-4 shadow-sm border-0">

                <!-- Cabecera tipo comprobante Comprobante de pago / Reserva-->
                <div
                    class="card-header bg-<?= $pago_pendiente ? 'danger' : 'success' ?> text-white d-flex justify-content-between align-items-center">
                    <div class="fw-bold">
                        <?= Yii::t('app', 'Estado del Pago de la Reserva ') . Yii::t('app', ($pago_pendiente ? 'Pendiente' : 'Confirmado')) ?>
                    </div>

                    <?php if ($reserva->reserva): ?>
                        <div class="text-end small">
                            <div class="fw-semibold">
                                <?= Yii::t('app', 'Reserva #') ?>         <?= (int) $reserva->reserva->id ?>
                            </div>
                            <div>
                                <?= Yii::$app->formatter->asDatetime($reserva->reserva->fecha ?? $reserva->fecha, 'php:d/m/Y H:i') ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-end small">
                            <div class="fw-semibold">
                                <?= Yii::t('app', 'Solicitud #') ?>         <?= (int) $reserva->id ?>
                            </div>
                            <div>
                                <?= Yii::$app->formatter->asDatetime($reserva->fecha, 'php:d/m/Y H:i') ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card-body">

                    <!-- Datos de la reserva (tipo "factura") -->
                    <div class="mb-3">
                        <div class="row">
                            <div class="col-6 text-muted small">
                                <?= Yii::t('app', 'Denominación') ?>
                            </div>
                            <div class="col-6 text-end fw-semibold">
                                <?= Html::encode($reserva->denominacion) ?>
                            </div>
                        </div>

                        <div class="row border-top pt-1 mt-1">
                            <div class="col-6 text-muted small">
                                <?= Yii::t('app', 'Email') ?>
                            </div>
                            <div class="col-6 text-end fw-semibold">
                                <?= Html::encode($reserva->email) ?>
                            </div>
                        </div>

                        <?php if ($reserva->reserva): ?>
                            <div class="row border-top pt-1 mt-1">
                                <div class="col-6 text-muted small">
                                    <?= Yii::t('app', 'Desde') ?>
                                </div>
                                <div class="col-6 text-end fw-semibold">
                                    <?= Html::encode(
                                        Yii::$app->formatter->asDate($reserva->reserva->desde, 'php:d/m/Y')
                                    ) ?>
                                </div>
                            </div>

                            <div class="row border-top pt-1 mt-1">
                                <div class="col-6 text-muted small">
                                    <?= Yii::t('app', 'Hasta') ?>
                                </div>
                                <div class="col-6 text-end fw-semibold">
                                    <?= Html::encode(
                                        Yii::$app->formatter->asDate($reserva->reserva->hasta, 'php:d/m/Y')
                                    ) ?>
                                </div>
                            </div>

                            <div class="row border-top pt-1 mt-1">
                                <div class="col-6 text-muted small">
                                    <?= Yii::t('app', 'Locador') ?>
                                </div>
                                <div class="col-6 text-end fw-semibold">
                                    <?= Html::encode($reserva->reserva->locador->denominacion ?? '-') ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Línea destacada de pago (tipo "TOTAL") -->
                    <div class="border-top pt-3 mt-2">
                        <div class="row align-items-center">
                            <div class="col-6 text-muted text-uppercase fw-bold">
                                <?= Yii::t('app', 'Total') ?>
                            </div>
                            <div class="col-6 text-end">
                                <span class="fs-4 fw-bold text-black">
                                    <?= '$ ' . number_format((float) $reserva->total, 2, ',', '.') ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Línea destacada de pago (tipo "TOTAL") -->
                    <div class="border-top pt-3 mt-2">
                        <div class="row align-items-center">
                            <div class="col-6 text-muted text-uppercase fw-bold">
                                <?= Yii::t('app', 'Pagado') ?>
                            </div>
                            <div class="col-6 text-end">
                                <span class="fs-5 fw-bold text-success">
                                    <?= '$ ' . number_format((float) $reserva->pagado, 2, ',', '.') ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <?php if ($pago_pendiente): ?>
                        <div class="border-top pt-3 mt-2">
                            <div class="row align-items-center">
                                <div class="col-6 text-muted text-uppercase fw-bold">
                                    <?= Yii::t('app', 'DEBE') ?>
                                </div>
                                <div class="col-6 text-end">
                                    <span class="fs-5 fw-bold text-danger">
                                        <?= '$ ' . number_format((float) $reserva->total - (float) $reserva->pagado, 2, ',', '.') ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            </div>

        <?php endif; ?>


        <?php if ($slug === 'rechazado'): ?>
            <div class="alert alert-danger mt-3" role="alert">
                <i class="bi bi-x-circle-fill me-2"></i>
                <strong><?= Yii::t('app', 'Su reserva fue rechazada.') ?></strong>
            </div>
        <?php endif; ?>



        <!-- Cabañas seleccionadas -->
        <h4><?= count($cabanas) > 1 ? Yii::t('app', 'Cabañas seleccionadas') . ' (' . count($cabanas) . ')' :
            Yii::t('app', 'Cabañas seleccionadas') . ' (' . count($cabanas) . ')' ?> </h4>

        <?php foreach ($cabanas as $cabana): ?>
            <?= $this->render('_cabana_card', [
                'model' => $cabana,
                'totales' => $totales,
                'desde' => $desdeDate->format('d-m-Y'), // para el pie de la card
                'hasta' => $hastaDate->format('d-m-Y'),
                'mostrarSwitch' => false,                        // no mostrar switch en seguimiento
            ]) ?>
        <?php endforeach; ?>

        <!-- Resumen reutilizable -->
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


        <?php
        $regPagos = is_array($reserva->registro_pagos) ? $reserva->registro_pagos : [];
        ?>

        <?php if (!empty($regPagos)): ?>
            <h4 class="mt-4 mb-3"><?= Yii::t('app', 'Pagos registrados') ?></h4>

            <div class="card mb-4">
                <div class="card-body">
                    <?php foreach ($regPagos as $idx => $pago): ?>
                        <?php
                        $fechaPago = $pago['fecha'] ?? null;
                        $montoPago = (float) ($pago['monto'] ?? 0);
                        $archivo = $pago['archivo'] ?? null;

                        $fechaFmt = $fechaPago
                            ? (new \DateTime($fechaPago))->format('d/m/Y H:i')
                            : '-';

                        $urlComprobante = null;
                        $esImagen = false;
                        $esPdf = false;

                        if (!empty($archivo) && is_string($archivo)) {
                            $urlComprobante = Yii::$app->urlManager->createUrl([
                                'disponibilidad/ver-comprobante',
                                'hash' => $reserva->hash,
                                'k' => $idx,
                            ]);
                            $ext = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));
                            $esImagen = in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'], true);
                            $esPdf = ($ext === 'pdf');
                        }
                        ?>
                        <div class="border-bottom py-2">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="text-muted small">
                                        <?= Yii::t('app', 'Fecha') ?>:
                                        <span class="fw-semibold"><?= Html::encode($fechaFmt) ?></span>
                                    </div>
                                    <div class="text-muted small">
                                        <?= Yii::t('app', 'Monto') ?>:
                                        <span class="fw-semibold">
                                            <?= '$ ' . number_format($montoPago, 2, ',', '.') ?>
                                        </span>
                                    </div>

                                    <?php if (!empty($pago['nota'])): ?>
                                        <div class="text-muted small mt-1">
                                            <?= Yii::t('app', 'Notas') ?>:
                                            <span class="fw-semibold">
                                                <?= nl2br(Html::encode($pago['nota'])) ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="text-end">
                                    <?php if ($urlComprobante): ?>
                                        <?php if ($esImagen): ?>
                                            <!-- Mostrar imagen embebida -->
                                            <a href="<?= Html::encode($urlComprobante) ?>" target="_blank" class="d-block mb-1">
                                                <img src="<?= Html::encode($urlComprobante) ?>"
                                                    alt="<?= Yii::t('app', 'Comprobante de pago') ?>" class="img-thumbnail"
                                                    style="max-width: 150px; max-height: 150px;">
                                            </a>
                                            <small class="text-muted d-block">
                                                <?= Yii::t('app', 'Click para ver en tamaño completo.') ?>
                                            </small>
                                        <?php elseif ($esPdf): ?>
                                            <!-- Abrir PDF en ventana emergente -->
                                            <a href="javascript:void(0);" onclick="window.open('<?= Html::encode($urlComprobante) ?>',
                           'comprobante_pdf',
                           'width=900,height=700,scrollbars=yes,resizable=yes'); return false;"
                                                class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-file-earmark-pdf"></i>
                                                <?= Yii::t('app', 'Ver comprobante (PDF)') ?>
                                            </a>
                                        <?php else: ?>
                                            <!-- Otro tipo de archivo: link de descarga / vista -->
                                            <a href="<?= Html::encode($urlComprobante) ?>" target="_blank"
                                                class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-file-earmark"></i>
                                                <?= Yii::t('app', 'Ver comprobante') ?>
                                            </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted small">
                                            <?= Yii::t('app', 'Sin archivo adjunto.') ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>


        <?php if (!empty($reserva->obs)): ?>
            <h4 class="mt-4 mb-3"><?= Yii::t('app', 'Notas / Observaciones') ?></h4>

            <div class="card mb-4">
                <div class="card-body">
                    <div class="text-muted small mb-1">
                        <?= Yii::t('app', 'Estas notas fueron registradas al momento de crear o gestionar la solicitud.') ?>
                    </div>
                    <div class="fw-semibold">
                        <?= nl2br(Html::encode($reserva->obs)) ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </section>
</div>

<?php
Modal::begin([
    'id' => 'modal-chat',
    'title' => Yii::t('app', 'Consultas y respuestas'),
    'size' => Modal::SIZE_LARGE,
]);
?>
<div id="modal-chat-content"></div>
<?php Modal::end(); ?>


<?php
$jsChat = <<<JS
(function() {

    // Abrir modal de chat desde el tracking público
    $(document).on('click', '#btn-consulta-chat', function(e) {
        e.preventDefault();
        var url = $(this).data('url');

        $.get(url, function(resp) {
            if (resp && resp.success) {
                $('#modal-chat-content').html(resp.html);
                var modal = new bootstrap.Modal(document.getElementById('modal-chat'));
                modal.show();
            } else {
                alert(resp && resp.message ? resp.message : 'Error');
            }
        }).fail(function() {
            alert('Error de comunicación con el servidor.');
        });
    });

    // Enviar el formulario del chat (consultas del cliente) por AJAX
    $(document).off('submit', '#form-chat-consultas').on('submit', '#form-chat-consultas', function(e) {
        e.preventDefault();
        var form = $(this);
        var url  = form.attr('action');

        $.post(url, form.serialize(), function(resp) {
            if (resp && resp.success && resp.html) {
                // Reemplazamos todo el contenido del chat
                $('#modal-chat-content').html(resp.html);
            } else if (resp && resp.html) {
                $('#modal-chat-content').html(resp.html);
            } else {
                alert(resp && resp.message ? resp.message : 'Error');
            }
        }).fail(function() {
            alert('Error de comunicación con el servidor.');
        });
    });

})();
JS;

$this->registerJs($jsChat);
?>


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