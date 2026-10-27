<?php
namespace app\components\asistente;

use app\components\PagosReserva;
use app\models\AsistentePagoForm;
use app\models\Estado;
use app\models\RequestReserva;
use app\models\Reserva;
use Yii;
use yii\helpers\FileHelper;

/** Private, human-confirmed payment writer. No provider or notifications. */
class CargarPagoService
{
    public function solicitud($identity, $id)
    {
        Acceso::exigir($identity);
        if (!is_int($id) || $id < 1 || !Reserva::findOne($id)) {
            throw new \DomainException('No se encontró la reserva. Indicá su número.');
        }
        $requests = RequestReserva::find()->where(['id_reserva' => $id])->limit(2)->all();
        if (count($requests) !== 1) {
            throw new \DomainException('La reserva debe tener una única solicitud asociada para cargar pagos desde el asistente. Revisala en la gestión de reservas.');
        }
        $this->exigirHabilitada($requests[0]);
        return $requests[0];
    }

    private function centavos($value)
    {
        if (!is_numeric($value) || !is_finite((float) $value) || (float) $value < 0 || (float) $value > 999999999999.99) {
            throw new \DomainException('Los importes de la solicitud requieren revisión en la gestión de reservas.');
        }
        return (int) round((float) $value * 100);
    }

    private function exigirHabilitada(RequestReserva $request)
    {
        if (!in_array($request->estado->slug ?? '', ['confirmado', 'confirmado-verificar-pago', 'pendiente-email-verificado'], true)) {
            throw new \DomainException('El estado actual de la solicitud no permite agregar pagos.');
        }
        $reserva = $request->reserva;
        if (!$reserva || !in_array($reserva->estado->slug ?? '', ['confirmado', 'confirmado-verificar-pago', 'pendiente-email-verificado'], true)) {
            throw new \DomainException('La reserva no está activa en un estado que permita cargar pagos.');
        }
        if ($this->centavos($request->total) <= $this->centavos($request->pagado)) {
            throw new \DomainException('La reserva no tiene saldo pendiente para agregar un pago.');
        }
        if ($request->registro_pagos !== null && !is_array($request->registro_pagos)) {
            throw new \DomainException('El historial de pagos requiere revisión antes de agregar movimientos.');
        }
        foreach ($request->registro_pagos ?? [] as $payment) {
            if (!is_array($payment) || !is_numeric($payment['monto'] ?? null)
                || !is_finite((float) $payment['monto']) || (float) $payment['monto'] < 0) {
                throw new \DomainException('El historial de pagos requiere revisión antes de agregar movimientos.');
            }
        }
    }

    private function snapshot(RequestReserva $request)
    {
        return hash('sha256', json_encode([$request->id, $request->id_reserva, $request->id_estado,
            $request->total, $request->pagado, $request->registro_pagos, $request->reserva->id_estado]));
    }

    public function revisar($identity, array $proposal, AsistentePagoForm $form)
    {
        Acceso::exigir($identity);
        if (!$form->validate()) { return null; }
        $request = $this->solicitud($identity, $proposal['id_reserva']);
        if ((int) $request->id !== $proposal['id_request']) { throw new \DomainException('Cambió la solicitud asociada. Prepará nuevamente el pago.'); }
        $amount = $form->centavos();
        if ($amount < 1 || $amount > $this->centavos($request->total) - $this->centavos($request->pagado)) {
            $form->addError('monto', 'El monto debe ser mayor que cero y no superar el saldo pendiente.');
            return null;
        }
        return ['id_reserva' => (int) $request->id_reserva, 'id_request' => (int) $request->id,
            'snapshot' => $this->snapshot($request), 'monto_centavos' => $amount, 'nota' => (string) $form->nota,
            'medio_pago' => $form->medio_pago ?: null,
            'total' => $this->centavos($request->total), 'pagado' => $this->centavos($request->pagado),
            'titular' => $request->reserva->locador->denominacion ?? $request->denominacion,
            'estado' => $request->estado->descr, 'verificacion_global' => ($request->estado->slug !== 'confirmado'),
            'archivo' => null];
    }

