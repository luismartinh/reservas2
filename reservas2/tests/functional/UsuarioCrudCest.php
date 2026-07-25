<?php

class UsuarioCrudCest
{
    private $seq = 0;

    public function puedeVerIndicesDeUsuarioYGrupoAcceso(\FunctionalTester $I)
    {
        $actor = $this->crearUsuario(2);
        $this->otorgarPermisos($actor, [
            'usuario/index',
            'grupo-acceso/index',
        ]);

        $I->amLoggedInAs($actor);

        $I->amOnRoute('usuario/index');
        $I->see('Administrar Usuarios');

        $I->amOnRoute('grupo-acceso/index');
        $I->see('Administrar Grupos de Acceso');
    }

    public function puedeCrearUsuarioDesdeElFormulario(\FunctionalTester $I)
    {
        $actor = $this->crearUsuario(2);
        $this->otorgarPermisos($actor, [
            'usuario/create',
            'usuario/view',
        ]);

        $suffix = $this->nextSuffix('nuevo_usuario');
        $login = 'crud_' . $suffix;

        $I->amLoggedInAs($actor);
        $I->amOnRoute('usuario/create');
        $I->see('Crear usuario');
        $I->submitForm('#usuario-form-id', [
            'Identificador[login]' => $login,
            'Identificador[nivel]' => 2,
            'Identificador[nombre]' => 'Nombre ' . $suffix,
            'Identificador[apellido]' => 'Apellido ' . $suffix,
            'Identificador[pwd]' => 'Secret123!',
            'Identificador[activo]' => 1,
            'Identificador[email]' => $suffix . '@example.com',
            'Identificador[codigo]' => 'COD' . strtoupper(substr(md5($suffix), 0, 6)),
        ]);

        $I->see('Ver Usuario');
        $I->see($login);

        $usuario = \app\models\Identificador::find()->where(['login' => $login])->one();
        $I->assertNotNull($usuario);
        $I->assertSame('Nombre ' . $suffix, $usuario->nombre);
    }

    public function puedeModificarUsuarioDesdeElFormulario(\FunctionalTester $I)
    {
        $actor = $this->crearUsuario(2);
        $target = $this->crearUsuario(2);
        $this->otorgarPermisos($actor, [
            'usuario/update',
            'usuario/view',
        ]);

        $nuevoApellido = 'Actualizado ' . $this->nextSuffix('apellido');

        $I->amLoggedInAs($actor);
        $I->amOnRoute('usuario/update', ['id' => $target->id]);
        $I->see('Modificar Usuario');
        $I->submitForm('#usuario-form-id', [
            'Identificador[login]' => $target->login,
            'Identificador[nivel]' => $target->nivel,
            'Identificador[nombre]' => $target->nombre,
            'Identificador[apellido]' => $nuevoApellido,
            'Identificador[pwd]' => 'OtroSecret123!',
            'Identificador[activo]' => $target->activo,
            'Identificador[email]' => $target->email,
            'Identificador[codigo]' => $target->codigo,
        ]);

        $I->see('Ver Usuario');
        $I->see($nuevoApellido);

        $target->refresh();
        $I->assertSame($nuevoApellido, $target->apellido);
    }

    public function puedeCrearYModificarGrupoAccesoDesdeElFormulario(\FunctionalTester $I)
    {
        $actor = $this->crearUsuario(2);
        $this->otorgarPermisos($actor, [
            'grupo-acceso/create',
            'grupo-acceso/update',
            'grupo-acceso/view',
        ]);

        $suffix = $this->nextSuffix('grupo_crud');
        $descr = 'Grupo ' . $suffix;
        $descrActualizada = $descr . ' actualizado';

        $I->amLoggedInAs($actor);
        $I->amOnRoute('grupo-acceso/create');
        $I->see('Grupo Acceso');
        $I->submitForm('#grupos-form-id', [
            'GrupoAcceso[descr]' => $descr,
            'GrupoAcceso[nivel]' => 2,
        ]);

        $I->see('Ver Grupo de acceso');
        $I->see($descr);

        $grupo = \app\models\GrupoAcceso::find()->where(['descr' => $descr])->one();
        $I->assertNotNull($grupo);

        $I->amOnRoute('grupo-acceso/update', ['id' => $grupo->id]);
        $I->see('Modificar Grupo de acceso');
        $I->submitForm('#grupos-form-id', [
            'GrupoAcceso[descr]' => $descrActualizada,
            'GrupoAcceso[nivel]' => 2,
        ]);

        $I->see('Ver Grupo de acceso');
        $I->see($descrActualizada);

        $grupo->refresh();
        $I->assertSame($descrActualizada, $grupo->descr);
    }

