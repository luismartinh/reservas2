<?php
namespace tests\unit\models;

require_once dirname(__DIR__, 2) . '/_support/AsistenteFixtures.php';

use app\components\asistente\AsistenteService;
use app\components\asistente\FuncionesReservas;
use app\models\Cabana;
use app\models\Estado;
use app\models\Locador;
use app\models\Reserva;
use app\models\ReservaCabana;
use app\models\RequestReserva;
use tests\support\GeminiAsistenteSimulado as Transport;
use Yii;

class AsistenteReservasTest extends \Codeception\Test\Unit
{
    use \tests\support\RuntimeAsistenteAislado;
    private $locador;
    private $estado;

    protected function _before()
    {
        $this->iniciarRuntimeAsistente();
        Yii::$app->controller = new \yii\web\Controller('asistente', Yii::$app);
        $suffix = bin2hex(random_bytes(5));
        $this->locador = new Locador(['denominacion' => 'Cliente privado ' . $suffix,
            'documento' => 'DOC' . $suffix, 'email' => $suffix . '@example.com',
            'telefono' => '123456', 'domicilio' => 'Domicilio privado']);
        $this->save($this->locador);
        $this->estado = Estado::find()->where(['slug' => 'confirmado'])->one();
        if (!$this->estado) {
            $this->estado = new Estado(['slug' => 'confirmado', 'descr' => 'Confirmado']);
            $this->save($this->estado);
        }
    }

    protected function _after() { $this->cerrarRuntimeAsistente(); }

    public function testUltimaReservaPorRegistroConDesempateYNoPorEstadiaNiIdSolo()
    {
        $latest = $this->reserva('2099-07-01 10:00:00', '2086-08-01', '2086-08-05');
        $this->reserva('2098-07-01 10:00:00', '2099-12-01', '2099-12-05');
        $functions = new FuncionesReservas();
        $data = $functions->consultarUltimaReserva($this->admin(), []);
        $this->assertSame([['id' => (int) $latest->id]], $data['reservas']);
        $tie = $this->reserva('2099-07-01 10:00:00', '2086-09-01', '2086-09-05');
        $data = $functions->consultarUltimaReserva($this->admin(), ['criterio' => 'fecha_registro']);
        $this->assertSame([['id' => (int) $tie->id]], $data['reservas']);
        $this->assertSame('#' . $tie->id, $data['visible_presentation']['rows'][0]['reserva']);
        $this->assertSame($this->locador->denominacion, $data['visible_presentation']['rows'][0]['locador']);
    }

    public function testUltimaReservaMantieneContextoSinDatosPrivadosEnElProveedor()
    {
        $latest = $this->reserva('2099-07-01 10:00:00', '2086-08-01', '2086-08-05');
        Transport::reset();
        $call = &Transport::$response['data']['candidates'][0]['content']['parts'][0]['functionCall'];
        $call['name'] = 'consultar_ultima_reserva'; $call['args'] = [];
        $service = new AsistenteService(new Transport(), new FuncionesReservas());
        $now = new \DateTimeImmutable('2026-10-27');
        $identity = function () { return $this->admin(); };
        $turn = $service->responder(['turns' => []], '¿Cuál es la última reserva hecha?', $now, $identity);
        $this->assertSame('ok', $turn['code']);
        $this->assertSame([['id' => (int) $latest->id]], $turn['messages'][2]['data']['reservas']);
        $this->assertSame('Última reserva registrada', $turn['visible'][1]['presentation']['title']);
        $call['name'] = 'consultar_detalle_reservas';
        $call['args'] = ['origen' => 'anteriores', 'detalle' => 'contacto'];
        $follow = $service->responder(['turns' => [$turn]], 'Dame su contacto', $now, $identity);
        $this->assertSame('ok', $follow['code']);
        $this->assertStringContainsString($this->locador->telefono, $follow['visible'][1]['presentation']['rows'][0]['contacto']);
        foreach ([$this->locador->denominacion, $this->locador->email, $this->locador->domicilio] as $private) {
            $this->assertStringNotContainsString($private, json_encode($follow['messages']));
            $this->assertStringNotContainsString($private, json_encode(Transport::$payloads));
        }
    }

    public function testUltimaReservaExigePermisoYRechazaFiltrosNoSoportados()
    {
        $functions = new FuncionesReservas();
        foreach ([['desde' => '2026-01-01'], ['criterio' => 'ingreso'], ['sql' => 'SELECT 1']] as $args) {
            try { $functions->consultarUltimaReserva($this->admin(), $args); $this->fail('Debe rechazar argumentos.'); }
            catch (\InvalidArgumentException $e) { $this->assertNotEmpty($e->getMessage()); }
        }
        $this->expectException(\yii\web\ForbiddenHttpException::class);
        $functions->consultarUltimaReserva((object) ['activo' => 1, 'nivel' => 4], []);
    }

    public function testEgresoUsaDiaSiguienteYCheckoutSinCambiarFechaGuardada()
    {
        $booking = $this->reserva('2086-07-01 15:16:05', '2086-07-20 14:00:00', '2086-07-26 23:59:59');
        $functions = new FuncionesReservas();
        $args = ['origen' => 'seleccionadas', 'ids_reservas' => [(int) $booking->id], 'detalle' => 'general'];
        $data = $functions->consultarDetalleReservas($this->admin(), $args);
        $this->assertSame('27/07/2086 · checkout no configurado', $data['visible_presentation']['rows'][0]['egreso']);
        $free = array_values(array_diff(range(1, 99), Cabana::find()->select('numero')->column()));
        $cabins = [];
        foreach (['11:00', '10:00'] as $index => $checkout) {
            $cabin = new Cabana(['numero' => $free[$index], 'descr' => 'Checkout ' . $index,
                'checkin' => '14:00', 'checkout' => $checkout, 'max_pax' => 4, 'activa' => 1, 'color_cabana' => '#3cb44b']);
            $this->save($cabin);
            $this->save(new ReservaCabana(['id_reserva' => $booking->id, 'id_cabana' => $cabin->id, 'valor' => 100]));
            $cabins[] = $cabin;
        }
        $data = $functions->consultarDetalleReservas($this->admin(), $args);
        $this->assertSame('Cabaña ' . $cabins[0]->numero . ': 27/07/2086 11:00' . "\n"
            . 'Cabaña ' . $cabins[1]->numero . ': 27/07/2086 10:00', $data['visible_presentation']['rows'][0]['egreso']);
        $cabins[1]->checkout = '11:00'; $this->save($cabins[1]);
        $data = $functions->consultarDetalleReservas($this->admin(), $args);
        $this->assertSame('27/07/2086 11:00', $data['visible_presentation']['rows'][0]['egreso']);
        $booking->refresh(); $this->assertSame('2086-07-26 23:59:59', $booking->hasta);
        foreach ([['2086-04-01', '2086-04-02', '03/04/2086 11:00', '2'],
            ['2086-04-01', '2086-04-01', '02/04/2086 11:00', '1'],
            ['2086-12-31', '2086-12-31', '01/01/2087 11:00', '1'],
            ['2088-02-28', '2088-02-29', '01/03/2088 11:00', '2']] as $case) {
            $booking->desde = $case[0] . ' 14:00:00';
            $booking->hasta = $case[1] . ' 23:59:59';
            $this->save($booking);
            $row = $functions->consultarDetalleReservas($this->admin(), $args)['visible_presentation']['rows'][0];
            $this->assertSame($case[2], $row['egreso']);
            $this->assertSame($case[3], $row['dias']);
        }

    }

