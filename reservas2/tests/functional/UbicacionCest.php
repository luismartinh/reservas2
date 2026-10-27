<?php
require_once dirname(__DIR__) . '/_support/AsistenteFixtures.php';

class UbicacionCest
{
    use \tests\support\CreaDatosAsistente;

    public function _before()
    {
        // El navegador de pruebas reutiliza Response entre peticiones AJAX y HTML.
        Yii::$app->on(\yii\base\Application::EVENT_BEFORE_REQUEST, function () {
            Yii::$app->response->format = \yii\web\Response::FORMAT_HTML;
        });
    }

    public function localidadesRequiereAccesoYFiltraProvincia(\FunctionalTester $I)
    {
        $localidad = \app\models\Localidad::find()->one();
        $url = '/index-test.php?r=locador%2Flocalidades&provincia=' . $localidad->id_provincia;
        $I->sendAjaxGetRequest($url);
        $I->seeResponseCodeIs(302);
        $I->amLoggedInAs($this->usuarioAsistente(2));
        $I->sendAjaxGetRequest($url);
        $I->seeResponseCodeIs(403);
        $I->amLoggedInAs($this->usuarioAsistente());
        $I->sendAjaxGetRequest($url);
        $I->seeResponseCodeIs(200);
        $items = json_decode($I->grabPageSource(), true)['items'];
        $I->assertNotEmpty($items);
        $expected = \app\models\Localidad::find()->select('id')->where(['id_provincia' => $localidad->id_provincia])->orderBy('descr')->column();
        $I->assertSame(array_map('intval', $expected), array_map('intval', array_column($items, 'id')));
        $I->sendAjaxGetRequest('/index-test.php?r=locador%2Flocalidades&provincia=incorrecta');
        $I->seeResponseCodeIs(400);
        $I->sendAjaxGetRequest('/index-test.php?r=locador%2Flocalidades&provincia=999999');
        $I->seeResponseCodeIs(404);
        $I->sendAjaxPostRequest($url);
        $I->seeResponseCodeIs(405);
    }

    public function listadoConBusquedaUbicacionClienteYEstrellas(\FunctionalTester $I)
    {
        $user = $this->usuarioAsistente(2);
        $access = \app\models\Acceso::findOne(['acceso' => 'locador/index']);
        if (!$access) {
            $access = new \app\models\Acceso(['acceso' => 'locador/index', 'descr' => 'Listar pasajeros']);
            $I->assertTrue($access->save());
        }
        $user->setAcceso($access->id);
        $user->refresh();
        $I->amLoggedInAs($user);
        $localidad = \app\models\Localidad::find()->one();
        $suffix = bin2hex(random_bytes(6));
        $model = new \app\models\Locador(['denominacion' => 'Cliente <script>alert(1)</script>',
            'documento' => $suffix, 'email' => $suffix . '@example.com', 'telefono' => '123456',
            'id_localidad' => $localidad->id, 'calificacion' => 3]);
        $I->assertTrue($model->save(), json_encode($model->errors));
        $I->amOnRoute('locador/index', ['LocadorSearch' => ['buscar' => $suffix,
            'id_provincia' => $localidad->id_provincia, 'id_localidad' => $localidad->id]]);
        $I->seeResponseCodeIs(200);
        $I->seeInField('#locadorsearch-buscar', $suffix);
        $I->seeOptionIsSelected('#locadorsearch-id_provincia', $localidad->provincia->descr);
        $I->seeOptionIsSelected('#locadorsearch-id_localidad', $localidad->descr);
        $I->seeElement('#locadorsearch-id_localidad[data-krajee-select2]');
        $I->seeElement('#locadorsearch-id_provincia[data-krajee-select2]');
        $I->see('Cliente', '#locador_grid th');
        $I->see('Documento: ' . $model->documento, '#locador_grid');
        $I->see('Teléfono: ' . $model->telefono, '#locador_grid');
        $I->see('Email: ' . $model->email, '#locador_grid');
        $I->seeElement('#locador_grid .locador-rating-readonly[aria-label="3 de 5 estrellas"]');
        $I->see('Localidad - Provincia', '#locador_grid');
        $I->assertStringNotContainsString('<script>alert(1)</script>', $I->grabPageSource());
        // Un usuario con acceso sólo al listado también puede cargar sus filtros.
        $I->sendAjaxGetRequest('/index-test.php?r=locador%2Flocalidades&provincia=' . $localidad->id_provincia);
        $I->seeResponseCodeIs(200);
    }