    public function miUpdatePermiteModificarElPropioPerfil(\FunctionalTester $I)
    {
        $actor = $this->crearUsuario(2);
        $this->otorgarPermisos($actor, [
            'usuario/mi-update',
        ]);

        $nuevoApellido = 'Perfil ' . $this->nextSuffix('miupdate');

        $I->amLoggedInAs($actor);
        $I->amOnRoute('usuario/mi-update');
        $I->see('Modificar Usuario');
        $I->submitForm('#usuario-form-id', [
            'Identificador[login]' => $actor->login,
            'Identificador[nombre]' => $actor->nombre,
            'Identificador[apellido]' => $nuevoApellido,
            'Identificador[pwd]' => 'Perfil123!',
            'Identificador[email]' => $actor->email,
            'Identificador[codigo]' => $actor->codigo,
        ]);
        $I->dontSee('Modificar Usuario');

        $actor->refresh();
        $I->assertSame($nuevoApellido, $actor->apellido);
    }

    public function deleteUsuarioEliminaRegistroConPermiso(\FunctionalTester $I)
    {
        $actor = $this->crearUsuario(2);
        $target = $this->crearUsuario(2);
        $this->otorgarPermisos($actor, [
            'usuario/delete',
            'usuario/index',
        ]);

        $I->amLoggedInAs($actor);
        $I->amOnRoute('usuario/delete', ['id' => $target->id]);
        $I->see('Administrar Usuarios');

        $eliminado = \app\models\Identificador::findOne($target->id);
        $I->assertNull($eliminado);
    }

    public function indicesRespetanJerarquiaPorNivel(\FunctionalTester $I)
    {
        $actor = $this->crearUsuario(2);
        $usuarioVisible = $this->crearUsuario(2);
        $usuarioOculto = $this->crearUsuario(1);
        $grupoVisible = $this->crearGrupoConDescr('Jerarquia Visible ' . $this->nextSuffix('gv'), 2);
        $grupoOculto = $this->crearGrupoConDescr('Jerarquia Oculto ' . $this->nextSuffix('go'), 1);

        $this->otorgarPermisos($actor, [
            'usuario/index',
            'grupo-acceso/index',
        ]);

        $I->amLoggedInAs($actor);

        $I->amOnRoute('usuario/index');
        $I->see($usuarioVisible->login);
        $I->dontSee($usuarioOculto->login);

        $I->amOnRoute('grupo-acceso/index');
        $I->see($grupoVisible->descr);
        $I->dontSee($grupoOculto->descr);
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

        $suffix = $this->nextSuffix('crud_acceso');
        $acceso = new \app\models\Acceso([
            'descr' => 'Acceso CRUD ' . $suffix,
            'acceso' => $codigo,
        ]);

        \PHPUnit\Framework\Assert::assertTrue($acceso->save(), implode(' | ', $acceso->getErrorSummary(true)));

        return $acceso;
    }

    private function crearUsuario($nivel)
    {
        $suffix = $this->nextSuffix('crud_user');
        $usuario = new \app\models\Identificador([
            'login' => 'crud_user_' . $suffix,
            'nombre' => 'Usuario ' . $suffix,
            'apellido' => 'Apellido ' . $suffix,
            'pwd' => 'secret123',
            'email' => $suffix . '@example.com',
            'activo' => 1,
            'nivel' => $nivel,
            'codigo' => 'USR' . strtoupper(substr(md5($suffix), 0, 6)),
        ]);
        $usuario->setPassword('secret123');
        $usuario->generateAuthKey();

        \PHPUnit\Framework\Assert::assertTrue($usuario->save(), implode(' | ', $usuario->getErrorSummary(true)));

        return $usuario;
    }

    private function crearGrupoConDescr($descr, $nivel)
    {
        $grupo = new \app\models\GrupoAcceso([
            'descr' => $descr,
            'nivel' => $nivel,
        ]);

        \PHPUnit\Framework\Assert::assertTrue($grupo->save(), implode(' | ', $grupo->getErrorSummary(true)));

        return $grupo;
    }

    private function nextSuffix($prefix)
    {
        $this->seq++;

        return $prefix . '_' . uniqid((string) $this->seq, false);
    }
}
