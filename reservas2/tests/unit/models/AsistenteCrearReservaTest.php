<?php
namespace tests\unit\models;
require_once dirname(__DIR__, 2) . '/_support/AsistenteFixtures.php';

use app\components\asistente\AsistenteService;
use app\components\asistente\BorradorReserva;
use app\components\asistente\CrearReservaService;
use app\components\asistente\FuncionesReservas;
use app\models\AsistenteReservaForm;
use app\models\Cabana;
use app\models\CabanaTarifa;
use app\models\Estado;
use app\models\Locador;
use app\models\RequestReserva;
use app\models\Reserva;
use app\models\ReservaCabana;
use app\models\Tarifa;
use tests\support\GeminiAsistenteSimulado as Transport;
use Yii;

class AsistenteCrearReservaTest extends \Codeception\Test\Unit
{
    use \tests\support\RuntimeAsistenteAislado;
    use \tests\support\CreaDatosAsistente;
    private $admin;
    private $cabana;
    private $tarifa;
    private $proposal;

    protected function _before()
    {
        $this->iniciarRuntimeAsistente();
        Yii::$app->controller = new \yii\web\Controller('asistente', Yii::$app);
        $this->admin = $this->usuarioAsistente();
        Yii::$app->user->setIdentity($this->admin);
        $free = array_values(array_diff(range(1, 99), Cabana::find()->select('numero')->column()));
        $this->cabana = new Cabana(['numero' => $free[0], 'descr' => 'Creación IA', 'activa' => 1,
            'checkin' => '14:00', 'checkout' => '11:00', 'max_pax' => 4, 'color_cabana' => '#3cb44b']);
        $this->save($this->cabana);
        $this->tarifa = new Tarifa(['descr' => 'Creación IA', 'inicio' => '2088-11-01', 'fin' => '2088-11-30',
            'activa' => 1, 'min_dias' => 3, 'valor_dia' => 100]);
        $this->save($this->tarifa);
        $this->save(new CabanaTarifa(['id_cabana' => $this->cabana->id, 'id_tarifa' => $this->tarifa->id]));
        if (!Estado::findOne(['slug' => 'confirmado'])) {
            $this->save(new Estado(['slug' => 'confirmado', 'descr' => 'Confirmado']));
        }
        $this->proposal = ['origen' => 'seleccionadas', 'numeros_cabanas' => [(int) $this->cabana->numero],
            'desde' => '2088-11-02', 'hasta' => '2088-11-05'];
    }

    protected function _after()
    {
        Yii::$app->user->setIdentity(null);
        $this->cerrarRuntimeAsistente();
    }

    private function form()
    {
        $suffix = bin2hex(random_bytes(6));
        return new AsistenteReservaForm(['documento' => 'IA' . $suffix, 'denominacion' => 'Pasajero privado <script>',
            'email' => $suffix . '@example.com', 'telefono' => '111222333', 'domicilio' => '', 'obs' => 'Reserva sin seña']);
    }

    private function draft($form = null)
    {
        $service = new CrearReservaService();
        $form = $form ?: $this->form();
        $review = $service->revisar($this->admin, $this->proposal, $form);
        $this->assertNotNull($review, json_encode($form->errors));
        return ['owner' => (string) $this->admin->id, 'expires' => time() + 900, 'token' => bin2hex(random_bytes(16)),
            'proposal' => $this->proposal, 'review' => $review];
    }

    public function testFormularioPrivadoGuardaLocalidadYReferencia()
    {
        $localidad = \app\models\Localidad::find()->one();
        $form = $this->form();
        $form->id_localidad = $localidad->id;
        $form->id_provincia = $localidad->id_provincia;
        $form->referencia = '  Referencia privada  ';
        $draft = $this->draft($form);
        $this->assertSame('Referencia privada', $draft['review']['referencia']);
        $id = (new CrearReservaService())->confirmar($this->admin, $draft);
        $reserva = Reserva::findOne($id);
        $this->assertSame((int) $localidad->id, (int) Locador::findOne($reserva->id_locador)->id_localidad);
        $this->assertSame('Referencia privada', RequestReserva::findOne(['id_reserva' => $id])->referencia);
    }

