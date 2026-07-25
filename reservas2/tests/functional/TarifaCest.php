<?php

use Codeception\Util\HttpCode;

class TarifaCest
{
    private $seq = 0;

    public function tarifaCrudBasico(\FunctionalTester $I)
    {
        $actor = $this->crearUsuario(2);
        $this->otorgarPermisos($actor, [
            'tarifa/index',
            'tarifa/create',
            'tarifa/update',
            'tarifa/view',
        ]);

        $suffix = $this->nextSuffix('tarifa');
        $descr = 'Tarifa ' . $suffix;
        $descrActualizada = 'TarifaEd ' . $suffix;

        $I->amLoggedInAs($actor);

        $I->amOnRoute('tarifa/index');
        $I->see('Administrar Tarifas');

        $I->amOnRoute('tarifa/create');
        $I->see('Nueva tarifa');
        $I->submitForm('#tarifa-form-id', [
            'Tarifa[descr]' => $descr,
            'Tarifa[inicio]' => '2026-12-01',
            'Tarifa[fin]' => '2026-12-10',
            'Tarifa[valor_dia]' => '250.50',
            'Tarifa[min_dias]' => '2',
            'Tarifa[activa]' => 1,
        ]);

        $I->see('Ver Tarifa');
        $I->see($descr);

        $tarifa = \app\models\Tarifa::find()->where(['descr' => $descr])->one();
        $I->assertNotNull($tarifa);

        $I->amOnRoute('tarifa/update', ['id' => $tarifa->id]);
        $I->see('Modificar tarifa');
        $I->submitForm('#tarifa-form-id', [
            'Tarifa[descr]' => $descrActualizada,
            'Tarifa[inicio]' => '2026-12-01',
            'Tarifa[fin]' => '2026-12-12',
            'Tarifa[valor_dia]' => '275.00',
            'Tarifa[min_dias]' => '3',
            'Tarifa[activa]' => 1,
        ]);

        $I->see('Ver Tarifa');
        $I->see($descrActualizada);

        $tarifa->refresh();
        $I->assertSame($descrActualizada, $tarifa->descr);
        $I->assertSame(3, (int) $tarifa->min_dias);
    }

    public function cabanaVincularTarifasBloqueaSolapesYPermiteNoSolapadas(\FunctionalTester $I)
    {
        $actor = $this->crearUsuario(2);
        $this->otorgarPermisos($actor, [
            'cabana/update',
            'tarifa/update',
        ]);

        $cabana = $this->crearCabana();
        $tarifaBase = $this->crearTarifa('Base', '2026-12-01', '2026-12-10', 100, 1);
        $tarifaSolapada = $this->crearTarifa('Solapada', '2026-12-05', '2026-12-15', 150, 1);
        $tarifaLibre = $this->crearTarifa('Libre', '2026-12-11', '2026-12-20', 200, 1);

        $this->vincularTarifa($cabana, $tarifaBase);

        $I->amLoggedInAs($actor);

        $I->amOnRoute('cabana/vincular-cabanas-tarifas', ['id_cabana' => $cabana->id]);
        $I->see('Vincular Tarifas a: ' . $cabana->descr);
        $I->dontSee($tarifaSolapada->descr);
        $I->see($tarifaLibre->descr);

        $I->submitForm('#form-vincular-tarifas', [
            'tarifa_ids' => [$tarifaSolapada->id],
        ]);
        $I->see('se superpone');
        $I->assertNull(\app\models\CabanaTarifa::find()->where([
            'id_cabana' => $cabana->id,
            'id_tarifa' => $tarifaSolapada->id,
        ])->one());

        $I->submitForm('#form-vincular-tarifas', [
            'tarifa_ids' => [$tarifaLibre->id],
        ]);
        $I->see('Se vincularon 1 tarifa');
        $I->assertNotNull(\app\models\CabanaTarifa::find()->where([
            'id_cabana' => $cabana->id,
            'id_tarifa' => $tarifaLibre->id,
        ])->one());
    }