    public function adjuntar($identity, array &$review, AsistentePagoForm $form, $token)
    {
        Acceso::exigir($identity);
        if (!$form->comprobante) { return; }
        if (!preg_match('/^[a-f0-9]{32}$/D', $token)) { throw new \DomainException('Propuesta inválida.'); }
        $dir = Yii::getAlias('@runtime/priv_comprobantes');
        FileHelper::createDirectory($dir, 0700);
        // Bounded cleanup of abandoned assistant uploads; referenced receipts are never removed.
        $checked = 0;
        foreach (glob($dir . '/asistente_pago_*.pending') ?: [] as $old) {
            if (++$checked > 50) { break; }
            if (is_file($old) && filemtime($old) < time() - 86400) { @unlink($old); }
        }
        $path = $dir . '/asistente_pago_' . $token . '_' . bin2hex(random_bytes(8)) . '.pending';
        if (!$form->comprobante->saveAs($path)) { throw new \DomainException('No se pudo conservar el comprobante privado. Intentá nuevamente.'); }
        @chmod($path, 0600);
        $review['archivo'] = ['path' => $path, 'extension' => strtolower($form->comprobante->extension), 'sha256' => hash_file('sha256', $path)];
    }

    public function confirmar($identity, array $draft)
    {
        Acceso::exigir($identity);
        if (($draft['owner'] ?? '') !== (string) $identity->id || ($draft['expires'] ?? 0) <= time()
            || empty($draft['review']) || !preg_match('/^[a-f0-9]{32}$/D', $draft['token'] ?? '')) {
            throw new \DomainException('Revisá el pago antes de confirmar.');
        }
        $review = $draft['review']; $finalPath = null;
        $tx = Yii::$app->db->beginTransaction();
        try {
            $request = PagosReserva::bloquear($review['id_request']);
            if ((int) $request->id_reserva !== $review['id_reserva']) { throw new \DomainException('Cambió la reserva asociada. Revisá nuevamente.'); }
            $payments = $request->registro_pagos ?? [];
            if (!is_array($payments)) { throw new \DomainException('El historial de pagos requiere revisión.'); }
            foreach ($payments as $payment) {
                if (($payment['asistente_token'] ?? '') === $draft['token']) {
                    $tx->commit(); return (int) $request->id_reserva;
                }
            }
            // Lock the reservation too, so cancellation/state updates cannot race this write.
            $command = Reserva::find()->where(['id' => $request->id_reserva])->createCommand();
            $params = $command->params;
            $command->setSql($command->getSql() . ' FOR UPDATE')->bindValues($params);
            $row = $command->queryOne();
            if (!$row) { throw new \DomainException('La reserva ya no existe.'); }
            $reservation = new Reserva(); Reserva::populateRecord($reservation, $row);
            $request->populateRelation('reserva', $reservation);
            $this->exigirHabilitada($request);
            if ($this->snapshot($request) !== $review['snapshot']) {
                throw new \DomainException('Cambiaron los pagos, el saldo o el estado. Revisá nuevamente antes de confirmar.');
            }
            $amount = $review['monto_centavos'];
            if (!is_int($amount) || $amount < 1 || $amount > $this->centavos($request->total) - $this->centavos($request->pagado)) {
                throw new \DomainException('El monto supera el saldo actual o no es válido.');
            }
            $state = Estado::findOne(['slug' => 'confirmado']);
            if (!$state) { throw new \DomainException('No está configurado el estado Confirmado.'); }
            if ($review['archivo']) {
                $file = $review['archivo'];
                $base = Yii::getAlias('@runtime/priv_comprobantes');
                if (dirname($file['path']) !== $base || !is_file($file['path']) || !is_readable($file['path'])
                    || !hash_equals($file['sha256'], hash_file('sha256', $file['path']))
                    || !in_array($file['extension'], ['png', 'jpg', 'jpeg', 'pdf'], true)) {
                    throw new \DomainException('El comprobante venció o cambió. Adjuntalo nuevamente.');
                }
                $finalPath = $base . '/asistente_pago_' . $draft['token'] . '.' . $file['extension'];
                if (!copy($file['path'], $finalPath)) { throw new \DomainException('No se pudo guardar el comprobante privado.'); }
                @chmod($finalPath, 0600);
            }
            $payments[] = ['fecha' => date('Y-m-d H:i:s'), 'monto' => $amount / 100, 'nota' => $review['nota'],
                'archivo' => $finalPath, 'medio_pago' => $review['medio_pago'] ?? null, 'asistente_token' => $draft['token'], 'administrador_id' => (int) $identity->id];
            $request->registro_pagos = $payments;
            $request->pagado = ($this->centavos($request->pagado) + $amount) / 100;
            $request->id_estado = $state->id;
            $reservation->id_estado = $state->id;
            if (!$request->save() || !$reservation->save()) { throw new \DomainException('No se pudo guardar el pago. No se aplicaron cambios.'); }
            $tx->commit();
            if ($review['archivo']) { @unlink($review['archivo']['path']); }
            return (int) $request->id_reserva;
        } catch (\Throwable $e) {
            if ($tx->isActive) {
                $tx->rollBack();
                if ($finalPath && is_file($finalPath)) { @unlink($finalPath); }
            }
            throw $e;
        }
    }
}
