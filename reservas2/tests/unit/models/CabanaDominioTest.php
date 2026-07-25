<?php

namespace tests\unit\models;

use app\models\Cabana;
use Yii;

class CabanaDominioTest extends \Codeception\Test\Unit
{
    private $seq = 0;

    public function testValidaHorariosYCamposObligatorios()
    {
        $cabana = new Cabana([
            'descr' => 'Cabana horario ' . $this->nextSuffix('hor'),
            'checkin' => '09:00',
            'checkout' => '10:00',
            'max_pax' => 4,
            'activa' => 1,
            'numero' => 11,
            'color_cabana' => '#3cb44b',
        ]);

        $this->assertFalse($cabana->validate());
        $this->assertArrayHasKey('checkin', $cabana->getErrors());
    }

    public function testGuardaColorYCaracteristicasTraducidas()
    {
        $cabana = new Cabana([
            'descr' => 'Cabana config ' . $this->nextSuffix('cfg'),
            'checkin' => '15:00',
            'checkout' => '10:00',
            'max_pax' => 5,
            'activa' => 1,
            'numero' => 12,
            'color_cabana' => '#4363d8',
            'caracteristicas_es' => "Vista al lago\nParrilla",
            'caracteristicas_en' => "Lake view\nGrill",
            'caracteristicas_pt_br' => "Vista para o lago\nChurrasqueira",
        ]);

        $this->assertTrue($cabana->save(), implode(' | ', $cabana->getErrorSummary(true)));

        $cabana->refresh();

        $this->assertSame('#4363d8', $cabana->color_cabana);
        $this->assertIsArray($cabana->config);
        $this->assertSame('#4363d8', $cabana->config['color_cabana']);
        $this->assertIsArray($cabana->caracteristicas);
        $this->assertSame("Vista al lago\nParrilla", $cabana->caracteristicas['es']);
        $this->assertSame("Lake view\nGrill", $cabana->caracteristicas_en);
        $this->assertSame("Vista para o lago\nChurrasqueira", $cabana->caracteristicas_pt_br);
    }

    public function testBuildFeaturesLinesRespetaIdiomaYFallbacks()
    {
        $originalLanguage = Yii::$app->language;

        Yii::$app->language = 'en-US';
        $this->assertSame(
            ['Lake view', 'Grill'],
            Cabana::buildFeaturesLines([
                'es' => "Vista al lago\nParrilla",
                'en' => "Lake view\nGrill",
            ])
        );

        Yii::$app->language = 'pt-BR';
        $this->assertSame(
            ['Solo espanol'],
            Cabana::buildFeaturesLines(['es' => 'Solo espanol'])
        );

        Yii::$app->language = 'es';
        $this->assertSame(
            ['Texto plano', 'Otra linea'],
            Cabana::buildFeaturesLines("Texto plano\nOtra linea")
        );
        $this->assertSame(
            ['Cabana fallback'],
            Cabana::buildFeaturesLines('', 'Cabana fallback')
        );

        Yii::$app->language = $originalLanguage;
    }

    public function testAfterFindSoportaFormatoJsonViejo()
    {
        $cabana = new Cabana([
            'descr' => 'Cabana legacy ' . $this->nextSuffix('legacy'),
            'checkin' => '15:00',
            'checkout' => '10:00',
            'max_pax' => 2,
            'activa' => 1,
            'numero' => 13,
            'color_cabana' => '#ffe119',
        ]);

        $this->assertTrue($cabana->save(), implode(' | ', $cabana->getErrorSummary(true)));

        Yii::$app->db->createCommand()->update('cabanas', [
            'caracteristicas' => json_encode("Linea uno\nLinea dos"),
        ], ['id' => $cabana->id])->execute();

        $cabana = Cabana::findOne($cabana->id);

        $this->assertSame('#ffe119', $cabana->color_cabana);
        $this->assertSame("Linea uno\nLinea dos", $cabana->caracteristicas_es);
        $this->assertSame(['Linea uno', 'Linea dos'], $cabana->getFeaturesLines());
    }

    public function testColoresUsadosYNumerosDisponiblesExcluyenYConservanActual()
    {
        $cabanaA = $this->crearCabana('Disponibilidad A', 21, '#3cb44b');
        $cabanaB = $this->crearCabana('Disponibilidad B', 22, '#4363d8');

        $coloresUsados = Cabana::coloresUsados();
        $this->assertContains('#3cb44b', $coloresUsados);
        $this->assertContains('#4363d8', $coloresUsados);

        $libresNuevo = Cabana::getNumerosDisponibles();
        $this->assertArrayNotHasKey(21, $libresNuevo);
        $this->assertArrayNotHasKey(22, $libresNuevo);

        $libresEdicion = Cabana::getNumerosDisponibles($cabanaA);
        $this->assertArrayHasKey(21, $libresEdicion);
        $this->assertArrayNotHasKey(22, $libresEdicion);
    }

    private function crearCabana($descr, $numero, $color)
    {
        $cabana = new Cabana([
            'descr' => $descr . ' ' . $this->nextSuffix('cab'),
            'checkin' => '15:00',
            'checkout' => '10:00',
            'max_pax' => 4,
            'activa' => 1,
            'numero' => $numero,
            'color_cabana' => $color,
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