    public function editarPasajeroConservaSeleccionYRechazaProvinciaAjena(\FunctionalTester $I)
    {
        $user = $this->usuarioAsistente(2);
        foreach (['locador/create', 'locador/update', 'locador/view'] as $route) {
            $access = \app\models\Acceso::findOne(['acceso' => $route]);
            if (!$access) {
                $access = new \app\models\Acceso(['acceso' => $route, 'descr' => 'Prueba ubicación']);
                $I->assertTrue($access->save());
            }
            $user->setAcceso($access->id);
        }
        $user->refresh();
        $I->amLoggedInAs($user);
        $I->amOnRoute('locador/create');
        $I->seeResponseCodeIs(200);
        $I->seeCheckboxIsChecked('#locador-calificacion-0');
        $I->seeNumberOfElements('.locador-rating-star', 5);
        $I->seeElement('#locador-id_localidad[data-krajee-select2]');
        $I->seeElement('#locador-id_provincia[data-krajee-select2]');
        $I->assertStringContainsString('"selectOnClose":false', $I->grabPageSource());
        $localidad = \app\models\Localidad::find()->one();
        $suffix = bin2hex(random_bytes(6));
        $model = new \app\models\Locador(['denominacion' => 'Pasajero ubicación', 'documento' => $suffix,
            'email' => $suffix . '@example.com', 'telefono' => '123456', 'id_localidad' => $localidad->id]);
        $I->assertTrue($model->save(), json_encode($model->errors));
        $I->amOnRoute('locador/update', ['id' => $model->id]);
        $I->seeResponseCodeIs(200);
        $I->seeOptionIsSelected('#locador-id_provincia', $localidad->provincia->descr);
        $I->seeOptionIsSelected('#locador-id_localidad', $localidad->descr);
        $I->sendAjaxGetRequest('/index-test.php?r=locador%2Flocalidades&provincia=' . $localidad->id_provincia);
        $I->seeResponseCodeIs(200);
        $otra = \app\models\Provincia::find()->select('id')->where(['<>', 'id', $localidad->id_provincia])->scalar();
        $I->sendAjaxPostRequest('/index-test.php?r=locador%2Fupdate&id=' . $model->id, ['Locador' => [
            'id_provincia' => $otra, 'id_localidad' => $localidad->id, 'calificacion' => 5,
        ]]);
        $I->see('La localidad no pertenece a la provincia indicada.');
        $model->refresh();
        $I->assertSame(0, (int) $model->calificacion);
        $I->sendAjaxPostRequest('/index-test.php?r=locador%2Fupdate&id=' . $model->id, ['Locador' => [
            'id_provincia' => $localidad->id_provincia, 'id_localidad' => $localidad->id, 'calificacion' => 5,
        ]]);
        $model->refresh();
        $I->assertSame(5, (int) $model->calificacion);
        $I->amOnRoute('locador/update', ['id' => $model->id]);
        $I->seeCheckboxIsChecked('#locador-calificacion-5');
        $I->assertSame((int) $localidad->id, (int) $model->id_localidad);
    }

    public function solicitarReservaGuardaUbicacionDelCliente(\FunctionalTester $I)
    {
        $user = $this->usuarioAsistente(2);
        $access = \app\models\Acceso::findOne(['acceso' => 'reserva/reservar']);
        if (!$access) {
            $access = new \app\models\Acceso(['acceso' => 'reserva/reservar', 'descr' => 'Crear reserva']);
            $I->assertTrue($access->save());
        }
        $user->setAcceso($access->id);
        $user->refresh();
        $I->amLoggedInAs($user);
        $free = array_values(array_diff(range(1, 99), \app\models\Cabana::find()->select('numero')->column()));
        $cabana = new \app\models\Cabana(['numero' => $free[0], 'descr' => 'Prueba ubicación reserva',
            'checkin' => '14:00', 'checkout' => '11:00', 'activa' => 1, 'max_pax' => 3, 'color_cabana' => '#3cb44b']);
        $I->assertTrue($cabana->save(), json_encode($cabana->errors));
        $tarifa = new \app\models\Tarifa(['descr' => 'Prueba ubicación reserva', 'inicio' => '2090-04-01',
            'fin' => '2090-04-30', 'valor_dia' => 100, 'min_dias' => 1, 'activa' => 1]);
        $I->assertTrue($tarifa->save(), json_encode($tarifa->errors));
        $link = new \app\models\CabanaTarifa(['id_cabana' => $cabana->id, 'id_tarifa' => $tarifa->id]);
        $I->assertTrue($link->save());
        $localidad = \app\models\Localidad::find()->one();
        $otra = \app\models\Provincia::find()->select('id')->where(['<>', 'id', $localidad->id_provincia])->scalar();
        $base = ['seleccionadas' => [$cabana->id], 'desde' => '2090-04-10', 'hasta' => '2090-04-12'];
        $I->amOnRoute('locador/localidades', ['provincia' => $localidad->id_provincia]);
        $I->seeResponseCodeIs(200);
        $I->sendAjaxPostRequest('/index-test.php?r=reserva%2Fsolicitar-reserva', $base);
        $I->seeResponseCodeIs(200);
        $I->see('Luego, complete el formulario con los datos del cliente:');
        // Select2 oculta el select nativo hasta inicializar su control visual.
        $I->assertStringContainsString('id="dynamicmodel-id_provincia"', $I->grabPageSource());
        $I->assertStringContainsString('id="dynamicmodel-id_localidad"', $I->grabPageSource());
        $I->sendAjaxGetRequest('/index-test.php?r=locador%2Flocalidades&provincia=' . $localidad->id_provincia);
        $I->seeResponseCodeIs(200);
        $suffix = bin2hex(random_bytes(6));
        $data = ['denominacion' => 'Pasajero ubicación reserva', 'documento' => $suffix,
            'email' => $suffix . '@example.com', 'telefono' => '123456', 'monto' => '100',
            'id_localidad' => $localidad->id, 'id_provincia' => $otra, 'send_email' => 0];
        $url = '/index-test.php?r=reserva%2Fsolicitar-reserva&first_post=0';
        $I->sendAjaxPostRequest($url, $base + ['DynamicModel' => $data]);
        $I->see('La localidad no pertenece a la provincia indicada.');
        $I->assertFalse(\app\models\Locador::find()->where(['documento' => $suffix])->exists());
        $data['id_provincia'] = $localidad->id_provincia;
        $I->sendAjaxPostRequest($url, $base + ['DynamicModel' => $data]);
        $cliente = \app\models\Locador::findOne(['documento' => $suffix]);
        $I->assertNotNull($cliente);
        $I->assertSame((int) $localidad->id, (int) $cliente->id_localidad);
        $I->assertTrue(\app\models\Reserva::find()->where(['id_locador' => $cliente->id])->exists());
    }
}