    public function testPrepararNoEscribeYNoEnviaTokenNiDatosPrivadosAlProveedor()
    {
        $before = Reserva::find()->count();
        Transport::reset();
        $call = &Transport::$response['data']['candidates'][0]['content']['parts'][0]['functionCall'];
        $call['name'] = 'preparar_reserva'; $call['args'] = $this->proposal;
        $service = new AsistenteService(new Transport(), new FuncionesReservas());
        $turn = $service->responder(['turns' => []], 'Reservar la cabaña para ese período', new \DateTimeImmutable(), function () { return $this->admin; });
        $this->assertSame('ok', $turn['code']);
        $this->assertSame($before, Reserva::find()->count());
        $this->assertEquals($this->proposal, $turn['_proposal']);
        $data = $turn['messages'][2]['data'];
        $this->assertArrayNotHasKey('visible_proposal', $data);
        $this->assertArrayNotHasKey('borrador', $data);
        $this->assertSame(300.0, $data['cabanas'][0]['total']);
        $this->assertStringContainsString('Aún no se guardó', $data['text']);
        $args = $this->proposal + ['email' => 'prohibido@example.com'];
        $this->expectException(\InvalidArgumentException::class);
        (new FuncionesReservas())->prepararReserva($this->admin, $args);
    }

    public function testInicioReservaPidePeriodoYAnioLuegoMuestraOpcionesYPreparaLaElegida()
    {
        Transport::reset();
        $service = new AsistenteService(new Transport(), new FuncionesReservas());
        $state = ['turns' => []];
        $now = new \DateTimeImmutable('2026-10-26');
        $call = &Transport::$response['data']['candidates'][0]['content']['parts'][0]['functionCall'];
        $call['name'] = 'iniciar_reserva'; $call['args'] = [];
        $before = Reserva::find()->count();
        $initial = $service->responder($state, 'Iniciemos', $now, function () { return $this->admin; });
        $this->assertSame('ok', $initial['code']);
        $this->assertStringContainsString('fechas de ingreso y salida', $initial['visible'][1]['text']);
        $this->assertArrayNotHasKey('_proposal', $initial);
        $state['turns'][] = $initial;
        $call['args'] = ['falta' => 'anio'];
        $year = $service->responder($state, 'Necesito indicar el año', $now, function () { return $this->admin; });
        $this->assertStringContainsString('¿Para qué año', $year['visible'][1]['text']);
        $this->assertArrayNotHasKey('desde', $year['messages'][2]['data']);
        $this->assertArrayNotHasKey('presentation', $year['visible'][1]);
        $state['turns'][] = $year;
        $call['args'] = ['desde' => '2088-11-02', 'hasta' => '2088-11-05'];
        $options = $service->responder($state, 'Consultar opciones', $now, function () { return $this->admin; });
        $this->assertSame('ok', $options['code']);
        $this->assertSame('tarifas', $options['visible'][1]['presentation']['type']);
        $this->assertContains((int) $this->cabana->id, array_map('intval', array_column($options['messages'][2]['data']['cabanas'], 'id')));
        $this->assertStringContainsString('qué número', implode(' ', $options['visible'][1]['presentation']['notes']));
        $this->assertArrayNotHasKey('_proposal', $options);
        $state['turns'][] = $options;
        $call['name'] = 'preparar_reserva';
        $call['args'] = ['origen' => 'seleccionadas', 'numeros_cabanas' => [(int) $this->cabana->numero]];
        $selection = $service->responder($state, 'La cabaña ' . $this->cabana->numero, $now, function () { return $this->admin; });
        $this->assertSame('ok', $selection['code']);
        $this->assertSame('2088-11-02', $selection['_proposal']['desde']);
        $this->assertSame('2088-11-05', $selection['_proposal']['hasta']);
        $this->assertSame([(int) $this->cabana->numero], $selection['_proposal']['numeros_cabanas']);
        $this->assertSame($before, Reserva::find()->count());
    }

