<?php
namespace tests\unit\models;
require_once dirname(__DIR__, 2) . '/_support/AsistenteFixtures.php';

use app\components\asistente\EditarPasajeroService;
use app\components\asistente\AsistenteService;
use app\components\asistente\FuncionesReservas;
use app\models\Locador;
use app\models\Reserva;
use tests\support\GeminiAsistenteSimulado as Transport;
use Yii;

class AsistentePasajeroTest extends \Codeception\Test\Unit
{
    use \tests\support\RuntimeAsistenteAislado;
    use \tests\support\CreaDatosAsistente;
    private $admin;
    private $cliente;
    private $service;

    protected function _before()
    {
        $this->iniciarRuntimeAsistente();
        Yii::$app->controller = new \yii\web\Controller('asistente', Yii::$app);
        $this->admin = $this->usuarioAsistente();
        Yii::$app->user->setIdentity($this->admin);
        $suffix = bin2hex(random_bytes(6));
        $this->cliente = new Locador(['denominacion' => 'Pasajero privado ' . $suffix, 'documento' => 'DOC' . $suffix,
            'email' => $suffix . '@example.com', 'telefono' => '123456', 'obs' => 'Nota anterior', 'calificacion' => 2]);
        $this->assertTrue($this->cliente->save(), json_encode($this->cliente->errors));
        $this->service = new EditarPasajeroService();
    }

    protected function _after() { Yii::$app->user->setIdentity(null); $this->cerrarRuntimeAsistente(); }

    private function draft($operation)
    {
        return ['token' => bin2hex(random_bytes(16)), 'owner' => (string) $this->admin->id,
            'expires' => time() + 900, 'id_locador' => (int) $this->cliente->id,
            'base' => $this->service->huella($this->cliente),
            'proposal' => $this->service->preparar($this->admin, ['operacion' => $operation, 'id_locador' => $this->cliente->id])];
    }

    public function testCalificacionEditaEstrellasYNotaSinCambiarDatosDeContacto()
    {
        $draft = $this->draft('calificacion');
        $draft['review'] = $this->service->revisar($this->admin, $draft, $this->service->cargar($this->admin, $this->cliente->id),
            ['calificacion' => 5, 'obs' => 'Nota nueva <script>', 'email' => 'forjado@example.com', 'denominacion' => 'Forjado', 'id' => 999]);
        $this->assertNotNull($draft['review']);
        $this->cliente->refresh();
        $this->assertSame('Nota anterior', $this->cliente->obs);
        $this->service->confirmar($this->admin, $draft);
        $this->cliente->refresh();
        $this->assertSame(5, (int) $this->cliente->calificacion);
        $this->assertSame('Nota nueva <script>', $this->cliente->obs);
        $this->assertNotSame('forjado@example.com', $this->cliente->email);
        $this->assertNotSame('Forjado', $this->cliente->denominacion);
        $this->assertSame((int) $this->admin->id, (int) $this->cliente->updated_by);
    }

    public function testDatosYUbicacionSeValidanYNoCambianCalificacion()
    {
        $draft = $this->draft('datos');
        $localidad = \app\models\Localidad::find()->one();
        $model = $this->service->cargar($this->admin, $this->cliente->id);
        $draft['review'] = $this->service->revisar($this->admin, $draft, $model, ['denominacion' => 'Nuevo nombre',
            'telefono' => '987654', 'id_localidad' => $localidad->id, 'id_provincia' => $localidad->id_provincia, 'calificacion' => 5]);
        $this->assertNotNull($draft['review'], json_encode($model->errors));
        $this->service->confirmar($this->admin, $draft);
        $this->cliente->refresh();
        $this->assertSame('Nuevo nombre', $this->cliente->denominacion);
        $this->assertSame('987654', $this->cliente->telefono);
        $this->assertSame(2, (int) $this->cliente->calificacion);
        $this->assertSame((int) $localidad->id, (int) $this->cliente->id_localidad);
    }

    public function testEdicionConcurrenteRequiereNuevaRevision()
    {
        $draft = $this->draft('calificacion');
        $draft['review'] = $this->service->revisar($this->admin, $draft, $this->service->cargar($this->admin, $this->cliente->id), ['calificacion' => 5, 'obs' => 'Mi nota']);
        Locador::updateAll(['obs' => 'Edición de otro administrador'], ['id' => $this->cliente->id]);
        try { $this->service->confirmar($this->admin, $draft); $this->fail('No debe sobrescribir.'); }
        catch (\DomainException $e) { $this->assertStringContainsString('cambiaron', $e->getMessage()); }
        $this->cliente->refresh();
        $this->assertSame('Edición de otro administrador', $this->cliente->obs);
        $this->assertSame(2, (int) $this->cliente->calificacion);
    }

    public function testPrepararEsSoloLecturaYNoAceptaCambiosComoArgumentos()
    {
        $proposal = $this->service->preparar($this->admin, ['operacion' => 'datos']);
        $this->assertSame([], $proposal['ids_locadores']);
        foreach ([['operacion' => 'datos', 'email' => 'nuevo@example.com'], ['operacion' => 'calificacion', 'calificacion' => 5],
            ['operacion' => 'datos', 'id_locador' => $this->cliente->id, 'titular' => 'Otra persona']] as $args) {
            try { $this->service->preparar($this->admin, $args); $this->fail('Debe rechazar cambios/selección ambigua.'); }
            catch (\InvalidArgumentException $e) { $this->assertNotEmpty($e->getMessage()); }
        }
        $this->cliente->refresh(); $this->assertSame('Nota anterior', $this->cliente->obs);
    }

