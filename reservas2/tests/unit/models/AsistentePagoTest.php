<?php
namespace tests\unit\models;
require_once dirname(__DIR__, 2) . '/_support/AsistenteFixtures.php';

use app\components\asistente\AsistenteService;
use app\components\asistente\BorradorReserva;
use app\components\asistente\CargarPagoService;
use app\components\asistente\FuncionesReservas;
use app\models\AsistentePagoForm;
use app\models\Estado;
use app\models\Locador;
use app\models\RequestReserva;
use app\models\Reserva;
use tests\support\GeminiAsistenteSimulado as Transport;
use Yii;

class AsistentePagoTest extends \Codeception\Test\Unit
{
    use \tests\support\RuntimeAsistenteAislado;
    use \tests\support\CreaDatosAsistente;
    private $admin;
    private $reserva;
    private $request;

    protected function _before()
    {
        $this->iniciarRuntimeAsistente();
        Yii::$app->controller = new \yii\web\Controller('asistente', Yii::$app);
        $this->admin = $this->usuarioAsistente(); Yii::$app->user->setIdentity($this->admin);
        $state = Estado::findOne(['slug' => 'confirmado']);
        if (!$state) { $state = new Estado(['slug' => 'confirmado', 'descr' => 'Confirmado']); $this->save($state); }
        $suffix = bin2hex(random_bytes(6));
        $locador = new Locador(['denominacion' => 'Titular privado <script>', 'documento' => 'P' . $suffix,
            'email' => $suffix . '@example.com', 'telefono' => '123456789']); $this->save($locador);
        $attributes = ['fecha' => '2026-10-26 10:00:00', 'desde' => '2088-11-02 14:00:00',
            'hasta' => '2088-11-05 11:00:00', 'pax' => 4, 'id_estado' => $state->id];
        $this->reserva = new Reserva($attributes + ['id_locador' => $locador->id]); $this->save($this->reserva);
        $this->request = new RequestReserva($attributes + ['id_reserva' => $this->reserva->id,
            'denominacion' => $locador->denominacion, 'email' => $locador->email, 'hash' => bin2hex(random_bytes(16)),
            'total' => 1000, 'pagado' => 0, 'registro_pagos' => [], 'codigo_reserva' => RequestReserva::generateUniqueCodigoReserva($locador->email)]);
        $this->save($this->request); Transport::reset();
    }

    protected function _after() { Yii::$app->user->setIdentity(null); $this->cerrarRuntimeAsistente(); }
    private function proposal() { return ['id_reserva' => (int) $this->reserva->id, 'id_request' => (int) $this->request->id]; }
    private function form($amount = '250,50') { return new AsistentePagoForm(['monto' => $amount, 'nota' => 'Nota privada <script>', 'acreditado' => '1']); }
    private function draft($amount = '250,50')
    {
        $review = (new CargarPagoService())->revisar($this->admin, $this->proposal(), $this->form($amount));
        $this->assertNotNull($review);
        return ['token' => bin2hex(random_bytes(16)), 'owner' => (string) $this->admin->id, 'expires' => time() + 900,
            'proposal' => $this->proposal(), 'review' => $review];
    }

    public function testCargarPagoConservaPreguntaReservaYPreparaSinEscribirNiLlamarIA()
    {
        $service = new AsistenteService(new Transport(), new FuncionesReservas());
        $now = new \DateTimeImmutable(); $identity = function () { return $this->admin; };
        $first = $service->responder(['turns' => []], 'cargar un pago', $now, $identity);
        $this->assertSame('reserva', $first['flujo_pago']['etapa']);
        $this->assertStringContainsString('número de reserva', $first['visible'][1]['text']);
        $next = $service->responder(['turns' => [$first]], (string) $this->reserva->id, $now, $identity);
        $this->assertSame($this->proposal(), $next['_payment']);
        $this->assertSame('reservas', $next['visible'][1]['presentation']['type']);
        $this->assertStringNotContainsString('Titular privado', json_encode($next['messages']));
        $this->assertArrayNotHasKey('pago_borrador', $next['messages'][1]);
        $this->assertCount(0, Transport::$payloads);
        $this->request->refresh(); $this->assertSame(0.0, (float) $this->request->pagado);
    }