    public function testFiltraRegistroInclusivoAunqueLaEstadiaSeaOtroMesYSinDatosPersonales()
    {
        $start = $this->reserva('2086-07-25 00:00:00', '2086-09-01', '2086-09-05');
        $end = $this->reserva('2086-07-31 23:59:59', '2086-10-01', '2086-10-05');
        $this->reserva('2086-07-24 23:59:59', '2086-07-25', '2086-07-31');
        $this->reserva('2086-08-01 00:00:00', '2086-07-25', '2086-07-31');
        $free = array_values(array_diff(range(1, 99), Cabana::find()->select('numero')->column()));
        $cabana = new Cabana(['numero' => $free[0], 'descr' => 'Listado IA ' . bin2hex(random_bytes(4)),
            'checkin' => '15:00', 'checkout' => '10:00', 'max_pax' => 4, 'activa' => 1, 'color_cabana' => '#3cb44b']);
        $this->save($cabana);
        $this->save(new ReservaCabana(['id_reserva' => $start->id, 'id_cabana' => $cabana->id, 'valor' => 100]));
        $functions = new FuncionesReservas();
        $args = ['desde' => '2086-07-25', 'hasta' => '2086-07-31'];
        $data = $functions->listarReservasRegistradas($this->admin(), $args);
        $this->assertSame([(int) $start->id, (int) $end->id], array_column($data['reservas'], 'id'));
        $this->assertSame([(int) $cabana->numero], $data['reservas'][0]['cabanas']);
        $this->assertStringStartsWith('2086-10-01', $data['reservas'][1]['desde']);
        $this->assertSame('fecha_registro', $data['criterio']);
        $this->assertSame($this->locador->denominacion, $data['visible_presentation']['rows'][0]['locador']);
        $internal = $data;
        unset($internal['visible_presentation']);
        foreach ([$this->locador->denominacion, $this->locador->email, $this->locador->documento, 'Nota privada'] as $private) {
            $this->assertStringNotContainsString($private, json_encode($internal));
        }
        $this->reserva('2086-07-27 10:00:00', '2086-09-01', '2086-09-05');
        $this->assertCount(3, $functions->listarReservasRegistradas($this->admin(), $args)['reservas']);
    }

    public function testTablaReservasConImportesGuardadosSeguimientoPrivadoYCobrosVerificados()
    {
        $booking = $this->reserva('2086-07-01 15:16:05', '2086-07-20 14:00:00', '2086-07-26 23:59:59');
        $free = array_values(array_diff(range(1, 99), Cabana::find()->select('numero')->column()));
        $cabana = new Cabana(['numero' => $free[0], 'descr' => 'Tabla reservas', 'checkin' => '14:00',
            'checkout' => '11:00', 'max_pax' => 4, 'activa' => 1, 'color_cabana' => '#3cb44b']);
        $this->save($cabana);
        $this->save(new ReservaCabana(['id_reserva' => $booking->id, 'id_cabana' => $cabana->id, 'valor' => 2500]));
        $request = $this->solicitudPagos($booking, 300, [['monto' => 300, 'archivo' => '/privado/recibo-tabla.pdf']]);
        $request->codigo_reserva = 'ABCD234'; $this->save($request);
        Transport::reset();
        $call = &Transport::$response['data']['candidates'][0]['content']['parts'][0]['functionCall'];
        $call['name'] = 'listar_reservas_por_estadia';
        $call['args'] = ['desde' => '2086-07-25', 'hasta' => '2086-07-31'];
        $service = new AsistenteService(new Transport(), new FuncionesReservas());
        $now = new \DateTimeImmutable('2086-10-26');
        $auth = function () { return $this->admin(); };
        $listing = $service->responder(['turns' => []], 'La ultima semana de julio, quien reservo?', $now, $auth);
        $this->assertSame('ok', $listing['code']);
        $table = $listing['visible'][1]['presentation'];
        $this->assertSame('reservas', $table['type']);
        $row = $table['rows'][0];
        $this->assertSame('#' . $booking->id, $row['reserva']);
        $this->assertSame($this->locador->denominacion, $row['locador']);
        $this->assertSame('ABCD234', $row['codigo_seguimiento']);
        $this->assertSame($this->locador->telefono, $row['telefono']);
        $this->assertSame($this->locador->email, $row['email']);
        foreach (['ABCD234', $this->locador->email, $this->locador->telefono] as $private) {
            $this->assertStringNotContainsString($private, json_encode($listing['messages']));
        }
        $this->assertSame('Cabaña ' . $cabana->numero . ' · cap. máx. 4', $row['recurso']);
        $this->assertSame('01/07/2086 15:16', $row['creada']);
        $this->assertSame('20/07/2086 14:00', $row['ingreso']);
        $this->assertSame('27/07/2086 11:00', $row['egreso']);
        $booking->refresh();
        $this->assertSame('2086-07-26 23:59:59', $booking->hasta);
        $this->assertSame('7', $row['dias']);
        $this->assertSame('$ 1.000,00', $row['total']); // Stored request total, not a current quote or cabin subtotal.
        $this->assertSame('$ 300,00', $row['cobrado']);
        parse_str(parse_url($row['seguimiento_url'], PHP_URL_QUERY), $params);
        $this->assertSame('disponibilidad/seguimiento', $params['r']);
        $this->assertSame($request->hash, $params['hash']);

        $pending = Estado::find()->where(['slug' => 'confirmado-verificar-pago'])->one();
        if (!$pending) {
            $pending = new Estado(['slug' => 'confirmado-verificar-pago', 'descr' => 'Pendiente de verificar']);
            $this->save($pending);
        }
        $request->id_estado = $pending->id;
        $this->save($request);
        $call['name'] = 'consultar_detalle_reservas';
        $call['args'] = ['origen' => 'anteriores', 'detalle' => 'general'];
        $detail = $service->responder(['turns' => [$listing]], 'Dame los datos de las reservas', $now, $auth);
        $this->assertSame('ok', $detail['code']);
        $this->assertSame('No determinable', $detail['visible'][1]['presentation']['rows'][0]['cobrado']);
        $call['name'] = 'consultar_pagos_reservas';
        $call['args'] = ['origen' => 'anteriores'];
        $payments = $service->responder(['turns' => [$listing, $detail]], 'Sus pagos', $now, $auth);
        $this->assertSame('ok', $payments['code']);
        $this->assertArrayHasKey('detail_text', $payments['visible'][1]['presentation']);
        foreach ([$request->hash, $this->locador->denominacion, '/privado/recibo-tabla.pdf', '$ 300,00'] as $private) {
            $this->assertStringNotContainsString($private, json_encode(Transport::$payloads));
            $this->assertStringNotContainsString($private, json_encode($listing['messages']));
            $this->assertStringNotContainsString($private, file_get_contents(Yii::getAlias('@runtime/asistente-diagnostics.log')));
        }
        $this->assertStringNotContainsString('/privado/recibo-tabla.pdf', json_encode($payments['visible']));
    }

    public function testSinResultadosLimiteYOrdenEstable()
    {
        $functions = new FuncionesReservas();
        $args = ['desde' => '2086-08-01', 'hasta' => '2086-08-01'];
        $this->assertStringContainsString('No hay reservas registradas', $functions->listarReservasRegistradas($this->admin(), $args)['text']);
        $ids = [];
        for ($i = 0; $i < 21; $i++) {
            $ids[] = (int) $this->reserva('2086-08-01 10:00:00', '2086-09-01', '2086-09-02')->id;
        }
        $data = $functions->listarReservasRegistradas($this->admin(), $args);
        $this->assertTrue($data['truncado']);
        $this->assertSame(array_slice($ids, 0, 20), array_column($data['reservas'], 'id'));
        $this->assertStringContainsString('primeras 20 reservas', $data['text']);
    }

