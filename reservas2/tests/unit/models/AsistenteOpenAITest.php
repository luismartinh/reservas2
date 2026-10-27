<?php
namespace tests\unit\models;

require_once dirname(__DIR__, 2) . '/_support/AsistenteFixtures.php';

use app\components\asistente\AsistenteService;
use app\components\asistente\Configuracion;
use app\components\asistente\ContextoConversacion;
use app\components\asistente\EntornoPrivado;
use app\components\asistente\FuncionesReservas;
use app\components\asistente\GeminiProvider;
use app\components\asistente\OpenAIProvider;
use app\components\asistente\ProveedorFactory;
use tests\support\OpenAIHttp as Http;
use Yii;

class AsistenteOpenAITest extends \Codeception\Test\Unit
{
    use \tests\support\RuntimeAsistenteAislado;
    private $environment;

    protected function _before()
    {
        $this->iniciarRuntimeAsistente();
        $this->environment = [];
        foreach (['OPENAI_API_KEY', 'ASISTENTE_PROVEEDOR', 'ASISTENTE_OPENAI_MODELO'] as $name) {
            $this->environment[$name] = getenv($name);
        }
        putenv('OPENAI_API_KEY=canary-openai-key');
        putenv('ASISTENTE_PROVEEDOR=gemini');
        putenv('ASISTENTE_OPENAI_MODELO=gpt-5.4-mini');
        Http::reset();
    }

    protected function _after()
    {
        foreach ($this->environment as $name => $value) { putenv($value === false ? $name : $name . '=' . $value); }
        $this->cerrarRuntimeAsistente();
    }

    public function testTransporteHTTPSYContratoComunSinClaveEnPayloadNiLogs()
    {
        $provider = new OpenAIProvider();
        $result = $provider->interpretar([['role' => 'user', 'text' => 'Consulta privada de prueba']], new \DateTimeImmutable('2086-10-26'));
        $this->assertTrue($result['ok']);
        $this->assertSame('call-openai', $result['tool_calls'][0]['id']);
        $this->assertSame(123, $result['usage']);
        $request = Http::$options[0];
        $options = $request['options'];
        $this->assertSame(OpenAIProvider::ENDPOINT, $request['url']);
        $this->assertContains('Authorization: Bearer canary-openai-key', $options[CURLOPT_HTTPHEADER]);
        $this->assertFalse($options[CURLOPT_FOLLOWLOCATION]);
        $this->assertTrue($options[CURLOPT_SSL_VERIFYPEER]);
        $this->assertSame(2, $options[CURLOPT_SSL_VERIFYHOST]);
        $this->assertSame(60, $options[CURLOPT_TIMEOUT]);
        $this->assertSame(5, $options[CURLOPT_CONNECTTIMEOUT]);
        $payload = json_decode($options[CURLOPT_POSTFIELDS], true);
        $this->assertSame('gpt-5.4-mini', $payload['model']);
        $this->assertFalse($payload['store']);
        $this->assertFalse($payload['parallel_tool_calls']);
        $this->assertCount(18, $payload['tools']);
        $this->assertFalse($payload['tools'][0]['strict']);
        $this->assertSame('object', $payload['tools'][0]['parameters']['type']);
        $this->assertStringNotContainsString('canary-openai-key', $options[CURLOPT_POSTFIELDS]);
        $this->assertStringNotContainsString('canary-openai-key', json_encode($result));
        $this->assertSame(1, Http::$requests);
        $this->assertSame(1, Http::$closed);
        $log = file_get_contents(Yii::getAlias('@runtime/openai-diagnostics.log'));
        $this->assertStringNotContainsString('canary-openai-key', $log);
        $this->assertStringNotContainsString('Consulta privada de prueba', $log);
    }