    public function calendarioMuestraTarifasYControlesDeNavegacion(\FunctionalTester $I)
    {
        $actor = $this->crearUsuario(2);
        $this->otorgarPermisos($actor, [
            'tarifa/index',
        ]);

        $cabana = $this->crearCabana();
        $tarifaJulio = $this->crearTarifaConFecha('Invierno', '2026-07-10', '2026-07-15', 300, 2, '2026-07-25 11:00:00');
        $tarifaAgosto = $this->crearTarifaConFecha('Agosto', '2026-08-01', '2026-08-07', 350, 3, '2026-07-24 11:00:00');

        $this->vincularTarifa($cabana, $tarifaJulio);
        $this->vincularTarifa($cabana, $tarifaAgosto);

        $I->amLoggedInAs($actor);
        $I->amOnRoute('tarifa/calendario', [
            'year' => 2026,
            'month' => 7,
            'id_cabana' => $cabana->id,
        ]);

        $I->see('Calendario de tarifas');
        $I->see('Buscar tarifas');
        $I->see('Agregar nueva tarifa');
        $I->see((string) $tarifaJulio->id . ' ' . $tarifaJulio->descr);
        $I->see((string) $tarifaAgosto->id . ' ' . $tarifaAgosto->descr);
        $I->seeInSource('r=tarifa%2Fcalendario&amp;year=2026&amp;month=06&amp;id_cabana=' . $cabana->id);
        $I->seeInSource('r=tarifa%2Fcalendario&amp;year=2026&amp;month=08&amp;id_cabana=' . $cabana->id);
    }

    public function tarifasPorCabanaDevuelveJsonOrdenadoPorFechaMasReciente(\FunctionalTester $I)
    {
        $actor = $this->crearUsuario(2);
        $cabana = $this->crearCabana();
        $tarifaReciente = $this->crearTarifaConFecha('Reciente', '2026-09-01', '2026-09-05', 500, 2, '2026-07-25 12:00:00');
        $tarifaAnterior = $this->crearTarifaConFecha('Anterior', '2026-08-10', '2026-08-12', 250, 1, '2026-07-20 12:00:00');

        $this->vincularTarifa($cabana, $tarifaAnterior);
        $this->vincularTarifa($cabana, $tarifaReciente);

        $I->amLoggedInAs($actor);
        $I->haveHttpHeader('X-Requested-With', 'XMLHttpRequest');
        $I->amOnPage('/index-test.php?r=tarifa%2Ftarifas-por-cabana&id_cabana=' . $cabana->id);
        $I->seeResponseCodeIs(HttpCode::OK);

        $response = json_decode($I->grabPageSource(), true);

        $I->assertIsArray($response);
        $I->assertCount(2, $response['results']);
        $I->assertSame((int) $tarifaReciente->id, (int) $response['results'][0]['id']);
        $I->assertSame((int) $tarifaAnterior->id, (int) $response['results'][1]['id']);
        $I->assertStringContainsString($tarifaReciente->descr, $response['results'][0]['text']);
        $I->assertStringContainsString('01-09-2026 a 05-09-2026', $response['results'][0]['text']);
        $I->assertStringContainsString('$500,00', $response['results'][0]['text']);
    }

    public function editarCalendarioTarifaRenderizaFormularioYGuardaCambios(\FunctionalTester $I)
    {
        $actor = $this->crearUsuario(2);
        $this->otorgarPermisos($actor, [
            'tarifa/update',
        ]);

        $tarifa = $this->crearTarifa('Editar', '2026-12-01', '2026-12-05', 410, 2);

        $I->amLoggedInAs($actor);
        $I->amOnRoute('tarifa/editar-calendario-tarifa', ['id' => $tarifa->id]);
        $I->see('Editar tarifa');
        $I->seeInSource('id="tarifa-calendario-modal-form"');
        $I->seeInSource('name="Tarifa[descr]"');

        $I->sendAjaxPostRequest('/index-test.php?r=tarifa%2Feditar-calendario-tarifa&id=' . $tarifa->id, [
            'Tarifa' => [
                'descr' => 'Tarifa Editada ' . $this->nextSuffix('editada'),
                'inicio' => '2026-12-02',
                'fin' => '2026-12-09',
                'valor_dia' => '455.75',
                'min_dias' => '4',
                'activa' => 1,
            ],
        ]);

        $I->seeResponseCodeIs(HttpCode::OK);
        $response = json_decode($I->grabPageSource(), true);

        $I->assertTrue($response['success']);

        $tarifa->refresh();
        $I->assertSame('2026-12-02 00:00:00', $tarifa->inicio);
        $I->assertSame('2026-12-09 23:59:59', $tarifa->fin);
        $I->assertSame(4, (int) $tarifa->min_dias);
        $I->assertSame(455.75, (float) $tarifa->valor_dia);
    }