    public function testEstadiaIncluyeSolapesParcialesAunqueSeRegistraranElDiaUno()
    {
        $expected = [];
        // Same dates as the reported 81, 82 and 83, independent of real database IDs.
        foreach ([['2086-07-20 14:00:00', '2086-07-26 23:59:59'],
            ['2086-07-21 14:00:00', '2086-07-26 23:59:59'],
            ['2086-07-21 14:00:00', '2086-07-26 23:59:59'],
            ['2086-07-27 14:00:00', '2086-07-30 10:00:00'],
            ['2086-07-01 14:00:00', '2086-08-15 10:00:00'],
            ['2086-07-20 14:00:00', '2086-07-25 00:00:00'],
            ['2086-07-31 23:59:59', '2086-08-05 10:00:00']] as $range) {
            $expected[] = (int) $this->reserva('2086-07-01 15:16:05', $range[0], $range[1])->id;
        }
        $this->reserva('2086-07-26 10:00:00', '2086-08-01 00:00:00', '2086-08-05 10:00:00');
        $this->reserva('2086-07-26 10:00:00', '2086-07-20 14:00:00', '2086-07-24 23:59:59');
        $functions = new FuncionesReservas();
        $args = ['desde' => '2086-07-25', 'hasta' => '2086-07-31'];
        $data = $functions->listarReservasPorEstadia($this->admin(), $args);
        $this->assertEqualsCanonicalizing($expected, array_column($data['reservas'], 'id'));
        $this->assertSame('estadia', $data['criterio']);
        $this->assertStringContainsString('total o parcialmente', $data['text']);
        $registered = $functions->listarReservasRegistradas($this->admin(), $args);
        $this->assertCount(2, $registered['reservas']); // The two non-overlapping stays were created on July 26.
        $this->assertEmpty(array_intersect($expected, array_column($registered['reservas'], 'id')));
    }

    public function testPermisoEnFuncionYFechasInvalidas()
    {
        $functions = new FuncionesReservas();
        foreach ([['desde' => '2086-07-31', 'hasta' => '2086-07-25'],
            ['desde' => '2086-02-30', 'hasta' => '2086-03-01'],
            ['desde' => '2086-07-25', 'hasta' => '2086-07-31', 'sql' => 'SELECT 1']] as $args) {
            try {
                $functions->listarReservasRegistradas($this->admin(), $args);
                $this->fail('Invalid listing arguments accepted');
            } catch (\InvalidArgumentException $e) { $this->assertNotEmpty($e->getMessage()); }
            try {
                $functions->listarReservasPorEstadia($this->admin(), $args);
                $this->fail('Invalid stay arguments accepted');
            } catch (\InvalidArgumentException $e) { $this->assertNotEmpty($e->getMessage()); }
            try {
                $functions->listarReservasPorIngreso($this->admin(), $args);
                $this->fail('Invalid arrival arguments accepted');
            } catch (\InvalidArgumentException $e) { $this->assertNotEmpty($e->getMessage()); }
        }
        try {
            $functions->listarReservasPorIngreso((object) ['nivel' => 2, 'activo' => 1], []);
            $this->fail('Unauthorized arrival query accepted');
        } catch (\yii\web\ForbiddenHttpException $e) { $this->assertSame(403, $e->statusCode); }
        try {
            $functions->listarReservasPorEstadia((object) ['nivel' => 2, 'activo' => 1], []);
            $this->fail('Unauthorized stay query accepted');
        } catch (\yii\web\ForbiddenHttpException $e) { $this->assertSame(403, $e->statusCode); }
        $this->expectException(\yii\web\ForbiddenHttpException::class);
        $functions->listarReservasRegistradas((object) ['nivel' => 2, 'activo' => 1], []);
    }

    public function testMesCompletoSinFuncionConsultaDatosRealesYConservaSeleccion()
    {
        $booking = $this->reserva('2086-06-01 12:00:00', '2086-07-10 14:00:00', '2086-07-12 23:59:59');
        Transport::reset();
        Transport::$response['data']['candidates'][0]['content']['parts'] = [['text' => 'Ayuda genérica sin función']];
        $service = new AsistenteService(new Transport(), new FuncionesReservas());
        $now = new \DateTimeImmutable('2086-10-27', new \DateTimeZone(Yii::$app->timeZone));
        foreach ([['ver las reservas  de julio', '2086-07-01', '2086-07-31'],
            ['mostrame las reservas de febrero de 2088', '2088-02-01', '2088-02-29'],
            ['reservas de abril de 2086', '2086-04-01', '2086-04-30'],
            ['reservas de diciembre pasado', '2085-12-01', '2085-12-31'],
            ['reservas de julio del año pasado', '2085-07-01', '2085-07-31']] as $case) {
            $turn = $service->responder(['turns' => []], $case[0], $now, function () { return $this->admin(); });
            $this->assertSame('ok', $turn['code']);
            $this->assertSame($case[1], $turn['seleccion_reservas']['desde']);
            $this->assertSame($case[2], $turn['seleccion_reservas']['hasta']);
            $this->assertSame('estadia', $turn['seleccion_reservas']['criterio']);
            $this->assertSame('reservas', $turn['visible'][1]['presentation']['type']);
            $this->assertStringNotContainsString($this->locador->email, json_encode($turn['messages']));
            if ($case[1] === '2086-07-01') { $listing = $turn; }
        }
        $this->assertContains((int) $booking->id, array_column($listing['seleccion_reservas']['reservas'], 'id'));
        $this->assertStringNotContainsString($this->locador->denominacion, json_encode($listing['messages']));
        Transport::reset();
        $call = &Transport::$response['data']['candidates'][0]['content']['parts'][0]['functionCall'];
        $call['name'] = 'consultar_detalle_reservas';
        $call['args'] = ['origen' => 'anteriores', 'detalle' => 'contacto'];
        $follow = $service->responder(['turns' => [$listing]], 'sus teléfonos?', $now, function () { return $this->admin(); });
        $this->assertSame('ok', $follow['code']);
        $this->assertSame($this->locador->telefono, $follow['visible'][1]['presentation']['rows'][0]['telefono']);
    }

    public function testRespaldoMesNoIgnoraFiltrosNiFallosYExigePermiso()
    {
        Transport::reset();
        Transport::$response['data']['candidates'][0]['content']['parts'] = [['text' => 'Ayuda']];
        $service = new AsistenteService(new Transport(), new FuncionesReservas());
        $now = new \DateTimeImmutable('2086-10-27');
        foreach (['reservas de julio de Jose', 'reservas impagas de julio', 'reservas registradas de julio',
            'reservas de julio para cabaña 1', 'reservas del 10 al 15 de julio'] as $text) {
            $turn = $service->responder(['turns' => []], $text, $now, function () { $this->fail('No debe omitir filtros.'); });
            $this->assertSame('clarification', $turn['code']);
        }
        Transport::$httpFailure = 503;
        $turn = $service->responder(['turns' => []], 'ver las reservas de julio', $now,
            function () { $this->fail('No debe consultar tras un fallo del proveedor.'); });
        $this->assertNotSame('ok', $turn['code']);
        Transport::$httpFailure = 0;
        $this->expectException(\yii\web\ForbiddenHttpException::class);
        $service->responder(['turns' => []], 'ver las reservas de julio', $now,
            function () { return (object) ['activo' => 1, 'nivel' => 4]; });
    }

