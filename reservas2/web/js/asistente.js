(function ($) {
    'use strict';
    var root = document.getElementById('asistente-chat');
    if (!root) { return; }
    var config = JSON.parse(root.getAttribute('data-config'));
    var conversation = config.initial.conversacion;
    var epoch = 0;
    var sending = false;
    var resetting = false;
    var resendButtons = [];
    var list = $('#asistente-mensajes');
    $('#asistente-ayuda-abrir').on('click', function () {
        $('#asistente-ayuda').prop('hidden', false);
        $('#asistente-ayuda-abrir').attr('aria-expanded', 'true');
    });
    $('#asistente-ayuda-cerrar').on('click', function () {
        $('#asistente-ayuda').prop('hidden', true);
        $('#asistente-ayuda-abrir').attr('aria-expanded', 'false');
        document.getElementById('asistente-ayuda-abrir').focus();
    });
    var paymentLoading = false;
    var paymentEpoch = 0;
    var paymentUrl = null;
    var lastPaymentToken = null;
    function paymentBusy(value) {
        paymentLoading = value;
        $('#asistente-pago-cargando').prop('hidden', !value);
        $('#asistente-pago-modal :input').prop('disabled', value);
        $('#asistente-pago-modal .btn-close').prop('disabled', value);
    }
    function paymentRequest(data) {
        if (paymentLoading || !paymentUrl) { return; }
        var current = paymentEpoch;
        $('#asistente-pago-error').text('').prop('hidden', true);
        paymentBusy(true);
        var options = { url: paymentUrl, method: data ? 'POST' : 'GET', dataType: 'json', timeout: 60000 };
        if (data) { options.data = data; options.processData = false; options.contentType = false; }
        $.ajax(options).done(function (response) {
            if (current !== paymentEpoch) { return; }
            if (typeof response.html === 'string') {
                // Only the authorized Yii payment endpoint supplies this escaped server-rendered view.
                $('#asistente-pago-contenido').html(response.html);
            } else { $('#asistente-pago-error').text(response.mensaje || 'No se pudo cargar el formulario de pago.').prop('hidden', false); }
            if (response.ok && response.completado && response.estado && response.estado.conversacion === conversation) {
                render(response.estado);
                paymentBusy(false);
                window.bootstrap.Modal.getOrCreateInstance(document.getElementById('asistente-pago-modal')).hide();
            }
        }).fail(function (xhr, status) {
            if (current !== paymentEpoch) { return; }
            $('#asistente-pago-error').text((xhr.responseJSON || {}).mensaje
                || (status === 'timeout' ? 'Se agotó el tiempo de espera. Comprobá el estado de la operación antes de reintentar.'
                    : 'No se pudo completar la operación. Intentá nuevamente.')).prop('hidden', false);
        }).always(function () { if (current === paymentEpoch) { paymentBusy(false); } });
    }
    function renderContact(item, holder) {
        if (item.telefono || item.email) {
            if (item.telefono) {
                var phone = String(item.telefono).trim();
                var digits = phone.replace(/\D/g, '').replace(/^00/, '');
                // Local ten-digit numbers are interpreted as Argentine mobiles.
                if (/^\d{10}$/.test(digits) && !/^\+|^00/.test(phone)) { digits = '549' + digits; }
                var phoneLine = $('<small>').addClass('asistente-criterio').appendTo(holder);
                if (/^\+?[\d\s().-]+$/.test(phone) && /^[1-9]\d{7,14}$/.test(digits)) {
                    $('<a>').text(phone).attr('href', 'https://wa.me/' + digits)
                        .attr('target', '_blank').attr('rel', 'noopener noreferrer').appendTo(phoneLine);
                } else { phoneLine.text(phone); }
            }
            if (item.email) {
                var email = String(item.email).trim();
                var emailLine = $('<small>').addClass('asistente-criterio').appendTo(holder);
                if (/^[^\s@<>?&#]+@[^\s@<>?&#]+\.[^\s@<>?&#]+$/.test(email)) {
                    $('<a>').text(email).attr('href', 'mailto:' + encodeURIComponent(email).replace(/%40/g, '@')).appendTo(emailLine);
                } else { emailLine.text(email); }
            }
        } else if (item.contacto) { $('<small>').addClass('asistente-criterio').text(item.contacto).appendTo(holder); }
    }
    function renderPassengers(presentation, bubble) {
        bubble.addClass('asistente-burbuja-tabla');
        $('<h2>').addClass('asistente-titulo').text(presentation.title).appendTo(bubble);
        if (presentation.comment) { $('<p>').addClass('asistente-comentario').text(presentation.comment).appendTo(bubble); }
        presentation.notes.forEach(function (note) { $('<p>').addClass('asistente-nota text-body-secondary').text(note).appendTo(bubble); });
        if (!presentation.rows.length) { return; }
        var wrapper = $('<div>').addClass('table-responsive asistente-tabla-contenedor').prop('tabIndex', 0);
        var table = $('<table>').addClass('table table-sm align-middle asistente-tabla asistente-tabla-pasajeros');
        $('<caption>').addClass('visually-hidden').text(presentation.title).appendTo(table);
        var headers = $('<tr>');
        ['ID', 'Pasajero', 'Observaciones', 'Calificación']
            .forEach(function (title) { $('<th>').prop('scope', 'col').text(title).appendTo(headers); });
        $('<thead>').append(headers).appendTo(table);
        var body = $('<tbody>');
        presentation.rows.forEach(function (item) {
            var row = $('<tr>');
            var identifier = $('<th>').prop('scope', 'row').appendTo(row);
            var match = /^#([1-9]\d*)$/.exec(item.pasajero || '');
            if (match && config.pasajeroDetalleUrl) {
                var url = config.pasajeroDetalleUrl + (config.pasajeroDetalleUrl.indexOf('?') === -1 ? '?' : '&') + 'id=' + match[1];
                var link = $('<a>').text(item.pasajero).attr('href', url).attr('target', '_blank').attr('rel', 'noopener noreferrer')
                    .attr('aria-label', 'Ver datos del pasajero ' + item.pasajero).appendTo(identifier);
                link.on('click', function (event) {
                    if (!window.bootstrap || !window.bootstrap.Modal) { return; }
                    event.preventDefault();
                    $('#asistente-pasajero-detalle-frame').attr('src', url);
                    window.bootstrap.Modal.getOrCreateInstance(document.getElementById('asistente-pasajero-detalle-modal')).show();
                });
            } else { identifier.text(item.pasajero || ''); }
            var passenger = $('<td>').addClass('asistente-datos-pasajero').appendTo(row);
            $('<strong>').text(item.nombre || '').appendTo(passenger);
            if (item.documento) { $('<small>').addClass('asistente-criterio').text('Documento: ' + item.documento).appendTo(passenger); }
            renderContact(item, passenger);
            if (item.direccion) { $('<small>').addClass('asistente-criterio').text(item.direccion).appendTo(passenger); }
            var location = [item.localidad, item.provincia].filter(Boolean).join(' · ');
            if (location) { $('<small>').addClass('asistente-criterio text-body-secondary').text(location).appendTo(passenger); }
            $('<td>').addClass('asistente-observaciones-pasajero').text(item.observaciones || '').appendTo(row);
            var rating = Number(item.calificacion);
            var validRating = Number.isInteger(rating) && rating >= 0 && rating <= 5;
            var label = rating ? rating + ' de 5 estrellas' : 'Sin calificación';
            var cell = $('<td>').appendTo(row);
            if (validRating) {
                var stars = $('<span>').addClass('locador-rating-readonly').attr('role', 'img').attr('aria-label', label).attr('title', label);
                $('<span>').addClass('locador-rating-filled').attr('aria-hidden', 'true').text('★'.repeat(rating)).appendTo(stars);
                $('<span>').attr('aria-hidden', 'true').text('☆'.repeat(5 - rating)).appendTo(stars);
                stars.appendTo(cell);
                if (!rating) { $('<small>').addClass('asistente-criterio text-body-secondary').text(label).appendTo(cell); }
            } else { cell.text(String(item.calificacion)); }
            body.append(row);
        });
        body.appendTo(table); table.appendTo(wrapper); wrapper.appendTo(bubble);
    }
    $('#asistente-pasajero-detalle-modal').on('hidden.bs.modal', function () {
        $('#asistente-pasajero-detalle-frame').attr('src', 'about:blank');
    });
    function openPayment(url) {
        var operation = 'agregar';
        var valid = [config.pagoUrl, config.eliminarPagoUrl, config.eliminarReservaUrl].some(function (base, index) {
            if (!base || typeof url !== 'string') { return false; }
            var prefix = base + (base.indexOf('?') === -1 ? '?' : '&') + 'borrador=';
            if (url.indexOf(prefix) === 0 && /^[a-f0-9]{32}$/.test(url.slice(prefix.length))) { operation = index === 2 ? 'eliminar_reserva' : (index === 1 ? 'eliminar_pago' : 'agregar'); return true; }
            return false;
        });
        if (!valid || !window.bootstrap || !window.bootstrap.Modal) { return false; }
        if (paymentLoading) { return true; }
        paymentEpoch++;
        paymentUrl = url + '&modal=1';
        $('#asistente-pago-modal .modal-title').text(operation === 'eliminar_reserva' ? 'Eliminar reserva' : (operation === 'eliminar_pago' ? 'Eliminar pago' : 'Agregar pago'));
        $('#asistente-pago-contenido').empty();
        window.bootstrap.Modal.getOrCreateInstance(document.getElementById('asistente-pago-modal')).show();
        paymentRequest();
        return true;
    }
    $('#asistente-pago-contenido').on('submit', 'form', function (event) {
        event.preventDefault();
        if (paymentLoading) { return; }
        var data = new FormData(this);
        data.set(yii.getCsrfParam(), yii.getCsrfToken());
        paymentRequest(data);
    });
    $('#asistente-pago-modal').on('hide.bs.modal', function (event) {
        if (paymentLoading) { event.preventDefault(); }
    });
    $('#asistente-pago-modal').on('hidden.bs.modal', function () {
        paymentEpoch++;
        paymentUrl = null;
        $('#asistente-pago-contenido').empty();
        $('#asistente-pago-error').text('').prop('hidden', true);
    });

    function error(text) { $('#asistente-error').text(text).prop('hidden', !text); }
    function busy(value) {
        sending = value;
        if (window.reservasAsistenteAudio) { window.reservasAsistenteAudio.actualizarEstado(value || resetting); }
        $('#asistente-enviar').prop('disabled', value || resetting);
        $('#asistente-procesando').prop('hidden', !value);
        resendButtons.forEach(function (button) { button.prop('disabled', value || resetting); });
    }
    function renderCabins(presentation, bubble) {
        bubble.addClass('asistente-burbuja-tabla');
        $('<h2>').addClass('asistente-titulo').text(presentation.title).appendTo(bubble);
        if (presentation.comment) { $('<p>').addClass('asistente-comentario').text(presentation.comment).appendTo(bubble); }
        presentation.notes.forEach(function (note) {
            $('<p>').addClass('asistente-nota text-body-secondary').text(note).appendTo(bubble);
        });
        if (!presentation.rows.length) { return; }
        var wrapper = $('<div>').addClass('table-responsive asistente-tabla-contenedor').prop('tabIndex', 0);
        var table = $('<table>').addClass('table table-sm align-middle asistente-tabla');
        $('<caption>').addClass('visually-hidden').text(presentation.title).appendTo(table);
        var head = $('<thead>');
        var headings = $('<tr>');
        var headers = ['Descripción', 'Ingreso', 'Egreso'];
        var prices = presentation.type === 'tarifas';
        if (prices) { headers.push('Total', 'Valor promedio por día'); }
        headers.forEach(function (header) { $('<th>').prop('scope', 'col').text(header).appendTo(headings); });
        head.append(headings).appendTo(table);
        var body = $('<tbody>');
        presentation.rows.forEach(function (item) {
            var row = $('<tr>');
            var description = $('<th>').prop('scope', 'row');
            $('<span>').text(item.descripcion).appendTo(description);
            if (prices) { $('<small>').addClass('asistente-criterio text-body-secondary').text(item.criterio).appendTo(description); }
            row.append(description);
            ['ingreso', 'egreso'].forEach(function (key) { $('<td>').addClass('asistente-fecha').text(item[key]).appendTo(row); });
            if (prices) {
                ['total', 'promedio'].forEach(function (key) { $('<td>').addClass('asistente-importe').text(item[key]).appendTo(row); });
            }
            body.append(row);
        });
        body.appendTo(table);
        table.appendTo(wrapper);
        wrapper.appendTo(bubble);
        if (config.reservaUrl && /^[a-f0-9]{32}$/.test(presentation.borrador || '')) {
            var url = config.reservaUrl + (config.reservaUrl.indexOf('?') === -1 ? '?' : '&') + 'borrador=' + presentation.borrador;
            var link = $('<a>').addClass('btn btn-primary btn-sm mt-2').text('Completar y revisar reserva')
                .attr('href', url).attr('target', '_blank').attr('rel', 'noopener noreferrer');
            link.on('click', function (event) {
                if (sending || resetting) { event.preventDefault(); return; }
                if (!window.bootstrap || !window.bootstrap.Modal) { return; }
                event.preventDefault();
                $('#asistente-reserva-frame').attr('src', url);
                window.bootstrap.Modal.getOrCreateInstance(document.getElementById('asistente-reserva-modal')).show();
            });
            link.appendTo(bubble);
        }
    }
    function renderConfiguredRates(presentation, bubble) {
        bubble.addClass('asistente-burbuja-tabla');
        $('<h2>').addClass('asistente-titulo').text(presentation.title).appendTo(bubble);
        $('<p>').addClass('asistente-comentario').text(presentation.comment).appendTo(bubble);
        presentation.notes.forEach(function (note) { $('<p>').addClass('asistente-nota text-body-secondary').text(note).appendTo(bubble); });
        if (!presentation.rows.length) { return; }
        var wrapper = $('<div>').addClass('table-responsive asistente-tabla-contenedor').prop('tabIndex', 0);
        var table = $('<table>').addClass('table table-sm align-middle asistente-tabla asistente-tabla-tarifas-configuradas');
        $('<caption>').addClass('visually-hidden').text(presentation.title).appendTo(table);
        var head = $('<thead>');
        var headings = $('<tr>');
        ['Recurso', 'Tarifa', 'Período', 'Días', 'Valor por día']
            .forEach(function (label) {
                var heading = $('<th>').prop('scope', 'col').text(label);
                if (label === 'Días') { heading.addClass('text-center'); }
                heading.appendTo(headings);
            });
        head.append(headings).appendTo(table);
        var body = $('<tbody>');
        presentation.rows.forEach(function (item) {
            var row = $('<tr>');
            $('<th>').prop('scope', 'row').text(item.recurso || '—').appendTo(row);
            var description = $('<td>');
            $('<span>').text(item.tarifa).appendTo(description);
            if (item.criterio) { $('<small>').addClass('asistente-criterio text-body-secondary').text(item.criterio).appendTo(description); }
            description.appendTo(row);
            var period = $('<td>').addClass('asistente-periodo-tarifa');
            [['Desde', 'inicio'], ['Hasta', 'fin']].forEach(function (entry) {
                var line = $('<div>').addClass('asistente-dato-agrupado');
                $('<span>').addClass('asistente-dato-etiqueta text-body-secondary').text(entry[0] + ':').appendTo(line);
                $('<span>').addClass('asistente-fecha').text(item[entry[1]]).appendTo(line);
                line.appendTo(period);
            });
            period.appendTo(row);
            $('<td>').addClass('text-center asistente-dias-tarifa').text(item.min_dias).appendTo(row);
            $('<td>').addClass('asistente-importe').text(item.valor_dia).appendTo(row);
            body.append(row);
        });
        body.appendTo(table);
        table.appendTo(wrapper);
        wrapper.appendTo(bubble);
    }
    function renderReservations(presentation, bubble) {
        bubble.addClass('asistente-burbuja-tabla');
        $('<h2>').addClass('asistente-titulo').text(presentation.title).appendTo(bubble);
        var removingReservation = /^[a-f0-9]{32}$/.test(presentation.eliminar_reserva_borrador || '');
        var removingPayment = /^[a-f0-9]{32}$/.test(presentation.eliminar_pago_borrador || '');
        var paymentBase = removingReservation ? config.eliminarReservaUrl : (removingPayment ? config.eliminarPagoUrl : config.pagoUrl);
        var paymentToken = removingReservation ? presentation.eliminar_reserva_borrador : (removingPayment ? presentation.eliminar_pago_borrador : presentation.pago_borrador);
        if (paymentBase && /^[a-f0-9]{32}$/.test(paymentToken || '')) {
            var paymentUrl = paymentBase + (paymentBase.indexOf('?') === -1 ? '?' : '&') + 'borrador=' + paymentToken;
            var paymentLink = $('<a>').addClass('btn btn-sm mt-2 ' + ((removingPayment || removingReservation) ? 'btn-danger' : 'btn-primary'))
                .text(removingReservation ? 'Revisar y eliminar reserva' : (removingPayment ? 'Revisar y eliminar pago' : 'Completar y revisar pago'))
                .attr('href', paymentUrl).attr('target', '_blank').attr('rel', 'noopener noreferrer');
            paymentLink.on('click', function (event) {
                if (sending || resetting) { event.preventDefault(); return; }
                if (openPayment(paymentUrl)) { event.preventDefault(); }
            });
            paymentLink.appendTo(bubble);
        }
        if (presentation.comment) { $('<p>').addClass('asistente-comentario').text(presentation.comment).appendTo(bubble); }
        presentation.notes.forEach(function (note) { $('<p>').addClass('asistente-nota text-body-secondary').text(note).appendTo(bubble); });
        if (!presentation.rows.length) { return; }
        var wrapper = $('<div>').addClass('table-responsive asistente-tabla-contenedor').prop('tabIndex', 0);
        var table = $('<table>').addClass('table table-sm align-middle asistente-tabla asistente-tabla-reservas');
        $('<caption>').addClass('visually-hidden').text(presentation.title).appendTo(table);
        var head = $('<thead>');
        var headings = $('<tr>');
        var headers = ['Reserva', 'Recurso', 'Locador', 'Registro', 'Cant. de días', 'Estado pago'];
        headers
            .forEach(function (label) {
                var heading = $('<th>').prop('scope', 'col').text(label);
                if (label === 'Cant. de días') { heading.addClass('text-center'); }
                heading.appendTo(headings);
            });
        head.append(headings).appendTo(table);
        var body = $('<tbody>');
        presentation.rows.forEach(function (item) {
            var row = $('<tr>');
            var reservation = $('<th>').prop('scope', 'row');
            var prefix = config.seguimientoUrl ? config.seguimientoUrl + (config.seguimientoUrl.indexOf('?') === -1 ? '?' : '&') + 'hash=' : '';
            var url = item.seguimiento_url;
            if (prefix && typeof url === 'string' && url.indexOf(prefix) === 0 && /^[A-Za-z0-9_-]{1,128}$/.test(url.slice(prefix.length))) {
                var link = $('<a>').text(item.reserva).attr('href', url).attr('target', '_blank').attr('rel', 'noopener noreferrer')
                    .attr('aria-label', 'Ver seguimiento de la reserva ' + item.reserva);
                link.on('click', function (event) {
                    if (!window.bootstrap || !window.bootstrap.Modal) { return; }
                    event.preventDefault();
                    $('#asistente-reserva-frame').attr('src', url);
                    window.bootstrap.Modal.getOrCreateInstance(document.getElementById('asistente-reserva-modal')).show();
                });
                link.appendTo(reservation);
            } else { reservation.text(item.reserva); }
            if (item.codigo_seguimiento) {
                $('<small>').addClass('asistente-criterio asistente-codigo-seguimiento text-body-secondary')
                    .text(item.codigo_seguimiento).appendTo(reservation);
            }
            row.append(reservation);
            $('<td>').addClass('asistente-recurso').text(item.recurso).appendTo(row);
            var holder = $('<td>');
            $('<span>').text(item.locador).appendTo(holder);
            renderContact(item, holder);
            holder.appendTo(row);
            var dates = $('<td>').addClass('asistente-registro');
            [['Creada', 'creada'], ['Ingreso', 'ingreso'], ['Egreso', 'egreso']].forEach(function (entry) {
                var line = $('<div>').addClass('asistente-dato-agrupado');
                $('<span>').addClass('asistente-dato-etiqueta text-body-secondary').text(entry[0] + ':').appendTo(line);
                $('<span>').addClass('asistente-fecha asistente-fecha-registro').text(item[entry[1]]).appendTo(line);
                line.appendTo(dates);
            });
            dates.appendTo(row);
            $('<td>').addClass('text-center asistente-cantidad-dias').text(item.dias).appendTo(row);
            var paid = $('<td>').addClass('asistente-estado-pago');
            var amounts = [['Valor total', 'total'], ['Total cobrado', 'cobrado']];
            if (presentation.mostrar_saldo) { amounts.push(['Saldo pendiente', 'saldo']); }
            amounts.forEach(function (entry) {
                var line = $('<div>').addClass('asistente-dato-agrupado');
                $('<span>').addClass('asistente-dato-etiqueta text-body-secondary').text(entry[0] + ':').appendTo(line);
                $('<span>').addClass('asistente-importe').text(item[entry[1]]).appendTo(line);
                line.appendTo(paid);
            });
            if (item.nota) { $('<small>').addClass('asistente-criterio asistente-nota-pago text-body-secondary').text(item.nota).appendTo(paid); }
            paid.appendTo(row);
            body.append(row);
        });
        body.appendTo(table);
        table.appendTo(wrapper);
        wrapper.appendTo(bubble);
        if (presentation.detail_text) {
            var details = $('<details>').addClass('asistente-detalle-pagos');
            $('<summary>').text('Detalle de pagos registrados').appendTo(details);
            $('<div>').addClass('asistente-texto-detalle').text(presentation.detail_text).appendTo(details);
            details.appendTo(bubble);
        }
    }
    function pendingPayment(state, autoOpen) {
        var actions = state.acciones || {};
        var removingReservation = !!actions.eliminar_reserva;
        var action = actions.eliminar_reserva || actions.pago;
        var removing = action && action.operacion === 'eliminar';
        var base = removingReservation ? config.eliminarReservaUrl : (removing ? config.eliminarPagoUrl : config.pagoUrl);
        var valid = action && base && /^[a-f0-9]{32}$/.test(action.token || '');
        var url = valid ? base + (base.indexOf('?') === -1 ? '?' : '&') + 'borrador=' + action.token : '#';
        $('#asistente-acciones-pendientes').prop('hidden', !valid).attr('data-operacion', removingReservation ? 'eliminar_reserva' : (removing ? 'eliminar' : 'agregar'));
        $('#asistente-pago-titulo').text(removingReservation ? 'Eliminación de reserva pendiente' : (removing ? 'Eliminación de pago pendiente' : 'Pago pendiente de confirmación'));
        $('#asistente-abrir-pago').attr('href', url).attr('class', (removing || removingReservation) ? 'btn btn-danger' : 'btn btn-primary')
            .text(removingReservation ? 'Revisar y eliminar reserva' : (removing ? 'Revisar y eliminar pago' : 'Completar y revisar pago'));
        $('#asistente-pago-descripcion').text(valid ? 'Reserva #' + action.reserva : '');
        var token = valid ? action.token : null;
        if (autoOpen && token && token !== lastPaymentToken) { openPayment(url); }
        lastPaymentToken = token;
    }
    $('#asistente-abrir-pago').on('click', function (event) {
        if (sending || resetting) { event.preventDefault(); return; }
        var action = this.getAttribute('href');
        if (openPayment(action)) { event.preventDefault(); }
    });
    var activePassengerToken = null;
    var lastPassengerToken = null;
    function openPassenger(url) {
        var base = config.pasajeroUrl;
        if (!base || typeof url !== 'string' || !window.bootstrap || !window.bootstrap.Modal) { return false; }
        var prefix = base + (base.indexOf('?') === -1 ? '?' : '&') + 'borrador=';
        if (url.indexOf(prefix) !== 0 || !/^[a-f0-9]{32}$/.test(url.slice(prefix.length))) { return false; }
        activePassengerToken = url.slice(prefix.length);
        $('#asistente-pasajero-frame').attr('src', url + '&modal=1');
        window.bootstrap.Modal.getOrCreateInstance(document.getElementById('asistente-pasajero-modal')).show();
        return true;
    }
    function pendingPassenger(state, autoOpen) {
        var action = (state.acciones || {}).pasajero;
        var valid = action && config.pasajeroUrl && /^[a-f0-9]{32}$/.test(action.token || '');
        var base = config.pasajeroUrl || '';
        var url = valid ? base + (base.indexOf('?') === -1 ? '?' : '&') + 'borrador=' + action.token : '#';
        $('#asistente-pasajero-pendiente').prop('hidden', !valid);
        $('#asistente-abrir-pasajero').attr('href', url).text(action && action.operacion === 'calificacion'
            ? 'Calificar y editar nota' : 'Editar datos del pasajero');
        if (autoOpen && valid && action.token !== lastPassengerToken) { openPassenger(url); }
        lastPassengerToken = valid ? action.token : null;
    }
    $('#asistente-abrir-pasajero').on('click', function (event) {
        if (sending || resetting) { event.preventDefault(); return; }
        if (openPassenger(this.getAttribute('href'))) { event.preventDefault(); }
    });
    $('#asistente-pasajero-modal').on('hidden.bs.modal', function () {
        activePassengerToken = null;
        $('#asistente-pasajero-frame').attr('src', 'about:blank');
    });
    window.addEventListener('message', function (event) {
        var frame = document.getElementById('asistente-pasajero-frame');
        var data = event.data;
        if (!frame || event.source !== frame.contentWindow || event.origin !== window.location.origin
            || !data || data.tipo !== 'asistente:pasajero-guardado' || !activePassengerToken
            || data.token !== activePassengerToken || !data.estado || data.estado.conversacion !== conversation
            || !Array.isArray(data.estado.mensajes)) { return; }
        render(data.estado);
        window.bootstrap.Modal.getOrCreateInstance(document.getElementById('asistente-pasajero-modal')).hide();
    });
    function render(state, autoOpen) {
        pendingPassenger(state, autoOpen);
        pendingPayment(state, autoOpen);
        list.empty();
        resendButtons = [];
        state.mensajes.forEach(function (message) {
            var user = message.role === 'user';
            var row = $('<div>').addClass('asistente-fila').toggleClass('usuario', user);
            var bubble = $('<div>').addClass('asistente-burbuja');
            var isError = !user && message.error === true;
            if (isError) { bubble.addClass('asistente-error-respuesta'); }
            $('<span>').addClass('asistente-etiqueta').text(user ? 'Vos' : (isError ? 'Asistente · Error' : 'Asistente')).appendTo(bubble);
            if (user) {
                var resend = $('<button>').addClass('asistente-reenviar').prop('type', 'button')
                    .prop('title', 'Volver a enviar esta consulta').prop('disabled', sending || resetting)
                    .attr('aria-label', 'Volver a enviar esta consulta');
                $('<i>').addClass('bi bi-arrow-repeat').attr('aria-hidden', 'true').appendTo(resend);
                resend.on('click', function () { send(message.text, false); });
                resend.appendTo(bubble);
                resendButtons.push(resend);
            }
            var presentation = message.presentation;
            if (!user && presentation && ['pasajeros', 'tarifas', 'disponibilidad', 'reservas', 'tarifas_configuradas'].indexOf(presentation.type) !== -1
                && Array.isArray(presentation.rows) && Array.isArray(presentation.notes)) {
                if (presentation.type === 'pasajeros') { renderPassengers(presentation, bubble); }
                else if (presentation.type === 'reservas') { renderReservations(presentation, bubble); }
                else if (presentation.type === 'tarifas_configuradas') { renderConfiguredRates(presentation, bubble); }
                else { renderCabins(presentation, bubble); }
            } else {
                $('<span>').text(message.text).appendTo(bubble); // never parse model/data text as HTML
            }
            row.append(bubble).appendTo(list);
        });
        $('#asistente-aviso').prop('hidden', !state.recortado);
        list.scrollTop(list[0].scrollHeight);
    }
    function post(url, data) {
        var result = $.Deferred();
        var requestEpoch = epoch;
        function request(retried) {
            var csrfParam = yii.getCsrfParam();
            data[csrfParam] = yii.getCsrfToken();
            $.ajax({ url: url, method: 'POST', data: data, dataType: 'json', timeout: 80000 })
                .done(function (response) { result.resolve(response); })
                .fail(function (xhr, status, exception) {
                    var response = xhr.responseJSON || {};
                    var csrf = response.csrf;
                    // Sólo se repite un rechazo CSRF explícito, anterior a la acción.
                    // Nunca se repiten timeouts, errores de IA ni operaciones de pago.
                    if (!retried && requestEpoch === epoch && xhr.status === 400
                        && response.codigo === 'csrf_invalido' && csrf
                        && typeof csrf.param === 'string' && csrf.param.length
                        && typeof csrf.token === 'string' && csrf.token.length) {
                        delete data[csrfParam];
                        yii.setCsrfToken(csrf.param, csrf.token);
                        yii.refreshCsrfToken();
                        request(true);
                    } else { result.reject(xhr, status, exception); }
                });
        }
        request(false);
        return result.promise();
    }
    function failed(xhr, status) {
        error(status === 'timeout' ? 'La consulta demoró demasiado. No se reintentó. Podés recargar para comprobar el estado.'
            : ((xhr.responseJSON || {}).mensaje || 'No se pudo completar la consulta. Intentá más tarde.'));
    }
    function send(text, clearInput) {
        if (sending || resetting || (window.reservasAsistenteAudio && window.reservasAsistenteAudio.ocupado())) { return; }
        text = text.trim();
        if (!text) { return; }
        if (Array.from(text).length > 2000) { error('El mensaje supera los 2.000 caracteres.'); return; }
        var current = epoch;
        error(''); busy(true);
        post(config.mensajeUrl, { mensaje: text, conversacion: conversation }).done(function (response) {
            if (current !== epoch) { return; }
            if (!response.ok || response.conversacion !== conversation) { error(response.mensaje || 'La conversación cambió.'); return; }
            render(response, true);
            if (clearInput && $('#asistente-mensaje').val().trim() === text) { $('#asistente-mensaje').val(''); }
        }).fail(function (xhr, status) {
            if (current === epoch) { failed(xhr, status); }
        }).always(function () { if (current === epoch) { busy(false); } });
    }
    $('#asistente-form').on('submit', function (event) {
        event.preventDefault();
        send($('#asistente-mensaje').val(), true);
    });
    $('#asistente-reserva-modal').on('hidden.bs.modal', function () {
        $('#asistente-reserva-frame').attr('src', 'about:blank');
    });
    $('#asistente-nueva').on('click', function () {
        if (resetting) { return; }
        if (window.reservasAsistenteAudio) { window.reservasAsistenteAudio.cancelar(); }
        paymentEpoch++;
        paymentUrl = null;
        paymentBusy(false);
        if (window.bootstrap && window.bootstrap.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(document.getElementById('asistente-pago-modal')).hide();
        }
        activePassengerToken = null;
        $('#asistente-pasajero-frame').attr('src', 'about:blank');
        if (window.bootstrap && window.bootstrap.Modal) { window.bootstrap.Modal.getOrCreateInstance(document.getElementById('asistente-pasajero-modal')).hide(); }
        epoch++; // invalidate pending callbacks BEFORE starting reset
        resetting = true;
        $('#asistente-nueva').prop('disabled', true);
        busy(false); error('');
        post(config.nuevaUrl, { conversacion: conversation }).done(function (response) {
            if (!response.ok) { error(response.mensaje); return; }
            conversation = response.conversacion;
            if (window.reservasAsistenteAudio) { window.reservasAsistenteAudio.cambiarConversacion(conversation); }
            render(response);
            $('#asistente-mensaje').val('');
        }).fail(failed).always(function () {
            resetting = false;
            $('#asistente-nueva').prop('disabled', false);
            busy(false);
        });
    });
    render(config.initial);
})(jQuery);
