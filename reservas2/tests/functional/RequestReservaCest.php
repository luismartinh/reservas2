<?php

use Codeception\Util\HttpCode;

class RequestReservaCest
{
    private $seq = 0;

    public function guardarReferenciaValidaYPreservaCamposNoEnviados(\FunctionalTester $I)
    {
        $actor = $this->crearUsuario(2);
        $this->otorgarPermisos($actor, ['request-reserva/update']);
        $request = $this->crearRequestReserva($this->crearEstado('confirmado', 'Confirmado'));
        $I->amLoggedInAs($actor);
        $url = '/index-test.php?r=request-reserva%2Fguardar-obs&id=' . $request->id;
        $I->sendAjaxPostRequest($url, ['obs' => 'Observación', 'referencia' => '  Agencia <b>uno</b>  ', 'total' => 1]);
        $I->seeResponseCodeIs(200);
        $request->refresh();
        $I->assertSame('Agencia <b>uno</b>', $request->referencia);
        $I->assertEquals(2200, $request->total);
        $I->sendAjaxPostRequest($url, ['obs' => 'Nueva observación']);
        $request->refresh();
        $I->assertSame('Agencia <b>uno</b>', $request->referencia);
        $I->sendAjaxPostRequest($url, ['obs' => 'No guardar', 'referencia' => str_repeat('a', 46)]);
        $I->assertFalse(json_decode($I->grabPageSource(), true)['success']);
        $request->refresh();
        $I->assertSame('Nueva observación', $request->obs);
        $I->assertSame('Agencia <b>uno</b>', $request->referencia);
        $I->sendAjaxPostRequest($url, ['obs' => '', 'referencia' => '']);
        $request->refresh();
        $I->assertNull($request->referencia);
    }

    public function referenciaExigePermisoPostYCsrf(\FunctionalTester $I)
    {
        $request = $this->crearRequestReserva($this->crearEstado('confirmado', 'Confirmado'));
        $I->amLoggedInAs($this->crearUsuario(2));
        $url = '/index-test.php?r=request-reserva%2Fguardar-obs&id=' . $request->id;
        $I->sendAjaxPostRequest($url, ['obs' => '', 'referencia' => 'Sin permiso']);
        $I->assertFalse(json_decode($I->grabPageSource(), true)['success']);
        $request->refresh();
        $I->assertNull($request->referencia);
        $actor = $this->crearUsuario(2);
        $this->otorgarPermisos($actor, ['request-reserva/update']);
        $I->amLoggedInAs($actor);
        $I->amOnPage($url);
        $I->seeResponseCodeIs(405);
        $handler = function () { Yii::$app->request->enableCsrfValidation = true; };
        Yii::$app->on(\yii\base\Application::EVENT_BEFORE_REQUEST, $handler);
        try {
            $I->sendAjaxPostRequest($url, ['obs' => '', 'referencia' => 'Sin CSRF']);
            $I->seeResponseCodeIs(400);
        } finally {
            Yii::$app->off(\yii\base\Application::EVENT_BEFORE_REQUEST, $handler);
            Yii::$app->request->enableCsrfValidation = false;
        }
        $request->refresh();
        $I->assertNull($request->referencia);
    }

    public function guardarObsActualizaLaSolicitud(\FunctionalTester $I)
    {
        $actor = $this->crearUsuario(2);
        $this->otorgarPermisos($actor, ['request-reserva/update']);
        $estado = $this->crearEstado('confirmado', 'Confirmado');
        $request = $this->crearRequestReserva($estado);

        $I->amLoggedInAs($actor);
        $I->sendAjaxPostRequest('/index-test.php?r=request-reserva%2Fguardar-obs&id=' . $request->id, [
            'obs' => 'Observacion funcional',
        ]);
        $I->seeResponseCodeIs(HttpCode::OK);

        $request->refresh();
        $I->assertSame('Observacion funcional', $request->obs);
    }

    public function chatPermiteCrearYEliminarMensajes(\FunctionalTester $I)
    {
        $actor = $this->crearUsuario(2);
        $this->otorgarPermisos($actor, ['request-reserva/update']);
        $estado = $this->crearEstado('pendiente-email-contestado', 'Pendiente contestado');
        $request = $this->crearRequestReserva($estado);

        $I->amLoggedInAs($actor);

        $I->sendAjaxPostRequest('/index-test.php?r=request-reserva%2Fchat&id=' . $request->id, [
            'DynamicModel' => [
                'response' => 'Respuesta administrativa',
            ],
        ]);
        $I->seeResponseCodeIs(HttpCode::OK);

        $mensaje = \app\models\RequestResponse::find()
            ->where([
                'id_request' => $request->id,
                'response' => 'Respuesta administrativa',
            ])->one();

        $I->assertNotNull($mensaje);
        $I->assertSame(1, (int) $mensaje->is_response);

        $I->sendAjaxPostRequest('/index-test.php?r=request-reserva%2Feliminar-mensaje-chat&id=' . $mensaje->id);
        $I->seeResponseCodeIs(HttpCode::OK);

        $mensajeEliminado = \app\models\RequestResponse::findOne($mensaje->id);
        $I->assertNull($mensajeEliminado);
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
            'descr' => 'Acceso Request ' . $this->nextSuffix('acc'),
            'acceso' => $codigo,
        ]);

        \PHPUnit\Framework\Assert::assertTrue($acceso->save(), implode(' | ', $acceso->getErrorSummary(true)));

        return $acceso;
    }

    private function crearUsuario($nivel)
    {
        $suffix = $this->nextSuffix('rr_usr');
        $usuario = new \app\models\Identificador([
            'login' => 'rr_usr_' . $suffix,
            'nombre' => 'Request ' . $suffix,
            'apellido' => 'Tester',
            'pwd' => 'secret123',
            'email' => $suffix . '@example.com',
            'activo' => 1,
            'nivel' => $nivel,
            'codigo' => 'RQR' . strtoupper(substr(md5($suffix), 0, 6)),
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

    private function crearRequestReserva($estado)
    {
        $suffix = $this->nextSuffix('req');
        $request = new \app\models\RequestReserva([
            'fecha' => '2026-07-25 11:00:00',
            'desde' => '2026-08-10 00:00:00',
            'hasta' => '2026-08-15 00:00:00',
            'denominacion' => 'Cliente ' . $suffix,
            'email' => $suffix . '@example.com',
            'pax' => 3,
            'hash' => substr(hash('sha1', $suffix), 0, 40),
            'total' => 2200,
            'id_estado' => $estado->id,
            'codigo_reserva' => strtoupper(substr(md5($suffix), 0, 7)),
            'pagado' => 0,
        ]);

        \PHPUnit\Framework\Assert::assertTrue($request->save(), implode(' | ', $request->getErrorSummary(true)));

        return $request;
    }

    private function nextSuffix($prefix)
    {
        $this->seq++;

        return $prefix . '_' . uniqid((string) $this->seq, false);
    }
}
