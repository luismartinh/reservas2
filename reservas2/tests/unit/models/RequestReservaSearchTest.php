<?php

namespace tests\unit\models;

use app\models\Estado;
use app\models\ParametrosGenerales;
use app\models\RequestReserva;
use app\models\RequestReservaSearch;

class RequestReservaSearchTest extends \Codeception\Test\Unit
{
    private $seq = 0;

    protected function _before()
    {
        ParametrosGenerales::setParametro('RESERVA_CFG', [
            'max_reintentos' => ['email_token_expira' => 24],
            'max_horas_venc' => [
                'confirmar_pago' => 48,
                'request_reserva' => 24,
            ],
        ], 'Config test reservas');
    }

    public function testSearchIndexFiltraPorDenominacionImpagas()
    {
        $estado = $this->crearEstado('confirmado', 'Confirmado');
        $visible = $this->crearRequestReserva($estado, [
            'denominacion' => 'Familia Lago ' . $this->nextSuffix('lago'),
            'email' => 'lago_' . $this->nextSuffix('mail') . '@example.com',
            'total' => 2000,
            'pagado' => 500,
        ]);
        $this->crearRequestReserva($estado, [
            'denominacion' => 'Familia Bosque ' . $this->nextSuffix('bosque'),
            'email' => 'bosque_' . $this->nextSuffix('mail') . '@example.com',
            'total' => 1000,
            'pagado' => 1000,
        ]);

        $search = new RequestReservaSearch();
        $provider = $search->searchIndex([
            'RequestReservaSearch' => [
                'denominacion' => 'Lago',
                'impagas' => 1,
            ],
        ]);

        $ids = array_map('intval', $provider->query->select(['request_reservas.id'])->column());

        $this->assertSame([(int) $visible->id], $ids);
    }

    public function testSearchIndexFiltraVencidasYCodigo()
    {
        $estadoVencida = $this->crearEstado('pendiente-email-verificar', 'Pendiente verificar');
        $estadoNoVencida = $this->crearEstado('confirmado', 'Confirmado');

        $visible = $this->crearRequestReserva($estadoVencida, [
            'fecha' => '2026-07-20 08:00:00',
            'codigo_reserva' => 'ABC2345',
        ]);
        $this->crearRequestReserva($estadoNoVencida, [
            'fecha' => '2026-07-25 08:00:00',
            'codigo_reserva' => 'XYZ6789',
        ]);

        $search = new RequestReservaSearch();
        $provider = $search->searchIndex([
            'RequestReservaSearch' => [
                'vencidas' => 1,
                'codigo_reserva' => 'ABC',
            ],
        ]);

        $ids = array_map('intval', $provider->query->select(['request_reservas.id'])->column());

        $this->assertSame([(int) $visible->id], $ids);
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

    private function crearRequestReserva($estado, array $attrs = [])
    {
        $suffix = $this->nextSuffix('rrs');
        $request = new RequestReserva(array_merge([
            'fecha' => '2026-07-25 10:00:00',
            'desde' => '2026-08-10 00:00:00',
            'hasta' => '2026-08-15 00:00:00',
            'denominacion' => 'Cliente ' . $suffix,
            'email' => $suffix . '@example.com',
            'pax' => 2,
            'hash' => substr(hash('sha1', $suffix), 0, 40),
            'total' => 1500,
            'id_estado' => $estado->id,
            'codigo_reserva' => strtoupper(substr(md5($suffix), 0, 7)),
            'pagado' => 0,
        ], $attrs));

        $this->assertTrue($request->save(), implode(' | ', $request->getErrorSummary(true)));

        return $request;
    }

    private function nextSuffix($prefix)
    {
        $this->seq++;

        return $prefix . '_' . uniqid((string) $this->seq, false);
    }
}