    public function testCargaPagoPermiteBuscarReservasPorNombreYContinuarConLaElegida()
    {
        $owner = $this->reserva->locador; $owner->denominacion = 'José Rodríguez'; $this->save($owner);
        $service = new AsistenteService(new Transport(), new FuncionesReservas());
        $now = new \DateTimeImmutable(); $identity = function () { return $this->admin; };
        $first = $service->responder(['turns' => []], 'agregar pago de jose rodriguez', $now, $identity);
        $this->assertSame('ok', $first['code']);
        $this->assertSame('reserva', $first['flujo_pago']['etapa']);
        $this->assertSame('jose rodriguez', $first['flujo_pago']['titular']);
        $this->assertSame([['id' => (int) $this->reserva->id]], $first['seleccion_reservas']['reservas']);
        $this->assertArrayNotHasKey('_payment', $first);
        $second = $service->responder(['turns' => [$first]], 'mostrame las reservas a nombre de jose rodriguez', $now, $identity);
        $this->assertSame('reservas', $second['visible'][1]['presentation']['type']);
        $this->assertStringContainsString('Titular: jose rodriguez', $second['visible'][1]['presentation']['comment']);
        $this->assertStringContainsString('continuar con la carga del pago', implode(' ', $second['visible'][1]['presentation']['notes']));
        $this->assertArrayNotHasKey('_payment', $second);
        $third = $service->responder(['turns' => [$second]], 'reserva ' . $this->reserva->id, $now, $identity);
        $this->assertSame($this->proposal(), $third['_payment']);
        $this->assertCount(0, Transport::$payloads);
        $this->request->refresh(); $this->assertSame(0.0, (float) $this->request->pagado);
        foreach ([$first, $second] as $turn) {
            $this->assertStringNotContainsString('José Rodríguez', json_encode($turn['messages'], JSON_UNESCAPED_UNICODE));
            $this->assertStringNotContainsString($this->request->hash, json_encode($turn['messages']));
        }
    }

    public function testBusquedaTitularDurantePagoConservaMontoYMedioRecibidos()
    {
        $owner = $this->reserva->locador; $owner->denominacion = 'José Rodríguez'; $this->save($owner);
        $service = new AsistenteService(new Transport(), new FuncionesReservas());
        $now = new \DateTimeImmutable(); $identity = function () { return $this->admin; };
        $first = $service->responder(['turns' => []], 'agregar un pago de 250,50 en efectivo', $now, $identity);
        $list = $service->responder(['turns' => [$first]], 'mostrame las reservas a nombre de jose rodriguez', $now, $identity);
        $this->assertSame('250.50', $list['flujo_pago']['precarga']['monto']);
        $this->assertSame('efectivo', $list['flujo_pago']['precarga']['medio_pago']);
        $selected = $service->responder(['turns' => [$list]], (string) $this->reserva->id, $now, $identity);
        $this->assertSame('250.50', $selected['_payment']['precarga']['monto']);
        $this->assertSame('efectivo', $selected['_payment']['precarga']['medio_pago']);
        $empty = $service->responder(['turns' => [$first]], 'mostrame las reservas a nombre de persona inexistente', $now, $identity);
        $this->assertSame([], $empty['seleccion_reservas']['reservas']);
        $this->assertArrayNotHasKey('_payment', $empty);
        $this->assertSame('reserva', $empty['flujo_pago']['etapa']);
    }