    public function testCorrienteAnioContratoYReservaNoEsSeleccionParaPrecios()
    {
        Transport::reset();
        $call = &Transport::$response['data']['candidates'][0]['content']['parts'][0]['functionCall'];
        $call['name'] = 'listar_reservas_por_estadia';
        $call['args'] = ['desde' => '2026-07-25', 'hasta' => '2026-07-31'];
        $service = new AsistenteService(new Transport(), new FuncionesReservas());
        $now = new \DateTimeImmutable('2026-10-23', new \DateTimeZone(Yii::$app->timeZone));
        $turn = $service->responder(['turns' => []], 'Dime las reservas tomadas desde el 25 al 31 de julio del corriente año',
            $now, function () { return $this->admin(); });
        $this->assertSame('ok', $turn['code']);
        $this->assertStringContainsString('Reservas con estadía entre 25/07/2026 00:00:00 al 31/07/2026 23:59:59', $turn['visible'][1]['text']);
        $prompt = Transport::$payloads[0]['systemInstruction']['parts'][0]['text'];
        $this->assertStringContainsString('significan 2026', $prompt);
        $this->assertStringContainsString('predeterminada de reservas SIEMPRE es por estadía', $prompt);
        $this->assertSame('opaque-signature', $turn['messages'][1]['provider_state']['gemini']['parts'][0]['thoughtSignature']);
        $call['name'] = 'consultar_tarifas';
        $call['args'] = ['origen' => 'anteriores'];
        $prior = ['messages' => [['role' => 'tool', 'name' => 'consultar_disponibilidad',
            'tool_call_id' => 'availability-call',
            'data' => ['ok' => true, 'desde' => '2026-11-01', 'hasta' => '2026-11-02', 'cabanas' => []]]]];
        $result = $service->responder(['turns' => [$prior, $turn]], 'Sus valores', $now, function () {
            $this->fail('A reservation list must not reuse an older cabin selection');
        });
        $this->assertSame('invalid_arguments', $result['code']);
    }

    public function testIngresoExigeInicioDentroDelRangoSinConfundirRegistroOEstadia()
    {
        $a = $this->reserva('2086-07-01 10:00:00', '2086-07-25 00:00:00', '2086-08-05 10:00:00');
        $b = $this->reserva('2086-07-01 10:00:00', '2086-07-31 23:59:59', '2086-08-05 10:00:00');
        $this->reserva('2086-07-26 10:00:00', '2086-07-20 14:00:00', '2086-07-30 10:00:00');
        $this->reserva('2086-07-26 10:00:00', '2086-08-01 00:00:00', '2086-08-05 10:00:00');
        $functions = new FuncionesReservas();
        $args = ['desde' => '2086-07-25', 'hasta' => '2086-07-31'];
        $data = $functions->listarReservasPorIngreso($this->admin(), $args);
        $this->assertSame([(int) $a->id, (int) $b->id], array_column($data['reservas'], 'id'));
        $this->assertSame('ingreso', $data['criterio']);
        $this->assertStringContainsString('Filtro aplicado: fecha de ingreso', $data['text']);
        $this->assertCount(3, $functions->listarReservasPorEstadia($this->admin(), $args)['reservas']);
        $empty = $functions->listarReservasPorIngreso($this->admin(), ['desde' => '2086-06-01', 'hasta' => '2086-06-01']);
        $this->assertStringContainsString('No hay reservas con ingreso', $empty['text']);
    }

    public function testTitularesContactoYDatosGeneralesSoloEnTextoVisible()
    {
        $record = $this->reserva('2086-07-01 10:00:00', '2086-07-20', '2086-07-26');
        $functions = new FuncionesReservas();
        $args = ['origen' => 'seleccionadas', 'ids_reservas' => [(int) $record->id], 'detalle' => 'titular'];
        $data = $functions->consultarDetalleReservas($this->admin(), $args);
        $this->assertStringContainsString($this->locador->denominacion, $data['visible_text']);
        $this->assertStringNotContainsString($this->locador->email, $data['visible_text']);
        $this->assertSame([['id' => (int) $record->id]], $data['reservas']);
        $internal = $data;
        unset($internal['visible_text'], $internal['visible_presentation']);
        $this->assertStringNotContainsString($this->locador->denominacion, json_encode($internal));
        $contact = $functions->consultarDetalleReservas($this->admin(), ['origen' => 'anteriores', 'detalle' => 'contacto'], $internal);
        $this->assertStringContainsString($this->locador->email, $contact['visible_text']);
        $this->assertStringContainsString($this->locador->telefono, $contact['visible_text']);
        $this->assertStringNotContainsString($this->locador->documento, $contact['visible_text']);
        $general = $functions->consultarDetalleReservas($this->admin(), ['origen' => 'anteriores'], $internal);
        $this->assertStringContainsString('Estadía: 20/07/2086', $general['visible_text']);
        $this->assertStringContainsString('Pax registrado: 1', $general['visible_text']);
        $this->assertStringNotContainsString('Nota privada', $general['visible_text']);
        $this->assertStringNotContainsString($this->locador->email, $general['visible_text']);
    }

    public function testDetalleNoAceptaIdsForjadosNiPermisosInsuficientes()
    {
        $functions = new FuncionesReservas();
        foreach ([['origen' => 'anteriores'], ['origen' => 'anteriores', 'ids_reservas' => [1]],
            ['origen' => 'seleccionadas', 'ids_reservas' => ['1']],
            ['origen' => 'seleccionadas', 'ids_reservas' => []],
            ['origen' => 'seleccionadas', 'ids_reservas' => [0]],
            ['origen' => 'seleccionadas', 'ids_reservas' => range(1, 21)],
            ['origen' => 'seleccionadas', 'ids_reservas' => [1], 'detalle' => 'sql'],
            ['origen' => 'seleccionadas', 'ids_reservas' => [1], 'detalle' => null]] as $args) {
            try {
                $functions->validarDetalleReservas($args);
                $this->fail('Invalid detail arguments accepted');
            } catch (\InvalidArgumentException $e) { $this->assertNotEmpty($e->getMessage()); }
        }
        $empty = $functions->consultarDetalleReservas($this->admin(), ['origen' => 'anteriores'], ['ok' => true, 'reservas' => []]);
        $this->assertSame([], $empty['reservas']);
        foreach ([null, (object) ['nivel' => 2, 'activo' => 1], (object) ['nivel' => 0, 'activo' => 0]] as $identity) {
            try {
                $functions->consultarDetalleReservas($identity, []);
                $this->fail('Unauthorized detail query accepted');
            } catch (\yii\web\ForbiddenHttpException $e) { $this->assertSame(403, $e->statusCode); }
        }
        $record = $this->reserva('2086-07-01 10:00:00', '2086-07-20', '2086-07-26');
        $this->expectException(\InvalidArgumentException::class);
        $functions->consultarDetalleReservas($this->admin(),
            ['origen' => 'seleccionadas', 'ids_reservas' => [(int) $record->id, 2147483647]]);
    }

