'use strict';
// Small DOM/AJAX doubles: exercise the actual script without browser packages or HTTP.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const elements = new Map();
const requests = [];
const paymentRequests = [];
const all = [];
class Element {
    constructor(tag) {
        this.tag = tag;
        this.children = [];
        this.props = {};
        this.attributes = {};
        this.events = {};
        this.value = '';
        this.content = '';
        this.classes = [];
        this[0] = { scrollHeight: 100 };
        all.push(this);
    }
    addClass(value) { this.classes.push(...value.split(' ')); return this; }
    toggleClass() { return this; }
    text(value) { this.content = value; return this; }
    prop(name, value) { this.props[name] = value; return this; }
    attr(name, value) { this.attributes[name] = value; return this; }
    append(value) { this.children.push(value); return this; }
    appendTo(target) { target.append(this); return this; }
    empty() { this.children = []; return this; }
    scrollTop() { return this; }
    val(value) { if (value === undefined) { return this.value; } this.value = value; return this; }
    on(name, selector, callback) { this.events[name] = callback || selector; return this; }
    html(value) { this.content = value; return this; }
}
function $(selector) {
    if (selector.startsWith('<')) { return new Element(selector); }
    if (!elements.has(selector)) { elements.set(selector, new Element(selector)); }
    return elements.get(selector);
}
$.Deferred = function () {
    let state = null;
    let values = [];
    const callbacks = {done: [], fail: [], always: []};
    const deferred = {};
    ['done', 'fail', 'always'].forEach(name => {
        deferred[name] = function (callback) {
            callbacks[name].push(callback);
            if (state && (state === name || name === 'always')) { callback(...values); }
            return deferred;
        };
    });
    ['resolve', 'reject'].forEach((name, index) => {
        deferred[name] = function (...args) {
            if (state) { return deferred; }
            state = index ? 'fail' : 'done'; values = args;
            callbacks[state].forEach(callback => callback(...values));
            callbacks.always.forEach(callback => callback(...values));
            return deferred;
        };
    });
    deferred.promise = () => deferred;
    return deferred;
};
$.ajax = function (options) {
    const request = { options, doneCallbacks: [], failCallbacks: [], alwaysCallbacks: [] };
    ['done', 'fail', 'always'].forEach(function (name) {
        request[name] = function (callback) { request[name + 'Callbacks'].push(callback); return request; };
    });
    request.resolve = function (response) {
        request.doneCallbacks.forEach(fn => fn(response));
        request.alwaysCallbacks.forEach(fn => fn());
    };
    request.reject = function (xhr, status = 'error') {
        request.failCallbacks.forEach(fn => fn(xhr, status));
        request.alwaysCallbacks.forEach(fn => fn());
    };
    if ((options.url.indexOf('r=asistente%2Fpago') !== -1 || options.url.indexOf('r=asistente%2Feliminar-pago') !== -1 || options.url.indexOf('r=asistente%2Feliminar-reserva') !== -1)) { paymentRequests.push(request); }
    else { requests.push(request); }
    return request;
};
const presentation = { type: 'tarifas', title: 'Tarifas del período: 02/11/2026 al 05/11/2026.',
    borrador: '1234567890abcdef1234567890abcdef', comment: 'Días facturables: 3', notes: ['Consulta actual', 'Promedio = total / días'],
    rows: [{ descripcion: 'Cabaña 1 · cap. 4 <img src=x onerror=alert(1)>', ingreso: '02/11/2026 14:00',
        egreso: '05/11/2026 11:00', total: '$ 285.000,00', promedio: '$ 95.000,00', criterio: 'min_dias aplicado: 3' }] };