    public function testPrecargaMontoEnMilesYEfectivoSinGuardarNiVerificar()
    {
        $service = new AsistenteService(new Transport(), new FuncionesReservas());
        $turn = $service->responder(['turns' => []], 'agregar pago de 500 mil en Efectivo a la reserva ' . $this->reserva->id . ' de Jose rodriguez',
            new \DateTimeImmutable(), function () { return $this->admin; });
        $this->assertSame('500000.00', $turn['_payment']['precarga']['monto']);
        $this->assertSame('efectivo', $turn['_payment']['precarga']['medio_pago']);
        $this->assertArrayNotHasKey('acreditado', $turn['_payment']['precarga']);
        $this->assertCount(0, Transport::$payloads);
        $this->request->refresh(); $this->assertSame(0.0, (float) $this->request->pagado);
        foreach (['500.000' => '500000.00', '500.000,50' => '500000.50', '500000,50' => '500000.50',
            '500.50' => '500.50', '1,5 millones' => '1500000.00'] as $text => $expected) {
            $data = \app\components\asistente\FlujoPago::precarga('agregar pago de ' . $text . ' en efectivo');
            $this->assertSame($expected, $data['monto']);
        }
        $first = $service->responder(['turns' => []], 'cargar un pago de 250,50 en efectivo',
            new \DateTimeImmutable(), function () { return $this->admin; });
        $next = $service->responder(['turns' => [$first]], 'reserva ' . $this->reserva->id,
            new \DateTimeImmutable(), function () { return $this->admin; });
        $this->assertSame('250.50', $next['_payment']['precarga']['monto']);
        $this->assertSame('efectivo', $next['_payment']['precarga']['medio_pago']);
    }

    public function testMedioPagoSeValidaYQuedaEnElMovimientoConfirmado()
    {
        $form = $this->form(); $form->medio_pago = 'efectivo';
        $review = (new CargarPagoService())->revisar($this->admin, $this->proposal(), $form);
        $this->assertSame('efectivo', $review['medio_pago']);
        $draft = $this->draft(); $draft['review'] = $review;
        (new CargarPagoService())->confirmar($this->admin, $draft);
        $this->request->refresh();
        $this->assertSame('efectivo', $this->request->registro_pagos[0]['medio_pago']);
        $invalid = $this->form(); $invalid->medio_pago = '<script>';
        $this->assertNull((new CargarPagoService())->revisar($this->admin, $this->proposal(), $invalid));
        $this->assertArrayHasKey('medio_pago', $invalid->errors);
    }

    public function testConfirmarAgregaMovimientoParcialUnaSolaVez()
    {
        $draft = $this->draft(); $service = new CargarPagoService();
        $this->assertSame((int) $this->reserva->id, $service->confirmar($this->admin, $draft));
        $service->confirmar($this->admin, $draft);
        $this->request->refresh();
        $this->assertSame(250.50, (float) $this->request->pagado);
        $this->assertCount(1, $this->request->registro_pagos);
        $this->assertSame(250.50, (float) $this->request->registro_pagos[0]['monto']);
        $this->assertSame('Nota privada <script>', $this->request->registro_pagos[0]['nota']);
        $this->assertNull($this->request->registro_pagos[0]['archivo']);
        $service->confirmar($this->admin, $this->draft('749.50'));
        $this->request->refresh(); $this->assertSame(1000.0, (float) $this->request->pagado);
        $this->assertCount(2, $this->request->registro_pagos);
    }

    public function testValidacionMontoVerificacionYSaldoSinModificarPagos()
    {
        foreach (['0', '-1', '1.001', '1.000,00', 'abc', '1000.01'] as $amount) {
            $form = $this->form($amount);
            $this->assertNull((new CargarPagoService())->revisar($this->admin, $this->proposal(), $form));
            $this->assertArrayHasKey('monto', $form->errors);
        }
        $form = $this->form(); $form->acreditado = '0';
        $this->assertNull((new CargarPagoService())->revisar($this->admin, $this->proposal(), $form));
        $this->assertArrayHasKey('acreditado', $form->errors);
        $this->request->refresh(); $this->assertSame([], $this->request->registro_pagos);
    }

