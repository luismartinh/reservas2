<?php
require_once dirname(__DIR__) . '/_support/AsistenteFixtures.php';
use tests\support\GeminiAsistenteSimulado as Transport;
use app\components\asistente\ContextoConversacion;
use app\models\Locador;

class AsistentePasajeroCest
{
    use \tests\support\CreaDatosAsistente;
    use \tests\support\RuntimeAsistenteAislado;
    private $env;
    private $cliente;

    public function _before()
    {
        $this->iniciarRuntimeAsistente();
        $this->env = getenv('ASISTENTE_PROVEEDOR');
        putenv('ASISTENTE_PROVEEDOR=gemini');
        Transport::reset();
        Yii::$container->set(\app\components\asistente\GeminiProvider::class, Transport::class);
        Yii::$app->on(\yii\base\Application::EVENT_BEFORE_REQUEST, function () {
            Yii::$app->request->enableCsrfValidation = true;
            Yii::$app->response->format = \yii\web\Response::FORMAT_HTML;
        });
        $suffix = bin2hex(random_bytes(6));
        $this->cliente = new Locador(['denominacion' => 'Pasajero privado ' . $suffix, 'documento' => $suffix,
            'email' => $suffix . '@example.com', 'telefono' => '123456', 'obs' => 'Nota original', 'calificacion' => 2]);
        \PHPUnit\Framework\Assert::assertTrue($this->cliente->save(), json_encode($this->cliente->errors));
    }

    public function _after()
    {
        Yii::$container->clear(\app\components\asistente\GeminiProvider::class);
        putenv($this->env === false ? 'ASISTENTE_PROVEEDOR' : 'ASISTENTE_PROVEEDOR=' . $this->env);
        $this->cerrarRuntimeAsistente();
    }

    public function detalleReutilizaVistaSoloParaAdminSinEditar(\FunctionalTester $I)
    {
        $I->amLoggedInAs($this->usuarioAsistente());
        $I->amOnRoute('asistente/pasajero-detalle', ['id' => $this->cliente->id]);
        $I->seeResponseCodeIs(200);
        $I->see($this->cliente->denominacion);
        $I->see($this->cliente->documento);
        $I->see($this->cliente->email);
        $I->see('Nota original');
        $I->seeElement('.locador-rating-readonly[aria-label="2 de 5 estrellas"]');
        $I->dontSeeElement('form');
        $I->assertStringContainsString('no-store', Yii::$app->response->headers->get('Cache-Control'));
        $I->amOnRoute('asistente/pasajero-detalle', ['id' => 'incorrecto']);
        $I->seeResponseCodeIs(400);
        $I->amOnRoute('asistente/pasajero-detalle', ['id' => 2147483647]);
        $I->seeResponseCodeIs(404);
        $user = $this->usuarioAsistente();
        $user->nivel = 4;
        $I->assertTrue($user->save(false));
        $I->amLoggedInAs($user);
        $I->amOnRoute('asistente/pasajero-detalle', ['id' => $this->cliente->id]);
        $I->seeResponseCodeIs(403);
        $I->dontSee($this->cliente->email);
    }

    private function abrir(\FunctionalTester $I, $operation, $args = [])
    {
        $I->amLoggedInAs($this->usuarioAsistente());
        $I->amOnRoute('asistente/index');
        $settings = json_decode($I->grabAttributeFrom('#asistente-chat', 'data-config'), true);
        $call = &Transport::$response['data']['candidates'][0]['content']['parts'][0]['functionCall'];
        $call['name'] = 'preparar_edicion_pasajero'; $call['args'] = ['operacion' => $operation] + $args;
        $I->sendAjaxPostRequest('/index.php?r=asistente/mensaje', ['conversacion' => $settings['initial']['conversacion'],
            'mensaje' => 'Preparar edición de pasajero', Yii::$app->request->csrfParam => Yii::$app->request->getCsrfToken()]);
        $I->seeResponseCodeIs(200);
        $result = json_decode($I->grabPageSource(), true);
        $I->assertSame('ok', $result['codigo']);
        $I->assertStringNotContainsString($this->cliente->email, $I->grabPageSource());
        $token = $result['acciones']['pasajero']['token'];
        $I->amOnRoute('asistente/index');
        $I->seeElement('#asistente-pasajero-pendiente:not([hidden])');
        return '/index.php?r=asistente/pasajero&borrador=' . $token . '&modal=1';
    }

    private function post(\FunctionalTester $I, $url, array $data)
    {
        $I->sendAjaxPostRequest($url, $data + [Yii::$app->request->csrfParam => Yii::$app->request->getCsrfToken()]);
    }