    public function testReplayConservaCallIdEItemsOpacosYSoloDatosInternos()
    {
        $provider = new OpenAIProvider();
        $raw = json_decode(Http::$raw, true);
        array_unshift($raw['output'], ['type' => 'reasoning', 'id' => 'rs-fixture', 'summary' => [], 'encrypted_content' => 'opaque-encrypted-state']);
        Http::$raw = json_encode($raw);
        $first = $provider->interpretar([['role' => 'user', 'text' => 'Disponibilidad']], new \DateTimeImmutable());
        $messages = [['role' => 'user', 'text' => 'Disponibilidad'],
            ['role' => 'assistant', 'text' => '', 'provider_state' => $first['provider_state']],
            ['role' => 'tool', 'name' => 'consultar_disponibilidad', 'tool_call_id' => 'call-openai', 'data' => ['ok' => true, 'cabanas' => []]],
            ['role' => 'assistant', 'text' => 'Resultado de Yii'], ['role' => 'user', 'text' => 'Sus precios']];
        $this->assertTrue($provider->interpretar($messages, new \DateTimeImmutable())['ok']);
        $payload = json_decode(Http::$options[1]['options'][CURLOPT_POSTFIELDS], true);
        $this->assertSame($raw['output'][0], $payload['input'][1]);
        $this->assertSame($raw['output'][1], $payload['input'][2]);
        $this->assertSame('function_call_output', $payload['input'][3]['type']);
        $this->assertSame('call-openai', $payload['input'][3]['call_id']);
        $this->assertSame(['ok' => true, 'cabanas' => []], json_decode($payload['input'][3]['output'], true));
        $before = Http::$requests;
        $this->assertFalse($provider->interpretar([['provider_state' => ['gemini' => []]]], new \DateTimeImmutable())['ok']);
        $this->assertSame($before, Http::$requests);
    }

    public function testRespuestasIncompletasArgumentosInvalidosYAccionesDesconocidas()
    {
        $provider = new OpenAIProvider();
        $valid = json_decode(Http::$raw, true);
        foreach (['incomplete', 'failed', 'cancelled'] as $status) {
            $bad = $valid; $bad['status'] = $status;
            $this->assertSame('invalid_response', $provider->normalizar($bad)['code']);
        }
        foreach (['[]', '{broken', 'null', '"text"'] as $arguments) {
            $bad = $valid; $bad['output'][0]['arguments'] = $arguments;
            $this->assertSame('invalid_response', $provider->normalizar($bad)['code']);
        }
        $bad = $valid; $bad['output'][] = $bad['output'][0];
        $this->assertSame('invalid_response', $provider->normalizar($bad)['code']);
        $functions = new class extends FuncionesReservas {
            public $queries = 0;
            public function consultarDisponibilidad($identity, array $args) { $this->queries++; return ['ok' => true, 'text' => 'Resultado comprobado']; }
        };
        $service = new AsistenteService($provider, $functions, 24576, ['provider' => 'openai', 'model' => 'gpt-5.4-mini']);
        $auth = function () { return (object) ['nivel' => 1, 'activo' => 1]; };
        $raw = $valid; $raw['output'][0]['name'] = 'execute_sql'; Http::$raw = json_encode($raw);
        $this->assertSame('unsupported_calls', $service->responder(['turns' => []], 'Consulta', new \DateTimeImmutable(), $auth)['code']);
        $raw = $valid; $second = $raw['output'][0]; $second['call_id'] = 'call-two'; $raw['output'][] = $second; Http::$raw = json_encode($raw);
        $this->assertSame('unsupported_calls', $service->responder(['turns' => []], 'Consulta', new \DateTimeImmutable(), $auth)['code']);
        Http::$raw = json_encode(['object' => 'response', 'status' => 'completed',
            'output' => [['type' => 'message', 'role' => 'assistant', 'content' => [['type' => 'output_text', 'text' => 'Inventado: hay veinte cabañas libres']]]]]);
        $turn = $service->responder(['turns' => []], 'Consulta', new \DateTimeImmutable(), $auth);
        $this->assertSame('clarification', $turn['code']);
        $this->assertStringNotContainsString('Inventado', $turn['visible'][1]['text']);
        $this->assertSame(0, $functions->queries);
        Http::$raw = json_encode($valid);
        $turn = $service->responder(['turns' => []], 'Consulta', new \DateTimeImmutable(), $auth);
        $this->assertSame('ok', $turn['code']);
        $this->assertSame(1, $functions->queries);
        $log = file_get_contents(Yii::getAlias('@runtime/asistente-diagnostics.log'));
        $this->assertStringContainsString('"provider":"openai"', $log);
        $this->assertStringContainsString('"model":"gpt-5.4-mini"', $log);
    }

