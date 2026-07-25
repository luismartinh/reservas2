<?php

namespace tests\unit\models;

use app\models\Cabana;
use app\models\Estado;
use app\models\Locador;
use app\models\RequestCabana;
use app\models\RequestReserva;
use app\models\Reserva;
use app\models\ReservaCabana;

class ReservaDominioTest extends \Codeception\Test\Unit
{
    private $seq = 0;

    public function testValidaRangoPaxYLongitudObs()
    {
        $estado = $this->crearEstado('confirmado', 'Confirmado');
        $locador = $this->crearLocador();

        $reserva = new Reserva([
            'fecha' => '2026-07-25 10:00:00',
            'desde' => '2026-08-20',
            'hasta' => '2026-08-10',
            'id_locador' => $locador->id,
            'pax' => 0,
            'id_estado' => $estado->id,
            'obs' => str_repeat('x', 501),
        ]);

        $this->assertFalse($reserva->validate());
        $this->assertArrayHasKey('desde', $reserva->getErrors());
        $this->assertArrayHasKey('pax', $reserva->getErrors());
        $this->assertArrayHasKey('obs', $reserva->getErrors());
    }

    public function testCabanasLibresYCabanaEstaLibreConsideranSolapes()
    {
        $estado = $this->crearEstado('confirmado', 'Confirmado');
        $locador = $this->crearLocador();
        $cabanaOcupada = $this->crearCabana('Ocupada', 61, '#3cb44b');
        $cabanaLibre = $this->crearCabana('Libre', 62, '#4363d8');

        $reserva = $this->crearReserva($locador, $estado, '2026-08-10 15:00:00', '2026-08-15 10:00:00', 4);
        $this->vincularCabanaReserva($reserva, $cabanaOcupada, 1000);

        $libres = Reserva::cabanasLibres('2026-08-12', '2026-08-13')->all();
        $idsLibres = array_map(function ($cabana) {
            return (int) $cabana->id;
        }, $libres);

        $this->assertNotContains((int) $cabanaOcupada->id, $idsLibres);
        $this->assertContains((int) $cabanaLibre->id, $idsLibres);

        $this->assertNull(Reserva::cabanasEstaLibre($cabanaOcupada->id, '2026-08-12', '2026-08-13')->one());
        $this->assertNotNull(Reserva::cabanasEstaLibre($cabanaLibre->id, '2026-08-12', '2026-08-13')->one());
    }

    public function testDetectaSolapesEnRequestsYListasDeCabanas()
    {
        $estadoReserva = $this->crearEstado('confirmado', 'Confirmado');
        $estadoRequest = $this->crearEstado('pendiente-email-contestado', 'Pendiente');
        $locador = $this->crearLocador();
        $cabana = $this->crearCabana('Solape', 63, '#ffe119');

        $reservaExistente = $this->crearReserva(
            $locador,
            $estadoReserva,
            '2026-08-10 15:00:00',
            '2026-08-15 10:00:00',
            4
        );
        $this->vincularCabanaReserva($reservaExistente, $cabana, 1200);

        $request = $this->crearRequestReserva(
            $estadoRequest,
            '2026-08-12 00:00:00',
            '2026-08-14 00:00:00'
        );
        $this->vincularCabanaRequest($request, $cabana, 900);

        $this->assertTrue(Reserva::estanReservadas($request));
        $this->assertTrue(Reserva::estanYaReservadas('2026-08-12 00:00:00', '2026-08-14 00:00:00', [$cabana->id]));
        $this->assertFalse(Reserva::estanYaReservadasExcluyendo(
            '2026-08-10 15:00:00',
            '2026-08-15 10:00:00',
            [$cabana->id],
            $reservaExistente->id
        ));
    }

    private function crearEstado($slug, $descr)
    {
        $estado = Estado::find()->where(['slug' => $slug])->one();
        if ($estado) {
            return $estado;
        }

        $estado = new Estado([
            'slug' => $slug,
            'descr' => $descr,
        ]);

        $this->assertTrue($estado->save(), implode(' | ', $estado->getErrorSummary(true)));

        return $estado;
    }

    private function crearLocador()
    {
        $suffix = $this->nextSuffix('loc');
        $locador = new Locador([
            'denominacion' => 'Locador ' . $suffix,
            'documento' => 'DOC' . strtoupper(substr(md5($suffix), 0, 8)),
            'email' => $suffix . '@example.com',
            'telefono' => '2944' . str_pad((string) $this->seq, 6, '0', STR_PAD_LEFT),
            'domicilio' => 'Calle ' . $suffix,
        ]);

        $this->assertTrue($locador->save(), implode(' | ', $locador->getErrorSummary(true)));

        return $locador;
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

    private function crearReserva($locador, $estado, $desde, $hasta, $pax)
    {
        $reserva = new Reserva([
            'fecha' => '2026-07-25 12:00:00',
            'desde' => $desde,
            'hasta' => $hasta,
            'id_locador' => $locador->id,
            'pax' => $pax,
            'id_estado' => $estado->id,
            'obs' => 'Reserva test',
        ]);

        $this->assertTrue($reserva->save(), implode(' | ', $reserva->getErrorSummary(true)));

        return $reserva;
    }

    private function crearRequestReserva($estado, $desde, $hasta)
    {
        $suffix = $this->nextSuffix('req');
        $request = new RequestReserva([
            'fecha' => '2026-07-25 10:00:00',
            'desde' => $desde,
            'hasta' => $hasta,
            'denominacion' => 'Cliente ' . $suffix,
            'email' => $suffix . '@example.com',
            'pax' => 2,
            'hash' => substr(hash('sha1', $suffix), 0, 40),
            'total' => 1800,
            'id_estado' => $estado->id,
            'codigo_reserva' => strtoupper(substr(md5($suffix), 0, 7)),
            'pagado' => 0,
        ]);

        $this->assertTrue($request->save(), implode(' | ', $request->getErrorSummary(true)));

        return $request;
    }

    private function vincularCabanaReserva($reserva, $cabana, $valor)
    {
        $rc = new ReservaCabana([
            'id_reserva' => $reserva->id,
            'id_cabana' => $cabana->id,
            'valor' => $valor,
        ]);

        $this->assertTrue($rc->save(), implode(' | ', $rc->getErrorSummary(true)));

        return $rc;
    }

    private function vincularCabanaRequest($request, $cabana, $valor)
    {
        $rc = new RequestCabana([
            'id_request' => $request->id,
            'id_cabana' => $cabana->id,
            'valor' => $valor,
        ]);

        $this->assertTrue($rc->save(), implode(' | ', $rc->getErrorSummary(true)));

        return $rc;
    }

    private function nextSuffix($prefix)
    {
        $this->seq++;

        return $prefix . '_' . uniqid((string) $this->seq, false);
    }
}
