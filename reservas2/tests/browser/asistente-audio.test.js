'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
function fixture(options = {}) {
    const nodes = new Map(), requests = [], tracks = [], recorders = [], events = {}, intervals = new Map();
    let permissionResolve, permissionReject, clock = 0;
    function $(id) {
        if (!nodes.has(id)) {
            nodes.set(id, {value: '', textValue: '', props: {}, attrs: {}, events: {},
                val(v) { if (v === undefined) { return this.value; } this.value = v; return this; },
                text(v) { this.textValue = v; return this; }, prop(k,v) { this.props[k] = v; return this; },
                attr(k,v) { this.attrs[k] = v; return this; }, on(k,v) { this.events[k] = v; return this; }});
        }
        return nodes.get(id);
    }
    $.ajax = config => {
        const callbacks = {};
        const request = {config, aborted: false,
            done(fn) { callbacks.done = fn; return this; }, fail(fn) { callbacks.fail = fn; return this; }, always(fn) { callbacks.always = fn; return this; },
            abort() { this.aborted = true; },
            resolve(data) { callbacks.done(data); callbacks.always(); }, reject(xhr, status) { callbacks.fail(xhr,status); callbacks.always(); }};
        requests.push(request); return request;
    };
    class Recorder {
        static isTypeSupported(type) { return options.mp4 ? type === 'audio/mp4' : type.startsWith('audio/webm'); }
        constructor(stream, config) { this.state = 'inactive'; this.config = config; recorders.push(this); }
        start() { this.state = 'recording'; }
        stop() { this.state = 'inactive'; this.ondataavailable({data: new Blob(['recorded'])}); this.onstop(); }
    }
    const config = {initial: {conversacion: 'first'}, audio: {habilitado: true, url: '/transcribir', maxBytes: 2097152, maxSegundos: 60}};
    const window = {isSecureContext: options.secure !== false, MediaRecorder: Recorder,
        addEventListener: (name, callback) => { events[name] = callback; },
        setInterval(fn) { intervals.set(1,fn); return 1; }, clearInterval(id) { intervals.delete(id); }};
    const navigator = {mediaDevices: {getUserMedia() {
        return new Promise((resolve,reject) => { permissionResolve = resolve; permissionReject = reject; });
    }}};
    const tokens = [];
    vm.runInNewContext(fs.readFileSync(path.join(__dirname,'../../web/js/asistente-audio.js'),'utf8'), {
        jQuery: $, window, navigator, Blob, Date: {now: () => clock},
        document: {getElementById: () => ({getAttribute: () => JSON.stringify(config), focus() {}})},
        FormData: class {constructor() { this.fields = {}; } append(k,v) { this.fields[k] = v; }},
        yii: {getCsrfParam: () => '_csrf', getCsrfToken: () => 'private-token', setCsrfToken(p,t) { tokens.push([p,t]); }, refreshCsrfToken() {}}
    });
    const click = id => $(id).events.click();
    async function grant() {
        const track = {stopped: false, stop() { this.stopped = true; }}; tracks.push(track);
        permissionResolve({getTracks: () => [track]}); await Promise.resolve(); await Promise.resolve();
    }
    return {$, requests, tracks, recorders, click, grant, window, events, tokens,
        deny: async () => { permissionReject(new Error('denied')); await Promise.resolve(); await Promise.resolve(); },
        tick: () => { clock = 60000; [...intervals.values()].forEach(fn => fn()); }};
}
(async () => {
    let f = fixture(); f.$('#asistente-mensaje').val('Texto ya escrito');
    f.click('#asistente-audio-grabar'); await f.grant();
    assert.equal(f.recorders[0].state, 'recording');
    f.click('#asistente-audio-detener'); assert(f.tracks[0].stopped);
    assert.equal(f.requests.length, 1); assert.equal(f.requests[0].config.data.fields._csrf, 'private-token');
    assert.equal(f.requests[0].config.data.fields.conversacion, 'first');
    f.requests[0].resolve({ok:true, texto:'Reservas de julio', conversacion:'first'});
    assert.equal(f.$('#asistente-audio-texto').val(), 'Reservas de julio');
    assert.equal(f.$('#asistente-mensaje').val(), 'Texto ya escrito');
    f.$('#asistente-audio-texto').val('Reservas de agosto'); f.click('#asistente-audio-usar');
    assert.equal(f.$('#asistente-mensaje').val(), 'Texto ya escrito\nReservas de agosto');
    assert.equal(f.requests.length, 1, 'Never automatically sends a chat query');
    assert(!f.window.reservasAsistenteAudio.ocupado());
    f = fixture(); f.click('#asistente-audio-grabar'); f.click('#asistente-audio-cancelar'); await f.grant();
    assert(f.tracks[0].stopped); assert.equal(f.recorders.length,0);
    f = fixture(); f.click('#asistente-audio-grabar'); await f.deny();
    assert(!f.$('#asistente-audio-error').props.hidden); assert(!f.window.reservasAsistenteAudio.ocupado());
    f = fixture(); f.click('#asistente-audio-grabar'); await f.grant(); f.tick(); assert.equal(f.requests.length,1);
    f.window.reservasAsistenteAudio.cambiarConversacion('second'); assert(f.requests[0].aborted);
    f.requests[0].resolve({ok:true,texto:'Resultado tardío',conversacion:'first'});
    assert.equal(f.$('#asistente-audio-texto').val(),'');
    f = fixture({secure:false}); assert(f.$('#asistente-audio-grabar').props.disabled);
    assert(!f.$('#asistente-enviar').props.disabled);
    f = fixture({mp4:true}); f.click('#asistente-audio-grabar'); await f.grant();
    assert.equal(f.recorders[0].config.mimeType, 'audio/mp4');
    f.events.pagehide(); assert(f.tracks[0].stopped); assert.equal(f.requests.length,0);
    f = fixture(); f.click('#asistente-audio-grabar'); await f.grant(); f.click('#asistente-audio-detener');
    f.requests[0].reject({status:400,responseJSON:{codigo:'csrf_invalido',csrf:{param:'_csrf',token:'fresh'}}},'error');
    assert.equal(f.requests.length,1); assert.equal(f.tokens[0][1],'fresh');
    f = fixture(); f.$('#asistente-mensaje').val('x'.repeat(1999));
    f.click('#asistente-audio-grabar'); await f.grant(); f.click('#asistente-audio-detener');
    f.requests[0].resolve({ok:true,texto:'Otra consulta',conversacion:'first'}); f.click('#asistente-audio-usar');
    assert.equal(f.$('#asistente-mensaje').val().length,1999); assert.equal(f.$('#asistente-audio-texto').val(),'Otra consulta');
    assert(!f.$('#asistente-audio-error').props.hidden);
    f = fixture(); f.window.reservasAsistenteAudio.actualizarEstado(true); f.click('#asistente-audio-grabar');
    assert.equal(f.recorders.length,0); assert(f.$('#asistente-audio-grabar').props.disabled);
    console.log('PASS: audio capture, editable review, cancel, permission, timeout boundary, stale responses, CSRF, mobile format and text preservation.');
})().catch(error => { console.error(error); process.exit(1); });
