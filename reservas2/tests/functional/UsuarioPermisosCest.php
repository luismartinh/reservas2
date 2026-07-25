<?php

use Codeception\Util\HttpCode;

class UsuarioPermisosCest
{
    private $seq = 0;

    public function usuarioSetGrupoAccesosAutorizaYAsigna(\FunctionalTester $I)
    {
        $actor = $this->crearUsuario(2);
        $target = $this->crearUsuario(2);
        $grupo = $this->crearGrupo(2);
        $permiso = $this->crearAcceso('usuario/view');

        $actor->setAcceso($permiso->id);
        $I->amLoggedInAs($actor);

        $I->sendAjaxPostRequest('/index-test.php?r=usuario%2Fsetgrupoaccesos', [
            'id' => $grupo->id,
            'estado' => 1,
            'id_usuario' => $target->id,
        ]);

        $I->seeResponseCodeIs(HttpCode::OK);
        $response = json_decode($I->grabPageSource(), true);

        $I->assertTrue($response['success']);
        $I->assertSame((string) $target->id, (string) $response['id_usuario']);
        $I->assertSame((string) $grupo->id, (string) $response['id_grupo']);
        $I->assertSame(1, (int) $response['count']);
        $I->assertNotNull(\app\models\GrupoAccesoUsuario::find()->where([
            'id_usuario' => $target->id,
            'id_grupo_acceso' => $grupo->id,
        ])->one());
    }

    public function usuarioSetGrupoAccesosDeniegaSinPermiso(\FunctionalTester $I)
    {
        $actor = $this->crearUsuario(2);
        $target = $this->crearUsuario(2);
        $grupo = $this->crearGrupo(2);

        $I->amLoggedInAs($actor);

        $I->sendAjaxPostRequest('/index-test.php?r=usuario%2Fsetgrupoaccesos', [
            'id' => $grupo->id,
            'estado' => 1,
            'id_usuario' => $target->id,
        ]);

        $I->seeResponseCodeIs(HttpCode::OK);
        $response = json_decode($I->grabPageSource(), true);

        $I->assertFalse($response['success']);
        $I->assertNull(\app\models\GrupoAccesoUsuario::find()->where([
            'id_usuario' => $target->id,
            'id_grupo_acceso' => $grupo->id,
        ])->one());
    }

    public function usuarioSetAccesosAutorizaYAsigna(\FunctionalTester $I)
    {
        $actor = $this->crearUsuario(2);
        $target = $this->crearUsuario(2);
        $permisoActor = $this->crearAcceso('usuario/view');
        $permisoTarget = $this->crearAcceso('permiso/target/' . $this->nextSuffix('pta'));

        $actor->setAcceso($permisoActor->id);
        $I->amLoggedInAs($actor);

        $I->sendAjaxPostRequest('/index-test.php?r=usuario%2Fsetaccesos', [
            'id' => $permisoTarget->id,
            'estado' => 1,
            'id_usuario' => $target->id,
        ]);

        $I->seeResponseCodeIs(HttpCode::OK);
        $response = json_decode($I->grabPageSource(), true);

        $I->assertTrue($response['success']);
        $I->assertSame((string) $target->id, (string) $response['id_usuario']);
        $I->assertSame((string) $permisoTarget->id, (string) $response['id_acceso']);
        $I->assertSame(1, (int) $response['count']);
        $I->assertNotNull(\app\models\UsuarioAcceso::find()->where([
            'id_usuario' => $target->id,
            'id_accesos' => $permisoTarget->id,
        ])->one());
    }

    public function grupoAccesoSetUsuarioYSetAccesoAutorizan(\FunctionalTester $I)
    {
        $actor = $this->crearUsuario(2);
        $target = $this->crearUsuario(2);
        $grupo = $this->crearGrupo(2);
        $permisoView = $this->crearAcceso('grupo-acceso/view');
        $permisoGrupo = $this->crearAcceso('permiso/grupo/' . $this->nextSuffix('pg'));

        $actor->setAcceso($permisoView->id);
        $I->amLoggedInAs($actor);

        $I->sendAjaxPostRequest('/index-test.php?r=grupo-acceso%2Fsetusuario', [
            'id' => $target->id,
            'estado' => 1,
            'id_grupo' => $grupo->id,
        ]);

        $I->seeResponseCodeIs(HttpCode::OK);
        $responseUsuario = json_decode($I->grabPageSource(), true);

        $I->assertTrue($responseUsuario['success']);
        $I->assertSame(1, (int) $responseUsuario['count']);
        $I->assertNotNull(\app\models\GrupoAccesoUsuario::find()->where([
            'id_usuario' => $target->id,
            'id_grupo_acceso' => $grupo->id,
        ])->one());

        $I->sendAjaxPostRequest('/index-test.php?r=grupo-acceso%2Fsetacceso', [
            'id' => $permisoGrupo->id,
            'estado' => 1,
            'id_grupo' => $grupo->id,
        ]);

        $I->seeResponseCodeIs(HttpCode::OK);
        $responseAcceso = json_decode($I->grabPageSource(), true);

        $I->assertTrue($responseAcceso['success']);
        $I->assertSame(1, (int) $responseAcceso['count']);
        $I->assertNotNull(\app\models\GrupoAccesoAcceso::find()->where([
            'id_grupo_acceso' => $grupo->id,
            'id_acceso' => $permisoGrupo->id,
        ])->one());
    }

    private function crearUsuario($nivel)
    {
        $suffix = $this->nextSuffix('fusr');
        $usuario = new \app\models\Identificador([
            'login' => 'fusr_' . $suffix,
            'nombre' => 'Funcional ' . $suffix,
            'apellido' => 'Tester',
            'pwd' => 'secret123',
            'email' => $suffix . '@example.com',
            'activo' => 1,
            'nivel' => $nivel,
        ]);
        $usuario->setPassword('secret123');
        $usuario->generateAuthKey();

        \PHPUnit\Framework\Assert::assertTrue($usuario->save(), implode(' | ', $usuario->getErrorSummary(true)));

        return $usuario;
    }

    private function crearGrupo($nivel)
    {
        $suffix = $this->nextSuffix('fgrp');
        $grupo = new \app\models\GrupoAcceso([
            'descr' => 'Grupo Funcional ' . $suffix,
            'nivel' => $nivel,
        ]);

        \PHPUnit\Framework\Assert::assertTrue($grupo->save(), implode(' | ', $grupo->getErrorSummary(true)));

        return $grupo;
    }

    private function crearAcceso($codigo)
    {
        $existente = \app\models\Acceso::find()->where(['acceso' => $codigo])->one();
        if ($existente) {
            return $existente;
        }

        $suffix = $this->nextSuffix('facc');
        $acceso = new \app\models\Acceso([
            'descr' => 'Acceso Funcional ' . $suffix,
            'acceso' => $codigo,
        ]);

        \PHPUnit\Framework\Assert::assertTrue($acceso->save(), implode(' | ', $acceso->getErrorSummary(true)));

        return $acceso;
    }

    private function nextSuffix($prefix)
    {
        $this->seq++;

        return $prefix . '_' . uniqid((string) $this->seq, false);
    }
}