    public function editarCalendarioTarifaDevuelveErroresSiLosDatosSonInvalidos(\FunctionalTester $I)
    {
        $actor = $this->crearUsuario(2);
        $this->otorgarPermisos($actor, [
            'tarifa/update',
        ]);

        $tarifa = $this->crearTarifa('Invalida', '2026-11-01', '2026-11-05', 123, 2);

        $I->amLoggedInAs($actor);
        $I->sendAjaxPostRequest('/index-test.php?r=tarifa%2Feditar-calendario-tarifa&id=' . $tarifa->id, [
            'Tarifa' => [
                'descr' => $tarifa->descr,
                'inicio' => '2026-11-06',
                'fin' => '2026-11-05',
                'valor_dia' => '0',
                'min_dias' => '0',
                'activa' => 1,
            ],
        ]);

        $I->seeResponseCodeIs(HttpCode::OK);
        $response = json_decode($I->grabPageSource(), true);

        $I->assertFalse($response['success']);
        $I->assertStringContainsString('La fecha de inicio debe ser menor que la fecha de fin.', $response['html']);
        $I->assertStringContainsString('Min. Dias', $response['html']);
    }

    public function crearCalendarioTarifaCreaYAsociaALaCabanaSeleccionada(\FunctionalTester $I)
    {
        $actor = $this->crearUsuario(2);
        $this->otorgarPermisos($actor, [
            'tarifa/create',
        ]);

        $cabana = $this->crearCabana();
        $suffix = $this->nextSuffix('nueva_tarifa');
        $descr = 'Tar cal ' . $suffix;

        $I->amLoggedInAs($actor);
        $I->amOnRoute('tarifa/crear-calendario-tarifa', ['id_cabana' => $cabana->id]);
        $I->see('Agregar nueva tarifa');
        $I->seeInSource('Crear y asociar');

        $I->sendAjaxPostRequest('/index-test.php?r=tarifa%2Fcrear-calendario-tarifa&id_cabana=' . $cabana->id, [
            'Tarifa' => [
                'descr' => $descr,
                'inicio' => '2026-10-10',
                'fin' => '2026-10-15',
                'valor_dia' => '678.90',
                'min_dias' => '3',
                'activa' => 1,
            ],
        ]);

        $I->seeResponseCodeIs(HttpCode::OK);
        $response = json_decode($I->grabPageSource(), true);

        $I->assertTrue($response['success']);

        $tarifa = \app\models\Tarifa::find()->where(['descr' => $descr])->one();
        $I->assertNotNull($tarifa);
        $I->assertNotNull(\app\models\CabanaTarifa::find()->where([
            'id_cabana' => $cabana->id,
            'id_tarifa' => $tarifa->id,
        ])->one());
    }

    public function crearCalendarioTarifaDevuelveErroresSiLaAsociacionSeSuperpone(\FunctionalTester $I)
    {
        $actor = $this->crearUsuario(2);
        $this->otorgarPermisos($actor, [
            'tarifa/create',
        ]);

        $cabana = $this->crearCabana();
        $existente = $this->crearTarifa('Existente', '2026-10-10', '2026-10-15', 500, 2);
        $this->vincularTarifa($cabana, $existente);

        $suffix = $this->nextSuffix('superpuesta');
        $descr = 'Tar sup ' . $suffix;

        $I->amLoggedInAs($actor);
        $I->sendAjaxPostRequest('/index-test.php?r=tarifa%2Fcrear-calendario-tarifa&id_cabana=' . $cabana->id, [
            'Tarifa' => [
                'descr' => $descr,
                'inicio' => '2026-10-12',
                'fin' => '2026-10-18',
                'valor_dia' => '700',
                'min_dias' => '2',
                'activa' => 1,
            ],
        ]);

        $I->seeResponseCodeIs(HttpCode::OK);
        $response = json_decode($I->grabPageSource(), true);

        $I->assertFalse($response['success']);
        $I->assertStringContainsString('se superpone', $response['html']);
        $I->assertNull(\app\models\Tarifa::find()->where(['descr' => $descr])->one());
    }