const communicationError = 'No se pudo completar la comunicación con Gemini. Volvé a enviar tu consulta en unos momentos, en esta misma conversación.';
const trackingBase = '/index.php?r=disponibilidad%2Fseguimiento';
const trackingUrl = trackingBase + '&hash=test-private-tracking';
const reservationPresentation = { pago_borrador: 'abcdef1234567890abcdef1234567890', type: 'reservas', title: 'Reservas con estadía', comment: 'Filtro por estadía', notes: [],
    rows: [{ reserva: '#81', codigo_seguimiento: 'ABCD234', telefono: '11 4568-9999', email: 'privado@example.com', seguimiento_url: trackingUrl, recurso: 'Cabaña 2 · cap. máx. 6', locador: '<img src=x onerror=alert(1)>',
        creada: '01/07/2026 15:16', ingreso: '20/07/2026 14:00', egreso: '26/07/2026 11:00', dias: '6',
        total: '$ 1.000,00', cobrado: '$ 300,00', nota: '' },
    { reserva: '#82', telefono: 'javascript:alert(1)', email: 'evil@example.com?subject=bad', seguimiento_url: 'javascript:alert(1)', recurso: 'Sin cabañas', locador: 'Titular',
        creada: '01/07/2026 15:16', ingreso: '20/07/2026 14:00', egreso: '26/07/2026 11:00', dias: '6',
        total: 'No determinable', cobrado: 'No determinable', nota: 'Pendiente de verificar' }] };
let modalShown = false;
let paymentModalShown = 0;
let paymentModalHidden = 0;
const configuredRates = { type: 'tarifas_configuradas', title: 'Tarifas de Cabaña 1', comment: 'Tarifas activas vigentes y futuras',
    notes: ['Sin fechas no se cotiza'], rows: [{ recurso: 'Cabaña 1', tarifa: '<script>solo texto</script>', inicio: '01/12/2026',
        fin: '20/12/2026', valor_dia: '$ 10.000,00', min_dias: '3', criterio: 'Requiere al menos 3 días de estadía.' }] };
const unpaid = { ...reservationPresentation, mostrar_saldo: true, rows: [{ ...reservationPresentation.rows[0], saldo: '$ 700,00' }] };
const initial = { conversacion: 'old', mensajes: [{ role: 'user', text: '<img src=x onerror=alert(1)>' },
    { role: 'assistant', text: 'plain-text fallback', presentation },
    { role: 'assistant', text: communicationError, error: true },
    { role: 'assistant', text: 'reservations fallback', presentation: reservationPresentation },
    { role: 'assistant', text: 'configured rates fallback', presentation: configuredRates },
    { role: 'assistant', text: 'unpaid fallback', presentation: unpaid },
    { role: 'assistant', text: 'empty payment', presentation: { ...reservationPresentation, title: 'Pago sin filas', rows: [] } }],
    acciones: { pago: { token: reservationPresentation.pago_borrador, reserva: 81 } }, recortado: false };
let csrfToken = 'csrf-fixture';
const messageListeners = [];
const passengerFrameWindow = {};
vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../../web/js/asistente.js'), 'utf8'), {
    jQuery: $, document: { getElementById: id => ({ id, contentWindow: id === 'asistente-pasajero-frame' ? passengerFrameWindow : null, getAttribute: () => JSON.stringify({ pasajeroDetalleUrl: '/index.php?r=asistente%2Fpasajero-detalle', pasajeroUrl: '/index.php?r=asistente%2Fpasajero', initial, mensajeUrl: '/mensaje', nuevaUrl: '/nueva', seguimientoUrl: trackingBase, reservaUrl: '/index.php?r=asistente%2Freservar', pagoUrl: '/index.php?r=asistente%2Fpago', eliminarPagoUrl: '/index.php?r=asistente%2Feliminar-pago', eliminarReservaUrl: '/index.php?r=asistente%2Feliminar-reserva' }) }) },
    window: { location: {origin: 'http://localhost'}, addEventListener(name, callback) { if (name === 'message') { messageListeners.push(callback); } }, bootstrap: { Modal: { getOrCreateInstance(element) {
        assert(['asistente-reserva-modal', 'asistente-pago-modal', 'asistente-pasajero-modal', 'asistente-pasajero-detalle-modal'].includes(element.id));
        return { show() { if (element.id === 'asistente-pago-modal') { paymentModalShown++; } else { modalShown = true; } },
            hide() { if (element.id === 'asistente-pago-modal') { paymentModalHidden++; } } };
    } } } },
    FormData: class { constructor(form) { this.fields = { ...form.fields }; } set(key, value) { this.fields[key] = value; } },
    yii: { getCsrfParam: () => '_csrf', getCsrfToken: () => csrfToken,
        setCsrfToken(param, token) { assert.equal(param, '_csrf'); csrfToken = token; }, refreshCsrfToken() {} }
});
assert(all.some(element => element.content === initial.mensajes[0].text));
assert(!all.some(element => element.tag === '<img>')); // only .text(), no HTML interpretation
function descendants(element) { return [element].concat(...element.children.map(descendants)); }
const initialTable = descendants($('#asistente-mensajes')).find(element => element.tag === '<table>');
const errorBubble = descendants($('#asistente-mensajes')).find(element => element.classes.includes('asistente-error-respuesta'));
assert(errorBubble);
assert(descendants(errorBubble).some(element => element.content === communicationError));
assert(descendants(errorBubble).some(element => element.content === 'Asistente · Error'));
assert(initialTable); // presentation survives page reload from the initial session configuration
assert.deepEqual(descendants(initialTable).filter(element => element.tag === '<th>' && element.props.scope === 'col').map(element => element.content),
    ['Descripción', 'Ingreso', 'Egreso', 'Total', 'Valor promedio por día']);