    public function testSaldoCambiadoEntreRevisionYConfirmacionExigeRevisar()
    {
        $first = $this->draft('100'); $stale = $this->draft('200'); $service = new CargarPagoService();
        $service->confirmar($this->admin, $first);
        try { $service->confirmar($this->admin, $stale); $this->fail('No debe guardar una revisión vieja.'); }
        catch (\DomainException $e) { $this->assertStringContainsString('Cambiaron', $e->getMessage()); }
        $this->request->refresh(); $this->assertSame(100.0, (float) $this->request->pagado);
        $this->assertCount(1, $this->request->registro_pagos);
    }

    public function testRechazaCanceladaSinSolicitudVariasSolicitudesYReservaSaldada()
    {
        $service = new CargarPagoService();
        $this->request->pagado = 1000; $this->save($this->request);
        try { $service->solicitud($this->admin, (int) $this->reserva->id); $this->fail('Saldada.'); }
        catch (\DomainException $e) { $this->assertStringContainsString('saldo pendiente', $e->getMessage()); }
        $this->request->pagado = 0; $this->save($this->request);
        $other = new RequestReserva($this->request->attributes); $other->id = null; $other->hash = bin2hex(random_bytes(16));
        $other->codigo_reserva = RequestReserva::generateUniqueCodigoReserva($other->email); $this->save($other);
        try { $service->solicitud($this->admin, (int) $this->reserva->id); $this->fail('Ambigua.'); }
        catch (\DomainException $e) { $this->assertStringContainsString('única solicitud', $e->getMessage()); }
        $other->delete();
        $canceled = Estado::findOne(['slug' => 'rechazado']);
        if (!$canceled) { $canceled = new Estado(['slug' => 'rechazado', 'descr' => 'Rechazado']); $this->save($canceled); }
        $this->reserva->id_estado = $canceled->id; $this->save($this->reserva);
        $this->expectException(\DomainException::class);
        $service->confirmar($this->admin, $this->draft());
    }

    public function testAutorizacionYPropietarioBorrador()
    {
        $draft = $this->draft();
        try { (new CargarPagoService())->confirmar($this->usuarioAsistente(), $draft); $this->fail('Propietario ajeno.'); }
        catch (\DomainException $e) { $this->assertNotEmpty($e->getMessage()); }
        $this->expectException(\yii\web\ForbiddenHttpException::class);
        (new CargarPagoService())->confirmar((object) ['id' => $this->admin->id, 'activo' => 1, 'nivel' => 4], $draft);
    }

    public function testHerramientaNoAceptaMontosYNoDevuelveDatosPrivadosAlProveedor()
    {
        $functions = new FuncionesReservas();
        $args = ['origen' => 'seleccionadas', 'ids_reservas' => [(int) $this->reserva->id]];
        $call = &Transport::$response['data']['candidates'][0]['content']['parts'][0]['functionCall'];
        $call['name'] = 'preparar_pago_reserva'; $call['args'] = $args;
        $turn = (new AsistenteService(new Transport(), $functions))->responder(['turns' => []], 'Prepará el formulario',
            new \DateTimeImmutable(), function () { return $this->admin; });
        $this->assertSame('ok', $turn['code']);
        $this->assertSame($this->proposal(), $turn['_payment']);
        $this->assertArrayNotHasKey('visible_payment', $turn['messages'][2]['data']);
        $this->assertStringNotContainsString('Titular privado', json_encode($turn['messages']));
        $this->expectException(\InvalidArgumentException::class);
        $functions->prepararPagoReserva($this->admin, $args + ['monto' => 10]);
    }

    public function testFalloDeGuardadoRevierteMontoMovimientoYEstado()
    {
        $draft = $this->draft();
        $handler = function ($event) { $event->isValid = false; };
        \yii\base\Event::on(Reserva::class, \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE, $handler);
        // Force an actual reservation update so the second save fails after the payment save.
        $other = Estado::findOne(['slug' => 'confirmado-verificar-pago']);
        if (!$other) { $other = new Estado(['slug' => 'confirmado-verificar-pago', 'descr' => 'Verificar']); $this->save($other); }
        \yii\base\Event::off(Reserva::class, \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE, $handler);
        $this->reserva->id_estado = $other->id; $this->save($this->reserva);
        $draft = $this->draft();
        \yii\base\Event::on(Reserva::class, \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE, $handler);
        try { (new CargarPagoService())->confirmar($this->admin, $draft); $this->fail('Debe revertir.'); }
        catch (\DomainException $e) { $this->assertStringContainsString('No se pudo guardar', $e->getMessage()); }
        finally { \yii\base\Event::off(Reserva::class, \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE, $handler); }
        $this->request->refresh(); $this->assertSame(0.0, (float) $this->request->pagado);
        $this->assertSame([], $this->request->registro_pagos);
    }

