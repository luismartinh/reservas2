<?php
namespace tests\unit\models;
require_once dirname(__DIR__, 2) . '/_support/AsistenteFixtures.php';

use app\components\asistente\ConfiguracionAudio;
use app\components\asistente\OpenAITranscriptor;
use tests\support\OpenAIHttp as Http;
use yii\web\UploadedFile;

class AsistenteAudioTest extends \Codeception\Test\Unit
{
    use \tests\support\RuntimeAsistenteAislado;
    private $environment;
    private $file;
    protected function _before()
    {
        $this->iniciarRuntimeAsistente();
        \Yii::$app->controller = new \yii\web\Controller('asistente', \Yii::$app);
        foreach (['OPENAI_API_KEY', 'ASISTENTE_AUDIO_MODELO', 'ASISTENTE_AUDIO_HABILITADO'] as $name) {
            $this->environment[$name] = getenv($name);
        }
        putenv('OPENAI_API_KEY=canary-audio-private-key');
        putenv('ASISTENTE_AUDIO_HABILITADO=1'); putenv('ASISTENTE_AUDIO_MODELO=gpt-transcribe');
        Http::reset(); Http::$raw = json_encode(['text' => 'Ver las reservas de julio']);
        $this->file = tempnam(sys_get_temp_dir(), 'asistente-audio-test-');
        $samples = str_repeat("\0", 1600);
        file_put_contents($this->file, 'RIFF' . pack('V', 36 + strlen($samples)) . 'WAVEfmt '
            . pack('VvvVVvv', 16, 1, 1, 8000, 16000, 2, 16) . 'data' . pack('V', strlen($samples)) . $samples);
    }
    protected function _after()
    {
        foreach ($this->environment as $name => $value) { putenv($value === false ? $name : $name . '=' . $value); }
        unlink($this->file); $this->cerrarRuntimeAsistente();
    }
    private function audio()
    {
        return new UploadedFile(['name' => '../../privado.webm', 'type' => 'text/html',
            'tempName' => $this->file, 'size' => 1, 'error' => UPLOAD_ERR_OK]);
    }
    private function admin() { return (object) ['activo' => 1, 'nivel' => 1]; }

    public function testMultipartPrivadoConMimeRealYTextoEditableSinGuardarArchivo()
    {
        $result = (new OpenAITranscriptor())->transcribir($this->admin(), $this->audio());
        $this->assertSame(['ok' => true, 'texto' => 'Ver las reservas de julio'], $result);
        $options = Http::$options[0];
        $this->assertSame(OpenAITranscriptor::ENDPOINT, $options['url']);
        $post = $options['options'][CURLOPT_POSTFIELDS];
        $this->assertInstanceOf(\CURLFile::class, $post['file']);
        $this->assertSame('consulta.wav', $post['file']->getPostFilename());
        $this->assertSame('gpt-transcribe', $post['model']);
        $this->assertSame('es', $post['languages[]']);
        $this->assertArrayNotHasKey('language', $post);
        $this->assertArrayNotHasKey('prompt', $post);
        $this->assertSame(60, $options['options'][CURLOPT_TIMEOUT]);
        $this->assertTrue($options['options'][CURLOPT_SSL_VERIFYPEER]);
        $this->assertFalse($options['options'][CURLOPT_FOLLOWLOCATION]);
        $this->assertSame(1, Http::$closed);
        $this->assertFileExists($this->file); // Original PHP temporary upload; never moved to web or DB.
        $this->assertStringNotContainsString('canary-audio-private-key', json_encode($result));
    }
    public function testValidaArchivoRealAntesDeLlamarAlProveedor()
    {
        foreach (['<html>archivo falso</html>', str_repeat('x', ConfiguracionAudio::MAX_BYTES + 1), ''] as $contents) {
            file_put_contents($this->file, $contents); clearstatcache(true, $this->file);
            try { (new OpenAITranscriptor())->transcribir($this->admin(), $this->audio()); $this->fail('Archivo inválido.'); }
            catch (\yii\web\UnprocessableEntityHttpException $e) { $this->assertSame(422, $e->statusCode); }
        }
        $this->assertSame(0, Http::$requests);
    }
    public function testErroresNoFiltranRespuestaYNoReintentan()
    {
        $provider = new OpenAITranscriptor();
        foreach ([429 => 'provider_rate_limit', 401 => 'provider_configuration', 503 => 'provider_unavailable', 400 => 'provider_error'] as $status => $code) {
            Http::reset(); Http::$status = $status; Http::$raw = '{"error":"secreto privado"}';
            $this->assertSame(['ok' => false, 'code' => $code], $provider->transcribir($this->admin(), $this->audio()));
            $this->assertSame(1, Http::$requests);
        }
        Http::reset(); Http::$errno = 28;
        $this->assertSame('provider_timeout', $provider->transcribir($this->admin(), $this->audio())['code']);
    }
    public function testRespuestaVaciaLargaInvalidaYConfiguracion()
    {
        foreach (['{}', '{"text":" "}', '{"text":123}', json_encode(['text' => str_repeat('a', 2001)])] as $raw) {
            Http::$raw = $raw;
            $this->assertFalse((new OpenAITranscriptor())->transcribir($this->admin(), $this->audio())['ok']);
        }
        Http::reset(); putenv('OPENAI_API_KEY=');
        $this->assertSame('provider_configuration', (new OpenAITranscriptor())->transcribir($this->admin(), $this->audio())['code']);
        $this->assertSame(0, Http::$requests);
        putenv('ASISTENTE_AUDIO_HABILITADO=0');
        $this->assertFalse(ConfiguracionAudio::leer()['habilitado']);
    }
    public function testExigeAdministradorAntesDeExaminarAudio()
    {
        $this->expectException(\yii\web\ForbiddenHttpException::class);
        (new OpenAITranscriptor())->transcribir((object) ['activo' => 1, 'nivel' => 4], $this->audio());
    }
}