    public function testListadoTitularesYContactoNoEnvianDatosPersonalesAGemini()
    {
        $record = $this->reserva('2086-07-01 10:00:00', '2086-07-20', '2086-07-26');
        Transport::reset();
        $call = &Transport::$response['data']['candidates'][0]['content']['parts'][0]['functionCall'];
        $call['name'] = 'listar_reservas_por_estadia';
        $call['args'] = ['desde' => '2086-07-25', 'hasta' => '2086-07-31'];
        $service = new AsistenteService(new Transport(), new FuncionesReservas());
        $now = new \DateTimeImmutable('2086-07-01');
        $auth = function () { return $this->admin(); };
        $listing = $service->responder(['turns' => []], 'Reservas del 25 al 31 de julio de 2086', $now, $auth);
        $call['name'] = 'consultar_detalle_reservas';
        $call['args'] = ['origen' => 'anteriores', 'detalle' => 'titular'];
        $details = $service->responder(['turns' => [$listing]], '¿A nombre de quién están las reservas encontradas?', $now, $auth);
        $this->assertSame('ok', $details['code']);
        $this->assertStringContainsString($this->locador->denominacion, $details['visible'][1]['text']);
        $this->assertStringNotContainsString($this->locador->denominacion, json_encode($details['messages']));
        $call['args']['detalle'] = 'contacto';
        $contact = $service->responder(['turns' => [$listing, $details]], 'Sus teléfonos y emails', $now, $auth);
        $this->assertStringContainsString($this->locador->email, $contact['visible'][1]['text']);
        $call['args']['detalle'] = 'general';
        $service->responder(['turns' => [$listing, $details, $contact]], 'Sus datos generales', $now, $auth);
        foreach ([$this->locador->denominacion, $this->locador->email, $this->locador->documento,
            $this->locador->domicilio, 'Nota privada'] as $private) {
            $this->assertStringNotContainsString($private, json_encode(Transport::$payloads));
            $this->assertStringNotContainsString($private, file_get_contents(Yii::getAlias('@runtime/asistente-diagnostics.log')));
        }
        $this->assertCount(4, Transport::$payloads);
        $this->assertSame('opaque-signature', $details['messages'][1]['provider_state']['gemini']['parts'][0]['thoughtSignature']);
        $call['args'] = ['origen' => 'anteriores', 'detalle' => 'titular'];
        $missing = $service->responder(['turns' => []], 'Sus titulares', $now, function () {
            $this->fail('Missing selection must not query private data');
        });
        $this->assertSame('invalid_arguments', $missing['code']);
    }

    public function testOpenAINoRecibeTitularesContactosHashNiResumenFinancieroEnContinuaciones()
    {
        $booking = $this->reserva('2086-07-01 10:00:00', '2086-07-20', '2086-07-26');
        $request = $this->solicitudPagos($booking, 300, [['monto' => 300, 'archivo' => '/privado/no-enviar.pdf']]);
        $oldKey = getenv('OPENAI_API_KEY');
        putenv('OPENAI_API_KEY=privacy-test-openai');
        \tests\support\OpenAIHttp::reset();
        try {
            $service = new AsistenteService(new \app\components\asistente\OpenAIProvider(), new FuncionesReservas(), 24576,
                ['provider' => 'openai', 'model' => 'gpt-5.4-mini']);
            $now = new \DateTimeImmutable('2086-10-26');
            $auth = function () { return $this->admin(); };
            $state = ['turns' => []];
            $raw = json_decode(\tests\support\OpenAIHttp::$raw, true);
            foreach ([['listar_reservas_por_estadia', ['desde' => '2086-07-25', 'hasta' => '2086-07-31']],
                ['consultar_detalle_reservas', ['origen' => 'anteriores', 'detalle' => 'contacto']],
                ['consultar_pagos_reservas', ['origen' => 'anteriores']],
                ['consultar_detalle_reservas', ['origen' => 'anteriores', 'detalle' => 'general']]] as $index => $query) {
                $raw['output'][0]['call_id'] = 'privacy-call-' . $index;
                $raw['output'][0]['name'] = $query[0];
                $raw['output'][0]['arguments'] = json_encode($query[1]);
                \tests\support\OpenAIHttp::$raw = json_encode($raw);
                $turn = $service->responder($state, 'Consulta ' . $index, $now, $auth);
                $this->assertSame('ok', $turn['code']);
                $this->assertSame($this->locador->denominacion, $turn['visible'][1]['presentation']['rows'][0]['locador']);
                $state['turns'][] = $turn;
            }
            $this->assertSame(4, \tests\support\OpenAIHttp::$requests);
            foreach ([$this->locador->denominacion, $this->locador->email, $this->locador->telefono,
                $request->hash, '/privado/no-enviar.pdf', '$ 300,00'] as $private) {
                $this->assertStringNotContainsString($private, json_encode(\tests\support\OpenAIHttp::$options));
                $this->assertStringNotContainsString($private, json_encode($turn['messages']));
                $this->assertStringNotContainsString($private, file_get_contents(Yii::getAlias('@runtime/asistente-diagnostics.log')));
            }
        } finally { putenv($oldKey === false ? 'OPENAI_API_KEY' : 'OPENAI_API_KEY=' . $oldKey); }
    }

    public function testTitularYTelefonoTrasFalloDelProveedorConservaReservasAnteriores()
    {
        $booking = $this->reserva('2086-07-01 10:00:00', '2086-07-20', '2086-07-26');
        Transport::reset();
        Transport::$response['data']['candidates'][0]['content']['parts'][0]['functionCall'] = [
            'id' => 'list-july', 'name' => 'listar_reservas_por_estadia',
            'args' => ['desde' => '2086-07-25', 'hasta' => '2086-07-31'],
        ];
        $service = new AsistenteService(new Transport(), new FuncionesReservas());
        $now = new \DateTimeImmutable('2086-10-23');
        $auth = function () { return $this->admin(); };
        $listing = $service->responder(['turns' => []], 'muestrame las reservas de la ultima semana de julio pasado', $now, $auth);
        $this->assertSame('ok', $listing['code']);
        $this->assertSame([(int) $booking->id], array_column($listing['messages'][2]['data']['reservas'], 'id'));

        // Shared transport maps a non-2xx HTTP response (including 503) to st=error.
        Transport::$response = ['st' => 'error'];
        $question = 'agrega quien las reservo y su telefono';
        $failure = $service->responder(['turns' => [$listing]], $question, $now, function () {
            $this->fail('Un fallo del proveedor no debe consultar datos de clientes.');
        });
        $this->assertSame('provider_error', $failure['code']);
        $this->assertStringContainsString('en esta misma conversación', $failure['visible'][1]['text']);

        Transport::reset();
        Transport::$response['data']['candidates'][0]['content']['parts'][0]['functionCall'] = [
            'id' => 'holder-and-phone', 'name' => 'consultar_detalle_reservas',
            'args' => ['origen' => 'anteriores', 'detalle' => 'contacto'],
        ];
        $contact = $service->responder(['turns' => [$listing, $failure]], $question, $now, $auth);
        $this->assertSame('ok', $contact['code']);
        $this->assertStringContainsString('Reserva #' . $booking->id, $contact['visible'][1]['text']);
        $this->assertStringContainsString($this->locador->denominacion, $contact['visible'][1]['text']);
        $this->assertStringContainsString('Teléfono: ' . $this->locador->telefono, $contact['visible'][1]['text']);
        $this->assertCount(1, $contact['messages'][1]['tool_calls']);
        $payload = Transport::$payloads[0];
        $this->assertStringContainsString($question, $payload['systemInstruction']['parts'][0]['text']);
        foreach ([$this->locador->denominacion, $this->locador->telefono, $this->locador->email] as $private) {
            $this->assertStringNotContainsString($private, json_encode($contact['messages']));
            $this->assertStringNotContainsString($private, json_encode($payload));
        }
    }

