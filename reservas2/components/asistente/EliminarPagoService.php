<?php
namespace app\components\asistente;

use app\components\PagosReserva;
use app\models\AsistenteEliminarPagoForm;
use app\models\RequestReserva;
use app\models\Reserva;
use Yii;

/** Removes one explicitly selected payment, following the existing administrative operation. */
class EliminarPagoService
{
    private function centavos($value)
    {
        if (!is_numeric($value) || !is_finite((float) $value) || (float) $value < 0 || (float) $value > 999999999999.99) {
            throw new \DomainException('Los importes del pago requieren revisión en la gestión de reservas.');
        }
        return (int) round((float) $value * 100);
    }

    private function exigirHabilitada(RequestReserva $request)
    {
        if (!in_array($request->estado->slug ?? '', ['confirmado', 'confirmado-verificar-pago', 'pendiente-email-verificado'], true)) {
            throw new \DomainException('El estado de la solicitud no permite eliminar pagos.');
        }
        $payments = $request->registro_pagos;
        if (!is_array($payments) || !$payments || array_keys($payments) !== range(0, count($payments) - 1)
            || $this->centavos($request->pagado) <= 0) {
            throw new \DomainException('No hay pagos eliminables con un historial válido y un importe cobrado positivo.');
        }
        $this->centavos($request->total);
        foreach ($payments as $payment) {
            if (!is_array($payment)) { throw new \DomainException('El historial de pagos requiere revisión.'); }
            $this->centavos($payment['monto'] ?? null);
        }
        if (!$request->reserva) { throw new \DomainException('La reserva ya no existe.'); }
    }

    public function solicitud($identity, $id)
    {
        Acceso::exigir($identity);
        if (!is_int($id) || $id < 1 || !Reserva::findOne($id)) { throw new \DomainException('No se encontró la reserva. Indicá su número.'); }
        $requests = RequestReserva::find()->where(['id_reserva' => $id])->limit(2)->all();
        if (count($requests) !== 1) { throw new \DomainException('La reserva debe tener una única solicitud asociada para eliminar pagos desde el asistente.'); }
        $this->exigirHabilitada($requests[0]);
        return $requests[0];
    }

    private function snapshot(RequestReserva $request, array $payments, $paid)
    {
        return hash('sha256', json_encode([(int) $request->id, (int) $request->id_reserva, (int) $request->id_estado,
            $this->centavos($request->total), $paid, $payments, (int) $request->reserva->id_estado]));
    }

    public function pagos($identity, RequestReserva $request)
    {
        Acceso::exigir($identity);
        $this->exigirHabilitada($request);
        $rows = [];
        foreach ($request->registro_pagos as $index => $payment) {
            $rows[] = ['indice' => $index, 'monto' => $this->centavos($payment['monto']),
                'fecha' => is_string($payment['fecha'] ?? null) ? $payment['fecha'] : 'Sin fecha informada',
                'medio' => \app\models\AsistentePagoForm::medios()[is_string($payment['medio_pago'] ?? null) ? $payment['medio_pago'] : ''] ?? 'No especificado',
                'nota' => is_string($payment['nota'] ?? null) ? $payment['nota'] : '',
                'comprobante' => !empty($payment['archivo'])];
        }
        return $rows;
    }

    public function revisar($identity, array $proposal, AsistenteEliminarPagoForm $form)
    {
        Acceso::exigir($identity);
        if (!$form->validate()) { return null; }
        $request = $this->solicitud($identity, $proposal['id_reserva']);
        if ((int) $request->id !== $proposal['id_request']) { throw new \DomainException('Cambió la solicitud asociada. Prepará otra vez la eliminación.'); }
        $index = (int) $form->indice; $payments = $request->registro_pagos;
        if (!isset($payments[$index])) { $form->addError('indice', 'Elegí uno de los pagos registrados.'); return null; }
        $paid = $this->centavos($request->pagado); $amount = $this->centavos($payments[$index]['monto']);
        if ($amount > $paid) { throw new \DomainException('El pago supera el acumulado cobrado. Revisá los datos antes de eliminarlo.'); }
        $after = $payments; unset($after[$index]); $after = array_values($after);
        return ['id_reserva' => (int) $request->id_reserva, 'id_request' => (int) $request->id,
            'indice' => $index, 'pago' => $this->pagos($identity, $request)[$index],
            'pago_hash' => hash('sha256', json_encode($payments[$index])),
            'snapshot' => $this->snapshot($request, $payments, $paid),
            'after' => $this->snapshot($request, $after, $paid - $amount),
            'pagado' => $paid, 'nuevo_pagado' => $paid - $amount, 'total' => $this->centavos($request->total),
            'titular' => $request->reserva->locador->denominacion ?? $request->denominacion];
    }