    public function testErroresDelTransporteSinFiltracionNiReintentos()
    {
        $provider = new OpenAIProvider();
        foreach ([429 => 'provider_rate_limit', 503 => 'provider_unavailable', 401 => 'provider_configuration',
            403 => 'provider_configuration', 400 => 'provider_error', 302 => 'provider_error', 504 => 'provider_timeout'] as $status => $code) {
            Http::reset(); Http::$status = $status; Http::$raw = 'canary-openai-key private-response-body';
            $result = $provider->interpretar([['role' => 'user', 'text' => 'Consulta']], new \DateTimeImmutable());
            $this->assertSame($code, $result['code']);
            $this->assertSame(1, Http::$requests);
            $this->assertSame(1, Http::$closed);
            $this->assertStringNotContainsString('private-response-body', json_encode($result));
        }
        Http::reset(); Http::$raw = false; Http::$errno = 28;
        $this->assertSame('provider_timeout', $provider->interpretar([], new \DateTimeImmutable())['code']);
        Http::reset(); Http::$raw = 'not JSON';
        $this->assertSame('invalid_response', $provider->interpretar([], new \DateTimeImmutable())['code']);
        $log = file_get_contents(Yii::getAlias('@runtime/openai-diagnostics.log'));
        $this->assertStringNotContainsString('canary-openai-key', $log);
        $this->assertStringNotContainsString('private-response-body', $log);
    }

    public function testArchivoPrivadoEntornoRotacionYConfiguracionInvalidaSinHTTP()
    {
        putenv('OPENAI_API_KEY');
        $file = Yii::getAlias('@runtime/openai-private.env');
        file_put_contents($file, "GEMINI_API_KEY=unrelated-key\nOPENAI_API_KEY=private-key-one\nASISTENTE_PROVEEDOR=openai\n");
        putenv('ASISTENTE_PROVEEDOR');
        $this->assertSame('openai', EntornoPrivado::valor('ASISTENTE_PROVEEDOR', $file));
        putenv('ASISTENTE_PROVEEDOR=gemini');
        $this->assertSame('gemini', EntornoPrivado::valor('ASISTENTE_PROVEEDOR', $file));
        $provider = new OpenAIProvider(['apiKeyFile' => $file]);
        $this->assertTrue($provider->interpretar([], new \DateTimeImmutable())['ok']);
        $this->assertContains('Authorization: Bearer private-key-one', Http::$options[0]['options'][CURLOPT_HTTPHEADER]);
        file_put_contents($file, "OPENAI_API_KEY=private-key-two\n");
        $this->assertTrue($provider->interpretar([], new \DateTimeImmutable())['ok']);
        $this->assertContains('Authorization: Bearer private-key-two', Http::$options[1]['options'][CURLOPT_HTTPHEADER]);
        putenv('OPENAI_API_KEY=environment-key');
        $provider->interpretar([], new \DateTimeImmutable());
        $this->assertContains('Authorization: Bearer environment-key', Http::$options[2]['options'][CURLOPT_HTTPHEADER]);
        foreach (['', 'invalid key', "invalid\nheader"] as $key) {
            putenv('OPENAI_API_KEY=' . $key);
            $before = Http::$requests;
            $this->assertSame('provider_configuration', $provider->interpretar([], new \DateTimeImmutable())['code']);
            $this->assertSame($before, Http::$requests);
        }
        putenv('OPENAI_API_KEY');
        foreach (["OPENAI_API_KEY=a\nOPENAI_API_KEY=b\n", "OPENAI_API_KEY='unclosed\n", 'OPENAI_API_KEY=$EXPANSION'] as $contents) {
            file_put_contents($file, $contents);
            $this->assertSame('provider_configuration', $provider->interpretar([], new \DateTimeImmutable())['code']);
        }
    }