    public function testPermisosVencimientoYCalificacionInvalida()
    {
        $draft = $this->draft('calificacion');
        $model = $this->service->cargar($this->admin, $this->cliente->id);
        $this->assertNull($this->service->revisar($this->admin, $draft, $model, ['calificacion' => 6]));
        $this->assertTrue($model->hasErrors('calificacion'));
        $draft['expires'] = time() - 1;
        try { $this->service->confirmar($this->admin, $draft); $this->fail('Debe rechazar vencido.'); }
        catch (\DomainException $e) { $this->assertNotEmpty($e->getMessage()); }
        $this->expectException(\yii\web\ForbiddenHttpException::class);
        $this->service->preparar((object) ['activo' => 1, 'nivel' => 4], ['operacion' => 'datos']);
    }

    public function testHerramientaYContextoNoIncluyenDatosPrivados()
    {
        Transport::reset();
        $call = &Transport::$response['data']['candidates'][0]['content']['parts'][0]['functionCall'];
        $call['name'] = 'preparar_edicion_pasajero'; $call['args'] = ['operacion' => 'calificacion', 'id_locador' => (int) $this->cliente->id];
        $service = new AsistenteService(new Transport(), new FuncionesReservas());
        $turn = $service->responder(['turns' => []], 'Calificar pasajero ' . $this->cliente->id, new \DateTimeImmutable(), function () { return $this->admin; });
        $this->assertSame('ok', $turn['code']);
        $this->assertSame([(int) $this->cliente->id], $turn['_passenger']['ids_locadores']);
        $this->assertArrayNotHasKey('visible_passenger', $turn['messages'][2]['data']);
        $call['args'] = ['operacion' => 'datos', 'origen' => 'anteriores'];
        $next = $service->responder(['turns' => [$turn]], 'Ahora sus datos', new \DateTimeImmutable(), function () { return $this->admin; });
        $this->assertSame('ok', $next['code']);
        $this->assertSame([(int) $this->cliente->id], $next['_passenger']['ids_locadores']);
        foreach ([$this->cliente->denominacion, $this->cliente->email, 'Nota anterior'] as $private) {
            $this->assertStringNotContainsString($private, json_encode($next['messages']));
            $this->assertStringNotContainsString($private, json_encode(Transport::$payloads));
        }
    }

    public function testSeleccionPorNombreAmbiguoYTitularDeReservaAnterior()
    {
        $suffix = bin2hex(random_bytes(5));
        $other = new Locador(['denominacion' => $this->cliente->denominacion, 'documento' => $suffix,
            'email' => $suffix . '@example.com', 'telefono' => '777777']);
        $this->assertTrue($other->save());
        $proposal = $this->service->preparar($this->admin, ['operacion' => 'datos', 'titular' => $this->cliente->denominacion]);
        $this->assertCount(2, $proposal['ids_locadores']);
        $booking = new Reserva(['fecha' => '2090-01-01', 'desde' => '2090-02-01', 'hasta' => '2090-02-03',
            'id_locador' => $other->id, 'pax' => 1, 'id_estado' => \app\models\Estado::find()->select('id')->where(['slug' => 'confirmado'])->scalar()]);
        $this->assertTrue($booking->save(), json_encode($booking->errors));
        $previous = ['ok' => true, 'reservas' => [['id' => (int) $booking->id]], 'truncado' => false];
        $proposal = $this->service->preparar($this->admin, ['operacion' => 'calificacion', 'origen' => 'anteriores'], $previous);
        $this->assertSame([(int) $other->id], $proposal['ids_locadores']);
        // Una búsqueda de pasajero vacía no reutiliza un titular viejo de otra reserva.
        try { $this->service->preparar($this->admin, ['operacion' => 'datos', 'origen' => 'anteriores'], $previous, []); $this->fail('No debe ampliar selección vacía.'); }
        catch (\DomainException $e) { $this->assertNotEmpty($e->getMessage()); }
    }

    public function testUnicidadDeEmailYProvinciaAjenaNoSeGuardan()
    {
        $suffix = bin2hex(random_bytes(5));
        $other = new Locador(['denominacion' => 'Otro', 'documento' => $suffix, 'email' => $suffix . '@example.com', 'telefono' => '777777']);
        $this->assertTrue($other->save());
        $draft = $this->draft('datos');
        $model = $this->service->cargar($this->admin, $this->cliente->id);
        $this->assertNull($this->service->revisar($this->admin, $draft, $model, ['email' => $other->email]));
        $this->assertTrue($model->hasErrors('email'));
        $localidad = \app\models\Localidad::find()->one();
        $provincia = \app\models\Provincia::find()->select('id')->where(['<>', 'id', $localidad->id_provincia])->scalar();
        $model = $this->service->cargar($this->admin, $this->cliente->id);
        $this->assertNull($this->service->revisar($this->admin, $draft, $model, ['id_localidad' => $localidad->id, 'id_provincia' => $provincia]));
        $this->assertTrue($model->hasErrors('id_localidad'));
    }
}
