<?php
namespace tests\unit\models;
require_once dirname(__DIR__, 2) . '/_support/AsistenteFixtures.php';

use app\components\asistente\AsistenteService;
use app\components\asistente\BuscarPasajerosService;
use app\components\asistente\FuncionesReservas;
use app\models\Locador;
use app\models\Localidad;
use app\models\LocadorSearch;
use tests\support\GeminiAsistenteSimulado as Transport;
use Yii;

class AsistenteBuscarPasajerosTest extends \Codeception\Test\Unit
{
    use \tests\support\RuntimeAsistenteAislado;
    private $clients = [];
    private $keyword;
    private $localidad;
    private $service;

    protected function _before()
    {
        $this->iniciarRuntimeAsistente();
        Yii::$app->controller = new \yii\web\Controller('asistente', Yii::$app);
        $this->keyword = 'buscar' . bin2hex(random_bytes(4));
        $this->localidad = Localidad::find()->one();
        $this->service = new BuscarPasajerosService();
        foreach (['denominacion', 'documento', 'telefono', 'email', 'domicilio', 'obs'] as $index => $field) {
            $suffix = bin2hex(random_bytes(5));
            $model = new Locador(['denominacion' => 'Persona privada ' . $suffix, 'documento' => 'DOC' . $suffix,
                'telefono' => '123456', 'email' => $suffix . '@example.com', 'domicilio' => 'Dirección privada',
                'obs' => 'Observación privada <script>alert(1)</script>', 'calificacion' => $index,
                'id_localidad' => $this->localidad->id]);
            $model->$field = $field === 'email' ? $this->keyword . '@example.com' : $this->keyword;
            $this->assertTrue($model->save(), json_encode($model->errors));
            $this->clients[] = $model;
        }
    }
    protected function _after() { $this->cerrarRuntimeAsistente(); }
    private function admin() { return (object) ['activo' => 1, 'nivel' => 1]; }

    public function testBuscaParcialEnTodosLosDatosYRespetaCampoExplicito()
    {
        $data = $this->service->buscar($this->admin(), ['texto' => $this->keyword]);
        $this->assertCount(6, $data['pasajeros']);
        foreach (['nombre', 'documento', 'telefono', 'email', 'direccion', 'observaciones'] as $index => $field) {
            $data = $this->service->buscar($this->admin(), ['texto' => $this->keyword, 'campo' => $field]);
            $this->assertSame([['id' => (int) $this->clients[$index]->id]], $data['pasajeros']);
        }
        $row = $data['visible_presentation']['rows'][0];
        foreach (['nombre', 'documento', 'telefono', 'email', 'direccion', 'observaciones', 'localidad', 'provincia', 'calificacion'] as $field) {
            $this->assertArrayHasKey($field, $row);
        }
    }

    public function testUbicacionYCalificacionConConsultasAcotadas()
    {
        $ids = array_column($this->clients, 'id');
        foreach (['localidad' => $this->localidad->descr, 'provincia' => $this->localidad->provincia->descr] as $field => $term) {
            $query = $this->service->consulta($this->admin(), ['texto' => $term, 'campo' => $field]);
            $this->assertSame(6, (int) $query->andWhere(['id' => $ids])->count());
        }
        $query = $this->service->consulta($this->admin(), ['texto' => '5', 'campo' => 'calificacion']);
        $this->assertSame([(int) $this->clients[5]->id], array_map('intval', $query->andWhere(['id' => $ids])->select('id')->column()));
        Locador::updateAll(['calificacion' => null], ['id' => $this->clients[1]->id]);
        $query = $this->service->consulta($this->admin(), ['texto' => '0', 'campo' => 'calificacion']);
        $this->assertSame(2, (int) $query->andWhere(['id' => $ids])->count());
    }

