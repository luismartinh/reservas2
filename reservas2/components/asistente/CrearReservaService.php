<?php
namespace app\components\asistente;

use app\components\ReservaOcupacion;
use app\models\AsistenteReservaForm;
use app\models\Estado;
use app\models\Locador;
use app\models\RequestCabana;
use app\models\RequestReserva;
use app\models\Reserva;
use app\models\ReservaCabana;
use Yii;

/** Explicit Yii writer. No provider, generated SQL, payment, notification or file upload. */
class CrearReservaService
{
    public function cotizar($identity, array $proposal)
    {
        Acceso::exigir($identity);
        $data = (new FuncionesReservas())->prepararReserva($identity, $proposal);
        $rows = [];
        foreach ($data['cabanas'] as $cabin) {
            $rows[] = array_intersect_key($cabin, array_flip([
                'id', 'numero', 'max_pax', 'ingreso', 'egreso', 'total', 'min_dias_aplicados',
            ]));
        }
        return ['desde' => $data['desde'], 'hasta' => $data['hasta'], 'dias' => $data['dias'],
            'rows' => $rows, 'total' => array_sum(array_column($rows, 'total'))];
    }

    public function revisar($identity, array $proposal, AsistenteReservaForm $form)
    {
        Acceso::exigir($identity);
        $locador = $form->locador();
        if (!$locador) { return null; }
        return ['quote' => $this->cotizar($identity, $proposal),
            'customer' => array_intersect_key($locador->attributes,
                array_flip(['id', 'documento', 'denominacion', 'email', 'telefono', 'domicilio', 'id_localidad'])),
            'location' => $locador->ubicacionDescripcion,
            'existing' => !$locador->isNewRecord, 'obs' => (string) $form->obs,
            'referencia' => (string) $form->referencia];
    }

    public function confirmar($identity, array $draft)
    {
        Acceso::exigir($identity);
        if ((string) $identity->id !== $draft['owner'] || $draft['expires'] <= time()
            || empty($draft['review']) || !preg_match('/^[a-f0-9]{32}$/D', $draft['token'])) {
            throw new \DomainException('Revisá la propuesta antes de confirmar.');
        }
        $review = $draft['review'];
        $tx = Yii::$app->db->beginTransaction();
        try {
            $ids = array_map('intval', array_column($review['quote']['rows'], 'id'));
            ReservaOcupacion::bloquear($ids);
            // Locking lookup makes a repeated POST safe, including after a lost HTTP response.
            $query = RequestReserva::find()->where(['hash' => $draft['token'], 'created_by' => $identity->id]);
            $command = $query->createCommand();
            $params = $command->params;
            $command->setSql($command->getSql() . ' FOR UPDATE')->bindValues($params);
            $existing = $command->queryOne();
            if ($existing && $existing['id_reserva']) {
                $tx->commit();
                return (int) $existing['id_reserva'];
            }
            ReservaOcupacion::exigirLibres($ids, $review['quote']['desde'], $review['quote']['hasta'], true);
            $quote = $this->cotizar($identity, $draft['proposal']);
            if (json_encode($quote) !== json_encode($review['quote'])) {
                throw new \DomainException('Cambiaron las tarifas, la capacidad o los horarios. Revisá nuevamente antes de confirmar.');
            }
            $state = Estado::findOne(['slug' => 'confirmado']);
            if (!$state) { throw new \DomainException('No está configurado el estado Confirmado.'); }
            $customer = $review['customer'];
            if ($review['existing']) {
                $locador = Locador::findOne($customer['id']);
                if (!$locador || array_intersect_key($locador->attributes, $customer) != $customer) {
                    throw new \DomainException('Cambiaron los datos del pasajero. Revisá nuevamente.');
                }
            } else {
                $locador = new Locador();
                foreach (['documento', 'denominacion', 'email', 'telefono', 'domicilio', 'id_localidad'] as $field) {
                    $locador->$field = $customer[$field] ?? null;
                }
                $this->guardar($locador);
            }
            $attributes = ['fecha' => date('Y-m-d H:i:s'), 'desde' => min(array_column($quote['rows'], 'ingreso')),
                'hasta' => max(array_column($quote['rows'], 'egreso')),
                // Match existing storage: pax is the sum of the selected cabins' maximum capacities.
                'pax' => array_sum(array_column($quote['rows'], 'max_pax')), 'id_estado' => $state->id,
                'obs' => $review['obs']];
            $reserva = new Reserva($attributes + ['id_locador' => $locador->id]);
            $this->guardar($reserva);
            $request = new RequestReserva($attributes + ['denominacion' => $locador->denominacion,
                'referencia' => $review['referencia'] ?? null,
                'email' => $locador->email, 'hash' => $draft['token'], 'total' => $quote['total'],
                'id_reserva' => $reserva->id, 'pagado' => 0, 'registro_pagos' => [],
                'codigo_reserva' => RequestReserva::generateUniqueCodigoReserva($locador->email)]);
            $this->guardar($request);
            foreach ($quote['rows'] as $row) {
                $this->guardar(new RequestCabana(['id_request' => $request->id, 'id_cabana' => $row['id'], 'valor' => $row['total']]));
                $this->guardar(new ReservaCabana(['id_reserva' => $reserva->id, 'id_cabana' => $row['id'], 'valor' => $row['total']]));
            }
            $tx->commit();
            return (int) $reserva->id;
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
    }

    private function guardar($model)
    {
        if (!$model->save()) {
            // Validation details may contain personal data; never expose/log them.
            throw new \DomainException('No se pudo guardar la reserva. Revisá los datos del pasajero y volvé a revisar la propuesta.');
        }
    }
}