    public function calificarPrecargaNotaRevisaYGuardaUnaSolaVez(\FunctionalTester $I)
    {
        $url = $this->abrir($I, 'calificacion', ['id_locador' => $this->cliente->id]);
        $I->amOnPage($url);
        $I->seeResponseCodeIs(200);
        $I->seeCheckboxIsChecked('#locador-calificacion-2');
        $I->seeInField('#locador-obs', 'Nota original');
        $I->dontSeeElement('#locador-email');
        $this->post($I, $url, ['fase' => 'revisar', 'Locador' => ['calificacion' => 5,
            'obs' => 'Nueva nota <script>alert(1)</script>', 'email' => 'no-cambiar@example.com', 'id' => 999999]]);
        $I->see('Confirmar y guardar cambios');
        $I->assertStringNotContainsString('<script>alert(1)</script>', $I->grabPageSource());
        $this->cliente->refresh();
        $I->assertSame('Nota original', $this->cliente->obs);
        $this->post($I, $url, ['fase' => 'confirmar', 'Locador' => ['calificacion' => 1, 'id' => 999999]]);
        $I->seeResponseCodeIs(200);
        $this->cliente->refresh();
        $I->assertSame(5, (int) $this->cliente->calificacion);
        $I->assertSame('Nueva nota <script>alert(1)</script>', $this->cliente->obs);
        $I->assertNotSame('no-cambiar@example.com', $this->cliente->email);
        $I->assertStringNotContainsString('Nueva nota', json_encode(Yii::$app->session->get(ContextoConversacion::KEY)));
        $turns = Yii::$app->session->get(ContextoConversacion::KEY)['turns'];
        $last = end($turns);
        $I->assertSame((int) $this->cliente->id, $last['pasajero_editado']);
        Locador::updateAll(['obs' => 'Cambio posterior'], ['id' => $this->cliente->id]);
        $this->post($I, $url, ['fase' => 'confirmar']);
        $this->cliente->refresh();
        $I->assertSame('Cambio posterior', $this->cliente->obs);
        $I->assertCount(1, Transport::$payloads);
    }

    public function datosBuscaEnModalSinIaYGuardaContactoConValidacion(\FunctionalTester $I)
    {
        $url = $this->abrir($I, 'datos');
        $I->amOnPage($url);
        $this->post($I, $url, ['fase' => 'buscar', 'buscar' => $this->cliente->documento]);
        $I->amOnPage($url);
        $I->seeInField('#locador-email', $this->cliente->email);
        $I->assertStringContainsString('data-krajee-select2', $I->grabPageSource());
        $this->post($I, $url, ['fase' => 'revisar', 'Locador' => ['telefono' => '987654', 'denominacion' => 'Nombre editado', 'calificacion' => 5]]);
        $this->post($I, $url, ['fase' => 'confirmar']);
        $this->cliente->refresh();
        $I->assertSame('987654', $this->cliente->telefono);
        $I->assertSame('Nombre editado', $this->cliente->denominacion);
        $I->assertSame(2, (int) $this->cliente->calificacion);
        $I->assertCount(1, Transport::$payloads);
    }

    public function exigeCsrfRevisionPermisoYPropietario(\FunctionalTester $I)
    {
        $I->amOnRoute('asistente/pasajero', ['borrador' => str_repeat('a', 32)]);
        $I->seeResponseCodeIs(403);
        $url = $this->abrir($I, 'calificacion', ['id_locador' => $this->cliente->id]);
        $I->amOnPage($url);
        $I->sendAjaxPostRequest($url, ['fase' => 'revisar', 'Locador' => ['calificacion' => 5]]);
        $I->seeResponseCodeIs(400);
        $this->post($I, $url, ['fase' => 'confirmar']);
        $this->cliente->refresh(); $I->assertSame(2, (int) $this->cliente->calificacion);
        $I->amLoggedInAs($this->usuarioAsistente(2));
        $I->amOnPage($url); $I->seeResponseCodeIs(403);
        $I->amLoggedInAs($this->usuarioAsistente());
        $I->amOnPage($url); $I->seeResponseCodeIs(409);
    }

    public function cambioConcurrenteNoSobrescribeYPermiteRecargar(\FunctionalTester $I)
    {
        $url = $this->abrir($I, 'calificacion', ['id_locador' => $this->cliente->id]);
        $I->amOnPage($url);
        $this->post($I, $url, ['fase' => 'revisar', 'Locador' => ['calificacion' => 5, 'obs' => 'Mi nota']]);
        Locador::updateAll(['obs' => 'Nota concurrente'], ['id' => $this->cliente->id]);
        $this->post($I, $url, ['fase' => 'confirmar']);
        $I->see('Los datos del pasajero cambiaron.');
        $this->post($I, $url, ['fase' => 'recargar']);
        $I->amOnPage($url);
        $I->seeInField('#locador-obs', 'Nota concurrente');
        $this->cliente->refresh(); $I->assertSame(2, (int) $this->cliente->calificacion);
    }