assert(descendants(initialTable).some(element => element.content === presentation.rows[0].descripcion));
assert(descendants(initialTable).some(element => element.content === '$ 95.000,00'));
assert(descendants(initialTable).some(element => element.content === 'min_dias aplicado: 3'));
const reservationTable = descendants($('#asistente-mensajes')).find(element => element.classes.includes('asistente-tabla-reservas'));
assert(reservationTable);
assert.deepEqual(descendants(reservationTable).filter(element => element.props.scope === 'col').map(element => element.content),
    ['Reserva', 'Recurso', 'Locador', 'Registro', 'Cant. de días', 'Estado pago']);
const dateGroup = descendants(reservationTable).find(element => element.classes.includes('asistente-registro'));
assert.deepEqual(descendants(dateGroup).filter(element => element.classes.includes('asistente-dato-etiqueta')).map(element => element.content),
    ['Creada:', 'Ingreso:', 'Egreso:']);
['01/07/2026 15:16', '20/07/2026 14:00', '26/07/2026 11:00'].forEach(value => {
    assert(descendants(dateGroup).some(element => element.content === value));
});
const paymentGroup = descendants(reservationTable).find(element => element.classes.includes('asistente-estado-pago'));
assert(descendants(paymentGroup).some(element => element.content === '$ 1.000,00'));
assert(descendants(paymentGroup).some(element => element.content === '$ 300,00'));
assert(descendants(reservationTable).find(element => element.classes.includes('asistente-cantidad-dias')).classes.includes('text-center'));
assert(descendants(reservationTable).find(element => element.props.scope === 'col' && element.content === 'Cant. de días').classes.includes('text-center'));
assert(descendants(reservationTable).some(element => element.content === 'ABCD234'));
assert(descendants(reservationTable).some(element => element.content === '11 4568-9999'));
assert(descendants(reservationTable).some(element => element.content === 'privado@example.com'));
const contactLinks = descendants(reservationTable).filter(element => element.tag === '<a>' && element.attributes.href !== trackingUrl);
assert.equal(contactLinks.length, 2);
assert(contactLinks.some(element => element.attributes.href === 'https://wa.me/5491145689999' && element.attributes.rel === 'noopener noreferrer'));
assert(contactLinks.some(element => element.attributes.href === 'mailto:privado@example.com'));
assert(!descendants(reservationTable).some(element => /^(Seguimiento|Teléfono|Email):/.test(element.content)));
const trackingLinks = descendants(reservationTable).filter(element => element.tag === '<a>' && element.attributes.href === trackingUrl);
assert.equal(trackingLinks.length, 1); // unsafe or unrelated URLs never become clickable links
assert.equal(trackingLinks[0].attributes.href, trackingUrl);
let preventedTracking = false;
trackingLinks[0].events.click({ preventDefault() { preventedTracking = true; } });
assert(preventedTracking && modalShown);
assert.equal($('#asistente-reserva-frame').attributes.src, trackingUrl);
$('#asistente-reserva-modal').events['hidden.bs.modal']();
assert.equal($('#asistente-reserva-frame').attributes.src, 'about:blank');
const prepare = descendants($('#asistente-mensajes')).find(element => element.content === 'Completar y revisar reserva');
assert(prepare);
assert.equal(prepare.attributes.href, '/index.php?r=asistente%2Freservar&borrador=' + presentation.borrador);
prepare.events.click({ preventDefault() {} });
assert.equal($('#asistente-reserva-frame').attributes.src, prepare.attributes.href);
const payment = descendants($('#asistente-mensajes')).find(element => element.content === 'Completar y revisar pago');
assert(payment);
assert.equal(payment.attributes.href, '/index.php?r=asistente%2Fpago&borrador=' + reservationPresentation.pago_borrador);
payment.events.click({ preventDefault() {} });
assert.equal(paymentRequests.length, 1);
assert.equal(paymentRequests[0].options.method, 'GET');
assert.equal(paymentRequests[0].options.url, payment.attributes.href + '&modal=1');
assert.equal(paymentModalShown, 1);
paymentRequests[0].resolve({ ok: true, html: '<form>Formulario privado servido por Yii</form>', completado: false });
assert.equal($('#asistente-pago-contenido').content, '<form>Formulario privado servido por Yii</form>');
assert.equal($('#asistente-acciones-pendientes').props.hidden, false);
assert.equal($('#asistente-abrir-pago').attributes.href, payment.attributes.href);
let preventedPayment = false;
$('#asistente-abrir-pago').events.click.call({ getAttribute: () => payment.attributes.href }, { preventDefault() { preventedPayment = true; } });
assert(preventedPayment);
assert.equal(paymentRequests.length, 2);
paymentRequests[1].resolve({ ok: true, html: '<form>Pago desde el bloque pendiente</form>', completado: false });

