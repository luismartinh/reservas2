<?php
namespace tests\unit\models;

require_once dirname(__DIR__, 2) . '/_support/AsistenteFixtures.php';

use app\components\asistente\Acceso;
use app\components\asistente\AsistenteService;
use app\components\asistente\Configuracion;
use app\components\asistente\ContextoConversacion;
use app\components\asistente\FuncionesReservas;
use app\components\asistente\Limites;
use tests\support\GeminiAsistenteSimulado as Transport;
use Yii;

class AsistenteTest extends \Codeception\Test\Unit
{
    use \tests\support\RuntimeAsistenteAislado;

    protected function _before()
    {
        $this->iniciarRuntimeAsistente();
        Yii::$app->controller = new \yii\web\Controller('asistente', Yii::$app);
    }

    protected function _after()
    {
        $this->cerrarRuntimeAsistente();
    }

    public function testRuntimeDePruebasEstaAisladoDelServidorWeb()
    {
        $this->assertSame($this->runtimePruebas, Yii::getAlias('@runtime'));
        $this->assertNotSame($this->runtimeOriginal, Yii::getAlias('@runtime'));
        $limits = new Limites();
        $limits->adquirir('runtime-isolation-test');
        $limits->liberar();
        $this->assertDirectoryExists($this->runtimePruebas . '/asistente-limites');
    }

    public function testPermisosPorNivelYActivo()
    {
        $this->assertFalse(Acceso::permitido(null));
        foreach ([0, 1, 2, 3, 4, 5] as $level) {
            $this->assertSame($level <= 1, Acceso::permitido((object) ['nivel' => $level, 'activo' => 1]));
        }
        $this->assertFalse(Acceso::permitido((object) ['nivel' => 0, 'activo' => 0]));
        $this->expectException(\yii\web\ForbiddenHttpException::class);
        (new FuncionesReservas())->consultarDisponibilidad(null, ['desde' => '2026-11-10', 'hasta' => '2026-11-15']);
    }

    public function testFechasEstrictasSinNormalizar()
    {
        $functions = new FuncionesReservas();
        $this->assertSame(['desde' => '2028-02-29', 'hasta' => '2028-02-29'],
            $functions->validar(['desde' => '2028-02-29', 'hasta' => '2028-02-29']));
        foreach ([['2026-02-29', '2026-03-01'], ['2026-04-31', '2026-05-01'],
            ['2026-11-20', '2026-11-16'], ['0000-01-01', '2026-01-01'],
            ['10-11-2026', '2026-11-15'], ['', '2026-11-15'], [[], '2026-11-15']] as $range) {
            try {
                $functions->validar(['desde' => $range[0], 'hasta' => $range[1]]);
                $this->fail('Accepted an invalid date/range');
            } catch (\InvalidArgumentException $e) {
                $this->assertNotEmpty($e->getMessage());
            }
        }
        $this->expectException(\InvalidArgumentException::class);
        $functions->validar(['desde' => '2026-11-10', 'hasta' => '2026-11-15', 'sql' => 'SELECT 1']);
    }

    public function testUnidadCompletaRecorteCaducidadYPertenencia()
    {
        $session = new class {
            public $values = [];
            public function get($key) { return $this->values[$key] ?? null; }
            public function set($key, $value) { $this->values[$key] = $value; }
        };
        $config = Configuracion::leer();
        $context = new ContextoConversacion($session, $config);
        $state = $context->nueva(10, 1000);
        $turn = ['visible' => [['role' => 'assistant', 'text' => 'Yii factual']], 'messages' => [
            ['role' => 'assistant', 'tool_calls' => [['id' => 'a']]], ['role' => 'tool', 'tool_call_id' => 'a']]];
        for ($i = 0; $i < 8; $i++) {
            $state = $context->guardar(10, $state, $turn, 1001 + $i);
        }
        $this->assertCount(6, $state['turns']);
        $this->assertTrue($state['recortado']);
        $this->assertCount(2, $state['turns'][0]['messages']);
        $this->assertArrayNotHasKey('messages', $context->visible($state));
        $failedState = $state;
        $failedState['turns'] = [['code' => 'provider_error', 'visible' => [
            ['role' => 'user', 'text' => 'Consulta'], ['role' => 'assistant', 'text' => 'Fallo de comunicación']]]];
        $visible = $context->visible($failedState)['mensajes'];
        $this->assertArrayNotHasKey('error', $visible[0]);
        $this->assertTrue($visible[1]['error']);
        $this->assertArrayNotHasKey('error', $context->visible($state)['mensajes'][0]);
        foreach ([[11, $state['id'], 1008], [10, str_repeat('a', 32), 1008],
            [10, $state['id'], 1008 + $config['ttl']]] as $input) {
            try {
                $context->exigir($input[0], $input[1], null, $input[2]);
                $this->fail('Accepted another/expired conversation');
            } catch (\yii\web\ConflictHttpException $e) {
                $this->assertSame(409, $e->statusCode);
            }
        }
        $large = $turn;
        $large['messages'][] = ['text' => str_repeat('x', 30000)];
        $state = $context->guardar(10, $state, $large, 1009);
        $this->assertLessThanOrEqual(24576, strlen(json_encode($state)));
        $this->assertEmpty($state['turns']);
        $context->nueva(10, 1010);
        $this->expectException(\yii\web\ConflictHttpException::class);
        $context->guardar(10, $state, $turn, 1011); // late result must never resurrect old history
    }