    public function testInicioReservaConDiasYMesPideSoloAnioSinConsultarAlProveedor()
    {
        Transport::reset();
        Transport::$response['data']['candidates'][0]['content']['parts'] = [['text' => 'Reserva confirmada inventada']];
        $turn = (new AsistenteService(new Transport(), new FuncionesReservas()))->responder(['turns' => []],
            'ingresar una reserva desde 15 de agosto al 30 de agosto', new \DateTimeImmutable(), function () {
                return $this->admin;
            });
        $this->assertSame('ok', $turn['code']);
        $this->assertStringContainsString('¿Para qué año', $turn['visible'][1]['text']);
        $this->assertSame('anio', $turn['flujo_reserva']['etapa']);
        $this->assertCount(0, Transport::$payloads);
        $this->assertStringNotContainsString('inventada', $turn['visible'][1]['text']);
        $this->assertArrayNotHasKey('_proposal', $turn);
    }

    public function testDialogoConservaDiasMesYAnioSinDependerDeRespuestasDeLaIA()
    {
        Transport::reset();
        $service = new AsistenteService(new Transport(), new FuncionesReservas());
        $state = ['turns' => []];
        $now = new \DateTimeImmutable('2026-10-26');
        $identity = function () { return $this->admin; };
        $start = $service->responder($state, 'quiero hacer una reserva', $now, $identity);
        $this->assertSame('periodo', $start['flujo_reserva']['etapa']);
        $this->assertStringContainsString('fechas de ingreso y salida', $start['visible'][1]['text']);
        $this->assertStringNotContainsString('¿Para qué año', $start['visible'][1]['text']);
        $state['turns'][] = $start;
        $dates = $service->responder($state, 'para el 15 al 30 de agosto', $now, $identity);
        $this->assertSame('anio', $dates['flujo_reserva']['etapa']);
        $this->assertSame(15, $dates['flujo_reserva']['dia_desde']);
        $this->assertSame(30, $dates['flujo_reserva']['dia_hasta']);
        $this->assertSame(8, $dates['flujo_reserva']['mes_desde']);
        $this->assertStringContainsString('15/08 al 30/08', $dates['visible'][1]['text']);
        $this->assertStringContainsString('¿Para qué año', $dates['visible'][1]['text']);
        $this->assertArrayNotHasKey('seleccion_reserva', $dates);
        $state['turns'][] = $dates;
        $call = &Transport::$response['data']['candidates'][0]['content']['parts'][0]['functionCall'];
        $call['name'] = 'iniciar_reserva'; $call['args'] = ['falta' => 'anio'];
        $toolClarification = $service->responder($state, '¿qué dato te falta?', $now, $identity);
        $this->assertSame($dates['flujo_reserva'], $toolClarification['flujo_reserva']);
        $state['turns'][] = $toolClarification;
        // A provider that returns prose instead of a call must not reset the pending question.
        Transport::$response['data']['candidates'][0]['content']['parts'] = [['text' => 'Respuesta libre no confiable']];
        $clarification = $service->responder($state, '¿qué dato te falta?', $now, $identity);
        $this->assertStringContainsString('confirmá el año', $clarification['visible'][1]['text']);
        $this->assertStringContainsString('15/08 al 30/08', $clarification['visible'][1]['text']);
        $this->assertSame($dates['flujo_reserva'], $clarification['flujo_reserva']);
        // Simulate whole-turn trimming: latest explicit workflow state is sufficient.
        $year = $service->responder(['turns' => [$clarification]], '2088', $now, $identity);
        $this->assertSame('2088-08-15', $year['seleccion_reserva']['desde']);
        $this->assertSame('2088-08-30', $year['seleccion_reserva']['hasta']);
        $this->assertSame('seleccion', $year['flujo_reserva']['etapa']);
        $this->assertSame('tarifas', $year['visible'][1]['presentation']['type']);
        $this->assertCount(2, Transport::$payloads); // Only the open-ended clarifications used the provider.
    }

