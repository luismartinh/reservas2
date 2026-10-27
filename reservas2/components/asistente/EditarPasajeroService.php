<?php
namespace app\components\asistente;

use app\models\Locador;
use app\models\LocadorSearch;
use app\models\Reserva;
use Yii;

/** Sólo prepara y ejecuta ediciones explícitas en formularios privados de Yii. */
class EditarPasajeroService
{
    public function validar(array $args)
    {
        if (array_diff(array_keys($args), ['operacion', 'id_locador', 'id_reserva', 'titular', 'origen', 'buscar', 'campo'])
            || !in_array($args['operacion'] ?? '', ['datos', 'calificacion'], true)) {
            throw new \InvalidArgumentException('Indicá editar datos o calificar al pasajero.');
        }
        $selectors = 0;
        if (isset($args['campo']) && !isset($args['buscar'])) { throw new \InvalidArgumentException('Indicá el texto de búsqueda.'); }
        foreach (['id_locador', 'id_reserva', 'titular', 'origen', 'buscar'] as $key) {
            if (!array_key_exists($key, $args)) { continue; }
            $selectors++;
            if (in_array($key, ['id_locador', 'id_reserva'], true)
                && (!is_scalar($args[$key]) || !preg_match('/^[1-9][0-9]{0,9}$/D', (string) $args[$key]))) {
                throw new \InvalidArgumentException('Número inválido.');
            }
            if ($key === 'titular' && (!is_string($args[$key]) || mb_strlen(trim($args[$key])) < 2 || mb_strlen($args[$key]) > 100)) {
                throw new \InvalidArgumentException('Nombre inválido.');
            }
            if ($key === 'origen' && $args[$key] !== 'anteriores') { throw new \InvalidArgumentException('Origen inválido.'); }
            if ($key === 'buscar') { (new BuscarPasajerosService())->validar(['texto' => $args[$key], 'campo' => $args['campo'] ?? 'todos']); }
        }
        if ($selectors > 1) { throw new \InvalidArgumentException('Elegí un único criterio para identificar al pasajero.'); }
    }

    public function preparar($identity, array $args, $reservas = null, $pasajeros = null)
    {
        Acceso::exigir($identity);
        $this->validar($args);
        $ids = [];
        if (isset($args['id_locador'])) { $ids = [(int) $args['id_locador']]; }
        elseif (isset($args['id_reserva'])) {
            $ids = Reserva::find()->select('id_locador')->where(['id' => $args['id_reserva']])->column();
            if (!$ids) { throw new \DomainException('La reserva indicada no existe.'); }
        } elseif (isset($args['titular'])) {
            $query = LocadorSearch::aplicarBusqueda(Locador::find(), $args['titular'], 'nombre', true);
            $ids = $query->select('id')->orderBy('denominacion')->limit(20)->column();
            // Cero coincidencias se conserva como selección vacía; el modal permite buscar.
        } elseif (isset($args['buscar'])) {
            $ids = $this->buscar($identity, $args['buscar'], $args['campo'] ?? 'todos');
        } elseif (isset($args['origen'])) {
            if (is_array($pasajeros)) { $ids = array_column($pasajeros, 'id'); }
            elseif (is_array($reservas) && !empty($reservas['ok']) && empty($reservas['truncado'])) {
                $bookingIds = array_column($reservas['reservas'] ?? [], 'id');
                if ($bookingIds) { $ids = Reserva::find()->select('id_locador')->where(['id' => $bookingIds])->distinct()->limit(20)->column(); }
            }
            if (!$ids) { throw new \DomainException('Consultá primero la reserva o indicá el pasajero en el formulario privado.'); }
        }
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if ($ids && Locador::find()->where(['id' => $ids])->count() != count($ids)) {
            throw new \DomainException('Algún pasajero ya no existe. Volvé a buscarlo.');
        }
        return ['operacion' => $args['operacion'], 'ids_locadores' => $ids];
    }

    public function buscar($identity, $text, $field = 'todos')
    {
        Acceso::exigir($identity);
        try {
            return array_map('intval', (new BuscarPasajerosService())->consulta($identity, ['texto' => $text, 'campo' => $field])
                ->select('id')->limit(20)->column());
        } catch (\InvalidArgumentException $e) { throw new \DomainException($e->getMessage()); }
    }

    public function campos($operation)
    {
        if ($operation === 'calificacion') { return ['calificacion', 'obs']; }
        if ($operation !== 'datos') { throw new \DomainException('Operación inválida.'); }
        return ['denominacion', 'documento', 'email', 'telefono', 'domicilio', 'id_localidad', 'id_provincia', 'obs'];
    }

    public function cargar($identity, $id)
    {
        Acceso::exigir($identity);
        $model = Locador::findOne($id);
        if (!$model) { throw new \DomainException('El pasajero ya no existe.'); }
        return $model;
    }

    public function huella(Locador $model) { return hash('sha256', serialize($model->getAttributes())); }

    public function revisar($identity, array $draft, Locador $model, array $input)
    {
        Acceso::exigir($identity);
        if (!isset($draft['id_locador'], $draft['base']) || (int) $model->id !== (int) $draft['id_locador']) {
            throw new \DomainException('Seleccioná primero al pasajero.');
        }
        if (!hash_equals($draft['base'], $this->huella($model))) {
            throw new \DomainException('Los datos del pasajero cambiaron. Pulsá «Recargar datos» y revisá nuevamente.');
        }
        $fields = $this->campos($draft['proposal']['operacion']);
        $model->setAttributes(array_intersect_key($input, array_flip($fields)));
        if (!$model->validate($fields)) { return null; }
        $values = [];
        foreach ($fields as $field) { $values[$field] = $model->$field; }
        return ['id_locador' => (int) $model->id, 'base' => $draft['base'], 'valores' => $values];
    }

    public function confirmar($identity, array $draft)
    {
        Acceso::exigir($identity);
        if (($draft['owner'] ?? '') !== (string) $identity->id || ($draft['expires'] ?? 0) <= time()
            || !preg_match('/^[a-f0-9]{32}$/D', $draft['token'] ?? '') || empty($draft['review'])) {
            throw new \DomainException('Completá y revisá los datos antes de confirmar.');
        }
        $review = $draft['review'];
        if ((int) $draft['id_locador'] !== (int) $review['id_locador']) { throw new \DomainException('La selección cambió.'); }
        $tx = Yii::$app->db->beginTransaction();
        try {
            $command = Locador::find()->where(['id' => $review['id_locador']])->createCommand();
            $model = Locador::findBySql($command->getSql() . ' FOR UPDATE', $command->params)->one();
            if (!$model || !hash_equals($review['base'], $this->huella($model))) {
                throw new \DomainException('Los datos del pasajero cambiaron. Pulsá «Recargar datos» y revisá nuevamente.');
            }
            $fields = $this->campos($draft['proposal']['operacion']);
            $model->setAttributes(array_intersect_key($review['valores'], array_flip($fields)));
            if (!$model->validate($fields)) { throw new \DomainException('Los datos ya no son válidos. Recargá y revisá el formulario.'); }
            $persist = array_values(array_diff($fields, ['id_provincia']));
            if (!$model->save(false, array_merge($persist, ['updated_at', 'updated_by']))) {
                throw new \DomainException('No se pudieron guardar los datos del pasajero.');
            }
            $tx->commit();
            return (int) $model->id;
        } catch (\Throwable $e) { $tx->rollBack(); throw $e; }
    }
}
