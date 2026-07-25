<?php

class CabanaCest
{
    private $seq = 0;

    public function cabanaCrudBasicoPersisteColorYCaracteristicas(\FunctionalTester $I)
    {
        $actor = $this->crearUsuario(2);
        $this->otorgarPermisos($actor, [
            'cabana/index',
            'cabana/create',
            'cabana/update',
            'cabana/view',
        ]);

        $suffix = $this->nextSuffix('cabana');
        $descr = 'Cabana ' . $suffix;
        $descrActualizada = 'CabanaEd ' . $suffix;

        $I->amLoggedInAs($actor);

        $I->amOnRoute('cabana/index');
        $I->see('Administrar Cabañas');

        $I->amOnRoute('cabana/create');
        $I->see('Nueva cabaña');
        $I->submitForm('#cabana-form-id', [
            'Cabana[descr]' => $descr,
            'Cabana[checkout]' => '10:00',
            'Cabana[checkin]' => '15:00',
            'Cabana[numero]' => 41,
            'Cabana[max_pax]' => 5,
            'Cabana[color_cabana]' => '#3cb44b',
            'Cabana[activa]' => 1,
            'Cabana[caracteristicas_es]' => "Vista al lago\nParrilla",
            'Cabana[caracteristicas_en]' => "Lake view\nGrill",
            'Cabana[caracteristicas_pt_br]' => "Vista para o lago\nChurrasqueira",
        ]);

        $I->see('Ver Cabaña');
        $I->see($descr);

        $cabana = \app\models\Cabana::find()->where(['descr' => $descr])->one();
        $I->assertNotNull($cabana);
        $I->assertSame('#3cb44b', $cabana->color_cabana);
        $I->assertSame("Vista al lago\nParrilla", $cabana->caracteristicas_es);

        $I->amOnRoute('cabana/update', ['id' => $cabana->id]);
        $I->see('Modificar cabana');
        $I->submitForm('#cabana-form-id', [
            'Cabana[descr]' => $descrActualizada,
            'Cabana[checkout]' => '10:00',
            'Cabana[checkin]' => '16:00',
            'Cabana[numero]' => 41,
            'Cabana[max_pax]' => 6,
            'Cabana[color_cabana]' => '#4363d8',
            'Cabana[activa]' => 1,
            'Cabana[caracteristicas_es]' => "Vista al lago\nDeck",
            'Cabana[caracteristicas_en]' => "Lake view\nDeck",
            'Cabana[caracteristicas_pt_br]' => "Vista para o lago\nDeck",
        ]);

        $I->see('Ver Cabaña');
        $I->see($descrActualizada);

        $cabanaActualizada = \app\models\Cabana::findOne($cabana->id);
        $I->assertSame($descrActualizada, $cabanaActualizada->descr);
        $I->assertSame(6, (int) $cabanaActualizada->max_pax);
        $I->assertSame('#4363d8', $cabanaActualizada->color_cabana);
        $I->assertSame("Lake view\nDeck", $cabanaActualizada->caracteristicas_en);
    }

    public function createMuestraErrorSiCheckinNoEsPosteriorACheckout(\FunctionalTester $I)
    {
        $actor = $this->crearUsuario(2);
        $this->otorgarPermisos($actor, [
            'cabana/create',
        ]);

        $suffix = $this->nextSuffix('cabana_err');

        $I->amLoggedInAs($actor);
        $I->amOnRoute('cabana/create');
        $I->submitForm('#cabana-form-id', [
            'Cabana[descr]' => 'Cabana invalida ' . $suffix,
            'Cabana[checkout]' => '10:00',
            'Cabana[checkin]' => '09:00',
            'Cabana[numero]' => 42,
            'Cabana[max_pax]' => 4,
            'Cabana[color_cabana]' => '#f58231',
            'Cabana[activa]' => 1,
        ]);

        $I->see('La hora de ingreso debe ser posterior a la hora de egreso.');
        $I->dontSee('Ver Cabaña');
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
            'descr' => 'Acceso Cabana ' . $this->nextSuffix('acc'),
            'acceso' => $codigo,
        ]);

        \PHPUnit\Framework\Assert::assertTrue($acceso->save(), implode(' | ', $acceso->getErrorSummary(true)));

        return $acceso;
    }

    private function crearUsuario($nivel)
    {
        $suffix = $this->nextSuffix('cab_usr');
        $usuario = new \app\models\Identificador([
            'login' => 'cab_usr_' . $suffix,
            'nombre' => 'Cabana ' . $suffix,
            'apellido' => 'Tester',
            'pwd' => 'secret123',
            'email' => $suffix . '@example.com',
            'activo' => 1,
            'nivel' => $nivel,
            'codigo' => 'CAB' . strtoupper(substr(md5($suffix), 0, 6)),
        ]);
        $usuario->setPassword('secret123');
        $usuario->generateAuthKey();

        \PHPUnit\Framework\Assert::assertTrue($usuario->save(), implode(' | ', $usuario->getErrorSummary(true)));

        return $usuario;
    }

    private function nextSuffix($prefix)
    {
        $this->seq++;

        return $prefix . '_' . uniqid((string) $this->seq, false);
    }
}