    public function testAnioPrimeroRangoDespuesYSeleccionUsaPeriodoDelServidor()
    {
        Transport::reset();
        $service = new AsistenteService(new Transport(), new FuncionesReservas());
        $now = new \DateTimeImmutable('2026-10-26'); $identity = function () { return $this->admin; };
        $first = $service->responder(['turns' => []], 'quiero hacer una reserva', $now, $identity);
        $year = $service->responder(['turns' => [$first]], 'para el año 2088', $now, $identity);
        $this->assertSame(2088, $year['flujo_reserva']['anio']);
        $dates = $service->responder(['turns' => [$year]], 'del 2 al 5 de noviembre', $now, $identity);
        $this->assertSame('2088-11-02', $dates['seleccion_reserva']['desde']);
        $this->assertSame('2088-11-05', $dates['seleccion_reserva']['hasta']);
        $this->assertContains((int) $this->cabana->id, array_map('intval', array_column($dates['seleccion_reserva']['cabanas'], 'id')));
        $call = &Transport::$response['data']['candidates'][0]['content']['parts'][0]['functionCall'];
        $call['name'] = 'preparar_reserva';
        $call['args'] = ['origen' => 'seleccionadas', 'numeros_cabanas' => [(int) $this->cabana->numero]];
        $selection = $service->responder(['turns' => [$dates]], 'la cabaña ' . $this->cabana->numero, $now, $identity);
        $this->assertSame('ok', $selection['code']);
        $this->assertSame('2088-11-02', $selection['_proposal']['desde']);
        $this->assertSame('2088-11-05', $selection['_proposal']['hasta']);
        $this->assertCount(1, Transport::$payloads);
    }

    public function testConversacionAnteriorSinEstadoExplicitoRecuperaFechasPendientes()
    {
        $history = ['turns' => [
            ['messages' => [['role' => 'user', 'text' => 'quiero hacer una reserva'],
                ['role' => 'assistant', 'text' => '¿Para qué año querés hacer la reserva?']]],
            ['messages' => [['role' => 'user', 'text' => 'para el 15 al 30 de agosto'],
                ['role' => 'assistant', 'text' => 'Respuesta genérica anterior']]],
        ]];
        $turn = (new AsistenteService(new Transport(), new FuncionesReservas()))->responder($history, '2088',
            new \DateTimeImmutable(), function () { return $this->admin; });
        $this->assertSame('2088-08-15', $turn['seleccion_reserva']['desde']);
        $this->assertSame('2088-08-30', $turn['seleccion_reserva']['hasta']);
    }

    public function testFlujoNoInfiereAnioYRechazaFechasImposibles()
    {
        $service = new AsistenteService(new Transport(), new FuncionesReservas());
        $now = new \DateTimeImmutable('2026-10-26'); $identity = function () { return $this->admin; };
        $start = $service->responder(['turns' => []], 'quiero hacer una reserva del 30 al 31 de febrero', $now, $identity);
        $this->assertArrayNotHasKey('anio', $start['flujo_reserva']);
        $turn = $service->responder(['turns' => [$start]], '2027', $now, $identity);
        $this->assertSame('invalid_arguments', $turn['code']);
        $this->assertArrayNotHasKey('seleccion_reserva', $turn);
        $this->assertArrayNotHasKey('_proposal', $turn);
    }

    public function testInicioReservaRechazaArgumentosAmbiguosYExigePermiso()
    {
        $functions = new FuncionesReservas();
        foreach ([['desde' => '2088-11-02'], ['falta' => 'anio', 'desde' => '2088-11-02', 'hasta' => '2088-11-05'],
            ['desde' => '2088-11-02', 'hasta' => '2088-11-02'], ['falta' => 'cabana'], ['total' => 1]] as $args) {
            try { $functions->iniciarReserva($this->admin, $args); $this->fail('Debe rechazar argumentos.'); }
            catch (\InvalidArgumentException $e) { $this->assertNotEmpty($e->getMessage()); }
        }
        $this->expectException(\yii\web\ForbiddenHttpException::class);
        $functions->iniciarReserva((object) ['activo' => 1, 'nivel' => 4], []);
    }