assert.equal($('#asistente-pago-descripcion').content, 'Reserva #81');
const emptyPaymentBubble = descendants($('#asistente-mensajes')).find(element => element.classes.includes('asistente-burbuja')
    && element.children.some(child => child.content === 'Pago sin filas'));
assert(emptyPaymentBubble);
assert(emptyPaymentBubble.children.some(child => child.content === 'Completar y revisar pago')); // independent of table rows
assert(!descendants(emptyPaymentBubble).some(child => child.tag === '<table>'));



const ratesTable = descendants($('#asistente-mensajes')).find(element => element.classes.includes('asistente-tabla-tarifas-configuradas'));
assert(ratesTable);
assert.deepEqual(descendants(ratesTable).filter(element => element.props.scope === 'col').map(element => element.content),
    ['Recurso', 'Tarifa', 'Período', 'Días', 'Valor por día']);
const ratePeriod = descendants(ratesTable).find(element => element.classes.includes('asistente-periodo-tarifa'));
['Desde:', 'Hasta:', '01/12/2026', '20/12/2026'].forEach(value => {
    assert(descendants(ratePeriod).some(element => element.content === value));
});
assert(descendants(ratesTable).find(element => element.props.scope === 'col' && element.content === 'Días').classes.includes('text-center'));
const rateDays = descendants(ratesTable).find(element => element.classes.includes('asistente-dias-tarifa'));
assert(rateDays.classes.includes('text-center'));
assert.equal(rateDays.content, '3');
assert(descendants(ratesTable).some(element => element.content === 'Cabaña 1'));
assert(descendants(ratesTable).some(element => element.content === configuredRates.rows[0].tarifa));
assert(descendants(ratesTable).some(element => element.content === '$ 10.000,00'));
const unpaidTable = descendants($('#asistente-mensajes')).filter(element => element.classes.includes('asistente-tabla-reservas'))[1];
assert.equal(descendants(unpaidTable).filter(element => element.props.scope === 'col').length, 6);
assert(descendants(unpaidTable).some(element => element.content === 'Saldo pendiente:'));
assert(descendants(unpaidTable).some(element => element.content === '$ 700,00'));
const resend = descendants($('#asistente-mensajes')).find(element => element.tag === '<button>');
assert(resend);
assert.equal(resend.props.type, 'button');
assert.equal(resend.attributes['aria-label'], 'Volver a enviar esta consulta');
$('#asistente-mensaje').val('Fechas completas');
$('#asistente-form').events.submit({ preventDefault() {} });
assert.equal(requests.length, 1);
assert.equal(requests[0].options.data._csrf, 'csrf-fixture');
assert.equal(requests[0].options.timeout, 80000);
assert.equal(resend.props.disabled, true);
resend.events.click();
assert.equal(requests.length, 1); // resend and normal submit share the pending-request guard
$('#asistente-form').events.submit({ preventDefault() {} });
assert.equal(requests.length, 1); // double submit suppressed
$('#asistente-nueva').events.click();
assert.equal(requests.length, 2);
requests[1].resolve({ ok: true, conversacion: 'new', mensajes: [], recortado: false });
assert.equal($('#asistente-mensajes').children.length, 0);
requests[0].resolve({ ok: true, conversacion: 'old', mensajes: [{ role: 'assistant', text: 'LATE' }], recortado: false });
assert.equal($('#asistente-acciones-pendientes').props.hidden, true);
assert.equal($('#asistente-mensajes').children.length, 0); // stale callback cannot reinsert history
assert(!all.some(element => element.content === 'LATE'));
$('#asistente-mensaje').val('Nueva consulta');
$('#asistente-form').events.submit({ preventDefault() {} });
assert.equal(requests[2].options.data.conversacion, 'new');
requests[2].resolve({ ok: true, conversacion: 'new', mensajes: [{ role: 'assistant', text: '<script>untrusted</script>' },
    { role: 'assistant', text: 'plain-text fallback', presentation }], recortado: true });
