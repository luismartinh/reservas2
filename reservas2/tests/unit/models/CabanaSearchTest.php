<?php

namespace tests\unit\models;

use app\models\Cabana;
use app\models\CabanaSearch;

class CabanaSearchTest extends \Codeception\Test\Unit
{
    private $seq = 0;

    public function testSearchIndexFiltraPorDescripcionYActiva()
    {
        $visible = $this->crearCabana('Busqueda lago visible', 31, 1, 4);
        $this->crearCabana('Busqueda bosque oculta', 32, 0, 2);

        $search = new CabanaSearch();
        $provider = $search->searchIndex([
            'CabanaSearch' => [
                'descr' => 'lago',
                'activa' => 1,
            ],
        ]);

        $ids = array_map('intval', $provider->query->select(['cabanas.id'])->column());

        $this->assertSame([(int) $visible->id], $ids);
    }

    public function testSearchIndexFiltraPorNumeroYCapacidad()
    {
        $cabana = $this->crearCabana('Busqueda numero', 33, 1, 6);
        $this->crearCabana('Busqueda otra', 34, 1, 3);

        $search = new CabanaSearch();
        $provider = $search->searchIndex([
            'CabanaSearch' => [
                'numero' => 33,
                'max_pax' => 6,
            ],
        ]);

        $ids = array_map('intval', $provider->query->select(['cabanas.id'])->column());

        $this->assertSame([(int) $cabana->id], $ids);
    }

    private function crearCabana($descr, $numero, $activa, $maxPax)
    {
        $cabana = new Cabana([
            'descr' => $descr . ' ' . $this->nextSuffix('cs'),
            'checkin' => '15:00',
            'checkout' => '10:00',
            'max_pax' => $maxPax,
            'activa' => $activa,
            'numero' => $numero,
            'color_cabana' => '#aaffc3',
        ]);

        $this->assertTrue($cabana->save(), implode(' | ', $cabana->getErrorSummary(true)));

        return $cabana;
    }

    private function nextSuffix($prefix)
    {
        $this->seq++;

        return $prefix . '_' . uniqid((string) $this->seq, false);
    }
}