    public function confirmar($identity, array $draft)
    {
        Acceso::exigir($identity);
        if (($draft['owner'] ?? '') !== (string) $identity->id || ($draft['expires'] ?? 0) <= time()
            || empty($draft['review']) || !preg_match('/^[a-f0-9]{32}$/D', $draft['token'] ?? '')) {
            throw new \DomainException('Elegí y revisá el pago antes de confirmar su eliminación.');
        }
        $review = $draft['review']; $receipt = null;
        $tx = Yii::$app->db->beginTransaction();
        try {
            $request = PagosReserva::bloquear($review['id_request']);
            if ((int) $request->id_reserva !== $review['id_reserva']) { throw new \DomainException('Cambió la reserva asociada. Revisá nuevamente.'); }
            $command = Reserva::find()->where(['id' => $request->id_reserva])->createCommand();
            $params = $command->params;
            $command->setSql($command->getSql() . ' FOR UPDATE')->bindValues($params);
            $row = $command->queryOne();
            if (!$row) { throw new \DomainException('La reserva ya no existe.'); }
            $reservation = new Reserva(); Reserva::populateRecord($reservation, $row);
            $request->populateRelation('reserva', $reservation);
            $payments = $request->registro_pagos;
            if (!is_array($payments)) { throw new \DomainException('Cambió el historial de pagos. Revisá nuevamente.'); }
            $paid = $this->centavos($request->pagado);
            $fingerprint = $this->snapshot($request, $payments, $paid);
            // A lost HTTP response or repeated confirmation can never delete the next reindexed payment.
            if (hash_equals($review['after'], $fingerprint)) { $tx->commit(); return (int) $request->id_reserva; }
            $this->exigirHabilitada($request);
            if (!hash_equals($review['snapshot'], $fingerprint)
                || !isset($payments[$review['indice']])
                || !hash_equals($review['pago_hash'], hash('sha256', json_encode($payments[$review['indice']])))) {
                throw new \DomainException('Cambiaron los pagos, el saldo o el estado. Seleccioná y revisá nuevamente; no se eliminó ningún pago.');
            }
            $receipt = $payments[$review['indice']]['archivo'] ?? null;
            $amount = $this->centavos($payments[$review['indice']]['monto']);
            if ($amount > $paid) { throw new \DomainException('El pago supera el acumulado cobrado. Revisá los datos.'); }
            unset($payments[$review['indice']]);
            $request->registro_pagos = array_values($payments);
            $request->pagado = ($paid - $amount) / 100;
            if (!$request->save()) { throw new \DomainException('No se pudo eliminar el pago. No se aplicaron cambios.'); }
            $tx->commit();
        } catch (\Throwable $e) {
            if ($tx->isActive) { $tx->rollBack(); }
            throw $e;
        }
        $this->limpiarComprobante($receipt);
        return (int) $request->id_reserva;
    }

    /** Never deletes a path outside the two existing receipt directories, or a still-referenced file. */
    private function limpiarComprobante($file)
    {
        if (!is_string($file) || !$file || is_link($file)) { return; }
        $path = realpath($file);
        if (!$path && strpos($file, '/uploads_tmp/comprobantes/') === 0) { $path = realpath(Yii::getAlias('@webroot') . $file); }
        if (!$path || !is_file($path)) { return; }
        $allowed = false;
        foreach (['@runtime/priv_comprobantes', '@webroot/uploads_tmp/comprobantes'] as $dir) {
            $root = realpath(Yii::getAlias($dir));
            if ($root && dirname($path) === $root) { $allowed = true; }
        }
        if (!$allowed) { return; }
        try {
            $candidates = RequestReserva::find()->select('registro_pagos')->where(['like', 'registro_pagos', basename($path)])->limit(201)->asArray()->all();
            if (count($candidates) > 200) { return; } // Conservative cleanup if references cannot be checked within the bound.
            foreach ($candidates as $candidate) {
                $entries = $candidate['registro_pagos'];
                if (is_string($entries)) { $entries = json_decode($entries, true); }
                if (!is_array($entries)) { return; }
                foreach ($entries as $payment) {
                    $other = $payment['archivo'] ?? null;
                    if (is_string($other) && (realpath($other) === $path || $other === basename($path)
                        || $other === '/uploads_tmp/comprobantes/' . basename($path))) { return; }
                }
            }
            @unlink($path);
        } catch (\Throwable $e) { /* Payment is committed; preserve the private receipt if cleanup cannot be verified. */ }
    }
}
