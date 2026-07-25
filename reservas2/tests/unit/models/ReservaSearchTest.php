<?php

namespace tests\unit\models;

use app\models\Cabana;
use app\models\Estado;
use app\models\Locador;
use app\models\RequestReserva;
use app\models\Reserva;
use app\models\ReservaCabana;
use app\models\ReservaSearch;
use Yii;

class ReservaSearchTest extends \Codeception\Test\Unit
{
    private $seq = 0;

    public function testSearchIndexFiltraPorCodigoReserva()
    {
        $estado = $this->crearEstado('confirmado', 'Confirmado');
        $locador = $this->crearLocador();
        $reservaVisible = $this->crearReserva($locador, $estado, '2026-08-10 15:00:00', '2026-08-15 10:00:00', 4);
        $reservaOculta = $this->crearReserva($locador, $estado, '2026-09-10 15:00:00', '2026-09-15 10:00:00', 2);

        $this->crearRequestReserva($estado, $reservaVisible, 'ABC2345');
        $this->crearRequestReserva($estado, $reservaOculta, 'XYZ6789');

        $search = new ReservaSearch();
        $provider = $search->searchIndex([
            'ReservaSearch' => [
                'codigo_reserva' => 'ABC',
            ],
        ]);

        $ids = array_map('intval', $provider->query->select(['reservas.id'])->column());
        $this->assertSame([(int) $reservaVisible->id], $ids);
    }

    public function testSearchCalendarioFiltraPorCabanaLocadorYCodigo()
    {
        $estado = $this->crearEstado('confirmado', 'Confirmado');
        $locadorVisible = $this->crearLocador();
        $locadorOculto = $this->crearLocador();
        $cabanaVisible = $this->crearCabana('Calendario visible', 71, '#3cb44b');
        $cabanaOculta = $this->crearCabana('Calendario oculta', 72, '#4363d8');

        $reservaVisible = $this->crearReserva($locadorVisible, $estado, '2026-08-10 15:00:00', '2026-08-15 10:00:00', 4);
        $this->vincularCabanaReserva($reservaVisible, $cabanaVisible, 1500);
        $this->crearRequestReserva($estado, $reservaVisible, 'CAL1234');

        $reservaOculta = $this->crearReserva($locadorOculto, $estado, '2026-08-10 15:00:00', '2026-08-15 10:00:00', 2);
        $this->vincularCabanaReserva($reservaOculta, $cabanaOculta, 900);
        $this->crearRequestReserva($estado, $reservaOculta, 'ZZZ9999');

        $request = Yii::$app->request;
        $request->setQueryParams([
            'cabanas' => [$cabanaVisible->id],
            'id_locador' => $locadorVisible->id,
            'codigo_reserva' => 'CAL',
        ]);

        [$reservas, $selectedCabanas, $idLocador, $locadorLabel, $codigoReserva] =
            ReservaSearch::searchCalendario($request, '2026-08-10 00:00:00', '2026-08-20 23:59:59');

        $ids = array_map(function ($reserva) {
            return (int) $reserva->id;
        }, $reservas);

        $this->assertSame([(int) $reservaVisible->id], $ids);
        $this->assertSame([(string) $cabanaVisible->id], array_map('strval', $selectedCabanas));
        $this->assertSame((int) $locadorVisible->id, $idLocador);
        $this->assertStringContainsString($locadorVisible->denominacion, $locadorLabel);
        $this->assertSame('CAL', $codigoReserva);
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
            'telefono' => '351' . str_pad((string) $this->seq, 7, '0', STR_PAD_LEFT),
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
            'obs' => 'Reserva search',
        ]);

        $this->assertTrue($reserva->save(), implode(' | ', $reserva->getErrorSummary(true)));

        return $reserva;
    }

    private function crearRequestReserva($estado, $reserva, $codigo)
    {
        $suffix = $this->nextSuffix('req');
        $request = new RequestReserva([
            'fecha' => '2026-07-25 10:00:00',
            'desde' => $reserva->desde,
            'hasta' => $reserva->hasta,
            'denominacion' => 'Cliente ' . $suffix,
            'email' => $suffix . '@example.com',
            'pax' => 2,
            'hash' => substr(hash('sha1', $suffix), 0, 40),
            'total' => 1800,
            'id_estado' => $estado->id,
            'id_reserva' => $reserva->id,
            'codigo_reserva' => $codigo,
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

    private function nextSuffix($prefix)
    {
        $this->seq++;

        return $prefix . '_' . uniqid((string) $this->seq, false);
    }
}
