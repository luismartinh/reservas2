<?php

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
        $cabana = new \app\models\Cabana([
            'descr' => 'Cabana Tarifa ' . $suffix,
            'checkin' => '15:00',
            'checkout' => '10:00',
            'max_pax' => 4,
            'activa' => 1,
            'numero' => 50 + $this->seq,
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

    private function vincularTarifa($cabana, $tarifa)
    {
        $vinculo = new \app\models\CabanaTarifa([
            'id_cabana' => $cabana->id,
            'id_tarifa' => $tarifa->id,
        ]);

        \PHPUnit\Framework\Assert::assertTrue($vinculo->save(), implode(' | ', $vinculo->getErrorSummary(true)));

        return $vinculo;
    }

    private function nextSuffix($prefix)
    {
        $this->seq++;

        return $prefix . '_' . uniqid((string) $this->seq, false);
    }
}