    private function otorgarPermisos($usuario, array $codigos)
    {
        foreach ($codigos as $codigo) {
            $acceso = $this->buscarOCrearAcceso($codigo);
            $usuario->setAcceso($acceso->id);
        }
        $usuario->refresh();
    }

    private function buscarOCrearAcceso($codigo)
    {
        $acceso = \app\models\Acceso::find()->where(['acceso' => $codigo])->one();
        if ($acceso) {
            return $acceso;
        }

        $acceso = new \app\models\Acceso([
            'descr' => 'Acceso Tarifa ' . $this->nextSuffix('acc'),
            'acceso' => $codigo,
        ]);

        \PHPUnit\Framework\Assert::assertTrue($acceso->save(), implode(' | ', $acceso->getErrorSummary(true)));

        return $acceso;
    }

    private function crearUsuario($nivel)
    {
        $suffix = $this->nextSuffix('tar_usr');
        $usuario = new \app\models\Identificador([
            'login' => 'tar_usr_' . $suffix,
            'nombre' => 'Tarifa ' . $suffix,
            'apellido' => 'Tester',
            'pwd' => 'secret123',
            'email' => $suffix . '@example.com',
            'activo' => 1,
            'nivel' => $nivel,
            'codigo' => 'TAR' . strtoupper(substr(md5($suffix), 0, 6)),
        ]);
        $usuario->setPassword('secret123');
        $usuario->generateAuthKey();

        \PHPUnit\Framework\Assert::assertTrue($usuario->save(), implode(' | ', $usuario->getErrorSummary(true)));

        return $usuario;
    }

    private function crearCabana()
    {
        $suffix = $this->nextSuffix('cab');
        $numero = $this->buscarNumeroCabanaDisponible();
        $cabana = new \app\models\Cabana([
            'descr' => 'Cabana Tarifa ' . $suffix,
            'checkin' => '15:00',
            'checkout' => '10:00',
            'max_pax' => 4,
            'activa' => 1,
            'numero' => $numero,
            'color_cabana' => '#3cb44b',
        ]);

        \PHPUnit\Framework\Assert::assertTrue($cabana->save(), implode(' | ', $cabana->getErrorSummary(true)));

        return $cabana;
    }

    private function crearTarifa($prefix, $inicio, $fin, $valorDia, $minDias)
    {
        $tarifa = new \app\models\Tarifa([
            'descr' => 'Tarifa ' . $prefix . ' ' . $this->nextSuffix('tar'),
            'inicio' => $inicio,
            'fin' => $fin,
            'valor_dia' => $valorDia,
            'min_dias' => $minDias,
            'activa' => 1,
            'fecha' => '2026-07-25 12:00:00',
        ]);

        \PHPUnit\Framework\Assert::assertTrue($tarifa->save(), implode(' | ', $tarifa->getErrorSummary(true)));

        return $tarifa;
    }

    private function crearTarifaConFecha($prefix, $inicio, $fin, $valorDia, $minDias, $fecha)
    {
        $tarifa = new \app\models\Tarifa([
            'descr' => 'Tarifa ' . $prefix . ' ' . $this->nextSuffix('tar'),
            'inicio' => $inicio,
            'fin' => $fin,
            'valor_dia' => $valorDia,
            'min_dias' => $minDias,
            'activa' => 1,
            'fecha' => $fecha,
        ]);

        \PHPUnit\Framework\Assert::assertTrue($tarifa->save(), implode(' | ', $tarifa->getErrorSummary(true)));

        return $tarifa;
    }

    private function vincularTarifa($cabana, $tarifa)
    {
        $vinculo = new \app\models\CabanaTarifa([
            'id_cabana' => $cabana->id,
            'id_tarifa' => $tarifa->id,
        ]);

        \PHPUnit\Framework\Assert::assertTrue($vinculo->save(), implode(' | ', $vinculo->getErrorSummary(true)));

        return $vinculo;
    }

    private function buscarNumeroCabanaDisponible()
    {
        for ($numero = 99; $numero >= 1; $numero--) {
            if (!\app\models\Cabana::find()->where(['numero' => $numero])->exists()) {
                return $numero;
            }
        }

        \PHPUnit\Framework\Assert::fail('No se encontró un número de cabaña disponible para la prueba funcional.');
    }

    private function nextSuffix($prefix)
    {
        $this->seq++;

        return $prefix . '_' . uniqid((string) $this->seq, false);
    }
}
