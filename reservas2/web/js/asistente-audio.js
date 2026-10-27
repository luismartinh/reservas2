(function ($) {
    'use strict';
    var root = document.getElementById('asistente-chat');
    if (!root) { return; }
    var config = JSON.parse(root.getAttribute('data-config'));
    var audio = config.audio;
    if (!audio || !audio.habilitado) { return; }
    var conversation = config.initial.conversacion;
    var revision = 0, state = 'idle', chatBusy = false;
    var stream = null, recorder = null, request = null, timer = null;
    var chunks = [], bytes = 0, started = 0;
    var supported = window.isSecureContext && navigator.mediaDevices && navigator.mediaDevices.getUserMedia
        && window.MediaRecorder && typeof window.MediaRecorder.isTypeSupported === 'function';
    var mime = supported && ['audio/webm;codecs=opus', 'audio/mp4', 'audio/webm']
        .filter(function (type) { return window.MediaRecorder.isTypeSupported(type); })[0];
    supported = Boolean(supported && mime);
    function message(text) { $('#asistente-audio-estado').text(text); }
    function error(text) { $('#asistente-audio-error').text(text).prop('hidden', !text); }
    function controls() {
        $('#asistente-audio-grabar').prop('disabled', !supported || chatBusy || state !== 'idle');
        $('#asistente-audio-detener').prop('hidden', state !== 'recording');
        $('#asistente-audio-cancelar').prop('hidden', state === 'idle');
        $('#asistente-audio-revision').prop('hidden', state !== 'review');
        $('#asistente-audio-usar').prop('disabled', chatBusy || state !== 'review');
        $('#asistente-enviar').prop('disabled', chatBusy || state !== 'idle');
    }
    function release() {
        if (timer !== null) { window.clearInterval(timer); timer = null; }
        if (stream) { stream.getTracks().forEach(function (track) { track.stop(); }); stream = null; }
    }
    function cancel() {
        revision++;
        if (recorder && recorder.state !== 'inactive') { recorder.stop(); }
        recorder = null;
        release();
        if (request) { request.abort(); request = null; }
        chunks = []; bytes = 0; state = 'idle';
        $('#asistente-audio-texto').val('');
        message(''); error(''); controls();
    }
    function fail(text) { cancel(); error(text); }
    function upload(blob, current) {
        if (current !== revision) { return; }
        if (!blob.size || blob.size > audio.maxBytes) { fail('El audio está vacío o supera los 2 MB. Grabá una consulta más breve.'); return; }
        state = 'transcribing'; message('Transcribiendo audio…'); controls();
        var data = new FormData();
        data.append('audio', blob, mime.indexOf('mp4') !== -1 ? 'consulta.mp4' : 'consulta.webm');
        data.append('conversacion', conversation);
        data.append(yii.getCsrfParam(), yii.getCsrfToken());
        var requestedConversation = conversation;
        request = $.ajax({url: audio.url, method: 'POST', data: data, processData: false, contentType: false,
            dataType: 'json', timeout: 80000}).done(function (response) {
            if (current !== revision || conversation !== requestedConversation) { return; }
            if (!response.ok || response.conversacion !== conversation || typeof response.texto !== 'string' || !response.texto.trim()) {
                fail(response.mensaje || 'No se pudo obtener una transcripción. Volvé a grabar.'); return;
            }
            state = 'review';
            $('#asistente-audio-texto').val(response.texto);
            message('Revisá el texto y pulsá «Pasar a consulta». Todavía no se envió la consulta.'); controls();
        }).fail(function (xhr, status) {
            if (current !== revision) { return; }
            var response = xhr.responseJSON || {};
            // Refresh a rejected token for the next recording; never resend audio automatically.
            if (xhr.status === 400 && response.codigo === 'csrf_invalido' && response.csrf) {
                yii.setCsrfToken(response.csrf.param, response.csrf.token); yii.refreshCsrfToken();
            }
            fail(response.mensaje || (status === 'timeout' ? 'Se agotó el tiempo para transcribir. Volvé a grabar o escribí la consulta.'
                : 'No se pudo transcribir el audio. Volvé a grabar o escribí la consulta.'));
        }).always(function () { if (current === revision) { request = null; } });
    }
    function stop() {
        if (state !== 'recording' || !recorder) { return; }
        state = 'stopping'; message('Preparando audio…'); controls();
        recorder.stop(); release();
    }
    $('#asistente-audio-grabar').on('click', function () {
        if (!supported || chatBusy || state !== 'idle') { return; }
        error(''); state = 'permission'; message('Esperando permiso del micrófono…'); controls();
        var current = ++revision;
        navigator.mediaDevices.getUserMedia({audio: true}).then(function (received) {
            if (current !== revision) { received.getTracks().forEach(function (track) { track.stop(); }); return; }
            stream = received;
            try {
                chunks = []; bytes = 0;
                recorder = new window.MediaRecorder(stream, {mimeType: mime, audioBitsPerSecond: 64000});
                recorder.ondataavailable = function (event) {
                    if (current !== revision || !event.data.size) { return; }
                    bytes += event.data.size;
                    if (bytes > audio.maxBytes) { fail('El audio supera los 2 MB. Grabá una consulta más breve.'); return; }
                    chunks.push(event.data);
                };
                recorder.onerror = function () { if (current === revision) { fail('Se interrumpió la grabación. Volvé a intentarlo.'); } };
                recorder.onstop = function () {
                    if (current !== revision) { return; }
                    release();
                    var blob = new Blob(chunks, {type: mime}); chunks = []; recorder = null;
                    upload(blob, current);
                };
                stream.getTracks().forEach(function (track) {
                    track.onended = function () { if (current === revision) { stop(); } };
                });
                recorder.start(1000); started = Date.now(); state = 'recording';
                message('Grabando… 0/' + audio.maxSegundos + ' s'); controls();
                timer = window.setInterval(function () {
                    var elapsed = Math.floor((Date.now() - started) / 1000);
                    message('Grabando… ' + Math.min(elapsed, audio.maxSegundos) + '/' + audio.maxSegundos + ' s');
                    if (elapsed >= audio.maxSegundos) { stop(); }
                }, 250);
            } catch (e) { fail('No se pudo iniciar la grabación. Podés escribir tu consulta.'); }
        }).catch(function () {
            if (current === revision) { fail('No se pudo acceder al micrófono. Revisá los permisos del navegador o escribí tu consulta.'); }
        });
    });
    $('#asistente-audio-detener').on('click', stop);
    $('#asistente-audio-cancelar').on('click', cancel);
    $('#asistente-audio-usar').on('click', function () {
        if (state !== 'review' || chatBusy) { return; }
        var text = $('#asistente-audio-texto').val().trim();
        if (!text) { error('Revisá la transcripción antes de pasarla a la consulta.'); return; }
        var previous = $('#asistente-mensaje').val().trim();
        var combined = previous ? previous + '\n' + text : text;
        if (Array.from(combined).length > 2000) { error('La consulta completa supera los 2.000 caracteres. Acortá la transcripción o el texto escrito.'); return; }
        $('#asistente-mensaje').val(combined);
        cancel(); message('Texto listo. Revisá la consulta y pulsá «Enviar».');
        document.getElementById('asistente-mensaje').focus();
    });
    window.addEventListener('pagehide', cancel);
    window.reservasAsistenteAudio = {
        ocupado: function () { return state !== 'idle'; }, cancelar: cancel,
        actualizarEstado: function (value) { chatBusy = value; controls(); },
        cambiarConversacion: function (value) { if (value !== conversation) { cancel(); conversation = value; } }
    };
    controls();
    if (!supported) { message('La grabación requiere HTTPS (o localhost) y un navegador compatible. Podés escribir la consulta.'); }
})(jQuery);