    public function testComprobanteSeConservaPrivadoYSeAsociaAlMovimiento()
    {
        $path = Yii::$app->runtimePath . '/recibo.pdf';
        file_put_contents($path, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF");
        $form = $this->form();
        $form->comprobante = new class(['name' => 'recibo.pdf', 'tempName' => $path,
            'type' => 'application/pdf', 'size' => filesize($path), 'error' => UPLOAD_ERR_OK]) extends \yii\web\UploadedFile {
            public function saveAs($file, $deleteTempFile = true) { return copy($this->tempName, $file); }
        };
        $service = new CargarPagoService(); $draft = $this->draft();
        $review = $service->revisar($this->admin, $this->proposal(), $form);
        $this->assertNotNull($review, json_encode($form->errors));
        $service->adjuntar($this->admin, $review, $form, $draft['token']);
        $this->assertFileExists($review['archivo']['path']);
        $draft['review'] = $review;
        $service->confirmar($this->admin, $draft);
        $this->request->refresh();
        $stored = $this->request->registro_pagos[0]['archivo'];
        $this->assertFileExists($stored);
        $this->assertSame(Yii::$app->runtimePath . '/priv_comprobantes', dirname($stored));
        $this->assertStringEndsWith('.pdf', $stored);
        $this->assertSame(hash_file('sha256', $path), hash_file('sha256', $stored));
        $this->assertFileDoesNotExist($review['archivo']['path']);
        $service->confirmar($this->admin, $draft);
        $this->assertFileExists($stored); // Retry does not remove or recreate the receipt.
    }

    public function testComprobanteInvalidoYEstadosPendientesVerificacionGlobal()
    {
        $bad = $this->form();
        $path = Yii::$app->runtimePath . '/invalido.php'; file_put_contents($path, '<?php echo 1;');
        $bad->comprobante = new \yii\web\UploadedFile(['name' => 'recibo.php', 'tempName' => $path,
            'size' => filesize($path), 'error' => UPLOAD_ERR_OK, 'type' => 'text/plain']);
        $this->assertNull((new CargarPagoService())->revisar($this->admin, $this->proposal(), $bad));
        $this->assertArrayHasKey('comprobante', $bad->errors);
        $pending = Estado::findOne(['slug' => 'confirmado-verificar-pago']);
        if (!$pending) { $pending = new Estado(['slug' => 'confirmado-verificar-pago', 'descr' => 'Verificar']); $this->save($pending); }
        $this->request->id_estado = $pending->id; $this->request->pagado = 100;
        $this->request->registro_pagos = [['fecha' => '2026-10-25 10:00:00', 'monto' => 100, 'archivo' => null]];
        $this->save($this->request);
        $this->reserva->id_estado = $pending->id; $this->save($this->reserva);
        $draft = $this->draft('150');
        $this->assertTrue($draft['review']['verificacion_global']);
        (new CargarPagoService())->confirmar($this->admin, $draft);
        $this->request->refresh(); $this->reserva->refresh();
        $this->assertSame(250.0, (float) $this->request->pagado);
        $this->assertSame('confirmado', $this->request->estado->slug);
        $this->assertSame('confirmado', $this->reserva->estado->slug);
    }

    public function testConfirmacionRechazaBorradorVencidoYSinRevision()
    {
        $draft = $this->draft(); $draft['expires'] = time() - 1;
        try { (new CargarPagoService())->confirmar($this->admin, $draft); $this->fail('Vencido.'); }
        catch (\DomainException $e) { $this->assertNotEmpty($e->getMessage()); }
        unset($draft['review']); $draft['expires'] = time() + 900;
        $this->expectException(\DomainException::class);
        (new CargarPagoService())->confirmar($this->admin, $draft);
    }

    public function testGuardiaLegacyYReubicacionNoSobrescribenMovimientosAgregados()
    {
        $snapshot = RequestReserva::findOne($this->request->id);
        (new CargarPagoService())->confirmar($this->admin, $this->draft());
        try { \app\components\PagosReserva::exigirSinCambios($snapshot); $this->fail('Debe rechazar snapshot viejo.'); }
        catch (\DomainException $e) { $this->assertStringContainsString('cambiaron', $e->getMessage()); }
        $this->request->refresh(); $payments = $this->request->registro_pagos;
        $payments[0]['archivo'] = '/tmp/original'; $this->request->registro_pagos = $payments; $this->save($this->request);
        \app\components\PagosReserva::reubicarArchivo($this->request->id, '/tmp/original', '/tmp/privado');
        $this->request->refresh(); $this->assertSame('/tmp/privado', $this->request->registro_pagos[0]['archivo']);
        $this->assertSame(250.50, (float) $this->request->pagado);
    }

    private function pagosParaEliminar($firstFile = null, $secondFile = null)
    {
        $this->request->pagado = 600;
        $this->request->registro_pagos = [
            ['fecha' => '2026-10-26 10:00:00', 'monto' => 200, 'archivo' => $firstFile, 'nota' => 'Pago erróneo privado <script>'],
            ['fecha' => '2026-10-27 10:00:00', 'monto' => 400, 'archivo' => $secondFile, 'nota' => 'Pago que debe conservarse'],
        ];
        $this->save($this->request);
    }

    private function borradorEliminar($index)
    {
        $form = new \app\models\AsistenteEliminarPagoForm(['indice' => (string) $index]);
        $review = (new \app\components\asistente\EliminarPagoService())->revisar($this->admin, $this->proposal(), $form);
        $this->assertNotNull($review, json_encode($form->errors));
        return ['token' => bin2hex(random_bytes(16)), 'owner' => (string) $this->admin->id, 'expires' => time() + 900,
            'proposal' => $this->proposal(), 'review' => $review];
    }

    public function testVerbosEliminarSoloPreparanSeleccionPrivadaSinModificarPagos()
    {
        $this->pagosParaEliminar();
        foreach (['anular', 'revertir', 'cancelar', 'eliminar', 'borrar'] as $verb) {
            $turn = (new AsistenteService(new Transport(), new FuncionesReservas()))->responder(['turns' => []],
                $verb . ' pago de 200 a la reserva ' . $this->reserva->id, new \DateTimeImmutable(), function () { return $this->admin; });
            $this->assertSame('ok', $turn['code']);
            $this->assertSame($this->proposal(), $turn['_deletion']);
            $this->assertArrayNotHasKey('_payment', $turn);
            $this->assertSame('eliminar', $turn['flujo_pago']['operacion']);
            $this->assertStringNotContainsString('Pago erróneo privado', json_encode($turn['messages'], JSON_UNESCAPED_UNICODE));
        }
        $this->request->refresh(); $this->assertSame(600.0, (float) $this->request->pagado);
        $this->assertCount(2, $this->request->registro_pagos); $this->assertCount(0, Transport::$payloads);
    }

    public function testEliminaSoloPagoElegidoActualizaCobradoYReintentoNoBorraElSiguiente()
    {
        $this->pagosParaEliminar();
        $service = new \app\components\asistente\EliminarPagoService();
        $draft = $this->borradorEliminar(0);
        $status = $this->reserva->id_estado; $dates = [$this->reserva->desde, $this->reserva->hasta];
        $service->confirmar($this->admin, $draft); $service->confirmar($this->admin, $draft);
        $this->request->refresh(); $this->reserva->refresh();
        $this->assertSame(400.0, (float) $this->request->pagado);
        $this->assertCount(1, $this->request->registro_pagos);
        $this->assertSame('Pago que debe conservarse', $this->request->registro_pagos[0]['nota']);
        $this->assertSame($status, $this->reserva->id_estado);
        $this->assertSame($dates, [$this->reserva->desde, $this->reserva->hasta]);
        $last = $this->borradorEliminar(0);
        $service->confirmar($this->admin, $last); $service->confirmar($this->admin, $last);
        $this->request->refresh(); $this->assertSame(0.0, (float) $this->request->pagado);
        $this->assertSame([], $this->request->registro_pagos);
    }

    public function testEliminacionRequiereSeleccionYRechazaHistorialCambiado()
    {
        $this->pagosParaEliminar(); $service = new \app\components\asistente\EliminarPagoService();
        foreach ([null, '999', [0, 1]] as $index) {
            $form = new \app\models\AsistenteEliminarPagoForm(['indice' => $index]);
            $this->assertNull($service->revisar($this->admin, $this->proposal(), $form));
        }
        $stale = $this->borradorEliminar(1);
        $service->confirmar($this->admin, $this->borradorEliminar(0));
        try { $service->confirmar($this->admin, $stale); $this->fail('No debe aplicar un índice viejo al historial reordenado.'); }
        catch (\DomainException $e) { $this->assertStringContainsString('Cambiaron', $e->getMessage()); }
        $this->request->refresh(); $this->assertCount(1, $this->request->registro_pagos);
        $this->assertSame(400.0, (float) $this->request->pagado);
    }

    public function testHerramientaEliminarEsDeLecturaYRechazaIndicesOConfirmacionDeLaIA()
    {
        $this->pagosParaEliminar();
        Transport::reset();
        $call = &Transport::$response['data']['candidates'][0]['content']['parts'][0]['functionCall'];
        $call['name'] = 'preparar_eliminacion_pago_reserva';
        $call['args'] = ['origen' => 'seleccionadas', 'ids_reservas' => [(int) $this->reserva->id]];
        $service = new AsistenteService(new Transport(), new FuncionesReservas());
        $turn = $service->responder(['turns' => []], 'Corregir el movimiento cargado por error', new \DateTimeImmutable(), function () { return $this->admin; });
        $this->assertSame('ok', $turn['code']);
        $this->assertSame($this->proposal(), $turn['_deletion']);
        $this->assertArrayNotHasKey('visible_deletion', $turn['messages'][2]['data']);
        $this->request->refresh(); $this->assertCount(2, $this->request->registro_pagos);
        $call['args']['indice'] = 0; $call['args']['confirmar'] = true;
        $bad = $service->responder(['turns' => []], 'Corregir el movimiento', new \DateTimeImmutable(), function () { $this->fail('Debe validar antes de ejecutar.'); });
        $this->assertSame('invalid_arguments', $bad['code']);
        $this->assertArrayNotHasKey('_deletion', $bad);
    }

    public function testSeleccionAmbiguaDeReservaConservaLaIntencionDeEliminar()
    {
        $this->pagosParaEliminar(); $service = new AsistenteService(new Transport(), new FuncionesReservas());
        $identity = function () { return $this->admin; }; $now = new \DateTimeImmutable();
        $first = $service->responder(['turns' => []], 'eliminar pago de reserva 1 y 2', $now, $identity);
        $this->assertSame('eliminar', $first['flujo_pago']['operacion']);
        $this->assertArrayNotHasKey('_deletion', $first);
        $next = $service->responder(['turns' => [$first]], 'reserva ' . $this->reserva->id, $now, $identity);
        $this->assertSame($this->proposal(), $next['_deletion']);
        $this->assertArrayNotHasKey('_payment', $next);
    }

    public function testEliminacionCompruebaPermisosPropietarioVencimientoYRevision()
    {
        $this->pagosParaEliminar(); $service = new \app\components\asistente\EliminarPagoService(); $draft = $this->borradorEliminar(0);
        $expired = $draft; $expired['expires'] = time() - 1;
        $withoutReview = $draft; unset($withoutReview['review']);
        foreach ([$expired, $withoutReview] as $invalid) {
            try { $service->confirmar($this->admin, $invalid); $this->fail('Debe exigir revisión vigente.'); }
            catch (\DomainException $e) { $this->assertNotEmpty($e->getMessage()); }
        }
        $foreign = $draft; $foreign['owner'] = 'otro';
        try { $service->confirmar($this->admin, $foreign); $this->fail('Propietario ajeno.'); }
        catch (\DomainException $e) { $this->assertNotEmpty($e->getMessage()); }
        $this->expectException(\yii\web\ForbiddenHttpException::class);
        $service->confirmar((object) ['id' => $this->admin->id, 'activo' => 1, 'nivel' => 4], $draft);
    }

    public function testEliminacionRevierteSiFallaGuardadoYComprobantesPermanecen()
    {
        $dir = Yii::$app->runtimePath . '/priv_comprobantes'; \yii\helpers\FileHelper::createDirectory($dir, 0700);
        $path = $dir . '/eliminar-rollback.pdf'; file_put_contents($path, 'comprobante privado');
        $this->pagosParaEliminar($path); $draft = $this->borradorEliminar(0);
        $handler = function ($event) { $event->isValid = false; };
        \yii\base\Event::on(RequestReserva::class, \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE, $handler);
        try { (new \app\components\asistente\EliminarPagoService())->confirmar($this->admin, $draft); $this->fail('Debe revertir.'); }
        catch (\DomainException $e) { $this->assertStringContainsString('No se pudo eliminar', $e->getMessage()); }
        finally { \yii\base\Event::off(RequestReserva::class, \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE, $handler); }
        $this->request->refresh(); $this->assertSame(600.0, (float) $this->request->pagado);
        $this->assertCount(2, $this->request->registro_pagos); $this->assertFileExists($path);
    }

    public function testComprobantesSoloSeBorranSiSonPrivadosYNoEstanEnUso()
    {
        $service = new \app\components\asistente\EliminarPagoService();
        $dir = Yii::$app->runtimePath . '/priv_comprobantes'; \yii\helpers\FileHelper::createDirectory($dir, 0700);
        $path = $dir . '/eliminar-unico.pdf'; file_put_contents($path, 'privado');
        $this->pagosParaEliminar($path); $service->confirmar($this->admin, $this->borradorEliminar(0));
        $this->assertFileDoesNotExist($path);
        $shared = $dir . '/eliminar-compartido.pdf'; file_put_contents($shared, 'privado compartido');
        $this->pagosParaEliminar($shared, $shared); $service->confirmar($this->admin, $this->borradorEliminar(0));
        $this->assertFileExists($shared);
        $outside = Yii::$app->runtimePath . '/no-es-comprobante.txt'; file_put_contents($outside, 'no borrar');
        $this->pagosParaEliminar($outside); $service->confirmar($this->admin, $this->borradorEliminar(0));
        $this->assertFileExists($outside);
    }

    public function testEliminarPagoPorTitularConservaOperacionHastaElegirReserva()
    {
        $this->pagosParaEliminar();
        $owner = $this->reserva->locador; $owner->denominacion = 'José Rodríguez'; $this->save($owner);
        $service = new AsistenteService(new Transport(), new FuncionesReservas());
        $identity = function () { return $this->admin; }; $now = new \DateTimeImmutable();
        $first = $service->responder(['turns' => []], 'revertir un pago', $now, $identity);
        $this->assertSame('reserva', $first['flujo_pago']['etapa']);
        $list = $service->responder(['turns' => [$first]], 'mostrame las reservas a nombre de jose rodriguez', $now, $identity);
        $this->assertSame('eliminar', $list['flujo_pago']['operacion']);
        $chosen = $service->responder(['turns' => [$list]], (string) $this->reserva->id, $now, $identity);
        $this->assertSame($this->proposal(), $chosen['_deletion']);
        $this->assertArrayNotHasKey('_payment', $chosen);
    }

    private function save($model) { $this->assertTrue($model->save(), implode(' | ', $model->getErrorSummary(true))); }
}