assert(all.some(element => element.content === '<script>untrusted</script>'));
assert.equal($('#asistente-aviso').props.hidden, false);
assert(descendants($('#asistente-mensajes')).some(element => element.tag === '<table>'));
assert(!all.some(element => element.tag === '<img>' || element.tag === '<script>'));
$('#asistente-mensaje').val('Enviar de nuevo <script>solo texto</script>');
$('#asistente-form').events.submit({ preventDefault() {} });
const repeatedText = requests[3].options.data.mensaje;
requests[3].resolve({ ok: true, conversacion: 'new', mensajes: [{ role: 'user', text: repeatedText },
    { role: 'assistant', text: 'Respuesta' }], recortado: false });
const buttons = descendants($('#asistente-mensajes')).filter(element => element.tag === '<button>');
assert.equal(buttons.length, 1); // only user bubbles have resend
const repeat = buttons[0];
assert.equal(repeat.props.disabled, false);
$('#asistente-mensaje').val('Borrador sin enviar');
repeat.events.click();
assert.equal(requests[4].options.data.mensaje, repeatedText);
assert.equal(requests[4].options.data.conversacion, 'new');
assert.equal(requests[4].options.data._csrf, 'csrf-fixture');
assert.equal(repeat.props.disabled, true);
repeat.events.click();
assert.equal(requests.length, 5);
requests[4].resolve({ ok: true, conversacion: 'new', mensajes: [{ role: 'user', text: repeatedText },
    { role: 'assistant', text: communicationError, error: true }], recortado: false });
assert(descendants($('#asistente-mensajes')).some(element => element.classes.includes('asistente-error-respuesta')));
assert.equal($('#asistente-mensaje').val(), 'Borrador sin enviar');
assert(all.some(element => element.content === repeatedText));
assert(!all.some(element => element.tag === '<script>'));
// A newly prepared payment opens the modal automatically; review/upload/confirm stays inside it.
$('#asistente-mensaje').val('agregar pago de 500 mil en efectivo a la reserva 94');
$('#asistente-form').events.submit({ preventDefault() {} });
const newToken = '99999999999999999999999999999999';
requests[5].resolve({ ok: true, conversacion: 'new', mensajes: [], recortado: false,
    acciones: { pago: { token: newToken, reserva: 94 } } });
assert.equal(paymentRequests.length, 3);
assert.equal(paymentRequests[2].options.url, '/index.php?r=asistente%2Fpago&borrador=' + newToken + '&modal=1');
assert.equal($('#asistente-pago-cargando').props.hidden, false);
let blockedClose = false;
$('#asistente-pago-modal').events['hide.bs.modal']({ preventDefault() { blockedClose = true; } });
assert(blockedClose);
paymentRequests[2].resolve({ ok: true, html: '<form>Pago precargado</form>', completado: false });
assert.equal($('#asistente-pago-cargando').props.hidden, true);
const privateForm = { fields: { fase: 'revisar', 'AsistentePagoForm[monto]': '500000.00',
    'AsistentePagoForm[medio_pago]': 'efectivo', comprobante: 'archivo-privado.pdf' } };
