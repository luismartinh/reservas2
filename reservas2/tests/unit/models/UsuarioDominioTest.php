<?php

namespace tests\unit\models;

use app\config\RootMenu;
use app\models\Acceso;
use app\models\GrupoAcceso;
use app\models\GrupoAccesoAcceso;
use app\models\GrupoAccesoUsuario;
use app\models\Identificador;
use app\models\Menu;
use app\models\UsuarioAcceso;

class UsuarioDominioTest extends \Codeception\Test\Unit
{
    private $seq = 0;

    public function testUsuarioPuedeAsignarYQuitarGrupoAcceso()
    {
        $usuario = $this->crearUsuario();
        $grupo = $this->crearGrupo();

        verify($usuario->setGrupoAcceso($grupo->id))->true();
        verify(
            (int) GrupoAccesoUsuario::find()
                ->where(['id_usuario' => $usuario->id, 'id_grupo_acceso' => $grupo->id])
                ->count()
        )->equals(1);

        verify($usuario->setGrupoAcceso($grupo->id))->true();
        verify(
            (int) GrupoAccesoUsuario::find()
                ->where(['id_usuario' => $usuario->id, 'id_grupo_acceso' => $grupo->id])
                ->count()
        )->equals(1);

        verify($usuario->setGrupoAcceso($grupo->id, false))->true();
        verify(
            GrupoAccesoUsuario::find()
                ->where(['id_usuario' => $usuario->id, 'id_grupo_acceso' => $grupo->id])
                ->exists()
        )->false();
    }

    public function testUsuarioPuedeAsignarYQuitarAccesoDirecto()
    {
        $usuario = $this->crearUsuario();
        $acceso = $this->crearAcceso();

        verify($usuario->setAcceso($acceso->id))->true();
        verify(
            (int) UsuarioAcceso::find()
                ->where(['id_usuario' => $usuario->id, 'id_accesos' => $acceso->id])
                ->count()
        )->equals(1);

        verify($usuario->setAcceso($acceso->id))->true();
        verify(
            (int) UsuarioAcceso::find()
                ->where(['id_usuario' => $usuario->id, 'id_accesos' => $acceso->id])
                ->count()
        )->equals(1);

        verify($usuario->setAcceso($acceso->id, false))->true();
        verify(
            UsuarioAcceso::find()
                ->where(['id_usuario' => $usuario->id, 'id_accesos' => $acceso->id])
                ->exists()
        )->false();
    }

    public function testAsignacionesFallanSiNoExisteEntidadRelacionada()
    {
        $usuario = $this->crearUsuario();
        $grupo = $this->crearGrupo();

        verify($usuario->setGrupoAcceso(-999999))->false();
        verify($usuario->setAcceso(-999999))->false();
        verify($grupo->setUsuario(-999999))->false();
        verify($grupo->setAcceso(-999999))->false();
    }

    public function testGrupoPuedeAsignarUsuarioYAccesoYTienePermiso()
    {
        $usuario = $this->crearUsuario();
        $grupo = $this->crearGrupo();
        $acceso = $this->crearAcceso();

        verify($grupo->setUsuario($usuario->id))->true();
        verify($grupo->setAcceso($acceso->id))->true();
        verify($grupo->tienePermiso($acceso))->true();

        verify(
            GrupoAccesoUsuario::find()
                ->where(['id_usuario' => $usuario->id, 'id_grupo_acceso' => $grupo->id])
                ->exists()
        )->true();

        verify(
            GrupoAccesoAcceso::find()
                ->where(['id_grupo_acceso' => $grupo->id, 'id_acceso' => $acceso->id])
                ->exists()
        )->true();
    }

    public function testAccesosDisponiblesPriorizaAccesosDelGrupo()
    {
        $usuario = $this->crearUsuario();
        $grupo = $this->crearGrupo(null, 1);
        $accesoGrupo = $this->crearAcceso('grupo');
        $accesoDirecto = $this->crearAcceso('directo');

        verify($grupo->setAcceso($accesoGrupo->id))->true();
        verify($usuario->setGrupoAcceso($grupo->id))->true();
        verify($usuario->setAcceso($accesoDirecto->id))->true();

        $accesos = $usuario->accesosDisponibles()->orderBy(['acceso' => SORT_ASC])->all();
        $codigos = array_map(static function (Acceso $acceso) {
            return $acceso->acceso;
        }, $accesos);

        $this->assertContains($accesoGrupo->acceso, $codigos);
        $this->assertNotContains($accesoDirecto->acceso, $codigos);
    }

