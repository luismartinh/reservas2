<?php

namespace tests\unit\models;

use app\models\Tarifa;
use app\models\TarifaSearch;

class TarifaSearchTest extends \Codeception\Test\Unit
{
    private $seq = 0;

    public function testSearchIndexFiltraPorDescripcionYActiva()
    {
        $tarifaVisible = $this->crearTarifa('Promo verano visible', 1, 100);
        $this->crearTarifa('Promo invierno oculta', 0, 200);

        $search = new TarifaSearch();
        $provider = $search->searchIndex([
            'TarifaSearch' => [
                'descr' => 'verano',
                'activa' => 1,
            ],
        ]);

        $ids = array_map('intval', $provider->query->select(['tarifas.id'])->column());

        $this->assertContains((int) $tarifaVisible->id, $ids);
        $this->assertCount(1, $ids);
    }

    public function testSearchIndexFiltraPorValorYMinDias()
    {
        $tarifa = $this->crearTarifa('Escapada 3 noches', 1, 333.5, 3);
        $this->crearTarifa('Escapada 5 noches', 1, 500, 5);

        $search = new TarifaSearch();
        $provider = $search->searchIndex([
            'TarifaSearch' => [
                'valor_dia' => 333.5,
                'min_dias' => 3,
            ],
        ]);

        $ids = array_map('intval', $provider->query->select(['tarifas.id'])->column());

        $this->assertSame([(int) $tarifa->id], $ids);
    }

    private function crearTarifa($descr, $activa, $valorDia, $minDias = 1)
    {
        $tarifa = new Tarifa([
            'descr' => $descr . ' ' . $this->nextSuffix('ts'),
            'inicio' => '2026-12-01',
            'fin' => '2026-12-10',
            'valor_dia' => $valorDia,
            'min_dias' => $minDias,
            'activa' => $activa,
            'fecha' => '2026-07-25 12:00:00',
        ]);

        $this->assertTrue($tarifa->save(), implode(' | ', $tarifa->getErrorSummary(true)));

        return $tarifa;
    }

    private function nextSuffix($prefix)
    {
        $this->seq++;

        return $prefix . '_' . uniqid((string) $this->seq, false);
    }
}