$('#asistente-pago-contenido').events.submit.call(privateForm, { preventDefault() {} });
assert.equal(paymentRequests.length, 4);
const reviewOptions = paymentRequests[3].options;
assert.equal(reviewOptions.method, 'POST');
assert.equal(reviewOptions.contentType, false);
assert.equal(reviewOptions.processData, false);
assert.equal(reviewOptions.data.fields._csrf, 'csrf-fixture');
assert.equal(reviewOptions.data.fields.comprobante, 'archivo-privado.pdf');
$('#asistente-pago-contenido').events.submit.call(privateForm, { preventDefault() {} });
assert.equal(paymentRequests.length, 4); // double-submit guard
paymentRequests[3].resolve({ ok: true, html: '<form>Revisar y confirmar pago</form>', completado: false });
assert.equal($('#asistente-pago-contenido').content, '<form>Revisar y confirmar pago</form>');
$('#asistente-mensaje').val('Borrador que debe conservarse');
$('#asistente-pago-contenido').events.submit.call({ fields: { fase: 'confirmar' } }, { preventDefault() {} });
assert.equal(paymentRequests.length, 5);
const closedBefore = paymentModalHidden;
paymentRequests[4].resolve({ ok: true, html: 'Pago registrado', completado: true,
    estado: { conversacion: 'new', mensajes: [{ role: 'assistant', text: 'Pago registrado' }], recortado: false, acciones: {} } });
assert.equal(paymentModalHidden, closedBefore + 1);
assert.equal($('#asistente-acciones-pendientes').props.hidden, true);
assert.equal($('#asistente-mensaje').val(), 'Borrador que debe conservarse');
assert(descendants($('#asistente-mensajes')).some(element => element.content === 'Pago registrado'));
// Deletion uses the same modal but a separate, explicitly allowed route and danger action.
$('#asistente-mensaje').val('eliminar un pago de la reserva 94');
$('#asistente-form').events.submit({ preventDefault() {} });
const deletionToken = 'dddddddddddddddddddddddddddddddd';
requests[6].resolve({ ok: true, conversacion: 'new', mensajes: [], recortado: false,
    acciones: { pago: { token: deletionToken, reserva: 94, operacion: 'eliminar' } } });
assert.equal(paymentRequests.length, 6);
assert.equal(paymentRequests[5].options.url, '/index.php?r=asistente%2Feliminar-pago&borrador=' + deletionToken + '&modal=1');
assert.equal($('#asistente-pago-modal .modal-title').content, 'Eliminar pago');
assert.equal($('#asistente-abrir-pago').content, 'Revisar y eliminar pago');
assert.equal($('#asistente-acciones-pendientes').attributes['data-operacion'], 'eliminar');
paymentRequests[5].resolve({ ok: true, html: '<form>Seleccionar un pago</form>', completado: false });
$('#asistente-pago-contenido').events.submit.call({ fields: { fase: 'revisar', 'AsistenteEliminarPagoForm[indice]': '0' } }, { preventDefault() {} });
assert.equal(paymentRequests[6].options.data.fields['AsistenteEliminarPagoForm[indice]'], '0');
assert.equal(paymentRequests[6].options.data.fields._csrf, 'csrf-fixture');
paymentRequests[6].resolve({ ok: true, html: '<form>Confirmar eliminación</form>', completado: false });
assert.equal($('#asistente-pago-contenido').content, '<form>Confirmar eliminación</form>');
$('#asistente-pago-contenido').events.submit.call({ fields: { fase: 'confirmar' } }, { preventDefault() {} });
paymentRequests[7].resolve({ ok: true, html: 'Pago eliminado', completado: true,
    estado: { conversacion: 'new', mensajes: [{ role: 'assistant', text: 'Pago eliminado' }], recortado: false, acciones: {} } });