    public function testPagosRealesPorSolicitudYActualizacionDelSaldo()
    {
        $booking = $this->reserva('2086-07-01 10:00:00', '2086-07-20', '2086-07-26');
        $zero = $this->solicitudPagos($booking, 0, [['fecha' => '2086-07-01', 'monto' => 0]]);
        $paid = $this->solicitudPagos($booking, 300, [
            ['fecha' => '2086-07-01', 'monto' => 100, 'archivo' => '/privado/comprobante.pdf', 'nota' => 'Nota bancaria privada'],
            ['fecha' => '2086-07-02', 'monto' => 200],
        ]);
        $functions = new FuncionesReservas();
        $args = ['origen' => 'seleccionadas', 'ids_reservas' => [(int) $booking->id]];
        $result = $functions->consultarPagosReservas($this->admin(), $args);
        $text = $result['visible_text'];
        $this->assertStringContainsString('Solicitud #' . $zero->id . ' · No hay importes de pago positivos', $text);
        $this->assertStringContainsString('Solicitud #' . $paid->id . ' · Tiene importes de pago registrados', $text);
        $this->assertStringContainsString('Pagado registrado: $ 300,00', $text);
        $this->assertStringContainsString('Saldo según verificación: $ 700,00', $text);
        $this->assertStringContainsString('Movimientos registrados: 2 (2 con importe positivo)', $text);
        foreach (['/privado/comprobante.pdf', 'Nota bancaria privada'] as $private) {
            $this->assertStringNotContainsString($private, json_encode($result));
        }
        $this->assertSame($this->locador->email, $result['visible_presentation']['rows'][0]['email']);
        $providerData = $result;
        unset($providerData['visible_presentation'], $providerData['visible_text']);
        $this->assertStringNotContainsString($this->locador->email, json_encode($providerData));
        $this->assertSame('No determinable', $result['visible_presentation']['rows'][0]['cobrado']);
        $this->assertStringContainsString('Varias solicitudes', $result['visible_presentation']['rows'][0]['nota']);
        $paid->pagado = 500;
        $paid->registro_pagos = [['monto' => 500]];
        $this->save($paid);
        $updated = $functions->consultarPagosReservas($this->admin(), ['origen' => 'anteriores'], $result);
        $this->assertStringContainsString('Pagado registrado: $ 500,00', $updated['visible_text']);
    }

    public function testPagosSinSolicitudHistoricoSinMovimientosYDatosIncompletos()
    {
        $booking = $this->reserva('2086-07-01 10:00:00', '2086-07-20', '2086-07-26');
        $functions = new FuncionesReservas();
        $args = ['origen' => 'seleccionadas', 'ids_reservas' => [(int) $booking->id]];
        $this->assertStringContainsString('Sin solicitud asociada', $functions->consultarPagosReservas($this->admin(), $args)['visible_text']);
        $request = $this->solicitudPagos($booking, 250, []);
        $text = $functions->consultarPagosReservas($this->admin(), $args)['visible_text'];
        $this->assertStringContainsString('Tiene importes de pago registrados', $text);
        $this->assertStringContainsString('no tiene movimientos detallados', $text);
        $request->pagado = 0;
        $request->registro_pagos = [['dato_incompleto' => true]];
        $this->save($request);
        $this->assertStringContainsString('Información incompleta', $functions->consultarPagosReservas($this->admin(), $args)['visible_text']);
        $request->pagado = 250;
        $request->registro_pagos = [['monto' => 100]];
        $pending = Estado::find()->where(['slug' => 'confirmado-verificar-pago'])->one();
        if (!$pending) {
            $pending = new Estado(['slug' => 'confirmado-verificar-pago', 'descr' => 'Confirmado verificar pago']);
            $this->save($pending);
        }
        $request->id_estado = $pending->id;
        $this->save($request);
        $text = $functions->consultarPagosReservas($this->admin(), $args)['visible_text'];
        $this->assertStringContainsString('difiere de la suma', $text);
        $this->assertStringContainsString('Pago pendiente de verificación', $text);
        $this->assertStringContainsString('Pagado verificado: No determinable', $text);
        $this->assertStringContainsString('Saldo según verificación: No determinable', $text);
        $this->assertStringNotContainsString('Saldo según verificación: $ 750,00', $text);
    }

    public function testPagosPermisosValidacionYLimiteDeSolicitudes()
    {
        $booking = $this->reserva('2086-07-01 10:00:00', '2086-07-20', '2086-07-26');
        for ($i = 0; $i < 6; $i++) { $this->solicitudPagos($booking, 0, []); }
        $functions = new FuncionesReservas();
        $args = ['origen' => 'seleccionadas', 'ids_reservas' => [(int) $booking->id]];
        $text = $functions->consultarPagosReservas($this->admin(), $args)['visible_text'];
        $this->assertSame(5, substr_count($text, 'Solicitud #'));
        $this->assertStringContainsString('primeras 5 solicitudes', $text);
        foreach ([['origen' => 'anteriores'], $args + ['detalle' => 'general'],
            ['origen' => 'seleccionadas', 'ids_reservas' => range(1, 21)]] as $invalid) {
            try {
                $functions->validarPagosReservas($invalid);
                $this->fail('Invalid payment arguments accepted');
            } catch (\InvalidArgumentException $e) { $this->assertNotEmpty($e->getMessage()); }
        }
        $empty = $functions->consultarPagosReservas($this->admin(), ['origen' => 'anteriores'], ['ok' => true, 'reservas' => []]);
        $this->assertSame([], $empty['reservas']);
        foreach ([null, (object) ['nivel' => 2, 'activo' => 1]] as $denied) {
            try {
                $functions->consultarPagosReservas($denied, $args);
                $this->fail('Unauthorized payment access accepted');
            } catch (\yii\web\ForbiddenHttpException $e) { $this->assertSame(403, $e->statusCode); }
        }
        $this->expectException(\InvalidArgumentException::class);
        $functions->consultarPagosReservas($this->admin(), ['origen' => 'seleccionadas', 'ids_reservas' => [(int) $booking->id, 2147483647]]);
    }

    public function testContinuacionPagosNoRepiteDatosGeneralesNiEnviaFinanzasAGemini()
    {
        $booking = $this->reserva('2086-07-01 10:00:00', '2086-07-20', '2086-07-26');
        $this->solicitudPagos($booking, 300, [['monto' => 300, 'archivo' => '/privado/comprobante.pdf']]);
        Transport::reset();
        $call = &Transport::$response['data']['candidates'][0]['content']['parts'][0]['functionCall'];
        $call['name'] = 'consultar_detalle_reservas';
        $call['args'] = ['origen' => 'seleccionadas', 'ids_reservas' => [(int) $booking->id]];
        $service = new AsistenteService(new Transport(), new FuncionesReservas());
        $now = new \DateTimeImmutable();
        $auth = function () { return $this->admin(); };
        $details = $service->responder(['turns' => []], 'Sus datos', $now, $auth);
        $call['name'] = 'consultar_pagos_reservas';
        $call['args'] = ['origen' => 'anteriores'];
        $payments = $service->responder(['turns' => [$details]], '¿Tienen pagos registrados?', $now, $auth);
        $this->assertSame('ok', $payments['code']);
        $this->assertStringContainsString('Pagado registrado: $ 300,00', $payments['visible'][1]['text']);
        $this->assertStringNotContainsString('Titular:', $payments['visible'][1]['text']);
        $service->responder(['turns' => [$details, $payments]], '¿Y su saldo?', $now, $auth);
        foreach ([$this->locador->denominacion, $this->locador->email, '/privado/comprobante.pdf', 'Pagado registrado: $ 300,00'] as $private) {
            $this->assertStringNotContainsString($private, json_encode(Transport::$payloads));
            $this->assertStringNotContainsString($private, file_get_contents(Yii::getAlias('@runtime/asistente-diagnostics.log')));
        }
        $this->assertCount(3, Transport::$payloads);
    }

