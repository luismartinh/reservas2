<?php
require_once dirname(__DIR__) . '/_support/AsistenteFixtures.php';

use app\components\asistente\ContextoConversacion;
use tests\support\OpenAIHttp as Http;

class AsistenteAudioCest
{
    use \tests\support\CreaDatosAsistente;
    use \tests\support\RuntimeAsistenteAislado;
    private $environment;
    private $file;
    private $upload = false;
    public function _before()
    {
        $this->iniciarRuntimeAsistente();
        foreach (['OPENAI_API_KEY', 'ASISTENTE_AUDIO_HABILITADO', 'ASISTENTE_AUDIO_MODELO', 'ASISTENTE_PROVEEDOR'] as $name) {
            $this->environment[$name] = getenv($name);
        }
        putenv('OPENAI_API_KEY=audio-functional-canary'); putenv('ASISTENTE_AUDIO_HABILITADO=1');
        putenv('ASISTENTE_AUDIO_MODELO=gpt-transcribe'); putenv('ASISTENTE_PROVEEDOR=gemini');
        Http::reset(); Http::$raw = '{"text":"Ver las reservas de julio"}';
        $this->file = tempnam(sys_get_temp_dir(), 'audio-functional-');
        file_put_contents($this->file, 'RIFF' . pack('V', 1636) . 'WAVEfmt '
            . pack('VvvVVvv', 16, 1, 1, 8000, 16000, 2, 16) . 'data' . pack('V', 1600) . str_repeat("\0", 1600));
        Yii::$app->on(\yii\base\Application::EVENT_BEFORE_REQUEST, function () {
            Yii::$app->request->enableCsrfValidation = true;
            Yii::$app->response->format = \yii\web\Response::FORMAT_HTML;
            \yii\web\UploadedFile::reset();
            if ($this->upload) {
                $_FILES['audio'] = ['name' => 'consulta.wav', 'type' => 'audio/wav', 'tmp_name' => $this->file,
                    'error' => UPLOAD_ERR_OK, 'size' => filesize($this->file)];
            }
        });
    }
    public function _after()
    {
        foreach ($this->environment as $name => $value) { putenv($value === false ? $name : $name . '=' . $value); }
        unset($_FILES['audio']); \yii\web\UploadedFile::reset();
        unlink($this->file); $this->cerrarRuntimeAsistente();
    }
    private function abrir(\FunctionalTester $I)
    {
        $user = $this->usuarioAsistente(); $I->amLoggedInAs($user); $I->amOnRoute('asistente/index');
        $config = json_decode($I->grabAttributeFrom('#asistente-chat', 'data-config'), true);
        $I->assertTrue($config['audio']['habilitado']);
        $I->seeElement('#asistente-audio-grabar');
        return [$user, $config['initial']['conversacion'], Yii::$app->request->getCsrfToken()];
    }
    private function enviar(\FunctionalTester $I, $conversation, $csrf)
    {
        $I->sendAjaxPostRequest('/index.php?r=asistente/transcribir', ['conversacion' => $conversation,
            Yii::$app->request->csrfParam => $csrf]);
    }
    public function transcribeSinGuardarHistorialNiEjecutarConsultas(\FunctionalTester $I)
    {
        list($user, $conversation, $csrf) = $this->abrir($I);
        $before = Yii::$app->session->get(ContextoConversacion::KEY);
        $this->upload = true; $this->enviar($I, $conversation, $csrf);
        $I->seeResponseCodeIs(200);
        $data = json_decode($I->grabPageSource(), true);
        $I->assertSame(['ok' => true, 'texto' => 'Ver las reservas de julio', 'conversacion' => $conversation], $data);
        $I->assertSame($before, Yii::$app->session->get(ContextoConversacion::KEY));
        $I->assertSame(1, Http::$requests);
        $I->assertStringContainsString('no-store', Yii::$app->response->headers->get('Cache-Control'));
        $I->assertStringNotContainsString('audio-functional-canary', $I->grabPageSource());
    }
    public function rechazaCsrfGetConversacionAjenaYFaltaDeAudio(\FunctionalTester $I)
    {
        list($user, $conversation, $csrf) = $this->abrir($I);
        $this->upload = true; $this->enviar($I, $conversation, 'invalid'); $I->seeResponseCodeIs(400);
        $this->enviar($I, str_repeat('b', 32), $csrf); $I->seeResponseCodeIs(409);
        $I->amOnRoute('asistente/transcribir'); $I->seeResponseCodeIs(405);
        $this->upload = false; $this->enviar($I, $conversation, $csrf); $I->seeResponseCodeIs(422);
        $I->assertSame(0, Http::$requests);
    }
    public function noDevuelveTextoSiSeRevocaPermisoDuranteTranscripcion(\FunctionalTester $I)
    {
        list($user, $conversation, $csrf) = $this->abrir($I); $this->upload = true;
        Http::$duringRequest = function () use ($user) { \app\models\Identificador::updateAll(['activo' => 0], ['id' => $user->id]); };
        $this->enviar($I, $conversation, $csrf); $I->seeResponseCodeIs(403);
        $I->assertStringNotContainsString('Ver las reservas', $I->grabPageSource());
    }
    public function exigeAdminConfiguracionYLimites(\FunctionalTester $I)
    {
        $I->sendAjaxPostRequest('/index.php?r=asistente/transcribir', []); $I->seeResponseCodeIs(403);
        list($user, $conversation, $csrf) = $this->abrir($I); $this->upload = true;
        putenv('ASISTENTE_AUDIO_HABILITADO=0');
        $this->enviar($I, $conversation, $csrf); $I->seeResponseCodeIs(403);
        putenv('ASISTENTE_AUDIO_HABILITADO=1');
        $config = \app\components\asistente\Configuracion::leer();
        $limits = new \app\components\asistente\Limites(); $limits->adquirir($user->id);
        for ($i = 0; $i < $config['porMinuto']; $i++) { $limits->consumir($config, new \DateTimeImmutable('now', new \DateTimeZone(Yii::$app->timeZone))); }
        $limits->liberar();
        $this->enviar($I, $conversation, $csrf); $I->seeResponseCodeIs(429);
        $I->assertSame(0, Http::$requests);
    }
}
