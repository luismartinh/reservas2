<?php
namespace tests\unit\models;

use app\models\Localidad;
use app\models\Locador;
use app\models\LocadorSearch;
use app\models\Provincia;
use app\models\RequestReserva;
use app\models\RequestReservaSearch;

class UbicacionReferenciaTest extends \Codeception\Test\Unit
{
    private function pasajero()
    {
        $suffix = bin2hex(random_bytes(6));
        return new Locador(['denominacion' => 'Prueba ubicación', 'documento' => $suffix,
            'email' => $suffix . '@example.com', 'telefono' => '123456']);
    }

    public function testFormularioSolicitudConservaNombreYValidaUbicacion()
    {
        $localidad = Localidad::find()->one();
        $form = new \app\models\ReservaSolicitudForm(['id_localidad', 'denominacion']);
        $form->addRule(['denominacion'], 'required');
        $this->assertTrue($form->load(['DynamicModel' => ['denominacion' => 'Pasajero',
            'id_localidad' => $localidad->id, 'id_provincia' => $localidad->id_provincia]]));
        $this->assertTrue($form->validate(), json_encode($form->errors));
        $form->id_provincia = Provincia::find()->select('id')->where(['<>', 'id', $localidad->id_provincia])->scalar();
        $this->assertFalse($form->validate());
        $this->assertTrue($form->hasErrors('id_localidad'));
        $form->id_localidad = '';
        $form->id_provincia = '';
        $this->assertTrue($form->validate(), json_encode($form->errors));
    }

    public function testGuardaUbicacionYRecuperaProvinciaParaEditar()
    {
        $localidad = Localidad::find()->one();
        $model = $this->pasajero();
        $this->assertTrue($model->load(['Locador' => ['calificacion' => 4,
            'id_localidad' => $localidad->id, 'id_provincia' => $localidad->id_provincia]]));
        $this->assertTrue($model->save(), json_encode($model->errors));
        $stored = Locador::findOne($model->id);
        $this->assertSame(4, (int) $stored->calificacion);
        $this->assertSame((int) $localidad->id_provincia, (int) $stored->id_provincia);
        $this->assertSame($localidad->descr . ' · ' . $localidad->provincia->descr, $stored->ubicacionDescripcion);
        $stored->telefono = '987654';
        $this->assertTrue($stored->save(), json_encode($stored->errors));
        $this->assertSame((int) $localidad->id, (int) $stored->id_localidad);
        $search = new LocadorSearch();
        $ids = $search->searchIndex(['LocadorSearch' => ['documento' => $model->documento,
            'calificacion' => 4, 'id_localidad' => $localidad->id]])->query->column();
        $this->assertSame([(string) $model->id], array_map('strval', $ids));
    }

    public function testRechazaLocalidadAjenaEInexistenteYPermiteQuitarUbicacion()
    {
        $localidad = Localidad::find()->one();
        $otra = Provincia::find()->where(['<>', 'id', $localidad->id_provincia])->one();
        $model = $this->pasajero();
        $model->id_localidad = $localidad->id;
        $model->id_provincia = $otra->id;
        $this->assertFalse($model->validate());
        $this->assertTrue($model->hasErrors('id_localidad'));
        $model->id_localidad = 99999999;
        $this->assertFalse($model->validate());
        $model->id_localidad = '';
        $this->assertFalse($model->validate());
        $model->id_provincia = '';
        $this->assertTrue($model->save(), json_encode($model->errors));
        $this->assertNull(Locador::findOne($model->id)->id_localidad);
    }

    public function testCalificacionDeUnaACincoEstrellasConOpcionSinCalificar()
    {
        $model = $this->pasajero();
        $this->assertTrue($model->validate(['calificacion']));
        $this->assertSame(0, $model->calificacion);
        foreach ([null, '', 0, 1, 2, 3, 4, 5] as $valid) {
            $model->calificacion = $valid;
            $this->assertTrue($model->validate(['calificacion']), json_encode($model->errors));
        }
        foreach ([-1, 6, 127, 1.5, 'texto'] as $invalid) {
            $model->calificacion = $invalid;
            $this->assertFalse($model->validate(['calificacion']));
        }
    }

    public function testCatalogosValidanUnicidadPorProvincia()
    {
        $localidad = Localidad::find()->one();
        $copy = new Localidad(['id_provincia' => $localidad->id_provincia,
            'descr' => $localidad->descr, 'slug' => $localidad->slug]);
        $this->assertFalse($copy->validate());
        $this->assertTrue($copy->hasErrors('descr'));
        $this->assertTrue($copy->hasErrors('slug'));
        $copy->id_provincia = Provincia::find()->select('id')->where(['<>', 'id', $localidad->id_provincia])->scalar();
        $copy->descr = 'Localidad de prueba repetible';
        $copy->slug = 'localidad-de-prueba-repetible';
        $this->assertTrue($copy->save(), json_encode($copy->errors));
        $second = new Localidad($copy->getAttributes(['descr', 'slug']));
        $second->id_provincia = $localidad->id_provincia;
        $this->assertTrue($second->save(), json_encode($second->errors));
    }

    public function testReferenciaLimiteTrimYNulo()
    {
        $model = new RequestReserva();
        $this->assertTrue($model->load(['RequestReserva' => ['referencia' => '  Agencia 123  ']]));
        $this->assertTrue($model->validate(['referencia']));
        $this->assertSame('Agencia 123', $model->referencia);
        $model->referencia = str_repeat('á', 45);
        $this->assertTrue($model->validate(['referencia']));
        $model->referencia .= 'á';
        $this->assertFalse($model->validate(['referencia']));
        $model->referencia = '  ';
        $this->assertTrue($model->validate(['referencia']));
        $this->assertNull($model->referencia);
    }

    public function testFiltroReferenciaNoSeOmiteAlBuscarEmail()
    {
        // Sin fixture: comprobar el resultado negativo con un email existente.
        $existing = RequestReserva::find()->one();
        $this->assertNotNull($existing);
        foreach (['search', 'searchIndex'] as $method) {
            $search = new RequestReservaSearch();
            $provider = $search->$method(['RequestReservaSearch' => [
                'denominacion' => $existing->email, 'referencia' => 'INEXISTENTE_' . bin2hex(random_bytes(6)),
            ]]);
            $this->assertFalse($provider->query->exists());
        }
    }
}