    public function testSeleccionPorEntornoFallaCerradoYCambioDeProveedorInvalidaConversacion()
    {
        $config = Configuracion::leer();
        $this->assertInstanceOf(GeminiProvider::class, ProveedorFactory::crear($config));
        $session = new class {
            public $value;
            public function get($key) { return $this->value; }
            public function set($key, $value) { $this->value = $value; }
        };
        $gemini = new ContextoConversacion($session, $config);
        $old = $gemini->nueva(1, time());
        putenv('ASISTENTE_PROVEEDOR=openai');
        $openaiConfig = Configuracion::leer();
        $this->assertInstanceOf(OpenAIProvider::class, ProveedorFactory::crear($openaiConfig));
        $openai = new ContextoConversacion($session, $openaiConfig);
        try { $openai->exigir(1, $old['id']); $this->fail('Una conversación Gemini no puede continuar en OpenAI.'); }
        catch (\yii\web\ConflictHttpException $e) { $this->assertSame(409, $e->statusCode); }
        $new = $openai->actual(1);
        $this->assertNotSame($old['id'], $new['id']);
        $this->assertSame([], $new['turns']);
        putenv('ASISTENTE_OPENAI_MODELO=gpt-5.4');
        $changedModel = new ContextoConversacion($session, Configuracion::leer());
        $this->assertNotSame($new['id'], $changedModel->actual(1)['id']);
        putenv('ASISTENTE_PROVEEDOR=untrusted-provider');
        $this->expectException(\InvalidArgumentException::class);
        Configuracion::leer();
    }

    public function testRevocaPermisoDuranteOpenAIYNoConsultaDatos()
    {
        $authorized = true;
        Http::$duringRequest = function () use (&$authorized) { $authorized = false; };
        $functions = new class extends FuncionesReservas {
            public function consultarDisponibilidad($identity, array $args) { throw new \RuntimeException('No debe ejecutar una consulta tras revocar acceso.'); }
        };
        $service = new AsistenteService(new OpenAIProvider(), $functions, 24576, ['provider' => 'openai', 'model' => 'gpt-5.4-mini']);
        $this->expectException(\yii\web\ForbiddenHttpException::class);
        $service->responder(['turns' => []], 'Disponibilidad', new \DateTimeImmutable(), function () use (&$authorized) {
            if (!$authorized) { throw new \yii\web\ForbiddenHttpException(); }
            return (object) ['nivel' => 1, 'activo' => 1];
        });
    }

    public function testClaveOpenAINoApareceEnElContextoDelLogGeneral()
    {
        $config = require Yii::getAlias('@app/config/web.php');
        $target = new class(['logVars' => $config['components']['log']['targets'][0]['logVars']]) extends \yii\log\FileTarget {
            public function context() { return $this->getContextMessage(); }
        };
        $old = $_SERVER['OPENAI_API_KEY'] ?? null;
        $_SERVER['OPENAI_API_KEY'] = 'canary-server-openai-secret';
        try {
            $this->assertStringNotContainsString('canary-server-openai-secret', $target->context());
        } finally {
            if ($old === null) { unset($_SERVER['OPENAI_API_KEY']); } else { $_SERVER['OPENAI_API_KEY'] = $old; }
        }
    }
}