    private function solicitudPagos($booking, $paid, array $entries)
    {
        $suffix = bin2hex(random_bytes(5));
        $request = new RequestReserva(['id_reserva' => $booking->id, 'fecha' => '2086-07-01 10:00:00',
            'desde' => $booking->desde, 'hasta' => $booking->hasta, 'denominacion' => 'Cliente privado ' . $suffix,
            'email' => $suffix . '@example.com', 'pax' => 1, 'hash' => bin2hex(random_bytes(16)),
            'total' => 1000, 'pagado' => $paid, 'registro_pagos' => $entries, 'id_estado' => $this->estado->id]);
        $this->save($request);
        return $request;
    }

    public function testImpagasSinListadoPrevioIncluyePagosParcialesYNoAfirmaSaldosDesconocidos()
    {
        $bookings = [];
        foreach (['zero', 'partial', 'full', 'multiple', 'missing', 'uncertain'] as $i => $name) {
            $bookings[$name] = $this->reserva('1901-07-01 10:00:00', '1901-07-' . (20 + $i), '1901-07-26');
        }
        $this->solicitudPagos($bookings['zero'], 0, []);
        $partial = $this->solicitudPagos($bookings['partial'], 300, [['monto' => 300]]);
        $this->solicitudPagos($bookings['full'], 1000, [['monto' => 1000]]);
        $this->solicitudPagos($bookings['multiple'], 0, []);
        $this->solicitudPagos($bookings['multiple'], 0, []);
        $uncertain = $this->solicitudPagos($bookings['uncertain'], 100, [['monto' => 100]]);
        $pending = Estado::find()->where(['slug' => 'confirmado-verificar-pago'])->one();
        if (!$pending) { $pending = new Estado(['slug' => 'confirmado-verificar-pago', 'descr' => 'Pendiente verificar']); $this->save($pending); }
        $uncertain->id_estado = $pending->id;
        $this->save($uncertain);
        foreach (['pending_zero' => [], 'bad_history' => [['dato_incompleto' => true]]] as $name => $entries) {
            $bookings[$name] = $this->reserva('1901-07-01 10:00:00', '1901-07-25', '1901-07-26');
            $request = $this->solicitudPagos($bookings[$name], 0, $entries);
            $request->id_estado = $pending->id;
            $this->save($request);
        }
        Transport::reset();
        $call = &Transport::$response['data']['candidates'][0]['content']['parts'][0]['functionCall'];
        $call['name'] = 'listar_reservas_impagas';
        $call['args'] = [];
        $service = new AsistenteService(new Transport(), new FuncionesReservas());
        $auth = function () { return $this->admin(); };
        $now = new \DateTimeImmutable('2086-10-26');
        $turn = $service->responder(['turns' => []], 'dame las reservas impagas', $now, $auth);
        $this->assertSame('ok', $turn['code']);
        $data = $turn['messages'][2]['data'];
        $ids = array_column($data['reservas'], 'id');
        $this->assertContains((int) $bookings['zero']->id, $ids);
        $this->assertContains((int) $bookings['partial']->id, $ids);
        $this->assertContains((int) $bookings['pending_zero']->id, $ids);
        foreach (['full', 'multiple', 'missing', 'uncertain', 'bad_history'] as $name) { $this->assertNotContains((int) $bookings[$name]->id, $ids); }
        $this->assertNull($data['desde']);
        $this->assertTrue($turn['visible'][1]['presentation']['mostrar_saldo']);
        $rows = array_column($turn['visible'][1]['presentation']['rows'], null, 'reserva');
        $this->assertSame('$ 1.000,00', $rows['#' . $bookings['zero']->id]['saldo']);
        $this->assertSame('$ 700,00', $rows['#' . $bookings['partial']->id]['saldo']);
        $this->assertStringContainsString('Sin filtro de fechas', $turn['visible'][1]['text']);
        $call['name'] = 'consultar_detalle_reservas';
        $call['args'] = ['origen' => 'anteriores', 'detalle' => 'titular'];
        $detail = $service->responder(['turns' => [$turn]], 'Sus titulares', $now, $auth);
        $this->assertSame('ok', $detail['code']);
        foreach ([$this->locador->denominacion, $partial->hash, '$ 700,00'] as $private) {
            $this->assertStringNotContainsString($private, json_encode(Transport::$payloads));
            $this->assertStringNotContainsString($private, json_encode($turn['messages']));
        }
    }

    public function testImpagasFechasPermisosYArgumentosInvalidos()
    {
        $booking = $this->reserva('2086-07-01 10:00:00', '2086-07-20', '2086-07-26');
        $this->solicitudPagos($booking, 300, [['monto' => 300]]);
        $functions = new FuncionesReservas();
        $args = ['desde' => '2086-07-25', 'hasta' => '2086-07-31'];
        $this->assertSame([['id' => (int) $booking->id]], $functions->listarReservasImpagas($this->admin(), $args)['reservas']);
        foreach (['fecha_registro', 'ingreso'] as $criterion) {
            $empty = $functions->listarReservasImpagas($this->admin(), $args + ['criterio' => $criterion]);
            $this->assertSame([], $empty['reservas']);
            $this->assertStringContainsString('No se encontraron reservas con saldo pendiente determinable', $empty['visible_text']);
        }
        foreach ([['desde' => '2086-02-30', 'hasta' => '2086-03-01'], ['desde' => '2086-07-01'],
            ['criterio' => 'estadia'], ['criterio' => 'sql'], ['ids_reservas' => [1]], ['desde' => null, 'hasta' => null]] as $bad) {
            try { $functions->validarReservasImpagas($bad); $this->fail('Debe rechazar argumentos inválidos.'); }
            catch (\InvalidArgumentException $e) { $this->assertNotEmpty($e->getMessage()); }
        }
        foreach ([null, (object) ['nivel' => 2, 'activo' => 1], (object) ['nivel' => 1, 'activo' => 0]] as $identity) {
            try { $functions->listarReservasImpagas($identity, []); $this->fail('Debe exigir permisos.'); }
            catch (\yii\web\ForbiddenHttpException $e) { $this->assertSame(403, $e->statusCode); }
        }
    }

