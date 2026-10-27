<?php
namespace tests\unit\models;

use app\models\Localidad;
use app\models\Locador;
use app\models\LocadorSearch;

class LocadorSearchTest extends \Codeception\Test\Unit
{
    private $clientes = [];
    private $localidad;
    private $otra;

    protected function _before()
    {
        $this->localidad = Localidad::find()->one();
        $this->otra = Localidad::find()->where(['<>', 'id_provincia', $this->localidad->id_provincia])->one();
        foreach (['Rodrigo Perez', 'Ana Rodriguez', 'María Fernandez', 'Luis Gomez', 'Agencia 100%'] as $index => $name) {
            $suffix = bin2hex(random_bytes(5));
            $model = new Locador(['denominacion' => $name,
                'documento' => ($index === 3 ? 'RODRI' : 'DOC') . $suffix,
                'email' => ($index === 2 ? 'rodri' : 'cliente') . $suffix . '@example.com',
                'telefono' => $index === 4 ? '543219876' : '123456',
                'id_localidad' => $index % 2 ? $this->otra->id : $this->localidad->id,
                'domicilio' => $index === 0 ? 'Domicilio seleccionado' : 'Otro domicilio',
                'obs' => 'Observación de prueba', 'calificacion' => $index]);
            $this->assertTrue($model->save(), json_encode($model->errors));
            $this->clientes[] = $model;
        }
    }

    private function ids(array $filters, $method = 'searchIndex')
    {
        $search = new LocadorSearch();
        $query = $search->$method(['LocadorSearch' => $filters])->query;
        $ids = $query->andWhere(['id' => array_column($this->clientes, 'id')])->select('id')->column();
        sort($ids);
        return array_map('intval', $ids);
    }

    private function expected(array $indexes)
    {
        $ids = array_map(function ($index) { return (int) $this->clientes[$index]->id; }, $indexes);
        sort($ids);
        return $ids;
    }

    public function testCoincidenciasParcialesEnNombreApellidoDocumentoYEmail()
    {
        foreach (['search', 'searchIndex'] as $method) {
            $this->assertSame($this->expected([0, 1, 2, 3]), $this->ids(['buscar' => '  rodri  '], $method));
        }
        $this->assertSame($this->expected([4]), $this->ids(['buscar' => '32198']));
        $this->assertSame($this->expected([0, 1, 2, 3, 4]), $this->ids(['buscar' => '']));
    }

    public function testFiltrosGeograficosSeCombinanConTodosLosCamposDelOr()
    {
        $this->assertSame($this->expected([0, 2, 4]), $this->ids(['id_provincia' => $this->localidad->id_provincia]));
        $this->assertSame($this->expected([0, 2]), $this->ids(['buscar' => 'rodri',
            'id_provincia' => $this->localidad->id_provincia]));
        $this->assertSame($this->expected([1, 3]), $this->ids(['buscar' => 'rodri', 'id_localidad' => $this->otra->id]));
        $this->assertSame([], $this->ids(['id_provincia' => $this->localidad->id_provincia, 'id_localidad' => $this->otra->id]));
        $this->assertSame($this->expected([0]), $this->ids(['buscar' => 'rodri', 'domicilio' => 'seleccionado']));
        $this->assertSame($this->expected([2]), $this->ids(['buscar' => 'rodri', 'calificacion' => 2]));
    }

    public function testEscapaComodinesYParametrosInvalidosNoAmplianLaBusqueda()
    {
        $this->assertSame($this->expected([4]), $this->ids(['buscar' => '%']));
        $this->assertSame([], $this->ids(['buscar' => "' OR 1=1 --"]));
        $this->assertSame([], $this->ids(['id_provincia' => 'incorrecta']));
        $this->assertSame([], $this->ids(['buscar' => str_repeat('a', 151)]));
    }
}
