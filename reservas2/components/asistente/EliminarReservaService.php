<?php
namespace app\components\asistente;

use app\components\ReservaOcupacion;
use app\models\AsistenteEliminarReservaForm;
use app\models\Cabana;
use app\models\RequestReserva;
use app\models\Reserva;
use app\models\ReservaCabana;
use Yii;

/** Deletes only Reserva and its occupancy. Requests, payment records and receipts survive. */
class EliminarReservaService
{
    private function rows($query, $lock)
    {
        if (!$lock) { return $query->asArray()->all(); }
        $command = $query->createCommand(); $params = $command->params;
        $command->setSql($command->getSql() . ' FOR UPDATE')->bindValues($params);
        return $command->queryAll();
    }

    private function cargar($id, $lock = false)
    {
        $requestRows = $this->rows(RequestReserva::find()->where(['id_reserva' => $id])->orderBy('id'), $lock);
        $reservationRows = $this->rows(Reserva::find()->where(['id' => $id]), $lock);
        if (!$reservationRows) { return null; }
        $reservation = new Reserva(); Reserva::populateRecord($reservation, $reservationRows[0]);
        $requests = [];
        foreach ($requestRows as $row) { $model = new RequestReserva(); RequestReserva::populateRecord($model, $row); $requests[] = $model; }
        $links = $this->rows(ReservaCabana::find()->where(['id_reserva' => $id])->orderBy('id'), $lock);
        return ['reservation' => $reservation, 'requests' => $requests, 'links' => $links];
    }

    private function fingerprint(array $data)
    {
        $requests = [];
        foreach ($data['requests'] as $request) {
            $requests[] = [(int) $request->id, (int) $request->id_estado, (int) $request->id_reserva,
                (string) $request->total, (string) $request->pagado, $request->registro_pagos];
        }
        // Normalize AR/database scalar types before comparing a normal read with a locking read.
        $links = array_map(function ($row) { return [(int) $row['id'], (int) $row['id_cabana'], (string) $row['valor']]; }, $data['links']);
        $reservation = $data['reservation'];
        $fields = [];
        foreach ($reservation->attributes as $key => $value) { $fields[$key] = $value === null ? null : (string) $value; }
        return hash('sha256', json_encode([$fields, $requests, $links]));
    }

    public function inspeccionar($identity, $id)
    {
        Acceso::exigir($identity);
        if (!is_int($id) || $id < 1) { throw new \InvalidArgumentException('Indicá una reserva por su número.'); }
        $data = $this->cargar($id);
        if (!$data) { throw new \DomainException('La reserva no existe o ya fue eliminada.'); }
        $reservation = $data['reservation'];
        $ids = array_values(array_unique(array_map('intval', array_column($data['links'], 'id_cabana'))));
        sort($ids, SORT_NUMERIC);
        $cabins = $ids ? Cabana::find()->select(['id', 'numero', 'descr'])->where(['id' => $ids])->orderBy('numero')->asArray()->all() : [];
        $requests = [];
        foreach ($data['requests'] as $request) {
            $requests[] = ['id' => (int) $request->id,
                'total' => is_numeric($request->total) ? (float) $request->total : null,
                'pagado' => is_numeric($request->pagado) ? (float) $request->pagado : null,
                'movimientos' => is_array($request->registro_pagos) ? count($request->registro_pagos) : null];
        }
        return ['id_reserva' => $id, 'fingerprint' => $this->fingerprint($data), 'cabana_ids' => $ids,
            'desde' => $reservation->desde, 'hasta' => $reservation->hasta,
            'estado' => $reservation->estado->descr ?? 'Sin estado informado',
            'titular' => $reservation->locador->denominacion ?? 'Sin titular asociado', 'cabanas' => $cabins,
            'requests' => $requests];
    }

    public function revisar($identity, array $proposal, AsistenteEliminarReservaForm $form)
    {
        Acceso::exigir($identity);
        if (!$form->validate()) { return null; }
        return $this->inspeccionar($identity, $proposal['id_reserva']);
    }

    public function confirmar($identity, array $draft)
    {
        Acceso::exigir($identity);
        if (($draft['owner'] ?? '') !== (string) $identity->id || ($draft['expires'] ?? 0) <= time()
            || empty($draft['review']) || !preg_match('/^[a-f0-9]{32}$/D', $draft['token'] ?? '')) {
            throw new \DomainException('Revisá la reserva y el impacto antes de confirmar su eliminación.');
        }
        $review = $draft['review']; $id = $review['id_reserva'];
        if ($id !== $draft['proposal']['id_reserva']) { throw new \DomainException('La propuesta no coincide con la reserva revisada.'); }
        $tx = Yii::$app->db->beginTransaction();
        try {
            if ($review['cabana_ids']) { ReservaOcupacion::bloquear($review['cabana_ids']); }
            $data = $this->cargar($id, true);
            if ($data === null) { $tx->commit(); return ['id_reserva' => $id, 'eliminada' => false]; } // Already absent: never delete another ID on a retry.
            if (!hash_equals($review['fingerprint'], $this->fingerprint($data))) {
                throw new \DomainException('Cambiaron la reserva, sus cabañas o sus pagos. Revisá nuevamente antes de eliminarla.');
            }
            foreach ($data['requests'] as $request) {
                $request->id_reserva = null;
                // Preserve the request, payments, conversations, observations and receipts. Audit the unlink.
                if (!$request->save(false, ['id_reserva', 'updated_at', 'updated_by'])) {
                    throw new \DomainException('No se pudo desvincular la solicitud. No se aplicaron cambios.');
                }
            }
            ReservaCabana::deleteAll(['id_reserva' => $id]);
            if ($data['reservation']->delete() !== 1) { throw new \DomainException('No se pudo eliminar la reserva. No se aplicaron cambios.'); }
            $tx->commit();
            return ['id_reserva' => $id, 'eliminada' => true];
        } catch (\Throwable $e) {
            if ($tx->isActive) { $tx->rollBack(); }
            throw $e;
        }
    }
}
