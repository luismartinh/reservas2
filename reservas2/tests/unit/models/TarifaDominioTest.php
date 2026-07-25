<?php

namespace tests\unit\models;

use app\models\Cabana;
use app\models\CabanaTarifa;
use app\models\Tarifa;

class TarifaDominioTest extends \Codeception\Test\Unit
{
    private $seq = 0;

    public function testTarifaNormalizaFechasYCompletaFechaPorDefecto()
    {
        $tarifa = new Tarifa([
            'descr' => 'Tarifa ' . $this->nextSuffix('norm'),
            'inicio' => '2026-08-01',
            'fin' => '2026-08-05',
            'valor_dia' => 100,
            'min_dias' => 1,
            'activa' => 1,
        ]);

        $this->assertTrue($tarifa->validate(), implode(' | ', $tarifa->getErrorSummary(true)));
        $this->assertSame('2026-08-01 00:00:00', $tarifa->inicio);
        $this->assertSame('2026-08-05 23:59:59', $tarifa->fin);
        $this->assertNotEmpty($tarifa->fecha);
    }

    public function testTarifaValidaRangoYValoresMinimos()
    {
        $tarifa = new Tarifa([
            'descr' => 'Tarifa ' . $this->nextSuffix('invalid'),
            'inicio' => '2026-08-06',
            'fin' => '2026-08-05',
            'valor_dia' => 0,
            'min_dias' => 0,
            'activa' => 1,
            'fecha' => '2026-07-25 12:00:00',
        ]);

        $this->assertFalse($tarifa->validate());
        $this->assertArrayHasKey('inicio', $tarifa->errors);
        $this->assertArrayHasKey('valor_dia', $tarifa->errors);
        $this->assertArrayHasKey('min_dias', $tarifa->errors);
    }

    public function testDetectaSolapeYFiltraTarifasDisponibles()
    {
        $cabana = $this->crearCabana();
        $tarifaVinculada = $this->crearTarifa('2026-09-01', '2026-09-10', 120, 1, 1);
        $tarifaSolapada = $this->crearTarifa('2026-09-05', '2026-09-15', 150, 1, 1);
        $tarifaLibre = $this->crearTarifa('2026-09-11', '2026-09-20', 180, 1, 1);

        $this->vincularTarifa($cabana, $tarifaVinculada);

        $solape = CabanaTarifa::isTarifaRangeOverlap($cabana->id, $tarifaSolapada->id);
        $libre = CabanaTarifa::isTarifaRangeOverlap($cabana->id, $tarifaLibre->id);

        $this->assertSame('SI', $solape['status']);
        $this->assertSame('NO', $libre['status']);

        $disponiblesSinControl = array_map(function (Tarifa $tarifa) {
            return (int) $tarifa->id;
        }, CabanaTarifa::getTarifasDisponibles($cabana->id, false));

        $disponiblesConControl = array_map(function (Tarifa $tarifa) {
            return (int) $tarifa->id;
        }, CabanaTarifa::getTarifasDisponibles($cabana->id, true));

        $this->assertContains((int) $tarifaSolapada->id, $disponiblesSinControl);
        $this->assertContains((int) $tarifaLibre->id, $disponiblesSinControl);
        $this->assertNotContains((int) $tarifaVinculada->id, $disponiblesSinControl);

        $this->assertNotContains((int) $tarifaSolapada->id, $disponiblesConControl);
        $this->assertContains((int) $tarifaLibre->id, $disponiblesConControl);
        $this->assertNotContains((int) $tarifaVinculada->id, $disponiblesConControl);
    }

    public function testCalculaTotalParaCabanaConCoberturaContinua()
    {
        $cabana = $this->crearCabana();
        $tarifa1 = $this->crearTarifa('2026-10-01', '2026-10-03', 100, 1, 1);
        $tarifa2 = $this->crearTarifa('2026-10-04', '2026-10-05', 150, 1, 1);

        $this->vincularTarifa($cabana, $tarifa1);
        $this->vincularTarifa($cabana, $tarifa2);

        $total = CabanaTarifa::calcularTotalParaCabana($cabana->id, '2026-10-01', '2026-10-05');

        $this->assertSame(600.0, $total);
    }

    public function testCalculaTotalesMarcaMenosUnoSiHayHuecos()
    {
        $cabana = $this->crearCabana();
        $tarifa1 = $this->crearTarifa('2026-11-01', '2026-11-02', 100, 1, 1);
        $tarifa2 = $this->crearTarifa('2026-11-04', '2026-11-05', 150, 1, 1);

        $this->vincularTarifa($cabana, $tarifa1);
        $this->vincularTarifa($cabana, $tarifa2);

        $totales = CabanaTarifa::calcularTotalesParaCabanas([$cabana->id], '2026-11-01', '2026-11-05');

        $this->assertSame(-1, $totales[$cabana->id]);
    }

    private function crearCabana()
    {
        $suffix = $this->nextSuffix('cabana');
        $cabana = new Cabana([
            'descr' => 'Cabana ' . $suffix,
            'checkin' => '15:00',
            'checkout' => '10:00',
            'max_pax' => 4,
            'activa' => 1,
            'numero' => 10 + $this->seq,
            'color_cabana' => '#e6194B',
        ]);

        $this->assertTrue($cabana->save(), implode(' | ', $cabana->getErrorSummary(true)));

        return $cabana;
    }

    private function crearTarifa($inicio, $fin, $valorDia, $minDias, $activa)
    {
        $tarifa = new Tarifa([
            'descr' => 'Tarifa ' . $this->nextSuffix('tarifa'),
            'inicio' => $inicio,
            'fin' => $fin,
            'valor_dia' => $valorDia,
            'min_dias' => $minDias,
            'activa' => $activa,
            'fecha' => '2026-07-25 12:00:00',
        ]);

        $this->assertTrue($tarifa->save(), implode(' | ', $tarifa->getErrorSummary(true)));

        return $tarifa;
    }

    private function vincularTarifa(Cabana $cabana, Tarifa $tarifa)
    {
        $vinculo = new CabanaTarifa([
            'id_cabana' => $cabana->id,
            'id_tarifa' => $tarifa->id,
        ]);

        $this->assertTrue($vinculo->save(), implode(' | ', $vinculo->getErrorSummary(true)));

        return $vinculo;
    }

    private function nextSuffix($prefix)
    {
        $this->seq++;

        return $prefix . '_' . uniqid((string) $this->seq, false);
    }
}