    public function testContinuidadFirmasIdsYConsultaNueva()
    {
        Transport::reset();
        $functions = new class extends FuncionesReservas {
            public $queries = 0;
            public function consultarDisponibilidad($identity, array $args) {
                Acceso::exigir($identity);
                $this->queries++;
                return ['ok' => true, 'desde' => $args['desde'], 'hasta' => $args['hasta'], 'text' => 'Resultado factual Yii'];
            }
        };
        $service = new AsistenteService(new Transport(), $functions);
        $now = new \DateTimeImmutable('2026-10-23 12:00:00', new \DateTimeZone('America/Argentina/Buenos_Aires'));
        $identity = function () { return (object) ['nivel' => 1, 'activo' => 1]; };
        $first = $service->responder(['turns' => []], 'Del 10 al 15 de noviembre de 2026', $now, $identity);
        Transport::$response['data']['candidates'][0]['content']['parts'][0]['functionCall']['args'] =
            ['desde' => '2026-11-16', 'hasta' => '2026-11-20'];
        $second = $service->responder(['turns' => [$first]], '¿Y del 16 al 20?', $now, $identity);
        $this->assertSame('ok', $second['code']);
        $this->assertSame(2, $functions->queries);
        $payload = Transport::$payloads[1];
        $this->assertSame('opaque-signature', $payload['contents'][1]['parts'][0]['thoughtSignature']);
        $this->assertSame('call-fixture', $payload['contents'][2]['parts'][0]['functionResponse']['id']);
        $this->assertSame('Resultado factual Yii', $payload['contents'][3]['parts'][0]['text']);
        $this->assertStringContainsString('2026-10-23', $payload['systemInstruction']['parts'][0]['text']);
        $this->assertStringNotContainsString('controladores:', json_encode($payload));
    }

    public function testErroresNoEjecutanOperacionesParcialesNiMuestranProsaDelModelo()
    {
        $functions = new class extends FuncionesReservas {
            public $queries = 0;
            public function consultarDisponibilidad($identity, array $args) { $this->queries++; throw new \RuntimeException('private-data'); }
        };
        $service = new AsistenteService(new Transport(), $functions);
        $now = new \DateTimeImmutable();
        $identity = function () { return (object) ['nivel' => 1, 'activo' => 1]; };
        Transport::reset();
        $call = Transport::$response['data']['candidates'][0]['content']['parts'][0];
        $cases = [
            ['parts' => [$call, $call], 'code' => 'unsupported_calls'],
            ['parts' => [['functionCall' => ['id' => 'x', 'name' => 'sql', 'args' => []]]], 'code' => 'unsupported_calls'],
            ['parts' => [['functionCall' => ['id' => 'x', 'name' => 'consultar_disponibilidad', 'args' => ['desde' => '2026-02-30', 'hasta' => '2026-03-01']]]], 'code' => 'invalid_arguments'],
            ['parts' => [['text' => '<script>alert(1)</script> Hay 20 cabañas libres']], 'code' => 'clarification'],
        ];
        foreach ($cases as $case) {
            Transport::$response['data']['candidates'][0]['content']['parts'] = $case['parts'];
            $turn = $service->responder(['turns' => []], 'Consulta', $now, $identity);
            $this->assertSame($case['code'], $turn['code']);
            $this->assertStringNotContainsString('<script>', $turn['visible'][1]['text']);
        }
        $this->assertSame(0, $functions->queries);
        Transport::reset();
        $turn = $service->responder(['turns' => []], 'Consulta', $now, $identity);
        $this->assertSame('query_error', $turn['code']);
        $this->assertStringNotContainsString('private-data', $turn['visible'][1]['text']);
        Transport::$response = ['st' => 'error', 'msg' => 'private-provider-error'];
        $turn = $service->responder(['turns' => []], 'Consulta', $now, $identity);
        $this->assertSame('provider_error', $turn['code']);
        $this->assertStringNotContainsString('private-provider-error', $turn['visible'][1]['text']);
        Transport::reset();
        Transport::$response['data']['candidates'][0]['finishReason'] = 'MAX_TOKENS';
        $turn = $service->responder(['turns' => []], 'Consulta', $now, $identity);
        $this->assertSame('invalid_response', $turn['code']);
    }

