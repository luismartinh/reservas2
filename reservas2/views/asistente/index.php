<?php
use app\assets\AsistenteAsset;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\bootstrap5\Modal;

AsistenteAsset::register($this);
\app\assets\CalificacionAsset::register($this);
$this->title = 'Asistente de disponibilidad, tarifas y reservas';
$settings = ['audio' => ['habilitado' => $audioConfig['habilitado'], 'url' => Url::to(['asistente/transcribir']),
    'maxBytes' => \app\components\asistente\ConfiguracionAudio::MAX_BYTES,
    'maxSegundos' => \app\components\asistente\ConfiguracionAudio::MAX_SEGUNDOS], 'pasajeroDetalleUrl' => Url::to(['asistente/pasajero-detalle']), 'pasajeroUrl' => Url::to(['asistente/pasajero']), 'initial' => $initial, 'mensajeUrl' => Url::to(['asistente/mensaje']),
    'nuevaUrl' => Url::to(['asistente/nueva']), 'reservaUrl' => Url::to(['asistente/reservar']), 'pagoUrl' => Url::to(['asistente/pago']), 'eliminarPagoUrl' => Url::to(['asistente/eliminar-pago']), 'eliminarReservaUrl' => Url::to(['asistente/eliminar-reserva']), 'seguimientoUrl' => Url::to(['disponibilidad/seguimiento'])];
?>
<section id="asistente-chat" class="asistente-chat card my-3" data-config="<?= Html::encode(Json::encode($settings)) ?>">
    <div class="card-header d-flex align-items-center justify-content-between gap-2">
        <h1 class="h5 mb-0"><?= Html::encode($this->title) ?></h1>
        <div class="d-flex gap-2 flex-shrink-0">
            <button type="button" class="btn btn-outline-info btn-sm" id="asistente-ayuda-abrir" aria-controls="asistente-ayuda" aria-expanded="false">Ayuda</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="asistente-nueva">Nueva conversación</button>
        </div>
    </div>
    <div class="card-body pb-0">
        <div id="asistente-ayuda" class="alert alert-info alert-dismissible small" role="region" aria-label="Ayuda del asistente" hidden>
        <button type="button" class="btn-close" id="asistente-ayuda-cerrar" aria-label="Cerrar ayuda"></button>
        <p class="mb-0">Consultas de disponibilidad, tarifas y reservas por estadía o fecha de registro con <?= Html::encode($providerLabel) ?>.<br>
            Podés pedir los valores de las cabañas de la respuesta anterior.<br>
            No incluyas datos personales de clientes.<br>
            También podés pedir las tarifas válidas de una cabaña por su número, sin indicar fechas.<br>
            Para reservas encontradas, podés pedir sus titulares, datos generales, contacto o pagos;<br>
            la aplicacion muestra esos datos solo aquí.<br>
            Para crear una reserva, podés comenzar indicando el período completo;<br>
            se mostrarán disponibilidad y precios para elegir las cabañas.<br>
            Luego completá y confirmá el formulario privado, sin pago inicial.<br>
            Podés comenzar con «cargar un pago» para identificar la reserva y completar el formulario privado.<br>
            Cada resultado corresponde al momento en que se consultó.</p>
        </div>
        <div id="asistente-mensajes" class="asistente-mensajes" role="log" aria-live="polite" aria-label="Conversación"></div>
        <p id="asistente-aviso" class="small text-body-secondary" hidden>Se recortó el contexto anterior. Indicá las fechas completas si hace falta.</p>
        <p id="asistente-procesando" class="asistente-procesando small" role="status" aria-atomic="true" hidden>
            <span class="asistente-espera" aria-hidden="true"></span>
            <span>Consulta enviada. Esperando respuesta…</span>
        </p>
        <p id="asistente-error" class="alert alert-danger" role="alert" hidden></p>
    </div>
    <div class="card-footer">
        <?php $passengerAction = $initial['acciones']['pasajero'] ?? null; ?>
        <div id="asistente-pasajero-pendiente" class="asistente-acciones-pendientes"<?= $passengerAction ? '' : ' hidden' ?>>
            <strong id="asistente-pasajero-titulo">Edición de pasajero pendiente</strong>
            <?= Html::a(($passengerAction['operacion'] ?? '') === 'calificacion' ? 'Calificar y editar nota' : 'Editar datos del pasajero',
                $passengerAction ? ['asistente/pasajero', 'borrador' => $passengerAction['token']] : '#',
                ['id' => 'asistente-abrir-pasajero', 'class' => 'btn btn-primary', 'target' => '_blank', 'rel' => 'noopener noreferrer']) ?>
        </div>

        <?php $removingReservation = isset($initial['acciones']['eliminar_reserva']); $pendingPayment = $initial['acciones']['eliminar_reserva'] ?? ($initial['acciones']['pago'] ?? null); $removingPayment = ($pendingPayment['operacion'] ?? '') === 'eliminar'; ?>
        <div id="asistente-acciones-pendientes" class="asistente-acciones-pendientes" data-operacion="<?= $removingReservation ? 'eliminar_reserva' : ($removingPayment ? 'eliminar' : 'agregar') ?>"<?= $pendingPayment ? '' : ' hidden' ?>>
            <div>
                <strong id="asistente-pago-titulo"><?= $removingReservation ? 'Eliminación de reserva pendiente' : ($removingPayment ? 'Eliminación de pago pendiente' : 'Pago pendiente de confirmación') ?></strong>
                <span id="asistente-pago-descripcion"><?= $pendingPayment ? Html::encode('Reserva #' . $pendingPayment['reserva']) : '' ?></span>
            </div>
            <?= Html::a($removingReservation ? 'Revisar y eliminar reserva' : ($removingPayment ? 'Revisar y eliminar pago' : 'Completar y revisar pago'), $pendingPayment
                ? [$removingReservation ? 'asistente/eliminar-reserva' : ($removingPayment ? 'asistente/eliminar-pago' : 'asistente/pago'), 'borrador' => $pendingPayment['token']] : '#',
                ['id' => 'asistente-abrir-pago', 'class' => ($removingPayment || $removingReservation) ? 'btn btn-danger' : 'btn btn-primary', 'target' => '_blank',
                    'rel' => 'noopener noreferrer', 'aria-describedby' => 'asistente-pago-descripcion']) ?>
        </div>
        <?= Html::beginForm(['asistente/mensaje'], 'post', ['id' => 'asistente-form']) ?>
        <label for="asistente-mensaje" class="form-label">Tu consulta</label>
        <div class="d-flex align-items-end gap-2">
            <textarea id="asistente-mensaje" name="mensaje" class="form-control" rows="2" maxlength="2000" required
                placeholder="Escribe aqui tu requerimiento"></textarea>
            <button type="submit" class="btn btn-primary" id="asistente-enviar">Enviar</button>
        </div>
        <?= Html::endForm() ?>
        <?php if ($audioConfig['habilitado']): ?>
        <div id="asistente-audio" class="asistente-audio mt-2">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <button type="button" id="asistente-audio-grabar" class="btn btn-outline-secondary btn-sm" aria-label="Grabar consulta por audio" aria-describedby="asistente-audio-aviso">🎙 Grabar consulta</button>
                <button type="button" id="asistente-audio-detener" class="btn btn-danger btn-sm" hidden>Detener y transcribir</button>
                <button type="button" id="asistente-audio-cancelar" class="btn btn-outline-secondary btn-sm" hidden>Cancelar audio</button>
                <span id="asistente-audio-estado" class="small" role="status" aria-live="polite"></span>
            </div>
            <p id="asistente-audio-aviso" class="small text-body-secondary mb-0 mt-1">Hasta 60 segundos. El audio se envía a OpenAI para transcribir. Revisá el texto antes de enviarlo.</p>
            <p id="asistente-audio-error" class="alert alert-danger small mt-2 mb-0" role="alert" hidden></p>
            <div id="asistente-audio-revision" class="mt-2" hidden>
                <label for="asistente-audio-texto" class="form-label small">Transcripción: revisá y corregí lo que haga falta</label>
                <textarea id="asistente-audio-texto" class="form-control" rows="3" maxlength="2000"></textarea>
                <button type="button" id="asistente-audio-usar" class="btn btn-primary btn-sm mt-2">Pasar a consulta</button>
            </div>
        </div>
        <?php endif; ?>

    </div>