    public function testConfirmacionSinPagoEsAtomicaEIdempotente()
    {
        $draft = $this->draft();
        $service = new CrearReservaService();
        $before = Reserva::find()->count();
        $id = $service->confirmar($this->admin, $draft);
        $this->assertSame($id, $service->confirmar($this->admin, $draft));
        $this->assertSame((int) $before + 1, (int) Reserva::find()->count());
        $reserva = Reserva::findOne($id);
        $this->assertSame('2088-11-02 14:00:00', $reserva->desde);
        $this->assertSame('2088-11-05 11:00:00', $reserva->hasta);
        $this->assertSame(4, (int) $reserva->pax);
        $this->assertSame('confirmado', $reserva->estado->slug);
        $request = RequestReserva::findOne(['id_reserva' => $id]);
        $this->assertSame(0.0, (float) $request->pagado);
        $this->assertSame([], $request->registro_pagos);
        $this->assertSame(300.0, (float) $request->total);
        $this->assertCount(1, $request->requestCabanas);
        $this->assertCount(1, $reserva->reservaCabanas);
        $this->assertSame(300.0, (float) $reserva->reservaCabanas[0]->valor);
        $this->assertSame((int) $this->admin->id, (int) $request->created_by);
        $this->assertNotEmpty($request->codigo_reserva);
    }

    public function testCambioDeTarifaNoGuardaHastaRevisarOtraVez()
    {
        $draft = $this->draft();
        $this->tarifa->valor_dia = 200; $this->save($this->tarifa);
        $before = Reserva::find()->count();
        try { (new CrearReservaService())->confirmar($this->admin, $draft); $this->fail('Debe rechazar tarifa cambiada.'); }
        catch (\DomainException $e) { $this->assertStringContainsString('Cambiaron', $e->getMessage()); }
        $this->assertSame($before, Reserva::find()->count());
        $this->assertNull(Locador::findOne(['documento' => $draft['review']['customer']['documento']]));
        $id = (new CrearReservaService())->confirmar($this->admin, $this->draft());
        $this->assertSame(600.0, (float) RequestReserva::findOne(['id_reserva' => $id])->total);
    }

    public function testSolapamientoRechazadoYRecambioEnMismoDiaPermitido()
    {
        $a = $this->draft(); $b = $this->draft();
        $service = new CrearReservaService();
        $service->confirmar($this->admin, $a);
        try { $service->confirmar($this->admin, $b); $this->fail('No debe duplicar ocupación.'); }
        catch (\DomainException $e) { $this->assertStringContainsString('reservada', $e->getMessage()); }
        $this->proposal['desde'] = '2088-11-05'; $this->proposal['hasta'] = '2088-11-08';
        $this->assertGreaterThan(0, $service->confirmar($this->admin, $this->draft()));
    }

    public function testPasajeroExistenteNoSeModificaYSinDatosInventados()
    {
        $form = $this->form();
        $existing = $form->locador(); $this->save($existing);
        $form->denominacion = 'No sobrescribir'; $form->telefono = '999';
        $draft = $this->draft($form);
        $id = (new CrearReservaService())->confirmar($this->admin, $draft);
        $this->assertSame((int) $existing->id, (int) Reserva::findOne($id)->id_locador);
        $existing->refresh();
        $this->assertSame('Pasajero privado <script>', $existing->denominacion);
        $this->assertSame('111222333', $existing->telefono);
        $invalid = $this->form(); $invalid->email = '';
        $this->assertNull((new CrearReservaService())->revisar($this->admin, $this->proposal, $invalid));
        $this->assertArrayHasKey('email', $invalid->errors);
    }

    public function testSinPermisoNoPuedePrepararNiConfirmar()
    {
        $draft = $this->draft();
        $this->expectException(\yii\web\ForbiddenHttpException::class);
        (new CrearReservaService())->confirmar((object) ['id' => $this->admin->id, 'nivel' => 4, 'activo' => 1], $draft);
    }

    public function testNoAceptaDiaCeroNiMinimoTarifaSuperiorALaEstadia()
    {
        $args = $this->proposal; $args['hasta'] = $args['desde'];
        try { (new FuncionesReservas())->prepararReserva($this->admin, $args); $this->fail('Día cero.'); }
        catch (\InvalidArgumentException $e) { $this->assertNotEmpty($e->getMessage()); }
        $args['hasta'] = '2088-11-03';
        $this->expectException(\DomainException::class);
        (new FuncionesReservas())->prepararReserva($this->admin, $args);
    }

