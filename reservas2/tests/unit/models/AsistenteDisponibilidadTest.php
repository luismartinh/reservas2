<?php
namespace tests\unit\models;

use app\components\asistente\FuncionesReservas;
use app\models\Cabana;
use app\models\Estado;
use app\models\Locador;
use app\models\Reserva;
use app\models\ReservaCabana;

class AsistenteDisponibilidadTest extends \Codeception\Test\Unit
{
    public function testMinimoUnDiaYPaxEstrictoSinAfectarListados()
    {
        $functions = new FuncionesReservas();
        $admin = (object) ['nivel' => 1, 'activo' => 1];
        foreach ([['desde' => '2026-11-10', 'hasta' => '2026-11-10'],
            ['desde' => '2026-11-10', 'hasta' => '2026-11-11', 'pax' => 0],
            ['desde' => '2026-11-10', 'hasta' => '2026-11-11', 'pax' => '2'],
            ['desde' => '2026-11-10', 'hasta' => '2026-11-11', 'pax' => 2.5]] as $args) {
            try {
                $functions->consultarDisponibilidad($admin, $args);
                $this->fail('Debió rechazar estadía o pax inválidos.');
            } catch (\InvalidArgumentException $e) {
                $this->assertNotEmpty($e->getMessage());
            }
        }
        $range = ['desde' => '2026-11-10', 'hasta' => '2026-11-10'];
        $this->assertSame($range, $functions->validar($range));
    }

    public function testSinDisponibilidadEsUnResultadoExitoso()
    {
        // Yii2 test module rolls this fixture change back with the transaction.
        Cabana::updateAll(['activa' => 0]);
        $data = (new FuncionesReservas())->consultarDisponibilidad((object) ['nivel' => 0, 'activo' => 1],
            ['desde' => '2026-11-10', 'hasta' => '2026-11-15']);
        $this->assertTrue($data['ok']);
        $this->assertSame([], $data['cabanas']);
        $this->assertStringContainsString('No hay cabañas disponibles', $data['text']);
    }

    public function testHorariosDelAsistenteSinModificarConsultaExistenteNiFiltrarEstados()
    {
        $suffix = bin2hex(random_bytes(5));
        $used = Cabana::find()->select('numero')->column();
        $available = array_values(array_diff(range(1, 99), $used));
        $this->assertNotEmpty($available);
        $cabana = new Cabana(['descr' => 'Extremos ' . $suffix, 'numero' => $available[0],
            'checkin' => '15:00', 'checkout' => '10:00', 'max_pax' => 4, 'activa' => 1, 'color_cabana' => '#3cb44b']);
        $this->save($cabana);
        $locador = new Locador(['denominacion' => 'Cliente NO enviar ' . $suffix, 'documento' => 'DOC' . $suffix,
            'email' => $suffix . '@example.com', 'telefono' => '123456', 'domicilio' => 'Privado']);
        $this->save($locador);
        // A non-confirmed state must STILL occupy if a Reserva record exists.
        $estado = Estado::find()->where(['slug' => 'rechazado'])->one();
        if (!$estado) {
            $estado = new Estado(['slug' => 'rechazado', 'descr' => 'Rechazado']);
            $this->save($estado);
        }
        $reserva = new Reserva(['fecha' => '2026-10-23 10:00:00', 'desde' => '2026-11-10 15:00:00',
            'hasta' => '2026-11-15 10:00:00', 'id_locador' => $locador->id, 'id_estado' => $estado->id, 'pax' => 1]);
        $this->save($reserva);
        $this->save(new ReservaCabana(['id_reserva' => $reserva->id, 'id_cabana' => $cabana->id, 'valor' => 100]));
        $functions = new FuncionesReservas();
        $admin = (object) ['nivel' => 1, 'activo' => 1];
        $ranges = [['2026-11-09', '2026-11-10', true], // requested checkout before next arrival
            ['2026-11-15', '2026-11-16', true], // requested checkin after previous checkout
            ['2026-11-12', '2026-11-13', false],
            ['2026-11-16', '2026-11-20', true]];
        foreach ($ranges as $range) {
            $data = $functions->consultarDisponibilidad($admin, ['desde' => $range[0], 'hasta' => $range[1]]);
            $ids = array_map('intval', array_column($data['cabanas'], 'id'));
            $this->assertSame($range[2], in_array((int) $cabana->id, $ids, true));
            $this->assertStringContainsString('horarios según cada cabaña', $data['text']);
            $this->assertStringNotContainsString($locador->denominacion, json_encode($data));
        }
        $this->assertNull(Reserva::cabanasEstaLibre($cabana->id, '2026-11-09', '2026-11-10')->one());
        $cabana->max_pax = 7;
        $this->save($cabana);
        foreach ([2 => true, 7 => true, 8 => false] as $pax => $expected) {
            $data = $functions->consultarDisponibilidad($admin, ['desde' => '2026-11-16', 'hasta' => '2026-11-20', 'pax' => $pax]);
            $this->assertSame($expected, in_array((int) $cabana->id, array_map('intval', array_column($data['cabanas'], 'id')), true));
        }
        $reserva->hasta = '2026-11-15 23:59:59';
        $this->save($reserva);
        $cabana->descr = 'Cabaña ' . $cabana->numero;
        $this->save($cabana);
        $data = $functions->consultarDisponibilidad($admin, ['desde' => '2026-11-15', 'hasta' => '2026-11-16']);
        $this->assertContains((int) $cabana->id, array_map('intval', array_column($data['cabanas'], 'id')));
        $this->assertStringContainsString('Cabaña ' . $cabana->numero . ' · Capacidad máxima:', $data['text']);
        $this->assertStringNotContainsString('Cabaña ' . $cabana->numero . ': Cabaña', $data['text']);
        $this->assertStringContainsString('Ingreso: 15/11/2026 15:00:00 · Egreso: 16/11/2026 10:00:00', $data['text']);
        $this->assertSame('2026-11-15', $data['desde']);
        $reserva->hasta = '2026-11-15 00:00:00';
        $this->save($reserva);
        $this->assertNotNull(Reserva::cabanasEstaLibre($cabana->id, '2026-11-15', '2026-11-16')->one());
        $reserva->desde = '2026-11-10 23:59:59';
        $this->save($reserva);
        $this->assertNotNull(Reserva::cabanasEstaLibre($cabana->id, '2026-11-09', '2026-11-10')->one());
        $cabana->activa = 0;
        $this->save($cabana);
        $data = $functions->consultarDisponibilidad($admin, ['desde' => '2026-12-01', 'hasta' => '2026-12-02']);
        $this->assertNotContains((int) $cabana->id, array_map('intval', array_column($data['cabanas'], 'id')));
    }

    private function save($model)
    {
        $this->assertTrue($model->save(), implode(' | ', $model->getErrorSummary(true)));
    }
}
