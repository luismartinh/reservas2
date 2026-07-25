<?php

namespace tests\unit\models;

use app\models\GrupoAcceso;
use app\models\GrupoAccesoSearch;
use app\models\Identificador;
use app\models\UsuarioSearch;

class UsuarioSearchDominioTest extends \Codeception\Test\Unit
{
    private $seq = 0;

    public function testUsuarioSearchIndexRespetaNivelMinimo()
    {
        $visible = $this->crearUsuario(null, 2);
        $oculto = $this->crearUsuario(null, 1);

        $search = new UsuarioSearch();
        $provider = $search->searchIndex([], 2);
        $ids = array_map('intval', $provider->query->select(['usuario.id'])->column());

        $this->assertContains((int) $visible->id, $ids);
        $this->assertNotContains((int) $oculto->id, $ids);
    }

    public function testUsuarioSearchGrupoAsignadosRespetaRelacionYNivel()
    {
        $grupo = $this->crearGrupo(null, 2);
        $asignadoVisible = $this->crearUsuario(null, 2);
        $asignadoOculto = $this->crearUsuario(null, 1);
        $noAsignado = $this->crearUsuario(null, 2);

        $grupo->setUsuario($asignadoVisible->id);
        $grupo->setUsuario($asignadoOculto->id);

        $search = new UsuarioSearch();
        $search->esUsuarioGrupo = 1;
        $provider = $search->searchGrupo([], $grupo->id, 2);
        $ids = array_map('intval', $provider->query->select(['usuario.id'])->column());

        $this->assertContains((int) $asignadoVisible->id, $ids);
        $this->assertNotContains((int) $asignadoOculto->id, $ids);
        $this->assertNotContains((int) $noAsignado->id, $ids);
    }

    public function testUsuarioSearchGrupoNoAsignadosRespetaRelacionYNivel()
    {
        $grupo = $this->crearGrupo(null, 2);
        $asignado = $this->crearUsuario(null, 2);
        $noAsignadoVisible = $this->crearUsuario(null, 2);
        $noAsignadoOculto = $this->crearUsuario(null, 1);

        $grupo->setUsuario($asignado->id);

        $search = new UsuarioSearch();
        $search->esUsuarioGrupo = 0;
        $provider = $search->searchGrupo([], $grupo->id, 2);
        $ids = array_map('intval', $provider->query->select(['usuario.id'])->column());

        $this->assertContains((int) $noAsignadoVisible->id, $ids);
        $this->assertNotContains((int) $asignado->id, $ids);
        $this->assertNotContains((int) $noAsignadoOculto->id, $ids);
    }

    public function testGrupoAccesoSearchIndexRespetaNivelMinimo()
    {
        $visible = $this->crearGrupo(null, 2);
        $oculto = $this->crearGrupo(null, 1);

        $search = new GrupoAccesoSearch();
        $provider = $search->searchIndex([], 2);
        $ids = array_map('intval', $provider->query->select(['grupo_acceso.id'])->column());

        $this->assertContains((int) $visible->id, $ids);
        $this->assertNotContains((int) $oculto->id, $ids);
    }

    public function testGrupoAccesoSearchUsuarioAsignadosRespetaRelacionYNivel()
    {
        $usuario = $this->crearUsuario(null, 2);
        $grupoVisible = $this->crearGrupo(null, 2);
        $grupoOculto = $this->crearGrupo(null, 1);
        $grupoNoAsignado = $this->crearGrupo(null, 2);

        $grupoVisible->setUsuario($usuario->id);
        $grupoOculto->setUsuario($usuario->id);

        $search = new GrupoAccesoSearch();
        $search->esUsuarioGrupo = 1;
        $provider = $search->searchUsuario([], $usuario->id, 2);
        $ids = array_map('intval', $provider->query->select(['grupo_acceso.id'])->column());

        $this->assertContains((int) $grupoVisible->id, $ids);
        $this->assertNotContains((int) $grupoOculto->id, $ids);
        $this->assertNotContains((int) $grupoNoAsignado->id, $ids);
    }

    public function testGrupoAccesoSearchUsuarioNoAsignadosRespetaRelacionYNivel()
    {
        $usuario = $this->crearUsuario(null, 2);
        $grupoAsignado = $this->crearGrupo(null, 2);
        $grupoVisible = $this->crearGrupo(null, 2);
        $grupoOculto = $this->crearGrupo(null, 1);

        $grupoAsignado->setUsuario($usuario->id);

        $search = new GrupoAccesoSearch();
        $search->esUsuarioGrupo = 0;
        $provider = $search->searchUsuario([], $usuario->id, 2);
        $ids = array_map('intval', $provider->query->select(['grupo_acceso.id'])->column());

        $this->assertContains((int) $grupoVisible->id, $ids);
        $this->assertNotContains((int) $grupoAsignado->id, $ids);
        $this->assertNotContains((int) $grupoOculto->id, $ids);
    }

    private function crearUsuario($suffix = null, $nivel = 2)
    {
        $suffix = $suffix ?: $this->nextSuffix('usuario');
        $usuario = new Identificador([
            'login' => 'usr_search_' . $suffix,
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

    private function crearGrupo($suffix = null, $nivel = 2)
    {
        $suffix = $suffix ?: $this->nextSuffix('grupo');
        $grupo = new GrupoAcceso([
            'descr' => 'Grupo Search ' . $suffix,
            'nivel' => $nivel,
        ]);

        $this->assertTrue($grupo->save(), implode(' | ', $grupo->getErrorSummary(true)));

        return $grupo;
    }

    private function nextSuffix($prefix)
    {
        $this->seq++;

        return $prefix . '_' . uniqid((string) $this->seq, false);
    }
}