assert.equal($('#asistente-acciones-pendientes').props.hidden, true);
assert(descendants($('#asistente-mensajes')).some(element => element.content === 'Pago eliminado'));
// Removing a reservation uses its own endpoint and confirmation, never the payment delete endpoint.
$('#asistente-mensaje').val('eliminar reserva 94 cargada por error');
$('#asistente-form').events.submit({ preventDefault() {} });
const reservationDeleteToken = 'eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee';
requests[7].resolve({ ok: true, conversacion: 'new', mensajes: [], recortado: false,
    acciones: { eliminar_reserva: { token: reservationDeleteToken, reserva: 94 } } });
assert.equal(paymentRequests[8].options.url, '/index.php?r=asistente%2Feliminar-reserva&borrador=' + reservationDeleteToken + '&modal=1');
assert.equal($('#asistente-pago-modal .modal-title').content, 'Eliminar reserva');
assert.equal($('#asistente-abrir-pago').content, 'Revisar y eliminar reserva');
assert.equal($('#asistente-acciones-pendientes').attributes['data-operacion'], 'eliminar_reserva');
paymentRequests[8].resolve({ ok: true, html: '<form>Revisar solicitudes y pagos que se conservan</form>', completado: false });
$('#asistente-pago-contenido').events.submit.call({ fields: { fase: 'revisar', 'AsistenteEliminarReservaForm[revisado]': '1' } }, { preventDefault() {} });
assert.equal(paymentRequests[9].options.data.fields._csrf, 'csrf-fixture');
paymentRequests[9].resolve({ ok: true, html: '<form>Confirmar eliminación de reserva</form>', completado: false });
$('#asistente-pago-contenido').events.submit.call({ fields: { fase: 'confirmar' } }, { preventDefault() {} });
paymentRequests[10].resolve({ ok: true, html: 'Reserva eliminada', completado: true,
    estado: { conversacion: 'new', mensajes: [{ role: 'assistant', text: 'Reserva eliminada' }], recortado: false, acciones: {} } });
assert.equal($('#asistente-acciones-pendientes').props.hidden, true);
assert(descendants($('#asistente-mensajes')).some(element => element.content === 'Reserva eliminada'));
// A CSRF rejection happens before the action: refresh and retry exactly once.
let start = requests.length;
$('#asistente-nueva').events.click();
const expired = {status: 400, responseJSON: {ok: false, codigo: 'csrf_invalido',
    csrf: {param: '_csrf', token: 'csrf-refreshed'}, mensaje: 'Token inválido'}};
requests[start].reject(expired);
assert.equal(requests.length, start + 2);
assert.equal(requests[start + 1].options.data._csrf, 'csrf-refreshed');
assert.equal($('#asistente-nueva').props.disabled, true);
requests[start + 1].resolve({ok: true, conversacion: 'refreshed', mensajes: [], recortado: false});
assert.equal($('#asistente-nueva').props.disabled, false);
start = requests.length;
$('#asistente-nueva').events.click();
requests[start].reject(expired);
requests[start + 1].reject(expired);
assert.equal(requests.length, start + 2, 'No retry loop after a second rejection');
assert.equal($('#asistente-error').content, 'Token inválido');
start = requests.length;
$('#asistente-nueva').events.click();
requests[start].reject({status: 400, responseJSON: {mensaje: 'Otro error'}});
assert.equal(requests.length, start + 1, 'Other HTTP 400 responses never retry');
start = requests.length;
$('#asistente-mensaje').val('Mensaje pendiente');
$('#asistente-form').events.submit({preventDefault() {}});
$('#asistente-nueva').events.click();
requests[start].reject(expired);
assert.equal(requests.length, start + 2, 'Late rejection cannot retry an abandoned message');
requests[start + 1].resolve({ok: true, conversacion: 'final', mensajes: [], recortado: false});
start = requests.length;
$('#asistente-mensaje').val('calificar pasajero 73');
$('#asistente-form').events.submit({preventDefault() {}});
const passengerToken = 'abababababababababababababababab';
requests[start].resolve({ok: true, conversacion: 'final', mensajes: [], recortado: false,
    acciones: {pasajero: {token: passengerToken, operacion: 'calificacion'}}});
