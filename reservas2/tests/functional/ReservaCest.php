<?php

class ReservaCest
{
    private $seq = 0;

    public function puedeVerIndiceYEliminarReserva(\FunctionalTester $I)
    {
        $actor = $this->crearUsuario(2);
        $this->otorgarPermisos($actor, [
            'reserva/index',
            'reserva/delete',
        ]);

        $estado = $this->crearEstado('confirmado', 'Confirmado');
        $locador = $this->crearLocador();
        $reserva = $this->crearReserva($locador, $estado);

        $I->amLoggedInAs($actor);
        $I->amOnRoute('reserva/index');
        $I->see('Administrar Reservas');
        $I->see($locador->denominacion);

        $I->amOnRoute('reserva/delete', ['id' => $reserva->id]);
        $I->see('Administrar Reservas');

        $eliminada = \app\models\Reserva::findOne($reserva->id);
        $I->assertNull($eliminada);
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
            'descr' => 'Acceso Reserva ' . $this->nextSuffix('acc'),
            'acceso' => $codigo,
        ]);

        \PHPUnit\Framework\Assert::assertTrue($acceso->save(), implode(' | ', $acceso->getErrorSummary(true)));

        return $acceso;
    }

    private function crearUsuario($nivel)
    {
        $suffix = $this->nextSuffix('res_usr');
        $usuario = new \app\models\Identificador([
            'login' => 'res_usr_' . $suffix,
            'nombre' => 'Reserva ' . $suffix,
            'apellido' => 'Tester',
            'pwd' => 'secret123',
            'email' => $suffix . '@example.com',
            'activo' => 1,
            'nivel' => $nivel,
            'codigo' => 'RSV' . strtoupper(substr(md5($suffix), 0, 6)),
        ]);
        $usuario->setPassword('secret123');
        $usuario->generateAuthKey();

        \PHPUnit\Framework\Assert::assertTrue($usuario->save(), implode(' | ', $usuario->getErrorSummary(true)));

        return $usuario;
    }

    private function crearEstado($slug, $descr)
    {
        $estado = \app\models\Estado::find()->where(['slug' => $slug])->one();
        if ($estado) {
            return $estado;
        }

        $estado = new \app\models\Estado([
            'slug' => $slug,
            'descr' => $descr,
        ]);

        \PHPUnit\Framework\Assert::assertTrue($estado->save(), implode(' | ', $estado->getErrorSummary(true)));

        return $estado;
    }

    private function crearLocador()
    {
        $suffix = $this->nextSuffix('loc');
        $locador = new \app\models\Locador([
            'denominacion' => 'Locador ' . $suffix,
            'documento' => 'DOC' . strtoupper(substr(md5($suffix), 0, 8)),
            'email' => $suffix . '@example.com',
            'telefono' => '223' . str_pad((string) $this->seq, 7, '0', STR_PAD_LEFT),
            'domicilio' => 'Calle ' . $suffix,
        ]);

        \PHPUnit\Framework\Assert::assertTrue($locador->save(), implode(' | ', $locador->getErrorSummary(true)));

        return $locador;
    }

    private function crearReserva($locador, $estado)
    {
        $reserva = new \app\models\Reserva([
            'fecha' => '2026-07-25 12:00:00',
            'desde' => '2026-08-10 15:00:00',
            'hasta' => '2026-08-15 10:00:00',
            'id_locador' => $locador->id,
            'pax' => 4,
            'id_estado' => $estado->id,
            'obs' => 'Reserva funcional',
        ]);

        \PHPUnit\Framework\Assert::assertTrue($reserva->save(), implode(' | ', $reserva->getErrorSummary(true)));

        return $reserva;
    }

    private function nextSuffix($prefix)
    {
        $this->seq++;

        return $prefix . '_' . uniqid((string) $this->seq, false);
    }
}