</section>
<?php Modal::begin(['id' => 'asistente-reserva-modal', 'title' => 'Reserva', 'size' => Modal::SIZE_EXTRA_LARGE]); ?>
<iframe id="asistente-reserva-frame" class="asistente-reserva-frame" src="about:blank" title="Seguimiento de la reserva" referrerpolicy="no-referrer"></iframe>
<?php Modal::end(); ?>

<?php Modal::begin(['id' => 'asistente-pago-modal', 'title' => 'Agregar pago', 'size' => Modal::SIZE_DEFAULT,
    'options' => ['tabindex' => false], 'dialogOptions' => ['class' => 'modal-dialog modal-dialog-centered modal-dialog-scrollable']]); ?>
<div id="asistente-pago-error" class="alert alert-danger" role="alert" hidden></div>
<div id="asistente-pago-contenido"></div>
<div id="asistente-pago-cargando" class="asistente-pago-cargando" role="status" hidden>
    <div class="text-center"><span class="spinner-border" aria-hidden="true"></span><p class="mt-2 mb-0">Procesando…</p></div>
</div>
<?php Modal::end(); ?>

<?php Modal::begin(['id' => 'asistente-pasajero-modal', 'title' => 'Editar pasajero', 'size' => Modal::SIZE_EXTRA_LARGE]); ?>
<iframe id="asistente-pasajero-frame" class="asistente-reserva-frame" src="about:blank" title="Formulario privado del pasajero" referrerpolicy="no-referrer"></iframe>
<?php Modal::end(); ?>

<?php Modal::begin(['id' => 'asistente-pasajero-detalle-modal', 'title' => 'Datos del pasajero', 'size' => Modal::SIZE_EXTRA_LARGE]); ?>
<iframe id="asistente-pasajero-detalle-frame" class="asistente-reserva-frame" src="about:blank" title="Datos completos del pasajero" referrerpolicy="no-referrer"></iframe>
<?php Modal::end(); ?>