    public function testAutorizarPorAccesoDirecto()
    {
        $usuario = $this->crearUsuario();
        $acceso = $this->crearAcceso('directo');

        verify($usuario->setAcceso($acceso->id))->true();

        $usuario->refresh();
        $resultado = Identificador::autorizar($usuario, $acceso->acceso, 'Permiso directo de prueba', null);

        verify($resultado['auth'])->true();
        verify($resultado['msg'])->equals('Acceso OK1');
    }

    public function testAutorizarPorGrupo()
    {
        $usuario = $this->crearUsuario();
        $grupo = $this->crearGrupo();
        $acceso = $this->crearAcceso('grupo');

        verify($grupo->setUsuario($usuario->id))->true();
        verify($grupo->setAcceso($acceso->id))->true();

        $usuario->refresh();
        $resultado = Identificador::autorizar($usuario, $acceso->acceso, 'Permiso heredado de grupo', null);

        verify($resultado['auth'])->true();
        verify($resultado['msg'])->equals('Acceso OK2');
    }

    public function testAutorizarCreaAccesoYMenuAunqueDeniegue()
    {
        $usuario = $this->crearUsuario();
        $suffix = $this->nextSuffix('auto');
        $permiso = 'permiso/' . $suffix;
        $menuPath = 'Seguridad/' . $suffix;

        $menu = new Menu([
            'descr' => 'Menu ' . $suffix,
            'label' => 'Label ' . $suffix,
            'menu' => (string) RootMenu::CONFIG,
            'menu_path' => $menuPath,
            'url' => 'site/' . $suffix,
        ]);

        $controllerAnterior = \Yii::$app->controller;
        \Yii::$app->controller = new \yii\web\Controller('site', \Yii::$app);

        $resultado = Identificador::autorizar($usuario, $permiso, 'Permiso autogenerado', $menu);

        \Yii::$app->controller = $controllerAnterior;

        verify($resultado['auth'])->false();
        verify($resultado['msg'])->equals('Acceso DENEGADO');

        $accesoGuardado = Acceso::find()->where(['acceso' => $permiso])->one();
        $menuGuardado = Menu::find()->where([
            'menu' => (string) RootMenu::CONFIG,
            'menu_path' => $menuPath,
        ])->one();

        verify($accesoGuardado)->notEmpty();
        verify($menuGuardado)->notEmpty();
        verify((int) $accesoGuardado->id_menu)->equals((int) $menuGuardado->id);
    }

    private function crearUsuario(?string $suffix = null, int $nivel = 2): Identificador
    {
        $suffix = $suffix ?? $this->nextSuffix('usuario');
        $usuario = new Identificador([
            'login' => 'usr_' . $suffix,
            'nombre' => 'Nombre ' . $suffix,
            'apellido' => 'Apellido ' . $suffix,
            'pwd' => 'secret123',
            'email' => $suffix . '@example.com',
            'activo' => 1,
            'nivel' => $nivel,
        ]);
        $usuario->setPassword('secret123');
        $usuario->generateAuthKey();

        $this->assertTrue($usuario->save(), implode(' | ', $usuario->getErrorSummary(true)));

        return $usuario;
    }

    private function crearGrupo(?string $suffix = null, int $nivel = 2): GrupoAcceso
    {
        $suffix = $suffix ?? $this->nextSuffix('grupo');
        $grupo = new GrupoAcceso([
            'descr' => 'Grupo ' . $suffix,
            'nivel' => $nivel,
        ]);

        $this->assertTrue($grupo->save(), implode(' | ', $grupo->getErrorSummary(true)));

        return $grupo;
    }

    private function crearAcceso(string $prefix = 'acceso'): Acceso
    {
        $suffix = $this->nextSuffix($prefix);
        $acceso = new Acceso([
            'descr' => 'Acceso ' . $suffix,
            'acceso' => 'permiso/' . $suffix,
        ]);

        $this->assertTrue($acceso->save(), implode(' | ', $acceso->getErrorSummary(true)));

        return $acceso;
    }

    private function nextSuffix(string $prefix): string
    {
        $this->seq++;

        return $prefix . '_' . uniqid((string) $this->seq, false);
    }
}