    public function homonimosExigenSeleccionYElBorradorVencidoNoPermiteEditar(\FunctionalTester $I)
    {
        $suffix = bin2hex(random_bytes(6));
        $other = new Locador(['denominacion' => $this->cliente->denominacion, 'documento' => $suffix,
            'email' => $suffix . '@example.com', 'telefono' => '777777']);
        $I->assertTrue($other->save());
        $url = $this->abrir($I, 'datos', ['titular' => $this->cliente->denominacion]);
        $I->amOnPage($url);
        $I->seeNumberOfElements('input[name="id_locador"]', 2);
        $I->dontSeeElement('#locador-email');
        $this->post($I, $url, ['fase' => 'seleccionar', 'id_locador' => 99999999]);
        $I->see('Seleccioná uno de los pasajeros encontrados.');
        $this->post($I, $url, ['fase' => 'seleccionar', 'id_locador' => $other->id]);
        $I->amOnPage($url);
        $I->seeInField('#locador-email', $other->email);
        $draft = Yii::$app->session->get('__asistente_pasajero');
        $draft['expires'] = time() - 1;
        Yii::$app->session->set('__asistente_pasajero', $draft);
        $I->amOnPage($url);
        $I->seeResponseCodeIs(409);
    }

    public function buscarPasajerosMuestraDatosYPermiteContinuarConCalificacion(\FunctionalTester $I)
    {
        $suffix = bin2hex(random_bytes(5));
        $this->cliente->domicilio = 'Calle privada ' . $suffix;
        $this->cliente->obs = 'Nota <script>alert(1)</script>';
        $localidad = \app\models\Localidad::find()->one();
        $this->cliente->id_localidad = $localidad->id;
        $this->cliente->id_provincia = $localidad->id_provincia;
        $I->assertTrue($this->cliente->save());
        $I->amLoggedInAs($this->usuarioAsistente());
        $I->amOnRoute('asistente/index');
        $settings = json_decode($I->grabAttributeFrom('#asistente-chat', 'data-config'), true);
        $call = &Transport::$response['data']['candidates'][0]['content']['parts'][0]['functionCall'];
        $call['name'] = 'buscar_pasajeros'; $call['args'] = ['texto' => $suffix, 'campo' => 'direccion'];
        $data = ['conversacion' => $settings['initial']['conversacion'], 'mensaje' => 'Buscar pasajeros por dirección',
            Yii::$app->request->csrfParam => Yii::$app->request->getCsrfToken()];
        $I->sendAjaxPostRequest('/index.php?r=asistente/mensaje', $data);
        $I->seeResponseCodeIs(200);
        $result = json_decode($I->grabPageSource(), true);
        $I->assertSame('ok', $result['codigo']);
        $I->assertArrayNotHasKey('pasajero', $result['acciones']);
        $table = $result['mensajes'][1]['presentation'];
        $I->assertSame('pasajeros', $table['type']);
        $I->assertCount(1, $table['rows']);
        $I->assertSame($this->cliente->domicilio, $table['rows'][0]['direccion']);
        $I->assertSame($this->cliente->obs, $table['rows'][0]['observaciones']);
        $I->assertSame($localidad->descr, $table['rows'][0]['localidad']);
        $I->assertSame($localidad->provincia->descr, $table['rows'][0]['provincia']);
        $call['name'] = 'preparar_edicion_pasajero'; $call['args'] = ['operacion' => 'calificacion', 'origen' => 'anteriores'];
        $data['mensaje'] = 'Ahora calificalo';
        $I->sendAjaxPostRequest('/index.php?r=asistente/mensaje', $data);
        $I->seeResponseCodeIs(200);
        $result = json_decode($I->grabPageSource(), true);
        $I->assertSame('ok', $result['codigo']);
        $I->assertSame([(int) $this->cliente->id], Yii::$app->session->get('__asistente_pasajero')['proposal']['ids_locadores']);
        foreach ([$this->cliente->email, $this->cliente->domicilio, $this->cliente->obs] as $private) {
            $I->assertStringNotContainsString($private, json_encode(Transport::$payloads));
        }
    }

    public function modalBuscaPorObservacionesYMuestraDireccionUbicacionYEstrellas(\FunctionalTester $I)
    {
        $suffix = bin2hex(random_bytes(5));
        $this->cliente->obs = 'Observación ' . $suffix;
        $this->cliente->domicilio = 'Dirección compartida ' . $suffix;
        $I->assertTrue($this->cliente->save());
        $other = new Locador(['denominacion' => 'Otro pasajero', 'documento' => $suffix,
            'email' => $suffix . '@example.com', 'telefono' => '777777',
            'obs' => $this->cliente->obs, 'domicilio' => $this->cliente->domicilio, 'calificacion' => 4]);
        $I->assertTrue($other->save());
        $url = $this->abrir($I, 'datos', ['buscar' => $suffix, 'campo' => 'direccion']);
        $I->amOnPage($url);
        $I->seeNumberOfElements('input[name="id_locador"]', 2);
        $I->see('Observaciones');
        $I->see($this->cliente->domicilio);
        $I->seeElement('.locador-rating-readonly[aria-label="4 de 5 estrellas"]');
        $this->post($I, $url, ['fase' => 'buscar', 'campo' => 'observaciones', 'buscar' => $suffix]);
        $I->amOnPage($url);
        $I->seeNumberOfElements('input[name="id_locador"]', 2);
        $I->assertCount(1, Transport::$payloads);
    }
}