    public function testFalloAlGuardarDetalleRevierteSolicitudReservaYPasajero()
    {
        $draft = $this->draft();
        $reservas = Reserva::find()->count();
        $requests = RequestReserva::find()->count();
        $handler = function ($event) { $event->isValid = false; };
        \yii\base\Event::on(\app\models\RequestCabana::class, \yii\db\ActiveRecord::EVENT_BEFORE_INSERT, $handler);
        try {
            (new CrearReservaService())->confirmar($this->admin, $draft);
            $this->fail('Se debe revertir toda la transacción.');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('No se pudo guardar', $e->getMessage());
        } finally {
            \yii\base\Event::off(\app\models\RequestCabana::class, \yii\db\ActiveRecord::EVENT_BEFORE_INSERT, $handler);
        }
        $this->assertSame($reservas, Reserva::find()->count());
        $this->assertSame($requests, RequestReserva::find()->count());
        $this->assertNull(Locador::findOne(['documento' => $draft['review']['customer']['documento']]));
    }

    public function testGuardiaDeLosFlujosExistentesTambienVeLaNuevaReserva()
    {
        (new CrearReservaService())->confirmar($this->admin, $this->draft());
        $this->expectException(\DomainException::class);
        \app\components\ReservaOcupacion::exigirLibres([$this->cabana->id], '2088-11-03 14:00:00', '2088-11-06 23:59:59');
    }

    public function testCambioDeHorariosOCapacidadExigeNuevaRevision()
    {
        $draft = $this->draft();
        $this->cabana->checkout = '10:00'; $this->save($this->cabana);
        $this->expectException(\DomainException::class);
        (new CrearReservaService())->confirmar($this->admin, $draft);
    }

    public function testBorradorVenceYCambiaConUsuarioConversacionOToken()
    {
        $store = new class { public $data; public function set($k, $v) { $this->data = $v; } public function get($k) { return $this->data; } };
        $drafts = new BorradorReserva($store);
        $draft = $drafts->crear(1, 'conv', $this->proposal);
        foreach ([[2, 'conv', $draft['token']], [1, 'otra', $draft['token']], [1, 'conv', 'falso']] as $args) {
            try { $drafts->exigir(...$args); $this->fail('No debe aceptar borrador ajeno.'); }
            catch (\yii\web\ConflictHttpException $e) { $this->assertSame(409, $e->statusCode); }
        }
        $draft['expires'] = time() - 1; $drafts->guardar($draft);
        $this->expectException(\yii\web\ConflictHttpException::class);
        $drafts->exigir(1, 'conv', $draft['token']);
    }

    private function borradorEliminarReserva($id)
    {
        $form = new \app\models\AsistenteEliminarReservaForm(['revisado' => '1']);
        $review = (new \app\components\asistente\EliminarReservaService())->revisar($this->admin, ['id_reserva' => $id], $form);
        $this->assertNotNull($review);
        return ['token' => bin2hex(random_bytes(16)), 'owner' => (string) $this->admin->id,
            'expires' => time() + 900, 'proposal' => ['id_reserva' => $id], 'review' => $review];
    }

    public function testEliminarReservaLiberaOcupacionYConservaSolicitudPagosChatYComprobantes()
    {
        $id = (new CrearReservaService())->confirmar($this->admin, $this->draft());
        $reserva = Reserva::findOne($id); $holder = $reserva->id_locador;
        $request = RequestReserva::findOne(['id_reserva' => $id]);
        $dir = Yii::$app->runtimePath . '/priv_comprobantes'; \yii\helpers\FileHelper::createDirectory($dir, 0700);
        $file = $dir . '/reserva-eliminada-conservar.pdf'; file_put_contents($file, 'Comprobante que se conserva');
        $entries = [['fecha' => '2026-10-27 10:00:00', 'monto' => 100, 'archivo' => $file, 'nota' => 'Pago privado']];
        $request->pagado = 100; $request->registro_pagos = $entries; $request->obs = 'Observación privada'; $this->save($request);
        $message = new \app\models\RequestResponse(['id_request' => $request->id, 'fecha' => '2026-10-27 10:00:00', 'response' => 'Mensaje privado', 'is_response' => 1]); $this->save($message);
        $functions = new FuncionesReservas();
        $args = ['desde' => '2088-11-02', 'hasta' => '2088-11-05'];
        $this->assertNotContains((int) $this->cabana->id, array_map('intval', array_column($functions->consultarDisponibilidad($this->admin, $args)['cabanas'], 'id')));
        $draft = $this->borradorEliminarReserva($id);
        $service = new \app\components\asistente\EliminarReservaService();
        $result = $service->confirmar($this->admin, $draft);
        $this->assertSame($id, $result['id_reserva']); $this->assertTrue($result['eliminada']);
        $repeated = $service->confirmar($this->admin, $draft);
        $this->assertSame($id, $repeated['id_reserva']); $this->assertFalse($repeated['eliminada']);
        $this->assertNull(Reserva::findOne($id)); $this->assertSame(0, (int) ReservaCabana::find()->where(['id_reserva' => $id])->count());
        $request->refresh();
        $this->assertNull($request->id_reserva); $this->assertSame(100.0, (float) $request->pagado);
        $this->assertEquals($entries, $request->registro_pagos); $this->assertSame('Observación privada', $request->obs);
        $this->assertSame(1, (int) \app\models\RequestCabana::find()->where(['id_request' => $request->id])->count());
        $this->assertNotNull(\app\models\RequestResponse::findOne($message->id)); $this->assertNotNull(Locador::findOne($holder));
        $this->assertFileExists($file);
        $this->assertContains((int) $this->cabana->id, array_map('intval', array_column($functions->consultarDisponibilidad($this->admin, $args)['cabanas'], 'id')));
    }