    public function testImpagasFiltraTitularConTildesOrdenYFechasAntesDelLimite()
    {
        $this->locador->denominacion = 'Rodríguez, José Alberto'; $this->save($this->locador);
        $expected = $this->reserva('1902-07-01 10:00:00', '1902-07-20', '1902-07-26');
        $this->solicitudPagos($expected, 300, [['monto' => 300]]);
        $paid = $this->reserva('1902-07-01 10:00:00', '1902-07-20', '1902-07-26');
        $this->solicitudPagos($paid, 1000, [['monto' => 1000]]);
        $otherPeriod = $this->reserva('1902-08-01 10:00:00', '1902-08-20', '1902-08-26');
        $this->solicitudPagos($otherPeriod, 0, []);
        foreach (['José Pérez', 'María Rodríguez'] as $name) {
            $suffix = bin2hex(random_bytes(5));
            $owner = new Locador(['denominacion' => $name, 'documento' => 'D' . $suffix,
                'email' => $suffix . '@example.com', 'telefono' => '123']); $this->save($owner);
            // More than 20 earlier unrelated rows must not consume the matching client's result limit.
            for ($i = 0; $i < 21; $i++) {
                $booking = $this->reserva('1902-07-01 10:00:00', '1902-07-10', '1902-07-11');
                $booking->id_locador = $owner->id; $this->save($booking); $this->solicitudPagos($booking, 0, []);
            }
        }
        $functions = new FuncionesReservas();
        $args = ['titular' => '  jose   rodriguez ', 'desde' => '1902-07-01', 'hasta' => '1902-07-31'];
        $result = $functions->listarReservasImpagas($this->admin(), $args);
        $this->assertSame([['id' => (int) $expected->id]], $result['reservas']);
        $this->assertFalse($result['truncado']);
        $this->assertTrue($result['filtro_titular_aplicado']);
        $this->assertStringContainsString('Titular: jose rodriguez', $result['visible_presentation']['comment']);
        $this->assertStringNotContainsString('Rodríguez, José Alberto', $result['text']);
        $both = $functions->listarReservasImpagas($this->admin(), ['titular' => 'RODRIGUEZ JOSE']);
        $this->assertSame([(int) $expected->id, (int) $otherPeriod->id], array_column($both['reservas'], 'id'));
        foreach (['Persona inexistente', 'zz_%', "zz' OR 1=1 --"] as $name) {
            $empty = $functions->listarReservasImpagas($this->admin(), ['titular' => $name]);
            $this->assertSame([], $empty['reservas']);
            $this->assertTrue($empty['filtro_titular_aplicado']);
        }
    }

    public function testImpagasNoIgnoraNombreDelUsuarioAunqueLaIAOmitaElArgumento()
    {
        $this->locador->denominacion = 'José Rodríguez'; $this->save($this->locador);
        $expected = $this->reserva('1903-07-01 10:00:00', '1903-07-20', '1903-07-26');
        $this->solicitudPagos($expected, 0, []);
        $other = $this->reserva('1903-07-01 10:00:00', '1903-07-20', '1903-07-26');
        $otherOwner = new Locador(['denominacion' => 'Otro cliente', 'documento' => bin2hex(random_bytes(8)),
            'email' => bin2hex(random_bytes(5)) . '@example.com', 'telefono' => '123']); $this->save($otherOwner);
        $other->id_locador = $otherOwner->id; $this->save($other); $this->solicitudPagos($other, 0, []);
        Transport::reset();
        $call = &Transport::$response['data']['candidates'][0]['content']['parts'][0]['functionCall'];
        $call['name'] = 'listar_reservas_impagas'; $call['args'] = [];
        $turn = (new AsistenteService(new Transport(), new FuncionesReservas()))->responder(['turns' => []],
            'dime las reservas impagas de jose rodriguez', new \DateTimeImmutable(), function () { return $this->admin(); });
        $this->assertSame('ok', $turn['code']);
        $this->assertSame([['id' => (int) $expected->id]], $turn['messages'][2]['data']['reservas']);
        $this->assertTrue($turn['messages'][2]['data']['filtro_titular_aplicado']);
        $this->assertStringContainsString('Titular: jose rodriguez', $turn['visible'][1]['presentation']['comment']);
        $this->assertStringNotContainsString('José Rodríguez', json_encode($turn['messages'], JSON_UNESCAPED_UNICODE));
        $this->assertStringNotContainsString($otherOwner->email, json_encode(Transport::$payloads));
        $this->assertNull((new FuncionesReservas())->titularImpagasEnTexto('reservas impagas de la última semana'));
    }

    public function testListarPorTitularIncluyePagadasEImpagasYPermiteAcotarFechas()
    {
        $this->locador->denominacion = 'José Rodríguez'; $this->save($this->locador);
        $unpaid = $this->reserva('1904-07-01 10:00:00', '1904-07-20', '1904-07-26'); $this->solicitudPagos($unpaid, 0, []);
        $paid = $this->reserva('1904-07-01 10:00:00', '1904-07-22', '1904-07-26'); $this->solicitudPagos($paid, 1000, [['monto' => 1000]]);
        $old = $this->reserva('1904-06-01 10:00:00', '1904-06-20', '1904-06-26'); $this->solicitudPagos($old, 0, []);
        $functions = new FuncionesReservas();
        $all = $functions->listarReservasPorTitular($this->admin(), ['titular' => 'RODRIGUEZ JOSE']);
        $this->assertSame([(int) $paid->id, (int) $unpaid->id, (int) $old->id], array_column($all['reservas'], 'id'));
        $dated = $functions->listarReservasPorTitular($this->admin(), ['titular' => 'jose rodriguez', 'desde' => '1904-07-25', 'hasta' => '1904-07-31']);
        $this->assertSame([(int) $paid->id, (int) $unpaid->id], array_column($dated['reservas'], 'id'));
        $this->assertStringNotContainsString('José Rodríguez', $all['text']);
        $this->assertSame([], $functions->listarReservasPorTitular($this->admin(), ['titular' => 'Persona inexistente'])['reservas']);
        try { $functions->listarReservasPorTitular($this->admin(), []); $this->fail('Debe exigir titular.'); }
        catch (\InvalidArgumentException $e) { $this->assertNotEmpty($e->getMessage()); }
        $this->expectException(\yii\web\ForbiddenHttpException::class);
        $functions->listarReservasPorTitular((object) ['nivel' => 4, 'activo' => 1], ['titular' => 'jose rodriguez']);
    }

    public function testImpagasValidaFiltroTitularSinAmpliarUnaBusquedaInvalida()
    {
        $functions = new FuncionesReservas();
        foreach ([null, [], '', ' ', 'x', str_repeat('a', 101), "Jose\nRodriguez"] as $invalid) {
            try { $functions->listarReservasImpagas($this->admin(), ['titular' => $invalid]); $this->fail('Filtro inválido.'); }
            catch (\InvalidArgumentException $e) { $this->assertNotEmpty($e->getMessage()); }
        }
        $this->expectException(\yii\web\ForbiddenHttpException::class);
        $functions->listarReservasImpagas((object) ['nivel' => 4, 'activo' => 1], ['titular' => 'José Rodríguez']);
    }

    public function testImpagasLimitaResultadosYAdvierteTruncamiento()
    {
        for ($i = 0; $i < 22; $i++) {
            $booking = $this->reserva('1900-01-01 10:00:00', '1900-07-20', '1900-07-26');
            $this->solicitudPagos($booking, 0, []);
        }
        Transport::reset();
        $call = &Transport::$response['data']['candidates'][0]['content']['parts'][0]['functionCall'];
        $call['name'] = 'listar_reservas_impagas';
        $call['args'] = ['desde' => '1900-07-20', 'hasta' => '1900-07-26'];
        $turn = (new AsistenteService(new Transport(), new FuncionesReservas()))->responder(['turns' => []],
            'Reservas impagas del período', new \DateTimeImmutable(), function () { return $this->admin(); });
        $this->assertSame('ok', $turn['code']);
        $this->assertCount(20, $turn['messages'][2]['data']['reservas']);
        $this->assertTrue($turn['messages'][2]['data']['truncado']);
        $this->assertStringContainsString('Se muestran hasta 20 reservas', $turn['visible'][1]['text']);
    }

    private function admin() { return (object) ['nivel' => 1, 'activo' => 1]; }

    private function reserva($fecha, $desde, $hasta)
    {
        $model = new Reserva(['fecha' => $fecha, 'desde' => $desde, 'hasta' => $hasta,
            'id_locador' => $this->locador->id, 'id_estado' => $this->estado->id, 'pax' => 1, 'obs' => 'Nota privada']);
        $this->save($model);
        return $model;
    }

    private function save($model) { $this->assertTrue($model->save(), implode(' | ', $model->getErrorSummary(true))); }
}
