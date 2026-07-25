<?php

namespace tests\unit\models;

use app\models\Cabana;
use app\models\Estado;
use app\models\ParametrosGenerales;
use app\models\RequestReserva;
use app\models\RequestResponse;
use Yii;
use yii\helpers\FileHelper;

class RequestReservaDominioTest extends \Codeception\Test\Unit
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

    public function testVencidaSegunEstadoYConfiguracion()
    {
        $estadoVerificar = $this->crearEstado('pendiente-email-verificar', 'Pendiente verificar');
        $estadoContestado = $this->crearEstado('pendiente-email-contestado', 'Pendiente contestado');
        $estadoConfirmado = $this->crearEstado('confirmado', 'Confirmado');

        $reqVerificar = $this->crearRequestReserva($estadoVerificar, '2026-07-20 10:00:00');
        $reqContestado = $this->crearRequestReserva($estadoContestado, '2026-07-20 10:00:00');
        $reqConfirmado = $this->crearRequestReserva($estadoConfirmado, '2026-07-24 10:00:00');

        $resVerificar = RequestReserva::vencida($reqVerificar->id, new \DateTime('2026-07-25 12:00:00'));
        $resContestado = RequestReserva::vencida($reqContestado->id, new \DateTime('2026-07-25 12:00:00'));
        $resConfirmado = RequestReserva::vencida($reqConfirmado->id, new \DateTime('2026-07-25 12:00:00'));

        $this->assertSame('vencida', $resVerificar['status']);
        $this->assertSame('pendiente-email-verificar', $resVerificar['estado']);
        $this->assertStringContainsString('expirado', $resVerificar['msg']);

        $this->assertSame('vencida', $resContestado['status']);
        $this->assertSame('pendiente-email-contestado', $resContestado['estado']);

        $this->assertSame('OK', $resConfirmado['status']);
        $this->assertSame('confirmado', $resConfirmado['estado']);
    }

    public function testGenerateUniqueCodigoReservaRespetaFormato()
    {
        $codigo = RequestReserva::generateUniqueCodigoReserva('codigo_' . $this->nextSuffix('mail') . '@example.com');

        $this->assertSame(7, strlen($codigo));
        $this->assertMatchesRegularExpression('/^[23456789ABCDEFGHJKLMNPQRSTUVWXYZ]{7}$/', $codigo);
    }

    public function testContieneEmailFalso()
    {
        $this->assertTrue(RequestReserva::contieneEmailFalso('cliente@emailFalso.test'));
        $this->assertTrue(RequestReserva::contieneEmailFalso('CLIENTE@EMAILFALSO.TEST'));
        $this->assertFalse(RequestReserva::contieneEmailFalso('cliente@example.com'));
        $this->assertFalse(RequestReserva::contieneEmailFalso(null));
    }

    public function testBeforeDeleteEliminaComprobantesPermitidos()
    {
        $estado = $this->crearEstado('rechazado', 'Rechazado');

        $privateDir = Yii::getAlias('@runtime/priv_comprobantes');
        FileHelper::createDirectory($privateDir);
        $path = $privateDir . '/comp_' . $this->nextSuffix('file') . '.txt';
        file_put_contents($path, 'ok');

        $request = $this->crearRequestReserva($estado);
        $request->registro_pagos = [['archivo' => $path, 'monto' => 10]];
        $this->assertTrue($request->save(false, ['registro_pagos']));

        $this->assertFileExists($path);
        $request->delete();
        $this->assertFileDoesNotExist($path);
    }

    public function testRequestResponsesSeOrdenanPorFechaEIdYNewMessageGuarda()
    {
        $estado = $this->crearEstado('confirmado', 'Confirmado');
        $request = $this->crearRequestReserva($estado);

        $msg1 = new RequestResponse([
            'id_request' => $request->id,
            'fecha' => '2026-07-25 10:00:00',
            'response' => 'Segundo por id',
            'is_response' => 1,
        ]);
        $this->assertTrue($msg1->save(), implode(' | ', $msg1->getErrorSummary(true)));

        $msg2 = new RequestResponse([
            'id_request' => $request->id,
            'fecha' => '2026-07-25 10:00:00',
            'response' => 'Tercero por id',
            'is_response' => 0,
        ]);
        $this->assertTrue($msg2->save(), implode(' | ', $msg2->getErrorSummary(true)));

        $result = RequestResponse::newMessage($request, 'Primero por helper', false);
        $this->assertTrue($result['success']);

        $messages = RequestReserva::findOne($request->id)->requestResponses;
        $texts = array_map(function ($msg) {
            return $msg->response;
        }, $messages);

        $this->assertCount(3, $messages);
        $this->assertSame('Segundo por id', $texts[0]);
        $this->assertSame('Tercero por id', $texts[1]);
        $this->assertSame('Primero por helper', $texts[2]);
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

    private function crearRequestReserva($estado, $fecha = '2026-07-25 10:00:00')
    {
        $suffix = $this->nextSuffix('req');
        $request = new RequestReserva([
            'fecha' => $fecha,
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
        ]);

        $this->assertTrue($request->save(), implode(' | ', $request->getErrorSummary(true)));

        return $request;
    }

    private function nextSuffix($prefix)
    {
        $this->seq++;

        return $prefix . '_' . uniqid((string) $this->seq, false);
    }
}