    public function testEliminarReservaRequiereRevisionYRespetaPropietarioVencimientoYPermisos()
    {
        $id = (new CrearReservaService())->confirmar($this->admin, $this->draft());
        $service = new \app\components\asistente\EliminarReservaService();
        $this->assertNull($service->revisar($this->admin, ['id_reserva' => $id], new \app\models\AsistenteEliminarReservaForm()));
        $draft = $this->borradorEliminarReserva($id);
        $without = $draft; unset($without['review']);
        $expired = $draft; $expired['expires'] = time() - 1;
        $foreign = $draft; $foreign['owner'] = 'otro';
        foreach ([$without, $expired, $foreign] as $invalid) {
            try { $service->confirmar($this->admin, $invalid); $this->fail('Debe rechazar.'); }
            catch (\DomainException $e) { $this->assertNotEmpty($e->getMessage()); }
        }
        $this->assertNotNull(Reserva::findOne($id));
        $this->expectException(\yii\web\ForbiddenHttpException::class);
        $service->confirmar((object) ['id' => $this->admin->id, 'activo' => 1, 'nivel' => 4], $draft);
    }

    public function testCambiosDePagosOReservaExigenRevisarAntesDeEliminar()
    {
        $id = (new CrearReservaService())->confirmar($this->admin, $this->draft());
        $draft = $this->borradorEliminarReserva($id);
        $request = RequestReserva::findOne(['id_reserva' => $id]); $request->pagado = 100; $request->registro_pagos = [['monto' => 100]]; $this->save($request);
        try { (new \app\components\asistente\EliminarReservaService())->confirmar($this->admin, $draft); $this->fail('Pago nuevo.'); }
        catch (\DomainException $e) { $this->assertStringContainsString('Cambiaron', $e->getMessage()); }
        $this->assertNotNull(Reserva::findOne($id)); $request->refresh(); $this->assertSame($id, (int) $request->id_reserva);
        $draft = $this->borradorEliminarReserva($id);
        $reserva = Reserva::findOne($id); $reserva->obs = 'Modificada por otro operador'; $this->save($reserva);
        $this->expectException(\DomainException::class);
        (new \app\components\asistente\EliminarReservaService())->confirmar($this->admin, $draft);
    }

    public function testFalloDeEliminacionRevierteDesvinculacionYDetalle()
    {
        $id = (new CrearReservaService())->confirmar($this->admin, $this->draft());
        $request = RequestReserva::findOne(['id_reserva' => $id]); $draft = $this->borradorEliminarReserva($id);
        $handler = function ($event) { $event->isValid = false; };
        \yii\base\Event::on(Reserva::class, \yii\db\ActiveRecord::EVENT_BEFORE_DELETE, $handler);
        try { (new \app\components\asistente\EliminarReservaService())->confirmar($this->admin, $draft); $this->fail('Debe revertir.'); }
        catch (\DomainException $e) { $this->assertStringContainsString('No se pudo eliminar', $e->getMessage()); }
        finally { \yii\base\Event::off(Reserva::class, \yii\db\ActiveRecord::EVENT_BEFORE_DELETE, $handler); }
        $this->assertNotNull(Reserva::findOne($id)); $request->refresh(); $this->assertSame($id, (int) $request->id_reserva);
        $this->assertSame(1, (int) ReservaCabana::find()->where(['id_reserva' => $id])->count());
    }

