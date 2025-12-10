<?php

use yii\bootstrap5\Html;
use yii\captcha\Captcha;
use yii\helpers\Url;
use yii\helpers\Json;
use yii\web\View;

/** @var yii\web\View $this */

// Mensajes ya escapados para JS
$msgErrorBuscar = Json::htmlEncode(Yii::t('app', 'Ocurrió un error al buscar la reserva.'));
$msgErrorServidor = Json::htmlEncode(Yii::t('app', 'Ocurrió un error al comunicarse con el servidor.'));
$csrfParam = Yii::$app->request->csrfParam;
$csrfToken = Yii::$app->request->getCsrfToken();
?>

<!-- 🔹 Modal MI RESERVA (partial reutilizable) -->
<div class="modal fade" id="miReservaModal" tabindex="-1" aria-labelledby="miReservaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered dh-modal">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title dh-heading" id="miReservaModalLabel">
                    <i class="bi bi-journal-check me-2"></i>
                    <?= Yii::t('app', 'Mi reserva') ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                    aria-label="<?= Yii::t('app', 'Cerrar') ?>"></button>
            </div>

            <div class="modal-body">
                <!-- ⚠️ mensaje de error dinámico -->
                <div class="alert alert-danger mi-reserva-error d-none" role="alert"></div>

                <p class="small text-muted mb-3">
                    <?= Yii::t('app', 'Ingresá el código de reserva y el email que usaste al hacer la solicitud.') ?>
                </p>

                <form id="mi-reserva-form" method="post" action="<?= Url::to(['disponibilidad/mi-reserva-buscar']) ?>"
                    novalidate>
                    <!-- CSRF como campo oculto (por si JS falla) -->
                    <?= Html::hiddenInput($csrfParam, $csrfToken) ?>

                    <div class="mb-3">
                        <label for="mi-reserva-codigo" class="form-label">
                            <?= Yii::t('app', 'Código de reserva') ?>
                        </label>
                        <input type="text" id="mi-reserva-codigo" name="codigo_reserva"
                            class="form-control text-uppercase" maxlength="7"
                            oninput="this.value = this.value.toUpperCase();" required pattern="[A-Z0-9]{7}"
                            placeholder="<?= Yii::t('app', 'Ej: ABC1234') ?>">
                    </div>

                    <div class="mb-3">
                        <label for="mi-reserva-email" class="form-label">
                            <?= Yii::t('app', 'Email') ?>
                        </label>
                        <input type="email" id="mi-reserva-email" name="email" class="form-control" required
                            placeholder="<?= Yii::t('app', 'tu-email@example.com') ?>">
                    </div>


                    <!-- 👇 BLOQUE CAPTCHA -->
                    <div class="row g-3 mt-2">
                        <div class="col-12">
                            <div class="card border-info">
                                <div class="card-body">
                                    <h5 class="card-title text-info mb-2">
                                        <i class="bi bi-shield-lock me-2"></i>
                                        <?= Yii::t('app', 'Verificación humana') ?>
                                    </h5>
                                    <p class="text-muted small mb-3">
                                        <?= Yii::t('app', 'Por favor copie los caracteres que ve en la imagen para confirmar que es una persona real y no un robot.') ?>
                                    </p>

                                    <?= Captcha::widget([
                                        'name' => 'verifyCode',
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
                                        'options' => [
                                            'class' => 'form-control',
                                            'placeholder' => Yii::t('app', 'Escribí el código de la imagen'),
                                        ],
                                    ]) ?>

                                    <small class="text-muted d-block mt-1">
                                        <?= Yii::t('app', 'Si el código no se lee bien, haga click en la imagen para generar uno nuevo.') ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- 👆 FIN BLOQUE CAPTCHA -->

                    <div class="d-flex justify-content-end gap-2 py-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <?= Yii::t('app', 'Cerrar') ?>
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search me-1"></i>
                            <?= Yii::t('app', 'Buscar reserva') ?>
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

<?php
// JS del modal (AJAX) – en POS_END, sin envolver en jQuery
$this->registerJs(<<<JS
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('mi-reserva-form');
    if (!form) {
        console.warn('mi-reserva-form no encontrado');
        return;
    }

    var modalEl   = document.getElementById('miReservaModal');
    var alertEl   = modalEl ? modalEl.querySelector('.mi-reserva-error') : null;
    var submitBtn = form.querySelector('button[type="submit"]');

    form.addEventListener('submit', function (e) {
        e.preventDefault(); // ✅ evitamos submit normal

        if (alertEl) {
            alertEl.classList.add('d-none');
            alertEl.textContent = '';
        }

        if (submitBtn) {
            submitBtn.disabled = true;
        }

        var formData = new FormData(form);

        // Por las dudas, garantizamos que el token CSRF esté presente
        formData.set('$csrfParam', '$csrfToken');

        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': '$csrfToken',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(function (response) {
            // Si el servidor responde 403/400 por CSRF, acá igual capturamos el JSON si lo hay
            return response.json().catch(function () {
                // Si no hay JSON, lanzamos un error para ir al catch
                throw new Error('Respuesta no válida');
            });
        })
        .then(function (data) {
            if (submitBtn) submitBtn.disabled = false;

            if (data && data.success && data.redirectUrl) {
                window.location.href = data.redirectUrl;
            } else if (data && data.message && alertEl) {
                alertEl.textContent = data.message;
                alertEl.classList.remove('d-none');
            } else if (alertEl) {
                alertEl.textContent = $msgErrorBuscar;
                alertEl.classList.remove('d-none');
            }
        })
        .catch(function (err) {
            if (submitBtn) submitBtn.disabled = false;
            console.error('Error al buscar la reserva:', err);
            if (alertEl) {
                alertEl.textContent = $msgErrorServidor;
                alertEl.classList.remove('d-none');
            }
        });
    });
});
JS, View::POS_END);
?>