assert.equal($('#asistente-pasajero-pendiente').props.hidden, false);
assert.equal($('#asistente-abrir-pasajero').content, 'Calificar y editar nota');
assert.equal($('#asistente-pasajero-frame').attributes.src, '/index.php?r=asistente%2Fpasajero&borrador=' + passengerToken + '&modal=1');
const message = {tipo: 'asistente:pasajero-guardado', token: passengerToken,
    estado: {conversacion: 'final', mensajes: [{role: 'assistant', text: 'Datos guardados'}], acciones: {}, recortado: false}};
messageListeners[0]({origin: 'http://malicioso', source: passengerFrameWindow, data: message});
assert.equal($('#asistente-pasajero-pendiente').props.hidden, false);
messageListeners[0]({origin: 'http://localhost', source: {}, data: message});
assert.equal($('#asistente-pasajero-pendiente').props.hidden, false);
$('#asistente-mensaje').val('Texto sin enviar');
messageListeners[0]({origin: 'http://localhost', source: passengerFrameWindow, data: message});
assert.equal($('#asistente-pasajero-pendiente').props.hidden, true);
assert.equal($('#asistente-mensaje').val(), 'Texto sin enviar');
assert(descendants($('#asistente-mensajes')).some(element => element.content === 'Datos guardados'));
start = requests.length;
$('#asistente-mensaje').val('Buscar pasajeros por dirección');
$('#asistente-form').events.submit({preventDefault() {}});
const passengerNote = '<script>nota privada</script>';
requests[start].resolve({ok: true, conversacion: 'final', acciones: {}, recortado: false,
    mensajes: [{role: 'assistant', text: 'Resumen interno', presentation: {type: 'pasajeros', title: 'Pasajeros encontrados',
        comment: 'Coincidencias parciales', notes: ['Consultado ahora'], rows: [{pasajero: '#73', nombre: '<img src=x onerror=alert(1)>',
            documento: '123', telefono: '456', email: 'privado@example.com', direccion: 'Calle privada',
            observaciones: passengerNote, localidad: 'Localidad', provincia: 'Provincia', calificacion: 4}]}}]});
const passengersRendered = descendants($('#asistente-mensajes'));
assert(passengersRendered.some(element => element.content === passengerNote));
assert(passengersRendered.some(element => element.content === '★★★★'));
assert(passengersRendered.some(element => element.content === 'Observaciones'));
assert(passengersRendered.some(element => element.content === 'Localidad · Provincia'));
const passengerTable = passengersRendered.find(element => element.classes.includes('asistente-tabla-pasajeros'));
assert.deepEqual(descendants(passengerTable).filter(element => element.props.scope === 'col').map(element => element.content),
    ['ID', 'Pasajero', 'Observaciones', 'Calificación']);
const passengerData = descendants(passengerTable).find(element => element.classes.includes('asistente-datos-pasajero'));
['Documento: 123', '456', 'privado@example.com', 'Calle privada', 'Localidad · Provincia'].forEach(value => {
    assert(descendants(passengerData).some(element => element.content === value));
});
assert(passengersRendered.some(element => element.classes.includes('locador-rating-readonly') && element.attributes['aria-label'] === '4 de 5 estrellas'));
const passengerLink = passengersRendered.find(element => element.tag === '<a>' && element.content === '#73');
assert.equal(passengerLink.attributes.href, '/index.php?r=asistente%2Fpasajero-detalle&id=73');
let passengerPrevented = false;
passengerLink.events.click({preventDefault() { passengerPrevented = true; }});
assert(passengerPrevented);
assert.equal($('#asistente-pasajero-detalle-frame').attributes.src, passengerLink.attributes.href);
$('#asistente-pasajero-detalle-modal').events['hidden.bs.modal']();
assert.equal($('#asistente-pasajero-detalle-frame').attributes.src, 'about:blank');
assert.equal($('#asistente-pasajero-pendiente').props.hidden, true, 'A read-only search must not open an edit');
assert(!passengersRendered.some(element => element.tag === '<img>' || element.tag === '<script>'));
console.log('PASS: safe rendering, CSRF recovery once, no retry for other failures or stale messages, private payment confirmation and context isolation.');