    public function testSeConservanVariasSolicitudesAlEliminarUnaReserva()
    {
        $id = (new CrearReservaService())->confirmar($this->admin, $this->draft());
        $request = RequestReserva::findOne(['id_reserva' => $id]);
        $other = new RequestReserva($request->attributes); $other->id = null; $other->hash = bin2hex(random_bytes(16));
        $other->codigo_reserva = RequestReserva::generateUniqueCodigoReserva($other->email); $this->save($other);
        (new \app\components\asistente\EliminarReservaService())->confirmar($this->admin, $this->borradorEliminarReserva($id));
        foreach ([$request, $other] as $item) { $item->refresh(); $this->assertNull($item->id_reserva); }
        $this->assertNull(Reserva::findOne($id));
    }

    public function testDialogoEliminarReservaPorTitularNoEliminaPagosNiReservaSinConfirmar()
    {
        Transport::reset();
        $id = (new CrearReservaService())->confirmar($this->admin, $this->draft());
        $owner = Reserva::findOne($id)->locador; $owner->denominacion = 'José Rodríguez'; $this->save($owner);
        $service = new AsistenteService(new Transport(), new FuncionesReservas());
        $identity = function () { return $this->admin; }; $now = new \DateTimeImmutable();
        $start = $service->responder(['turns' => []], 'eliminar una reserva cargada por error', $now, $identity);
        $this->assertSame('reserva', $start['flujo_eliminar_reserva']['etapa']);
        $list = $service->responder(['turns' => [$start]], 'mostrame las reservas a nombre de jose rodriguez', $now, $identity);
        $this->assertSame([['id' => $id]], $list['seleccion_reservas']['reservas']);
        $selection = $service->responder(['turns' => [$list]], 'reserva ' . $id, $now, $identity);
        $this->assertSame(['id_reserva' => $id], $selection['_reservation_deletion']);
        $this->assertArrayNotHasKey('_deletion', $selection); $this->assertArrayNotHasKey('_payment', $selection);
        $this->assertNotNull(Reserva::findOne($id)); $this->assertCount(0, Transport::$payloads);
        $this->assertStringNotContainsString('José Rodríguez', json_encode($selection['messages'], JSON_UNESCAPED_UNICODE));
    }

    public function testHerramientaEliminarReservaSoloPreparaYRechazaInstruccionesDeEscritura()
    {
        $id = (new CrearReservaService())->confirmar($this->admin, $this->draft());
        Transport::reset();
        $call = &Transport::$response['data']['candidates'][0]['content']['parts'][0]['functionCall'];
        $call['name'] = 'preparar_eliminacion_reserva'; $call['args'] = ['origen' => 'seleccionadas', 'ids_reservas' => [$id]];
        $service = new AsistenteService(new Transport(), new FuncionesReservas());
        $turn = $service->responder(['turns' => []], 'Revisar el registro cargado por error', new \DateTimeImmutable(), function () { return $this->admin; });
        $this->assertSame('ok', $turn['code']); $this->assertSame(['id_reserva' => $id], $turn['_reservation_deletion']);
        $this->assertArrayNotHasKey('visible_reservation_deletion', $turn['messages'][2]['data']);
        $this->assertNotNull(Reserva::findOne($id));
        $call['args']['eliminar_pagos'] = true;
        $invalid = $service->responder(['turns' => []], 'Revisar registro', new \DateTimeImmutable(), function () { $this->fail('Argumentos inválidos.'); });
        $this->assertSame('invalid_arguments', $invalid['code']); $this->assertNotNull(Reserva::findOne($id));
    }

    private function save($model) { $this->assertTrue($model->save(), implode(' | ', $model->getErrorSummary(true))); }
}
