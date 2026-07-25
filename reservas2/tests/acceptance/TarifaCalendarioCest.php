<?php

class TarifaCalendarioCest
{
    private $seq = 0;

    public function calendarioNoDebeMostrarLineasFantasma(AcceptanceTester $I)
    {
        $cabana = $this->crearCabana();
        $tarifaA = $this->crearTarifa('A', '2026-07-03', '2026-07-05', 100, 1);
        $tarifaB = $this->crearTarifa('B', '2026-07-07', '2026-07-09', 120, 2);

        $this->vincularTarifa($cabana, $tarifaA);
        $this->vincularTarifa($cabana, $tarifaB);

        $I->amOnPage('/index-test.php?r=site/login');
        $I->submitForm('#login-form', [
            'LoginForm[username]' => 'admin',
            'LoginForm[password]' => 'admin',
        ]);
        $I->wait(1);

        $I->amOnPage('/index-test.php?r=tarifa/calendario&year=2026&month=7&id_cabana=' . $cabana->id);
        $I->wait(1);
        $I->see($tarifaA->descr);

        $offendingBorders = $I->executeJS(<<<'JS'
return Array.from(document.querySelectorAll('.tarifa-lane-row + .week-days-row > td'))
    .map(function(td, index) {
        var styles = window.getComputedStyle(td);
        return {
            index: index,
            borderTopWidth: styles.borderTopWidth,
            borderTopStyle: styles.borderTopStyle,
            borderTopColor: styles.borderTopColor
        };
    })
    .filter(function(item) {
        return item.borderTopWidth !== '0px' && item.borderTopStyle !== 'none';
    });
JS);

        $I->comment('Bordes detectados: ' . json_encode($offendingBorders));
        $I->makeScreenshot('tarifa-calendario-lineas');

        \PHPUnit\Framework\Assert::assertSame([], $offendingBorders, 'Se detectaron líneas fantasma en la semana siguiente a una fila de tarifas.');
    }

    private function crearCabana()
    {
        $suffix = $this->nextSuffix('cab');
        $numero = $this->buscarNumeroCabanaDisponible();
        $cabana = new \app\models\Cabana([
            'descr' => 'Cabana Acceptance ' . $suffix,
            'checkin' => '15:00',
            'checkout' => '10:00',
            'max_pax' => 4,
            'activa' => 1,
            'numero' => $numero,
            'color_cabana' => '#3cb44b',
        ]);

        \PHPUnit\Framework\Assert::assertTrue($cabana->save(), implode(' | ', $cabana->getErrorSummary(true)));

        return $cabana;
    }

    private function crearTarifa($prefix, $inicio, $fin, $valorDia, $minDias)
    {
        $tarifa = new \app\models\Tarifa([
            'descr' => 'Tarifa Acceptance ' . $prefix . ' ' . $this->nextSuffix('tar'),
            'inicio' => $inicio,
            'fin' => $fin,
            'valor_dia' => $valorDia,
            'min_dias' => $minDias,
            'activa' => 1,
            'fecha' => '2026-07-25 12:00:00',
        ]);

        \PHPUnit\Framework\Assert::assertTrue($tarifa->save(), implode(' | ', $tarifa->getErrorSummary(true)));

        return $tarifa;
    }

    private function vincularTarifa($cabana, $tarifa)
    {
        $vinculo = new \app\models\CabanaTarifa([
            'id_cabana' => $cabana->id,
            'id_tarifa' => $tarifa->id,
        ]);

        \PHPUnit\Framework\Assert::assertTrue($vinculo->save(), implode(' | ', $vinculo->getErrorSummary(true)));

        return $vinculo;
    }

    private function buscarNumeroCabanaDisponible()
    {
        for ($numero = 99; $numero >= 1; $numero--) {
            if (!\app\models\Cabana::find()->where(['numero' => $numero])->exists()) {
                return $numero;
            }
        }

        \PHPUnit\Framework\Assert::fail('No se encontró un número de cabaña disponible para la prueba de acceptance.');
    }

    private function nextSuffix($prefix)
    {
        $this->seq++;

        return $prefix . '_' . uniqid((string) $this->seq, false);
    }
}