    public function testSinCoincidenciasLimitesValidacionYPermisos()
    {
        $none = $this->service->buscar($this->admin(), ['texto' => 'inexistente' . bin2hex(random_bytes(6))]);
        $this->assertSame([], $none['pasajeros']);
        $this->assertSame([], $none['visible_presentation']['rows']);
        foreach ([['texto' => 'a'], ['texto' => 'hola', 'campo' => 'sql'], ['texto' => '6', 'campo' => 'calificacion'],
            ['texto' => 'hola', 'sql' => 'SELECT 1']] as $args) {
            try { $this->service->buscar($this->admin(), $args); $this->fail('Debe rechazar argumentos.'); }
            catch (\InvalidArgumentException $e) { $this->assertNotEmpty($e->getMessage()); }
        }
        $this->expectException(\yii\web\ForbiddenHttpException::class);
        $this->service->buscar((object) ['activo' => 1, 'nivel' => 4], ['texto' => $this->keyword]);
    }

    public function testDatosSoloVisiblesYContinuacionPermitePrepararEdicion()
    {
        Transport::reset();
        $call = &Transport::$response['data']['candidates'][0]['content']['parts'][0]['functionCall'];
        $call['name'] = 'buscar_pasajeros'; $call['args'] = ['texto' => $this->keyword, 'campo' => 'direccion'];
        $functions = new FuncionesReservas();
        $service = new AsistenteService(new Transport(), $functions);
        $auth = function () { return $this->admin(); };
        $turn = $service->responder(['turns' => []], 'Busca pasajeros por esa dirección', new \DateTimeImmutable(), $auth);
        $this->assertSame('ok', $turn['code']);
        $this->assertSame('pasajeros', $turn['visible'][1]['presentation']['type']);
        $this->assertArrayNotHasKey('_passenger', $turn); // Consultar no abre una edición.
        $call['name'] = 'preparar_edicion_pasajero'; $call['args'] = ['operacion' => 'calificacion', 'origen' => 'anteriores'];
        $next = $service->responder(['turns' => [$turn]], 'Ahora calificalo', new \DateTimeImmutable(), $auth);
        $this->assertSame('ok', $next['code']);
        $this->assertSame([(int) $this->clients[4]->id], $next['_passenger']['ids_locadores']);
        foreach ([$this->clients[4]->denominacion, $this->clients[4]->email, $this->clients[4]->obs] as $private) {
            $this->assertStringNotContainsString($private, json_encode($turn['messages']));
            $this->assertStringNotContainsString($private, json_encode(Transport::$payloads));
        }
        $draft = (new \app\components\asistente\EditarPasajeroService())->preparar($this->admin(),
            ['operacion' => 'datos', 'buscar' => $this->keyword, 'campo' => 'observaciones']);
        $this->assertSame([(int) $this->clients[5]->id], $draft['ids_locadores']);
    }

    public function testObservacionesLargasSeBuscanCompletasYSoloSeAbreviaLaPresentacion()
    {
        $model = $this->clients[5];
        $model->obs = str_repeat('Nota privada. ', 1000) . $this->keyword;
        $this->assertTrue($model->save());
        $data = $this->service->buscar($this->admin(), ['texto' => $this->keyword, 'campo' => 'observaciones']);
        $this->assertCount(1, $data['pasajeros']);
        $this->assertSame(201, mb_strlen($data['visible_presentation']['rows'][0]['observaciones']));
    }

    public function testLimiteMantieneIdsYFilasConsistentes()
    {
        for ($i = 0; $i < 21; $i++) {
            $suffix = bin2hex(random_bytes(5));
            $model = new Locador(['denominacion' => 'Límite ' . $suffix, 'documento' => $suffix,
                'email' => $suffix . '@example.com', 'telefono' => '777777', 'obs' => $this->keyword]);
            $this->assertTrue($model->save());
        }
        $data = $this->service->buscar($this->admin(), ['texto' => $this->keyword, 'campo' => 'observaciones']);
        $this->assertTrue($data['truncado']);
        $this->assertLessThanOrEqual(20, count($data['pasajeros']));
        $this->assertSame(array_map(function ($item) { return '#' . $item['id']; }, $data['pasajeros']),
            array_column($data['visible_presentation']['rows'], 'pasajero'));
    }
}
