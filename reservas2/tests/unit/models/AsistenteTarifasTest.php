<?php
namespace tests\unit\models;

require_once dirname(__DIR__, 2) . '/_support/AsistenteFixtures.php';

use app\components\asistente\AsistenteService;
use app\components\asistente\FuncionesReservas;
use app\models\Cabana;
use app\models\CabanaTarifa;
use app\models\Tarifa;
use tests\support\GeminiAsistenteSimulado as Transport;
use Yii;

class AsistenteTarifasTest extends \Codeception\Test\Unit
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

    public function testDiasFacturablesSegmentosYFaltaDeCobertura()
    {
        $cabana = $this->cabana();
        $this->tarifa($cabana, '2026-11-01', '2026-11-03', 100);
        $this->tarifa($cabana, '2026-11-04', '2026-11-05', 150);
        $functions = new FuncionesReservas();
        $args = ['origen' => 'seleccionadas', 'numeros_cabanas' => [(int) $cabana->numero],
            'desde' => '2026-11-01', 'hasta' => '2026-11-05'];
        $data = $functions->consultarTarifas($this->admin(), $args);
        $this->assertSame(4, $data['dias']);
        $this->assertSame(450.0, $data['cabanas'][0]['total']);
        $this->assertSame(112.5, $data['cabanas'][0]['promedio_diario']);
        $this->assertSame([1], $data['cabanas'][0]['min_dias_aplicados']);
        $this->assertSame('$ 112,50', $data['visible_presentation']['rows'][0]['promedio']);
        $this->assertSame('01/11/2026 15:00', $data['visible_presentation']['rows'][0]['ingreso']);
        $this->assertStringContainsString('Total: $ 450,00', $data['text']);
        // The site's inclusive helper retains its original result for the same raw dates.
        $this->assertSame(600.0, CabanaTarifa::calcularTotalParaCabana($cabana->id, '2026-11-01', '2026-11-05'));
        $daily = $functions->consultarTarifas($this->admin(), $args + ['detalle' => 'diario']);
        $this->assertCount(2, $daily['cabanas'][0]['tramos_diarios']);
        $this->assertSame(450.0, $daily['cabanas'][0]['total']);
        $this->assertStringContainsString('01/11/2026 al 03/11/2026: $ 100,00 por día', $daily['text']);
        $this->assertStringContainsString('04/11/2026 al 04/11/2026: $ 150,00 por día', $daily['text']);
        $this->assertStringNotContainsString('120,00 por día', $daily['text']); // total/days is only an average
        $this->assertStringNotContainsString('Total: $', $daily['text']);
        $args['hasta'] = '2026-11-07';
        $data = $functions->consultarTarifas($this->admin(), $args);
        $this->assertNull($data['cabanas'][0]['total']);
        $this->assertNull($data['cabanas'][0]['promedio_diario']);
        $this->assertSame('—', $data['visible_presentation']['rows'][0]['promedio']);
        $this->assertFalse($data['cabanas'][0]['tarifa_completa']);
        $this->assertStringContainsString('Sin tarifa completa', $data['text']);
        $this->assertStringNotContainsString('$ -1', $data['text']);
        $daily = $functions->consultarTarifas($this->admin(), $args + ['detalle' => 'diario']);
        $this->assertStringContainsString('Sin tarifa completa', $daily['text']);
        $this->assertArrayNotHasKey('tramos_diarios', $daily['cabanas'][0]);
        $inactive = $this->tarifa($cabana, '2026-11-06', '2026-11-06', 999);
        $inactive->activa = 0;
        $this->save($inactive);
        $this->assertNull($functions->consultarTarifas($this->admin(), $args)['cabanas'][0]['total']);
    }

    public function testAnterioresConservaSeleccionYPeriodoYRecalculaLosPrecios()
    {
        $cabana = $this->cabana();
        $promo = $this->tarifa($cabana, '2026-11-15', '2026-11-30', 80, 15);
        $previous = ['ok' => true, 'desde' => '2026-11-15', 'hasta' => '2026-11-30',
            'cabanas' => [['id' => $cabana->id]], 'truncado' => true];
        $functions = new FuncionesReservas();
        $data = $functions->consultarTarifas($this->admin(), ['origen' => 'anteriores'], $previous);
        $this->assertSame(15, $data['dias']);
        $this->assertCount(1, $data['cabanas']);
        $this->assertSame(1200.0, $data['cabanas'][0]['total']);
        $daily = $functions->consultarTarifas($this->admin(), ['origen' => 'anteriores', 'detalle' => 'diario'], $data);
        $this->assertSame(80.0, $daily['cabanas'][0]['tramos_diarios'][0]['valor_dia']);
        $this->assertStringContainsString('15/11/2026 al 29/11/2026: $ 80,00 por día', $daily['text']);
        $this->assertTrue($data['truncado']);
        $promo->valor_dia = 90;
        $this->save($promo);
        $this->assertSame(1350.0, $functions->consultarTarifas($this->admin(), ['origen' => 'anteriores'], $data)['cabanas'][0]['total']);
        $selected = $functions->consultarTarifas($this->admin(),
            ['origen' => 'seleccionadas', 'numeros_cabanas' => [(int) $cabana->numero]], $data);
        $this->assertSame('2026-11-15', $selected['desde']);
        $this->assertSame(1350.0, $selected['cabanas'][0]['total']);
    }

    public function testMinimoDiasEligeMayorUmbralValidoDelPeriodoActivo()
    {
        $cabana = $this->cabana();
        $base = $this->tarifa($cabana, '2026-12-01', '2026-12-20', 15000, 1);
        $this->tarifa($cabana, '2026-12-01', '2026-12-20', 10000, 3, true);
        $inactive = $this->tarifa($cabana, '2026-12-01', '2026-12-20', 1, 4, true);
        $inactive->activa = 0;
        $this->save($inactive);
        $functions = new FuncionesReservas();
        foreach ([1 => [15000.0, 15000.0], 2 => [15000.0, 30000.0],
            3 => [10000.0, 30000.0], 4 => [10000.0, 40000.0], 5 => [10000.0, 50000.0]] as $days => $expected) {
            $args = ['origen' => 'seleccionadas', 'numeros_cabanas' => [(int) $cabana->numero],
                'desde' => '2026-12-03', 'hasta' => sprintf('2026-12-%02d', 3 + $days), 'detalle' => 'diario'];
            $result = $functions->consultarTarifas($this->admin(), $args);
            $this->assertSame($days, $result['dias']);
            $this->assertSame($expected[0], $result['cabanas'][0]['tramos_diarios'][0]['valor_dia']);
            $this->assertSame($expected[1], $result['cabanas'][0]['total']);
            $this->assertSame([$days < 3 ? 1 : 3], $result['cabanas'][0]['min_dias_aplicados']);
            $this->assertSame($expected[0], $result['cabanas'][0]['promedio_diario']);
        }
        $base->activa = 0;
        $this->save($base);
        $args['hasta'] = '2026-12-04';
        $missing = $functions->consultarTarifas($this->admin(), $args);
        $this->assertNull($missing['cabanas'][0]['total']);
        $this->assertFalse($missing['cabanas'][0]['tarifa_completa']);
        $this->assertArrayNotHasKey('tramos_diarios', $missing['cabanas'][0]);
        $this->assertStringContainsString('Sin tarifa completa', $missing['text']);
        // Legacy site keeps its previous fallback; this rule changes only the assistant.
        $this->assertSame(10000.0, CabanaTarifa::calcularTotalParaCabana($cabana->id, '2026-12-03', '2026-12-03'));
        $args['desde'] = '2026-12-20';
        $args['hasta'] = '2026-12-23';
        $this->assertNull($functions->consultarTarifas($this->admin(), $args)['cabanas'][0]['total']);
    }

    public function testMinimoDiasUsaDuracionTotalConPromocionParcialYRespetaFinInclusivo()
    {
        $cabana = $this->cabana();
        $this->tarifa($cabana, '2026-12-01', '2026-12-20', 15000, 1);
        $this->tarifa($cabana, '2026-12-04', '2026-12-05', 10000, 3, true);
        $functions = new FuncionesReservas();
        $args = ['origen' => 'seleccionadas', 'numeros_cabanas' => [(int) $cabana->numero],
            'desde' => '2026-12-03', 'hasta' => '2026-12-06', 'detalle' => 'diario'];
        $result = $functions->consultarTarifas($this->admin(), $args);
        $this->assertSame(35000.0, $result['cabanas'][0]['total']);
        $this->assertSame([15000.0, 10000.0], array_column($result['cabanas'][0]['tramos_diarios'], 'valor_dia'));
        $this->assertSame([1, 2], array_column($result['cabanas'][0]['tramos_diarios'], 'dias'));
        $this->assertSame([1, 3], $result['cabanas'][0]['min_dias_aplicados']);
        $this->assertStringContainsString('min_dias por tramo: 03/12/2026 al 03/12/2026: 1; 04/12/2026 al 05/12/2026: 3', $result['cabanas'][0]['criterio_tarifa']);
        $this->assertStringContainsString('04/12/2026 al 05/12/2026: $ 10.000,00 por día', $result['text']);
        // A two-day stay does not qualify even if both days are in the promotion's dates.
        $args['desde'] = '2026-12-04';
        $this->assertSame(30000.0, $functions->consultarTarifas($this->admin(), $args)['cabanas'][0]['total']);
        $args['desde'] = '2026-12-20';
        $args['hasta'] = '2026-12-21';
        $this->assertSame(15000.0, $functions->consultarTarifas($this->admin(), $args)['cabanas'][0]['total']);
    }

    public function testSinSeleccionNoSeConvierteEnTodasLasCabanasYPermisos()
    {
        $functions = new FuncionesReservas();
        $data = $functions->consultarTarifas($this->admin(), ['origen' => 'anteriores'],
            ['ok' => true, 'desde' => '2026-11-15', 'hasta' => '2026-11-30', 'cabanas' => []]);
        $this->assertSame([], $data['cabanas']);
        $this->expectException(\yii\web\ForbiddenHttpException::class);
        $functions->consultarTarifas((object) ['nivel' => 2, 'activo' => 1], ['origen' => 'anteriores']);
    }

    public function testTarifasDeDisponiblesConsultaLaSeleccionActual()
    {
        Cabana::updateAll(['activa' => 0]);
        $cabana = $this->cabana();
        $this->tarifa($cabana, '2026-11-15', '2026-11-30', 100);
        $functions = new FuncionesReservas();
        $args = ['origen' => 'disponibles', 'desde' => '2026-11-15', 'hasta' => '2026-11-30'];
        $data = $functions->consultarTarifas($this->admin(), $args);
        $this->assertCount(1, $data['cabanas']);
        $this->assertSame((int) $cabana->id, (int) $data['cabanas'][0]['id']);
        $this->assertSame(1500.0, $data['cabanas'][0]['total']);
        $cabana->activa = 0;
        $this->save($cabana);
        $this->assertSame([], $functions->consultarTarifas($this->admin(), $args)['cabanas']);
    }

    public function testArgumentosInvalidosSeRechazanAntesDeConsultar()
    {
        $functions = new FuncionesReservas();
        foreach ([['origen' => 'anteriores'],
            ['origen' => 'anteriores', 'desde' => '2026-11-15'],
            ['origen' => 'disponibles', 'desde' => '2026-02-30', 'hasta' => '2026-03-01'],
            ['origen' => 'seleccionadas', 'numeros_cabanas' => ['1']],
            ['origen' => 'seleccionadas', 'numeros_cabanas' => []],
            ['origen' => 'seleccionadas', 'numeros_cabanas' => [1], 'desde' => '2026-11-15'],
            ['origen' => 'disponibles', 'desde' => '2026-11-15', 'hasta' => '2026-11-30', 'detalle' => 'arbitrario'],
            ['origen' => 'disponibles', 'desde' => '2026-11-15', 'hasta' => '2026-11-30', 'detalle' => null],
            ['origen' => 'disponibles', 'desde' => '2026-11-15', 'hasta' => '2026-11-30', 'pax' => 0],
            ['origen' => 'disponibles', 'desde' => '2026-11-15', 'hasta' => '2026-11-30', 'pax' => '3'],
            ['origen' => 'disponibles', 'desde' => '2026-11-15', 'hasta' => '2026-11-30', 'pax' => null],
            ['origen' => 'anteriores', 'pax' => 3],
            ['origen' => 'seleccionadas', 'numeros_cabanas' => [1], 'desde' => '2026-11-15', 'hasta' => '2026-11-30', 'pax' => 3],
            ['origen' => 'disponibles', 'sql' => 'SELECT 1']] as $args) {
            try {
                $functions->validarTarifas($args);
                $this->fail('Accepted invalid tariff arguments');
            } catch (\InvalidArgumentException $e) {
                $this->assertNotEmpty($e->getMessage());
            }
        }
    }

    public function testContinuacionUsaSoloContextoDeFuncionesYConservaFirma()
    {
        $cabana = $this->cabana();
        $this->tarifa($cabana, '2026-11-15', '2026-11-30', 100);
        $state = ['turns' => [['messages' => [[
            'role' => 'tool', 'name' => 'consultar_disponibilidad', 'tool_call_id' => 'prior-call',
            'data' => ['ok' => true, 'desde' => '2026-11-15', 'hasta' => '2026-11-30', 'cabanas' => [['id' => $cabana->id]]],
        ]]]]];
        Transport::reset();
        $call = &Transport::$response['data']['candidates'][0]['content']['parts'][0]['functionCall'];
        $call['name'] = 'consultar_tarifas';
        $call['args'] = ['origen' => 'anteriores'];
        $service = new AsistenteService(new Transport(), new FuncionesReservas());
        $turn = $service->responder($state, '¿Puedes darme los valores de cada una?', new \DateTimeImmutable(), function () { return $this->admin(); });
        $this->assertSame('ok', $turn['code']);
        $this->assertStringContainsString('Total: $ 1.500,00', $turn['visible'][1]['text']);
        $this->assertSame('tarifas', $turn['visible'][1]['presentation']['type']);
        $this->assertArrayNotHasKey('visible_presentation', $turn['messages'][2]['data']);
        $this->assertSame('opaque-signature', $turn['messages'][1]['provider_state']['gemini']['parts'][0]['thoughtSignature']);
        $this->assertCount(1, Transport::$payloads);
        $this->assertCount(18, Transport::$payloads[0]['tools'][0]['functionDeclarations']);
        $call['args'] = ['origen' => 'anteriores', 'detalle' => 'diario'];
        $state['turns'][] = $turn;
        $daily = $service->responder($state, '¿Y el valor por día?', new \DateTimeImmutable(), function () { return $this->admin(); });
        $this->assertSame('ok', $daily['code']);
        $this->assertStringContainsString('$ 100,00 por día', $daily['visible'][1]['text']);
        $this->assertStringNotContainsString('"presentation"', json_encode(Transport::$payloads));
        $this->assertStringNotContainsString('visible_presentation', json_encode(Transport::$payloads));
        $this->assertSame('2026-11-15', $daily['messages'][2]['data']['desde']);
        $this->assertStringNotContainsString('Total: $', $daily['visible'][1]['text']);
        $turn = $service->responder(['turns' => []], 'Valores de cada una', new \DateTimeImmutable(), function () {
            $this->fail('Missing context must not query');
        });
        $this->assertSame('invalid_arguments', $turn['code']);
    }

    public function testEjemploDosDiasHorariosPropiosYMinimoUnDia()
    {
        $cabana = $this->cabana();
        // Exercise the business example independently of the existing cabin editor's validations.
        $cabana->checkin = '11:00';
        $cabana->checkout = '14:00';
        $cabana->save(false, ['checkin', 'checkout']);
        $this->tarifa($cabana, '2026-10-01', '2026-10-02', 100, 2);
        $functions = new FuncionesReservas();
        $args = ['origen' => 'seleccionadas', 'numeros_cabanas' => [(int) $cabana->numero],
            'desde' => '2026-10-01', 'hasta' => '2026-10-03'];
        $data = $functions->consultarTarifas($this->admin(), $args);
        $this->assertSame(2, $data['dias']);
        $this->assertSame(200.0, $data['cabanas'][0]['total']);
        $this->assertSame('2026-10-01 11:00:00', $data['cabanas'][0]['ingreso']);
        $this->assertSame('2026-10-03 14:00:00', $data['cabanas'][0]['egreso']);
        $args['hasta'] = '2026-10-02';
        $this->assertSame(1, $functions->consultarTarifas($this->admin(), $args)['dias']);
        $args['hasta'] = $args['desde'];
        $this->expectException(\InvalidArgumentException::class);
        $functions->consultarTarifas($this->admin(), $args);
    }

    public function testDisponibilidadYPreciosParaTresPersonasEnUnaConsultaYContinuacionDiaria()
    {
        Cabana::updateAll(['activa' => 0]);
        $small = $this->cabana();
        $small->max_pax = 2;
        $this->save($small);
        $fits = $this->cabana();
        $fits->max_pax = 3;
        $fits->descr = 'Cabaña ' . $fits->numero;
        $this->save($fits);
        $missingRate = $this->cabana();
        $occupied = $this->cabana();
        foreach ([$small, $fits, $occupied] as $cabin) {
            $this->tarifa($cabin, '2086-11-01', '2086-11-06', 100);
        }
        $suffix = bin2hex(random_bytes(5));
        $holder = new \app\models\Locador(['denominacion' => 'Privado ' . $suffix,
            'documento' => 'D' . $suffix, 'email' => $suffix . '@example.com', 'telefono' => '123456', 'domicilio' => 'Privado']);
        $this->save($holder);
        $status = \app\models\Estado::find()->where(['slug' => 'confirmado'])->one();
        if (!$status) {
            $status = new \app\models\Estado(['slug' => 'confirmado', 'descr' => 'Confirmado']);
            $this->save($status);
        }
        $booking = new \app\models\Reserva(['fecha' => '2086-10-23 10:00:00', 'desde' => '2086-11-03',
            'hasta' => '2086-11-05', 'id_locador' => $holder->id, 'id_estado' => $status->id, 'pax' => 1]);
        $this->save($booking);
        $this->save(new \app\models\ReservaCabana(['id_reserva' => $booking->id, 'id_cabana' => $occupied->id, 'valor' => 100]));

        Transport::reset();
        $call = &Transport::$response['data']['candidates'][0]['content']['parts'][0]['functionCall'];
        $call['name'] = 'consultar_tarifas';
        $call['args'] = ['origen' => 'disponibles', 'desde' => '2086-11-01', 'hasta' => '2086-11-07', 'pax' => 3];
        $functions = new FuncionesReservas();
        $service = new AsistenteService(new Transport(), $functions);
        $now = new \DateTimeImmutable('2086-10-23');
        $auth = function () { return $this->admin(); };
        $turn = $service->responder(['turns' => []],
            'son 3 personas para la primer semana de noviembre, necesito saber disponibilidad y precios', $now, $auth);
        $this->assertSame('ok', $turn['code']);
        $data = $turn['messages'][2]['data'];
        $this->assertSame([(int) $fits->id, (int) $missingRate->id], array_map('intval', array_column($data['cabanas'], 'id')));
        $this->assertSame(3, $data['pax_solicitado']);
        $this->assertTrue($data['disponibilidad_consultada']);
        $this->assertSame(6, $data['dias']);
        $this->assertSame(600.0, $data['cabanas'][0]['total']);
        $this->assertNull($data['cabanas'][1]['total']);
        $this->assertStringContainsString('Cabaña ' . $fits->numero . ' · Capacidad máxima:', $data['text']);
        $this->assertStringContainsString('Cabaña ' . $missingRate->numero . ': ' . $missingRate->descr, $data['text']);
        foreach (['Cabañas disponibles al momento', 'Capacidad máxima: 3', 'Huéspedes solicitados por cabaña: 3',
            'Total: $ 600,00', 'Sin tarifa completa', '07/11/2086 10:00:00'] as $text) {
            $this->assertStringContainsString($text, $turn['visible'][1]['text']);
        }
        $this->assertCount(1, Transport::$payloads);
        $declarations = array_column(Transport::$payloads[0]['tools'][0]['functionDeclarations'], null, 'name');
        $this->assertSame('INTEGER', $declarations['consultar_tarifas']['parameters']['properties']['pax']['type']);
        $this->assertStringContainsString('disponibilidad Y precios juntos', Transport::$payloads[0]['systemInstruction']['parts'][0]['text']);
        $call['args'] = ['origen' => 'anteriores', 'detalle' => 'diario'];
        $daily = $service->responder(['turns' => [$turn]], 'y el valor por dia?', $now, $auth);
        $this->assertSame('ok', $daily['code']);
        $this->assertStringContainsString('$ 100,00 por día', $daily['visible'][1]['text']);
        $this->assertSame(3, $daily['messages'][2]['data']['pax_solicitado']);
        $this->assertSame(array_column($data['cabanas'], 'id'), array_column($daily['messages'][2]['data']['cabanas'], 'id'));
        $this->assertFalse($daily['messages'][2]['data']['disponibilidad_consultada']);
        $empty = $functions->consultarTarifas($this->admin(),
            ['origen' => 'disponibles', 'desde' => '2086-11-01', 'hasta' => '2086-11-07', 'pax' => 8]);
        $this->assertSame([], $empty['cabanas']);
        $this->assertStringContainsString('No hay cabañas disponibles', $empty['text']);
        $this->assertStringNotContainsString('Total: $', $empty['text']);
    }

    public function testListadoTarifasSinFechasExcluyeInactivasVencidasYOtrasCabanas()
    {
        $cabin = $this->cabana();
        $base = $this->tarifa($cabin, '2086-12-01', '2086-12-20', 15000, 1);
        $promo = $this->tarifa($cabin, '2086-12-01', '2086-12-20', 10000, 3, true);
        $this->tarifa($cabin, '2000-01-01', '2000-01-10', 100);
        $inactive = $this->tarifa($cabin, '2086-12-01', '2086-12-20', 1, 4, true);
        $inactive->activa = 0;
        $this->save($inactive);
        $other = $this->cabana();
        $this->tarifa($other, '2086-12-01', '2086-12-20', 50);
        $functions = new FuncionesReservas();
        $result = $functions->listarTarifasCabana($this->admin(), ['numero_cabana' => (int) $cabin->numero]);
        $this->assertSame('vigentes', $result['periodo_fuente']);
        $this->assertNull($result['desde']);
        $this->assertSame([(int) $promo->id, (int) $base->id], array_map('intval', array_column($result['tarifas'], 'id')));
        $this->assertSame('tarifas_configuradas', $result['visible_presentation']['type']);
        $this->assertSame('Cabaña ' . $cabin->numero, $result['visible_presentation']['rows'][0]['recurso']);
        $this->assertSame($promo->descr, $result['visible_presentation']['rows'][0]['tarifa']);
        $this->assertSame('01/12/2086', $result['visible_presentation']['rows'][0]['inicio']);
        $this->assertSame('$ 10.000,00', $result['visible_presentation']['rows'][0]['valor_dia']);
        $this->assertSame('3', $result['visible_presentation']['rows'][0]['min_dias']);
        $this->assertStringContainsString('Sin fechas de estadía no se elige una tarifa', $result['text']);
        $this->assertStringNotContainsString('Aplicada en el cálculo', $result['text']);
        $empty = $this->cabana();
        $this->assertSame([], $functions->listarTarifasCabana($this->admin(), ['numero_cabana' => (int) $empty->numero])['tarifas']);
    }

    public function testListadoTarifasUsaContextoYPermiteCotizarLaMismaCabana()
    {
        $cabin = $this->cabana();
        $base = $this->tarifa($cabin, '2086-12-01', '2086-12-20', 15000, 1);
        $promo = $this->tarifa($cabin, '2086-12-01', '2086-12-20', 10000, 3, true);
        $expensiveMin = $this->tarifa($cabin, '2086-12-01', '2086-12-20', 8000, 5, true);
        $this->tarifa($cabin, '2086-12-06', '2086-12-20', 7000, 3, true); // begins on checkout: not involved
        $previous = ['ok' => true, 'desde' => '2086-12-03', 'hasta' => '2086-12-06', 'cabanas' => [['id' => $cabin->id]]];
        $state = ['turns' => [['messages' => [['role' => 'tool', 'name' => 'consultar_disponibilidad',
            'tool_call_id' => 'prior', 'data' => $previous]]]]];
        Transport::reset();
        $call = &Transport::$response['data']['candidates'][0]['content']['parts'][0]['functionCall'];
        $call['name'] = 'listar_tarifas_cabana';
        $call['args'] = ['numero_cabana' => (int) $cabin->numero];
        $service = new AsistenteService(new Transport(), new FuncionesReservas());
        $now = new \DateTimeImmutable('2086-10-26');
        $auth = function () { return $this->admin(); };
        $turn = $service->responder($state, 'Puedes mostrarme las tarifas validas para la cabaña ' . $cabin->numero . '?', $now, $auth);
        $this->assertSame('ok', $turn['code']);
        $data = $turn['messages'][2]['data'];
        $this->assertSame('anterior', $data['periodo_fuente']);
        $this->assertCount(3, $data['tarifas']);
        $byId = array_column($data['tarifas'], null, 'id');
        $this->assertStringContainsString('Aplicada en el cálculo', $byId[$promo->id]['criterio']);
        $this->assertStringContainsString('no seleccionada', $byId[$base->id]['criterio']);
        $this->assertStringContainsString('No aplica', $byId[$expensiveMin->id]['criterio']);
        $this->assertSame('tarifas_configuradas', $turn['visible'][1]['presentation']['type']);
        $this->assertArrayNotHasKey('visible_presentation', $data);
        $this->assertCount(1, Transport::$payloads);
        $schema = array_column(Transport::$payloads[0]['tools'][0]['functionDeclarations'], null, 'name')['listar_tarifas_cabana'];
        $this->assertArrayHasKey('todas', $schema['parameters']['properties']);
        $state['turns'][] = $turn;
        $call['name'] = 'consultar_tarifas';
        $call['args'] = ['origen' => 'anteriores'];
        $quote = $service->responder($state, 'Y el total para esos días?', $now, $auth);
        $this->assertSame('ok', $quote['code']);
        $this->assertSame(30000.0, $quote['messages'][2]['data']['cabanas'][0]['total']);
        $functions = new FuncionesReservas();
        $current = $functions->listarTarifasCabana($this->admin(), ['numero_cabana' => (int) $cabin->numero, 'usar_contexto' => false], $previous);
        $this->assertSame('vigentes', $current['periodo_fuente']);
        $explicit = $functions->listarTarifasCabana($this->admin(), ['numero_cabana' => (int) $cabin->numero,
            'desde' => '2086-12-03', 'hasta' => '2086-12-04', 'periodo_tipo' => 'estadia'], $previous);
        $this->assertSame('indicado', $explicit['periodo_fuente']);
        $byId = array_column($explicit['tarifas'], null, 'id');
        $this->assertStringContainsString('Aplicada en el cálculo', $byId[$base->id]['criterio']);
    }

    public function testListadoTarifasPermisosArgumentosYCabanaInexistente()
    {
        $functions = new FuncionesReservas();
        foreach ([null, (object) ['nivel' => 2, 'activo' => 1], (object) ['nivel' => 1, 'activo' => 0]] as $identity) {
            try {
                $functions->listarTarifasCabana($identity, ['numero_cabana' => 1]);
                $this->fail('Debe exigir permisos.');
            } catch (\yii\web\ForbiddenHttpException $e) { $this->assertSame(403, $e->statusCode); }
        }
        foreach ([[], ['numero_cabana' => '1'], ['numero_cabana' => 0], ['numero_cabana' => 100],
            ['numero_cabana' => 1, 'usar_contexto' => 'false'], ['numero_cabana' => 1, 'sql' => 'SELECT 1'],
            ['numero_cabana' => 1, 'desde' => '2086-12-01'],
            ['numero_cabana' => 1, 'desde' => '2086-12-01', 'hasta' => '2086-12-01', 'periodo_tipo' => 'estadia'],
            ['numero_cabana' => 1, 'todas' => true], ['todas' => false], ['todas' => 'true'],
            ['todas' => true, 'periodo_tipo' => 'arbitrario']] as $args) {
            try { $functions->validarListadoTarifas($args); $this->fail('Argumentos inválidos aceptados.'); }
            catch (\InvalidArgumentException $e) { $this->assertNotEmpty($e->getMessage()); }
        }
        $free = array_values(array_diff(range(1, 99), Cabana::find()->select('numero')->column()));
        $this->expectException(\InvalidArgumentException::class);
        $functions->listarTarifasCabana($this->admin(), ['numero_cabana' => $free[0]]);
    }

    public function testTarifasDeTodasPorMesNoConsultaDisponibilidadNiConvierteElMesEnEstadia()
    {
        $a = $this->cabana();
        $b = $this->cabana();
        $b->activa = 0;
        $this->save($b);
        $empty = $this->cabana();
        $base = $this->tarifa($a, '2086-06-01', '2086-06-30', 15000, 1);
        $promo = $this->tarifa($a, '2086-06-01', '2086-06-30', 10000, 3, true);
        $last = $this->tarifa($a, '2086-06-30', '2086-06-30', 20000, 1, true);
        $july = $this->tarifa($a, '2086-07-01', '2086-07-10', 30000);
        $inactive = $this->tarifa($a, '2086-06-01', '2086-06-30', 1, 5, true);
        $inactive->activa = 0;
        $this->save($inactive);
        $this->save(new CabanaTarifa(['id_cabana' => $b->id, 'id_tarifa' => $base->id]));
        $functions = new class extends FuncionesReservas {
            public function consultarDisponibilidad($identity, array $args) {
                throw new \RuntimeException('No debe consultarse disponibilidad para listar tarifas.');
            }
        };
        Transport::reset();
        $call = &Transport::$response['data']['candidates'][0]['content']['parts'][0]['functionCall'];
        $call['name'] = 'listar_tarifas_cabana';
        $call['args'] = ['todas' => true, 'desde' => '2086-06-01', 'hasta' => '2086-06-30', 'periodo_tipo' => 'vigencia'];
        $service = new AsistenteService(new Transport(), $functions);
        $now = new \DateTimeImmutable('2086-10-26');
        $auth = function () { return $this->admin(); };
        $turn = $service->responder(['turns' => []], 'cuales son las tarifas para todas las cabañas de junio de 2086?', $now, $auth);
        $this->assertSame('ok', $turn['code']);
        $data = $turn['messages'][2]['data'];
        $this->assertSame('vigencia', $data['periodo_tipo']);
        $this->assertSame('2086-06-30', $data['hasta']);
        $this->assertCount(4, $data['tarifas']);
        $ids = array_map('intval', array_column($data['tarifas'], 'id'));
        $this->assertContains((int) $promo->id, $ids);
        $this->assertContains((int) $last->id, $ids); // Last calendar day is included.
        $this->assertNotContains((int) $inactive->id, $ids);
        $this->assertNotContains((int) $july->id, $ids);
        $this->assertSame(2, count(array_filter($ids, function ($id) use ($base) { return $id === (int) $base->id; })));
        $this->assertArrayNotHasKey('dias', $data);
        $this->assertStringNotContainsString('Días facturables', $turn['visible'][1]['text']);
        $this->assertStringNotContainsString('Aplicada en el cálculo', $turn['visible'][1]['text']);
        $table = $turn['visible'][1]['presentation'];
        $this->assertSame('tarifas_configuradas', $table['type']);
        $this->assertSame('Vigencia consultada: 01/06/2086 al 30/06/2086 (ambos días incluidos).', $table['comment']);
        $this->assertContains('Cabaña ' . $b->numero, array_column($table['rows'], 'recurso')); // No availability/active-cabin filter.
        $this->assertMatchesRegularExpression('/Sin tarifas activas[^\n]*\b' . $empty->numero . '(?:,|\.)/', $turn['visible'][1]['text']);
        $this->assertCount(1, Transport::$payloads);
        $this->assertStringContainsString('No confundas listar tarifas', Transport::$payloads[0]['systemInstruction']['parts'][0]['text']);
        $call['name'] = 'consultar_tarifas';
        $call['args'] = ['origen' => 'anteriores'];
        $quote = $service->responder(['turns' => [$turn]], 'Y el total?', $now, function () {
            $this->fail('La vigencia del catálogo no define una estadía para cotizar.');
        });
        $this->assertSame('invalid_arguments', $quote['code']);
        $oneDay = $functions->listarTarifasCabana($this->admin(),
            ['numero_cabana' => (int) $a->numero, 'desde' => '2086-06-30', 'hasta' => '2086-06-30']);
        $this->assertCount(3, $oneDay['tarifas']);
    }

    private function admin() { return (object) ['nivel' => 1, 'activo' => 1]; }

    private function cabana()
    {
        $free = array_values(array_diff(range(1, 99), Cabana::find()->select('numero')->column()));
        $cabana = new Cabana(['descr' => 'Tarifas IA ' . bin2hex(random_bytes(4)), 'numero' => $free[0],
            'checkin' => '15:00', 'checkout' => '10:00', 'max_pax' => 4, 'activa' => 1, 'color_cabana' => '#3cb44b']);
        $this->save($cabana);
        return $cabana;
    }

    private function tarifa($cabana, $desde, $hasta, $valor, $min = 1, $fixtureConSolape = false)
    {
        $tarifa = new Tarifa(['descr' => 'Tarifa IA ' . bin2hex(random_bytes(4)), 'inicio' => $desde,
            'fin' => $hasta, 'valor_dia' => $valor, 'min_dias' => $min, 'activa' => 1]);
        $this->save($tarifa);
        $link = new CabanaTarifa(['id_cabana' => $cabana->id, 'id_tarifa' => $tarifa->id]);
        if ($fixtureConSolape) {
            // Seed the stated business scenario without changing the legacy editor's overlap validation.
            $this->assertTrue($link->save(false));
        } else {
            $this->save($link);
        }
        return $tarifa;
    }

    private function save($model) { $this->assertTrue($model->save(), implode(' | ', $model->getErrorSummary(true))); }
}