    public function testCuotaIndisponibilidadYTimeoutSeDistinguenSinReintentarNiConsultarReservas()
    {
        Transport::reset();
        $service = new AsistenteService(new Transport(), new FuncionesReservas());
        $noQuery = function () { $this->fail('Un rechazo del proveedor no debe ejecutar funciones de datos.'); };
        Transport::$httpFailure = 429;
        Transport::$response = ['st' => 'error', 'msg' => 'private-response-body'];
        $turn = $service->responder(['turns' => []], 'Disponibilidad', new \DateTimeImmutable(), $noQuery);
        $this->assertSame('provider_rate_limit', $turn['code']);
        $this->assertStringContainsString('límite de uso o cuota (429)', $turn['visible'][1]['text']);
        $this->assertStringContainsString('Google AI Studio', $turn['visible'][1]['text']);
        $this->assertStringNotContainsString('private-response-body', json_encode($turn));
        $this->assertCount(1, Transport::$payloads);
        $log = file_get_contents(Yii::getAlias('@runtime/asistente-diagnostics.log'));
        $this->assertStringContainsString('provider_rate_limit', $log);
        $this->assertStringNotContainsString('private-response-body', $log);
        // Same provider instance must not retain a previous HTTP failure classification.
        Transport::$httpFailure = 503;
        $turn = $service->responder(['turns' => []], 'Otra consulta', new \DateTimeImmutable(), $noQuery);
        $this->assertSame('provider_unavailable', $turn['code']);
        $this->assertStringContainsString('no está disponible temporalmente', $turn['visible'][1]['text']);
        Transport::$httpFailure = 0;
        Transport::$curlFailure = 28;
        $turn = $service->responder(['turns' => []], 'Otra consulta', new \DateTimeImmutable(), $noQuery);
        $this->assertSame('provider_timeout', $turn['code']);
        $this->assertStringContainsString('Se agotó el tiempo de espera', $turn['visible'][1]['text']);
        Transport::$curlFailure = 0;
        $turn = $service->responder(['turns' => []], 'Otra consulta', new \DateTimeImmutable(), $noQuery);
        $this->assertSame('provider_error', $turn['code']);
        $this->assertCount(4, Transport::$payloads);
        Transport::reset();
    }

    public function testLimitesAtomicosExclusionVentanasYLiberacion()
    {
        $id = 'test-' . bin2hex(random_bytes(8));
        $a = new Limites();
        $b = new Limites();
        $config = Configuracion::leer();
        $config['porMinuto'] = 1;
        $config['porDia'] = 2;
        $now = new \DateTimeImmutable('2026-10-23 23:58:00', new \DateTimeZone(Yii::$app->timeZone));
        $a->adquirir($id);
        try {
            $b->adquirir($id);
            $this->fail('Two locks for one user');
        } catch (\yii\web\TooManyRequestsHttpException $e) {
            $this->assertSame(429, $e->statusCode);
        }
        $a->consumir($config, $now);
        try {
            $a->consumir($config, $now);
            $this->fail('Exceeded minute quota');
        } catch (\yii\web\TooManyRequestsHttpException $e) { $this->assertSame(429, $e->statusCode); }
        $a->consumir($config, $now->modify('+1 minute'));
        try {
            $a->consumir($config, $now->modify('+90 seconds'));
            $this->fail('Exceeded daily quota');
        } catch (\yii\web\TooManyRequestsHttpException $e) { $this->assertSame(429, $e->statusCode); }
        $a->liberar();
        $b->adquirir($id);
        $b->consumir($config, $now->modify('+2 minutes'));
        $b->liberar();
    }

    public function testContenidoNativoGrandeSeRechazaCompletoYUsoDesconocidoEsNull()
    {
        Transport::reset();
        $provider = new Transport();
        $response = Transport::$response['data'];
        $normalized = $provider->normalizar($response);
        $this->assertNull($normalized['usage']);
        $response['usageMetadata'] = ['totalTokenCount' => 123];
        $this->assertSame(123, $provider->normalizar($response)['usage']);
        $response['candidates'][0]['content']['parts'] = [
            ['text' => 'No inventar resultados', 'thoughtSignature' => str_repeat('x', 30000)],
        ];
        Transport::$response['data'] = $response;
        $service = new AsistenteService($provider, new FuncionesReservas());
        $this->expectException(\yii\web\HttpException::class);
        $this->expectExceptionCode(0); // HttpException's HTTP status is separate from its exception code.
        $service->responder(['turns' => []], 'Disponibilidad', new \DateTimeImmutable(), function () {
            $this->fail('A clarification must not execute a data function');
        });
    }
}
