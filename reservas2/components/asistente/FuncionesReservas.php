<?php
namespace app\components\asistente;

use app\models\Reserva;
use app\models\Cabana;
use app\models\CabanaTarifa;
use app\models\Tarifa;
use app\models\ReservaSearch;
use app\models\RequestReserva;
use app\models\ReservaCabana;
use app\models\Estado;
use app\models\Locador;
use yii\db\Expression;
use yii\db\Query;
use yii\helpers\Url;
use Yii;

class FuncionesReservas
{
    /** Display only; keep ISO dates in queries and conversation data. */
    private function fechaVisible($value)
    {
        if (!is_string($value) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})( (?:[01]\d|2[0-3]):[0-5]\d:[0-5]\d)?$/D', $value, $parts)
            || !checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])) {
            return 'No informada';
        }
        return $parts[3] . '/' . $parts[2] . '/' . $parts[1] . ($parts[4] ?? '');
    }

    private function nombreCabana(array $cabana)
    {
        $label = 'Cabaña ' . $cabana['numero'];
        $description = trim((string) $cabana['descr']);
        $repeated = preg_match('/^caba[ñn]a\s+0*' . preg_quote((string) (int) $cabana['numero'], '/') . '$/iu', $description);
        return $description === '' || $repeated ? $label : $label . ': ' . $description;
    }

    /** Display strings only; no HTML or provider-generated markup. */
    private function filaPresentacion(array $cabana, $dias = null)
    {
        $row = ['descripcion' => $this->nombreCabana($cabana) . ' · cap. ' . $cabana['max_pax'],
            'ingreso' => substr($this->fechaVisible($cabana['ingreso']), 0, 16),
            'egreso' => substr($this->fechaVisible($cabana['egreso']), 0, 16)];
        if ($dias !== null) {
            $row['total'] = $cabana['tarifa_completa'] ? '$ ' . number_format($cabana['total'], 2, ',', '.') : 'Sin tarifa completa';
            $row['promedio'] = $cabana['tarifa_completa'] ? '$ ' . number_format($cabana['promedio_diario'], 2, ',', '.') : '—';
            $row['criterio'] = $cabana['criterio_tarifa'];
        }
        return $row;
    }

    public function validar(array $args)
    {
        $keys = array_keys($args);
        sort($keys);
        if ($keys !== ['desde', 'hasta']) {
            throw new \InvalidArgumentException('Indicá desde y hasta, sin otros filtros.');
        }
        foreach (['desde', 'hasta'] as $key) {
            $value = $args[$key];
            if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value)) {
                throw new \InvalidArgumentException('Indicá fechas completas en formato AAAA-MM-DD.');
            }
            $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value, new \DateTimeZone(Yii::$app->timeZone));
            $errors = \DateTimeImmutable::getLastErrors();
            if (!$date || ($errors && ($errors['warning_count'] || $errors['error_count']))
                || $date->format('Y-m-d') !== $value || substr($value, 0, 4) === '0000') {
                throw new \InvalidArgumentException('El período contiene una fecha inexistente.');
            }
        }
        if ($args['desde'] > $args['hasta']) {
            throw new \InvalidArgumentException('La fecha inicial debe ser anterior o igual a la final.');
        }
        return $args;
    }

    public function validarEstadia(array $args)
    {
        $args = $this->validar($args);
        if ($args['desde'] === $args['hasta']) {
            throw new \InvalidArgumentException('La estadía mínima es un día; la salida debe ser posterior al ingreso.');
        }
        return $args;
    }

    public function validarDisponibilidad(array $args)
    {
        $pax = $args['pax'] ?? null;
        if (array_key_exists('pax', $args) && (!is_int($pax) || $pax < 1)) {
            throw new \InvalidArgumentException('La cantidad de huéspedes debe ser un entero positivo.');
        }
        unset($args['pax']);
        return $this->validarEstadia($args) + ['pax' => $pax];
    }

    private function horariosCabana(array $cabana, $desde, $hasta)
    {
        foreach (['checkin', 'checkout'] as $field) {
            if (!is_string($cabana[$field] ?? null)
                || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/D', $cabana[$field])) {
                throw new \RuntimeException('invalid_cabin_schedule');
            }
        }
        $tz = new \DateTimeZone(Yii::$app->timeZone);
        return ['ingreso' => (new \DateTimeImmutable($desde . ' ' . $cabana['checkin'], $tz))->format('Y-m-d H:i:s'),
            'egreso' => (new \DateTimeImmutable($hasta . ' ' . $cabana['checkout'], $tz))->format('Y-m-d H:i:s')];
    }

    public function consultarDisponibilidad($identity, array $args)
    {
        Acceso::exigir($identity);
        $args = $this->validarDisponibilidad($args);
        // Assistant-only interpretation. Existing Reserva::cabanasLibres() remains unchanged.
        // Both requested and stored stay dates use each cabin's configured hours.
        $overlap = (new Query())->from(['rc' => ReservaCabana::tableName()])
            ->innerJoin(['r' => Reserva::tableName()], 'r.id = rc.id_reserva')
            ->where('rc.id_cabana = c.id')
            ->andWhere(new Expression('NOT (TIMESTAMP(:hasta, c.checkout) <= TIMESTAMP(DATE(r.desde), c.checkin)'
                . ' OR TIMESTAMP(:desde, c.checkin) >= TIMESTAMP(DATE(r.hasta), c.checkout))',
                [':desde' => $args['desde'], ':hasta' => $args['hasta']]));
        $query = Cabana::find()->alias('c')->where(['c.activa' => 1])->andWhere(['not exists', $overlap]);
        if ($args['pax'] !== null) { $query->andWhere(['>=', 'c.max_pax', $args['pax']]); }
        $rows = $query->select(['c.id', 'c.numero', 'c.descr', 'c.max_pax', 'c.checkin', 'c.checkout'])
            ->orderBy(['c.numero' => SORT_ASC, 'c.id' => SORT_ASC])->limit(51)->asArray()->all();
        $truncated = count($rows) > 50;
        $rows = array_slice($rows, 0, 50);
        $now = new \DateTimeImmutable('now', new \DateTimeZone(Yii::$app->timeZone));
        $lines = [
            'Período aplicado: ingreso ' . $this->fechaVisible($args['desde']) . ', salida ' . $this->fechaVisible($args['hasta']) . '; horarios según cada cabaña.',
            'Consultado el ' . $now->format('d/m/Y H:i:s P') . '. Disponibilidad al momento de esta consulta.',
        ];
        if (!$rows) {
            $lines[] = 'No hay cabañas disponibles para ese período.';
        }
        $presentation = ['type' => 'disponibilidad', 'title' => $lines[0], 'comment' => '',
            'notes' => array_slice($lines, 1), 'rows' => []];
        foreach ($rows as &$row) {
            $row += $this->horariosCabana($row, $args['desde'], $args['hasta']);
            $lines[] = $this->nombreCabana($row) . ' · Capacidad máxima: ' . $row['max_pax']
                . ' · Ingreso: ' . $this->fechaVisible($row['ingreso']) . ' · Egreso: ' . $this->fechaVisible($row['egreso']) . '.';
            $presentation['rows'][] = $this->filaPresentacion($row);
        }
        unset($row);
        if ($args['pax'] !== null) {
            $lines[] = 'Huéspedes solicitados por cabaña: ' . $args['pax'] . '.';
            $presentation['notes'][] = end($lines);
        }
        if ($truncated) {
            $lines[] = 'Se muestran las primeras 50 cabañas; el resultado está truncado.';
            $presentation['notes'][] = end($lines);
        }
        return ['ok' => true, 'desde' => $args['desde'], 'hasta' => $args['hasta'],
            'pax_solicitado' => $args['pax'], 'consultado' => $now->format(DATE_ATOM), 'cabanas' => $rows, 'truncado' => $truncated,
            'text' => implode("\n", $lines), 'visible_presentation' => $presentation];
    }

    public function listarReservasRegistradas($identity, array $args)
    {
        return $this->listarReservas($identity, $args, 'fecha_registro');
    }

    /** Private table data: never retained in the provider's tool result. */
    private function egresoVisibleReserva(array $record)
    {
        $lastDay = substr((string) $record['hasta'], 0, 10);
        if ($this->fechaVisible($lastDay) === 'No informada') { return 'No informada'; }
        $date = (new \DateTimeImmutable($lastDay, new \DateTimeZone(Yii::$app->timeZone)))
            ->modify('+1 day')->format('d/m/Y');
        if (!$record['cabanas']) { return $date . ' · checkout no configurado'; }
        $values = [];
        foreach ($record['cabanas'] as $cabin) {
            $checkout = $cabin['checkout'] ?? '';
            $values[] = ['numero' => $cabin['numero'], 'valor' => is_string($checkout)
                && preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/D', $checkout)
                    ? $date . ' ' . substr($checkout, 0, 5) : $date . ' · checkout no configurado'];
        }
        if (count(array_unique(array_column($values, 'valor'))) === 1) { return $values[0]['valor']; }
        return implode("\n", array_map(function ($value) { return 'Cabaña ' . $value['numero'] . ': ' . $value['valor']; }, $values));
    }

    private function presentacionReservas(array $ids, $title, $comment = '', array $notes = [], $contact = false)
    {
        $presentation = ['type' => 'reservas', 'title' => $title, 'comment' => $comment,
            'notes' => $notes, 'rows' => []];
        if (!$ids) { return $presentation; }
        $records = Reserva::find()->select(['id', 'fecha', 'desde', 'hasta', 'id_locador'])
            ->where(['id' => $ids])->with([
                'locador' => function ($q) {
                    $q->select(['id', 'denominacion', 'email', 'telefono']);
                },
                'cabanas' => function ($q) { $q->select(['id', 'numero', 'max_pax', 'checkout'])->orderBy(['numero' => SORT_ASC]); },
                'reservaCabanas' => function ($q) { $q->select(['id', 'id_reserva', 'valor']); },
            ])->indexBy('id')->asArray()->all();
        $presentation['notes'][] = 'Total cobrado: importe verificado por el estado Confirmado de la solicitud. No se recalculan tarifas actuales.';
        $presentation['notes'][] = 'Egreso: día siguiente al último día registrado, a la hora de checkout de cada cabaña. Días de estadía: desde y hasta inclusive.';
        $tz = new \DateTimeZone(Yii::$app->timeZone);
        foreach ($ids as $id) {
            if (!isset($records[$id])) { throw new \InvalidArgumentException('La reserva ya no existe.'); }
            $record = $records[$id];
            // Two rows detect ambiguity without loading an unbounded request history.
            $requests = RequestReserva::find()->select(['id', 'hash', 'codigo_reserva', 'total', 'pagado', 'registro_pagos', 'id_estado'])
                ->where(['id_reserva' => $id])->with(['estado' => function ($q) { $q->select(['id', 'slug']); }])
                ->orderBy(['id' => SORT_ASC])->limit(2)->asArray()->all();
            $total = null;
            $paid = null;
            $hash = $requests ? $requests[0]['hash'] : null;
            $financialNote = '';
            if (count($requests) === 1) {
                $payment = $this->analizarPagos($requests[0]);
                $total = $payment['total'];
                $paid = $payment['verificado'];
                if ($paid === null) { $financialNote = 'Cobrado no determinable con el estado actual.'; }
                if (!$payment['historial_valido'] || ($payment['pagado'] !== null
                    && $payment['cantidad'] && abs($payment['suma'] - $payment['pagado']) > 0.005)) {
                    $financialNote .= ' Revisar el registro de pagos: información incompleta o inconsistente.';
                }
            } elseif (count($requests) > 1) {
                $financialNote = 'Varias solicitudes asociadas: no se suman ni se elige un importe arbitrario. Consultá el detalle de pagos.';
            } else {
                $amounts = array_column($record['reservaCabanas'], 'valor');
                if ($amounts && count(array_filter($amounts, [$this, 'importeValido'])) === count($amounts)) {
                    $total = (float) array_sum($amounts);
                }
                $financialNote = 'Sin solicitud asociada: total según los recursos guardados; cobrado no informado.';
            }
            $from = substr((string) $record['desde'], 0, 10);
            $to = substr((string) $record['hasta'], 0, 10);
            $days = 'No determinable';
            if ($this->fechaVisible($from) !== 'No informada' && $this->fechaVisible($to) !== 'No informada' && $to >= $from) {
                $days = (string) ((new \DateTimeImmutable($from, $tz))->diff(new \DateTimeImmutable($to, $tz))->days + 1);
            }
            $row = ['reserva' => '#' . $id,
                'codigo_seguimiento' => count($requests) === 1 ? (string) ($requests[0]['codigo_reserva'] ?? '') : '',
                'seguimiento_url' => is_string($hash) && preg_match('/^[A-Za-z0-9_-]{1,128}$/D', $hash)
                    ? Url::to(['disponibilidad/seguimiento', 'hash' => $hash]) : null,
                'recurso' => $record['cabanas'] ? implode("\n", array_map(function ($c) {
                    return 'Cabaña ' . $c['numero'] . ' · cap. máx. ' . $c['max_pax'];
                }, $record['cabanas'])) : 'Sin cabañas asociadas',
                'locador' => $record['locador']['denominacion'] ?? 'Sin titular asociado',
                'telefono' => $record['locador']['telefono'] ?? 'No informado',
                'email' => $record['locador']['email'] ?? 'No informado',
                'creada' => substr($this->fechaVisible($record['fecha']), 0, 16),
                'ingreso' => substr($this->fechaVisible($record['desde']), 0, 16),
                'egreso' => $this->egresoVisibleReserva($record), 'dias' => $days,
                'total' => $total === null ? 'No determinable' : '$ ' . number_format($total, 2, ',', '.'),
                'cobrado' => $paid === null ? 'No determinable' : '$ ' . number_format($paid, 2, ',', '.'),
                'saldo_pendiente' => $total !== null && $paid !== null ? $total - $paid : null,
                'saldo' => $total !== null && $paid !== null ? '$ ' . number_format($total - $paid, 2, ',', '.') : 'No determinable',
                'nota' => trim($financialNote)];
            if ($contact) {
                $row['contacto'] = 'Email: ' . ($record['locador']['email'] ?? 'No informado')
                    . ' · Teléfono: ' . ($record['locador']['telefono'] ?? 'No informado');
            }
            $presentation['rows'][] = $row;
        }
        return $presentation;
    }

    public function listarReservasPorEstadia($identity, array $args)
    {
        return $this->listarReservas($identity, $args, 'estadia');
    }

    public function listarReservasPorIngreso($identity, array $args)
    {
        return $this->listarReservas($identity, $args, 'ingreso');
    }

    private function listarReservas($identity, array $args, $criterio)
    {
        Acceso::exigir($identity);
        $args = $this->validar($args);
        $tz = new \DateTimeZone(Yii::$app->timeZone);
        $from = (new \DateTimeImmutable($args['desde'], $tz))->format('d-m-Y');
        $to = (new \DateTimeImmutable($args['hasta'], $tz))->format('d-m-Y');
        if ($criterio === 'estadia') {
            // Same inclusive overlap condition as ReservaSearch::searchCalendario().
            // Avoid that method's unbounded all() and loading of client relations.
            $query = Reserva::find()->andWhere(['NOT', ['OR',
                ['<', 'reservas.hasta', $args['desde'] . ' 00:00:00'],
                ['>', 'reservas.desde', $args['hasta'] . ' 23:59:59'],
            ]]);
        } elseif ($criterio === 'ingreso') {
            $query = Reserva::find()->andWhere(['between', 'reservas.desde',
                $args['desde'] . ' 00:00:00', $args['hasta'] . ' 23:59:59']);
        } else {
            $search = new ReservaSearch();
            $query = $search->searchIndex(['ReservaSearch' => ['fecha' => $from . ' - ' . $to]])->query;
        }
        $records = $query->select(['reservas.id', 'reservas.fecha', 'reservas.desde', 'reservas.hasta', 'reservas.id_estado'])
            ->with([
                'cabanas' => function ($query) { $query->select(['id', 'numero'])->orderBy(['numero' => SORT_ASC]); },
                'estado' => function ($query) { $query->select(['id', 'descr']); },
            ])
            ->orderBy([$criterio === 'fecha_registro' ? 'reservas.fecha' : 'reservas.desde' => SORT_ASC, 'reservas.id' => SORT_ASC])
            ->limit(21)->asArray()->all();
        $truncated = count($records) > 20;
        $now = new \DateTimeImmutable('now', $tz);
        $headers = ['estadia' => 'Reservas con estadía entre ', 'ingreso' => 'Reservas con ingreso del ',
            'fecha_registro' => 'Reservas registradas del '];
        $filters = ['estadia' => 'Filtro aplicado: estadías que coinciden total o parcialmente con el período.',
            'ingreso' => 'Filtro aplicado: fecha de ingreso (inicio de la estadía).',
            'fecha_registro' => 'Filtro aplicado: fecha de registro (Creada), no fechas de estadía.'];
        $lines = [$headers[$criterio]
                . $this->fechaVisible($args['desde']) . ' 00:00:00 al ' . $this->fechaVisible($args['hasta']) . ' 23:59:59.',
            $filters[$criterio],
            'Consultado el ' . $now->format('d/m/Y H:i:s P') . '.'];
        $rows = [];
        foreach (array_slice($records, 0, 20) as $record) {
            // Explicit projection: no clients, notes, hashes, contacts, or full AR serialization.
            $row = ['id' => (int) $record['id'], 'fecha' => $record['fecha'],
                'desde' => $record['desde'], 'hasta' => $record['hasta'],
                'cabanas' => array_map('intval', array_column($record['cabanas'], 'numero')),
                'estado' => $record['estado']['descr'] ?? 'Sin estado'];
            $rows[] = $row;
            $lines[] = 'Reserva #' . $row['id'] . ' · Registrada: ' . $this->fechaVisible($row['fecha'])
                . ' · Estadía: ' . $this->fechaVisible($row['desde']) . ' al ' . $this->fechaVisible($row['hasta'])
                . ' · Cabañas: ' . ($row['cabanas'] ? implode(', ', $row['cabanas']) : 'Sin cabañas asociadas')
                . ' · Estado: ' . $row['estado'] . '.';
        }
        if (!$rows) {
            $empty = ['estadia' => 'No hay reservas con estadía en ese período.',
                'ingreso' => 'No hay reservas con ingreso en ese período.',
                'fecha_registro' => 'No hay reservas registradas en ese período.'];
            $lines[] = $empty[$criterio];
        }
        if ($truncated) {
            $lines[] = 'Se muestran las primeras 20 reservas; hay más resultados. Consultá un rango más acotado.';
        }
        return ['ok' => true, 'desde' => $args['desde'], 'hasta' => $args['hasta'],
            'criterio' => $criterio, 'consultado' => $now->format(DATE_ATOM),
            'reservas' => $rows, 'truncado' => $truncated, 'text' => implode("\n", $lines),
            'visible_presentation' => $this->presentacionReservas(array_column($rows, 'id'), $lines[0], $lines[1],
                array_merge([$lines[2]], !$rows || $truncated ? [end($lines)] : []))];
    }

    /** Preserve a clearly stated person filter even if the interpreter omits the optional argument. */
    public function titularImpagasEnTexto($text)
    {
        if (!preg_match('/\breservas?\s+impagas?\s+(?:de|a\s+nombre\s+de|del\s+titular)\s+([\p{L}\p{M}][\p{L}\p{M}\s\x{2019}\x{0027}-]{1,99})[?.!]*$/iu', trim($text), $match)) { return null; }
        $name = trim(preg_replace('/\s+/u', ' ', $match[1]));
        // These can be date/group descriptions; let the interpreter resolve them rather than treat them as names.
        if (preg_match('/\b(?:hoy|ayer|semana|mes|año|anio|todos|todas|última|ultima|último|ultimo|enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|octubre|noviembre|diciembre)\b/iu', $name)) { return null; }
        return $name;
    }

    public function validarReservasPorTitular(array $args)
    {
        $filters = $this->validarReservasImpagas($args);
        if ($filters['titular'] === null) { throw new \InvalidArgumentException('Indicá el nombre del titular.'); }
        return $filters;
    }

    private function filtrarTitular(Query $query, $name)
    {
        $query->innerJoin(['l' => Locador::tableName()], 'l.id = r.id_locador');
        foreach (explode(' ', $name) as $word) {
            $query->andWhere(['like', new Expression('CONVERT(l.denominacion USING utf8mb4) COLLATE utf8mb4_unicode_ci'), $word]);
        }
    }

    public function validarUltimaReserva(array $args)
    {
        if (array_diff(array_keys($args), ['criterio'])
            || (isset($args['criterio']) && $args['criterio'] !== 'fecha_registro')) {
            throw new \InvalidArgumentException('La última reserva se consulta por fecha de registro, sin otros filtros.');
        }
    }

    public function prepararEdicionPasajero($identity, array $args, $reservas = null, $pasajeros = null)
    {
        $proposal = (new EditarPasajeroService())->preparar($identity, $args, $reservas, $pasajeros);
        return ['ok' => true, 'pasajeros' => array_map(function ($id) { return ['id' => $id]; }, $proposal['ids_locadores']),
            'text' => 'Se preparó el formulario privado para ' . ($args['operacion'] === 'calificacion' ? 'calificar al pasajero y editar su nota.' : 'editar los datos del pasajero.')
                . ' Identificá o revisá al pasajero y confirmá los cambios en el formulario. Todavía no se guardó ninguna edición.',
            'visible_passenger' => $proposal];
    }

    public function buscarPasajeros($identity, array $args)
    {
        return (new BuscarPasajerosService())->buscar($identity, $args);
    }

    public function consultarUltimaReserva($identity, array $args)
    {
        Acceso::exigir($identity);
        $this->validarUltimaReserva($args);
        $ids = array_map('intval', Reserva::find()->select('id')
            ->orderBy(['fecha' => SORT_DESC, 'id' => SORT_DESC])->limit(1)->column());
        $now = new \DateTimeImmutable('now', new \DateTimeZone(Yii::$app->timeZone));
        $notes = ['Consultado el ' . $now->format('d/m/Y H:i:s P') . '.'];
        if (!$ids) { $notes[] = 'No hay reservas registradas.'; }
        return ['ok' => true, 'reservas' => array_map(function ($id) { return ['id' => $id]; }, $ids),
            'truncado' => false, 'criterio' => 'fecha_registro', 'consultado' => $now->format(DATE_ATOM),
            'text' => $ids ? 'Última reserva registrada: #' . $ids[0] . '. El detalle se mostró solo al administrador.'
                : 'No hay reservas registradas.',
            'visible_presentation' => $this->presentacionReservas($ids, 'Última reserva registrada',
                'Orden: fecha de registro (Creada) descendente; a igual fecha, mayor número de reserva. Sin filtro de estadía.', $notes)];
    }

    public function listarReservasPorTitular($identity, array $args)
    {
        Acceso::exigir($identity);
        $filters = $this->validarReservasPorTitular($args);
        $query = (new Query())->from(['r' => Reserva::tableName()]);
        $this->filtrarTitular($query, $filters['titular']);
        $comment = 'Titular: ' . $filters['titular'] . '. Coincidencia por todas las palabras, sin distinguir mayúsculas ni tildes.';
        if (isset($filters['desde'], $filters['hasta'])) {
            $from = $filters['desde'] . ' 00:00:00'; $to = $filters['hasta'] . ' 23:59:59';
            if ($filters['criterio'] === 'estadia') {
                $query->andWhere(['not', ['or', ['<', 'r.hasta', $from], ['>', 'r.desde', $to]]]);
            } else {
                $query->andWhere(['between', $filters['criterio'] === 'ingreso' ? 'r.desde' : 'r.fecha', $from, $to]);
            }
            $labels = ['estadia' => 'estadía', 'fecha_registro' => 'fecha de registro', 'ingreso' => 'fecha de ingreso'];
            $comment .= ' Filtro por ' . $labels[$filters['criterio']] . ': ' . $this->fechaVisible($filters['desde']) . ' al ' . $this->fechaVisible($filters['hasta']) . '.';
        } else { $comment .= ' Sin filtro de fechas; no se reutiliza un período anterior.'; }
        $ids = array_map('intval', $query->select('r.id')->orderBy(['r.desde' => SORT_DESC, 'r.id' => SORT_DESC])->limit(21)->column());
        $truncated = count($ids) > 20;
        $ids = array_slice($ids, 0, 20);
        $notes = ['Consultado el ' . (new \DateTimeImmutable('now', new \DateTimeZone(Yii::$app->timeZone)))->format('d/m/Y H:i:s P') . '.',
            'Reservas existentes del titular solicitado, incluidas pagadas y con saldo pendiente. Ordenadas por ingreso, más recientes primero.'];
        if ($truncated) { $notes[] = 'Se muestran hasta 20 reservas. Indicá un período para acotar la búsqueda.'; }
        if (!$ids) { $notes[] = 'No se encontraron reservas para el titular y los filtros indicados.'; }
        $presentation = $this->presentacionReservas($ids, 'Reservas del titular solicitado', $comment, $notes);
        $presentation['mostrar_saldo'] = true;
        $rows = array_map(function ($row) { return ['id' => (int) substr($row['reserva'], 1)]; }, $presentation['rows']);
        return ['ok' => true, 'reservas' => $rows, 'truncado' => $truncated, 'criterio' => 'titular',
            'desde' => $filters['desde'] ?? null, 'hasta' => $filters['hasta'] ?? null,
            'text' => 'Yii consultó las reservas del titular solicitado. El detalle se mostró solo al administrador. IDs: '
                . ($rows ? implode(', ', array_column($rows, 'id')) : '(selección vacía)') . '.',
            'visible_presentation' => $presentation];
    }

    public function validarReservasImpagas(array $args)
    {
        if (array_diff(array_keys($args), ['desde', 'hasta', 'criterio', 'titular'])) {
            throw new \InvalidArgumentException('Filtros no permitidos.');
        }
        $name = null;
        if (array_key_exists('titular', $args)) {
            if (!is_string($args['titular']) || preg_match('/[\x00-\x1F\x7F]/u', $args['titular'])) {
                throw new \InvalidArgumentException('Titular inválido.');
            }
            $name = trim(preg_replace('/\s+/u', ' ', $args['titular']));
            if (mb_strlen($name, 'UTF-8') < 2 || mb_strlen($name, 'UTF-8') > 100 || count(explode(' ', $name)) > 10) {
                throw new \InvalidArgumentException('Indicá un nombre de titular de 2 a 100 caracteres.');
            }
        }
        $criterion = $args['criterio'] ?? 'estadia';
        if (!in_array($criterion, ['estadia', 'fecha_registro', 'ingreso'], true)) {
            throw new \InvalidArgumentException('Criterio de fechas inválido.');
        }
        $dates = [];
        if (array_key_exists('desde', $args) || array_key_exists('hasta', $args)) {
            $dates = $this->validar(['desde' => $args['desde'] ?? null, 'hasta' => $args['hasta'] ?? null]);
        } elseif (array_key_exists('criterio', $args)) {
            throw new \InvalidArgumentException('Un criterio de fechas requiere un período.');
        }
        return $dates + ['criterio' => $criterion, 'titular' => $name];
    }

    public function listarReservasImpagas($identity, array $args)
    {
        Acceso::exigir($identity);
        $args = $this->validarReservasImpagas($args);
        // Only real reservations with one unambiguous request and a verifiable unpaid amount.
        // The two request states are the ones that create/synchronize Reserva in the existing flow.
        $single = (new Query())->from(RequestReserva::tableName())->select('id_reserva')
            ->groupBy('id_reserva')->having(new Expression('COUNT(*) = 1'));
        $query = (new Query())->from(['r' => Reserva::tableName()])
            ->innerJoin(['rr' => RequestReserva::tableName()], 'rr.id_reserva = r.id')
            ->innerJoin(['e' => Estado::tableName()], 'e.id = rr.id_estado')
            ->where(['in', 'r.id', $single])
            ->andWhere(['e.slug' => ['confirmado', 'confirmado-verificar-pago']])
            ->andWhere(['or', ['e.slug' => 'confirmado'], ['rr.pagado' => 0]])
            ->andWhere(new Expression('rr.total > rr.pagado AND rr.pagado >= 0 AND rr.total >= 0'));
        if (isset($args['desde'], $args['hasta'])) {
            $from = $args['desde'] . ' 00:00:00';
            $to = $args['hasta'] . ' 23:59:59';
            if ($args['criterio'] === 'estadia') {
                $query->andWhere(['not', ['or', ['<', 'r.hasta', $from], ['>', 'r.desde', $to]]]);
            } else {
                $query->andWhere(['between', $args['criterio'] === 'ingreso' ? 'r.desde' : 'r.fecha', $from, $to]);
            }
        }
        if ($args['titular'] !== null) {
            $this->filtrarTitular($query, $args['titular']);
        }
        $candidates = $query->select(['id_reserva' => 'r.id', 'rr.total', 'rr.pagado', 'rr.registro_pagos', 'slug' => 'e.slug'])
            ->orderBy(['r.desde' => SORT_ASC, 'r.id' => SORT_ASC])->limit(201)->all();
        $scanLimited = count($candidates) > 200;
        $candidates = array_slice($candidates, 0, 200);
        $ids = [];
        $examined = 0;
        foreach ($candidates as $candidate) {
            $examined++;
            $candidate['estado'] = ['slug' => $candidate['slug']];
            $payment = $this->analizarPagos($candidate);
            if ($payment['total'] !== null && $payment['verificado'] !== null && $payment['total'] > $payment['verificado']) {
                $ids[] = (int) $candidate['id_reserva'];
                if (count($ids) === 21) { break; }
            }
        }
        $now = new \DateTimeImmutable('now', new \DateTimeZone(Yii::$app->timeZone));
        $comment = 'Saldo pendiente mayor que cero según pagos verificados; incluye reservas con pagos parciales.';
        if (isset($args['desde'], $args['hasta'])) {
            $labels = ['estadia' => 'estadía', 'fecha_registro' => 'fecha de registro', 'ingreso' => 'fecha de ingreso'];
            $comment .= ' Filtro por ' . $labels[$args['criterio']] . ': ' . $this->fechaVisible($args['desde']) . ' al ' . $this->fechaVisible($args['hasta']) . '.';
        } else { $comment .= ' Sin filtro de fechas; no se reutiliza un período anterior.'; }
        if ($args['titular'] !== null) {
            $comment .= ' Titular: ' . $args['titular'] . '. Coincidencia por todas las palabras del nombre, sin distinguir mayúsculas ni tildes.';
        }
        $notes = ['Consultado el ' . $now->format('d/m/Y H:i:s P') . '.',
            'Solo reservas existentes con una única solicitud en estado Confirmado o Confirmado a verificar pago y un saldo determinable. '
                . 'No se clasifican como impagas las que tienen pagos positivos sin verificar, datos insuficientes o varias solicitudes asociadas.'];
        $presentation = $this->presentacionReservas($ids, 'Reservas con saldo pendiente', $comment, $notes);
        // Recheck the amounts actually shown, in case payment data changed after selecting candidates.
        $presentation['rows'] = array_values(array_filter($presentation['rows'], function ($row) {
            return $row['saldo_pendiente'] !== null && $row['saldo_pendiente'] > 0;
        }));
        $truncated = count($presentation['rows']) > 20 || $scanLimited || $examined < count($candidates);
        $presentation['rows'] = array_slice($presentation['rows'], 0, 20);
        $presentation['mostrar_saldo'] = true;
        if ($truncated) { $presentation['notes'][] = 'Se muestran hasta 20 reservas; la búsqueda está acotada a 200 candidatos. Hay más registros por revisar; indicá un período.'; }
        if (!$presentation['rows']) { $presentation['notes'][] = 'No se encontraron reservas con saldo pendiente determinable entre los registros revisados.'; }
        $visible = [$presentation['title'], $comment];
        $rows = [];
        foreach ($presentation['rows'] as $row) {
            $id = (int) substr($row['reserva'], 1);
            $rows[] = ['id' => $id];
            $visible[] = 'Reserva ' . $row['reserva'] . ' · Titular: ' . $row['locador'] . ' · Total: ' . $row['total']
                . ' · Cobrado verificado: ' . $row['cobrado'] . ' · Saldo pendiente: ' . $row['saldo'];
        }
        return ['ok' => true, 'reservas' => $rows, 'criterio' => 'saldo_pendiente',
            'desde' => $args['desde'] ?? null, 'hasta' => $args['hasta'] ?? null,
            'truncado' => $truncated, 'consultado' => $now->format(DATE_ATOM),
            'filtro_titular_aplicado' => $args['titular'] !== null,
            'text' => 'Yii consultó las reservas con saldo pendiente' . ($args['titular'] !== null ? ' filtradas por el titular solicitado' : '')
                . ' y mostró el detalle financiero solo al administrador. IDs: '
                . ($rows ? implode(', ', array_column($rows, 'id')) : '(selección vacía)') . '.',
            'visible_text' => implode("\n", array_merge($visible, $presentation['notes'])), 'visible_presentation' => $presentation];
    }

    public function validarDetalleReservas(array $args, $previous = null)
    {
        $detail = array_key_exists('detalle', $args) ? $args['detalle'] : 'general';
        if (!in_array($detail, ['titular', 'general', 'contacto'], true)) {
            throw new \InvalidArgumentException('Detalle inválido.');
        }
        unset($args['detalle']);
        $keys = array_keys($args);
        sort($keys);
        if (($args['origen'] ?? '') === 'anteriores' && $keys === ['origen']) {
            if (!is_array($previous) || empty($previous['ok']) || !isset($previous['reservas'])) {
                throw new \InvalidArgumentException('Falta un listado anterior.');
            }
            $ids = array_map('intval', array_column($previous['reservas'], 'id'));
            $truncated = !empty($previous['truncado']);
        } elseif (($args['origen'] ?? '') === 'seleccionadas' && $keys === ['ids_reservas', 'origen']) {
            $ids = $args['ids_reservas'];
            $truncated = false;
            if (!is_array($ids) || !$ids || array_keys($ids) !== range(0, count($ids) - 1)) {
                throw new \InvalidArgumentException('Selección inválida.');
            }
        } else {
            throw new \InvalidArgumentException('Argumentos inválidos.');
        }
        if (count($ids) > 20) {
            throw new \InvalidArgumentException('Demasiadas reservas.');
        }
        foreach ($ids as $id) {
            if (!is_int($id) || $id < 1) {
                throw new \InvalidArgumentException('Número de reserva inválido.');
            }
        }
        return ['ids' => array_values(array_unique($ids)), 'detalle' => $detail, 'truncado' => $truncated];
    }

    /** Private fields are returned ONLY in visible_text, never in the provider's tool result. */
    public function consultarDetalleReservas($identity, array $args, $previous = null)
    {
        Acceso::exigir($identity);
        $selection = $this->validarDetalleReservas($args, $previous);
        $contact = $selection['detalle'] === 'contacto';
        $models = $selection['ids'] ? Reserva::find()
            ->select(['id', 'fecha', 'desde', 'hasta', 'id_estado', 'id_locador', 'pax'])
            ->where(['id' => $selection['ids']])->with([
                'locador' => function ($query) use ($contact) {
                    $query->select($contact ? ['id', 'denominacion', 'email', 'telefono'] : ['id', 'denominacion']);
                },
                'cabanas' => function ($query) { $query->select(['id', 'numero'])->orderBy(['numero' => SORT_ASC]); },
                'estado' => function ($query) { $query->select(['id', 'descr']); },
            ])->indexBy('id')->asArray()->all() : [];
        if (count($models) !== count($selection['ids'])) {
            throw new \InvalidArgumentException('Alguna reserva no existe.');
        }
        $now = new \DateTimeImmutable('now', new \DateTimeZone(Yii::$app->timeZone));
        $lines = ['Datos de las reservas consultados el ' . $now->format('d/m/Y H:i:s P') . '.'];
        foreach ($selection['ids'] as $id) {
            $record = $models[$id];
            $holder = $record['locador']['denominacion'] ?? 'Sin titular asociado';
            $lines[] = 'Reserva #' . $id . ' · Titular: ' . $holder . '.';
            if ($selection['detalle'] === 'general') {
                $cabins = array_column($record['cabanas'], 'numero');
                $lines[] = '  Estadía: ' . $this->fechaVisible($record['desde']) . ' al ' . $this->fechaVisible($record['hasta'])
                    . ' · Registrada: ' . $this->fechaVisible($record['fecha']) . ' · Cabañas: ' . ($cabins ? implode(', ', $cabins) : 'Sin cabañas asociadas')
                    . ' · Pax registrado: ' . $record['pax'] . ' · Estado: ' . ($record['estado']['descr'] ?? 'Sin estado') . '.';
            } elseif ($contact) {
                $lines[] = '  Email: ' . ($record['locador']['email'] ?? 'No informado')
                    . ' · Teléfono: ' . ($record['locador']['telefono'] ?? 'No informado') . '.';
            }
        }
        if (!$selection['ids']) {
            $lines[] = 'No hay reservas en la selección anterior para consultar sus datos.';
        }
        if ($selection['truncado']) {
            $lines[] = 'Se consultan únicamente las reservas mostradas en el listado anterior, que estaba limitado a 20.';
        }
        // Only allowlisted IDs and a fixed factual summary enter the AI conversation.
        return ['ok' => true, 'reservas' => array_map(function ($id) { return ['id' => $id]; }, $selection['ids']),
            'detalle' => $selection['detalle'], 'truncado' => $selection['truncado'], 'consultado' => $now->format(DATE_ATOM),
            'text' => 'Yii consultó el detalle ' . $selection['detalle'] . ' de las reservas '
                . ($selection['ids'] ? '#' . implode(', #', $selection['ids']) : '(selección vacía)')
                . '. Los datos personales se mostraron únicamente al administrador y no se incluyen en este contexto.',
            'visible_text' => implode("\n", $lines),
            'visible_presentation' => $this->presentacionReservas($selection['ids'], 'Datos de las reservas consultadas', '',
                array_merge([$lines[0]], !$selection['ids'] || $selection['truncado'] ? [end($lines)] : []), $contact)];
    }

    public function validarPrepararPago(array $args, $previous = null)
    {
        $selection = $this->validarPagosReservas($args, $previous);
        if (count($selection['ids']) !== 1 || $selection['truncado']) {
            throw new \InvalidArgumentException('Elegí una sola reserva para cargar el pago.');
        }
        return $selection;
    }

    public function prepararEliminacionReserva($identity, array $args, $previous = null)
    {
        Acceso::exigir($identity);
        $selection = $this->validarPrepararPago($args, $previous);
        $id = $selection['ids'][0];
        (new EliminarReservaService())->inspeccionar($identity, $id);
        $data = $this->consultarDetalleReservas($identity, ['origen' => 'seleccionadas', 'ids_reservas' => [$id], 'detalle' => 'general']);
        $data['visible_presentation']['title'] = 'Preparar eliminación · Reserva #' . $id;
        $data['visible_presentation']['notes'][] = 'Se quitarán la reserva y su ocupación. Las solicitudes y sus pagos se conservarán desvinculados. Revisá el impacto y confirmá en el formulario privado; todavía no se eliminó la reserva.';
        $data['visible_reservation_deletion'] = ['id_reserva' => $id];
        $data['text'] = 'Yii preparó la revisión privada para eliminar la reserva #' . $id . '. No se eliminó todavía; requiere revisión y confirmación humana.';
        $data['visible_text'] = $data['text'];
        return $data;
    }

    public function prepararEliminacionPagoReserva($identity, array $args, $previous = null)
    {
        Acceso::exigir($identity);
        $selection = $this->validarPrepararPago($args, $previous);
        $id = $selection['ids'][0];
        $request = (new EliminarPagoService())->solicitud($identity, $id);
        $data = $this->consultarPagosReservas($identity, ['origen' => 'seleccionadas', 'ids_reservas' => [$id]]);
        $data['visible_presentation']['title'] = 'Preparar eliminación de pago · Reserva #' . $id;
        $data['visible_presentation']['notes'][] = 'Elegí el movimiento en el formulario privado y revisá el importe a descontar de lo cobrado antes de confirmar. Todavía no se eliminó ningún pago.';
        $data['visible_deletion'] = ['id_reserva' => $id, 'id_request' => (int) $request->id];
        $data['text'] = 'Yii preparó la selección privada de un pago de la reserva #' . $id . ' para su eliminación. Todavía no se eliminó ningún pago; requiere selección, revisión y confirmación humana.';
        $data['visible_text'] = $data['text'];
        return $data;
    }

    public function prepararPagoReserva($identity, array $args, $previous = null)
    {
        Acceso::exigir($identity);
        $selection = $this->validarPrepararPago($args, $previous);
        $id = $selection['ids'][0];
        $request = (new CargarPagoService())->solicitud($identity, $id);
        $data = $this->consultarPagosReservas($identity, ['origen' => 'seleccionadas', 'ids_reservas' => [$id]]);
        $data['visible_presentation']['title'] = 'Preparar pago · Reserva #' . $id;
        $data['visible_presentation']['notes'][] = 'Todavía no se registró ningún pago. Completá monto, nota y comprobante opcional en el formulario privado; revisá y confirmá la acreditación.';
        $data['visible_payment'] = ['id_reserva' => $id, 'id_request' => (int) $request->id];
        $data['text'] = 'Yii preparó el formulario privado para cargar un pago de la reserva #' . $id . '. Todavía no se registró ningún pago; requiere revisión y confirmación humana.';
        $data['visible_text'] = $data['text'];
        return $data;
    }

    public function validarPagosReservas(array $args, $previous = null)
    {
        if (array_key_exists('detalle', $args)) {
            throw new \InvalidArgumentException('Argumentos de pagos inválidos.');
        }
        return $this->validarDetalleReservas($args, $previous);
    }

    public function consultarPagosReservas($identity, array $args, $previous = null)
    {
        Acceso::exigir($identity);
        $selection = $this->validarPagosReservas($args, $previous);
        $found = $selection['ids'] ? Reserva::find()->select('id')->where(['id' => $selection['ids']])->count() : 0;
        if ((int) $found !== count($selection['ids'])) {
            throw new \InvalidArgumentException('Alguna reserva no existe.');
        }
        $now = new \DateTimeImmutable('now', new \DateTimeZone(Yii::$app->timeZone));
        $lines = ['Pagos de las reservas consultados el ' . $now->format('d/m/Y H:i:s P') . '.'];
        foreach ($selection['ids'] as $id) {
            $requests = RequestReserva::find()->select(['id', 'total', 'pagado', 'registro_pagos', 'id_estado'])
                ->where(['id_reserva' => $id])->with(['estado' => function ($query) {
                    $query->select(['id', 'descr', 'slug']);
                }])->orderBy(['id' => SORT_ASC])->limit(6)->asArray()->all();
            $lines[] = 'Reserva #' . $id . ':';
            if (!$requests) {
                $lines[] = '  Sin solicitud asociada con información de pagos; no se puede determinar si tiene pagos registrados.';
            }
            foreach (array_slice($requests, 0, 5) as $request) {
                $payment = $this->analizarPagos($request);
                $paid = $payment['pagado'];
                $total = $payment['total'];
                $verified = $payment['verificado'];
                $validHistory = $payment['historial_valido'];
                $positives = $payment['positivos'];
                $sum = $payment['suma'];
                $confirmed = $payment['confirmado'];
                $count = $payment['cantidad'];
                if (($paid !== null && $paid > 0) || $positives > 0) {
                    $answer = 'Tiene importes de pago registrados.';
                } elseif (!$validHistory || $paid === null) {
                    $answer = 'Información incompleta: no se puede determinar si tiene pagos registrados.';
                } else {
                    $answer = 'No hay importes de pago positivos registrados.';
                }
                $money = function ($value) { return $value === null ? 'No informado' : '$ ' . number_format($value, 2, ',', '.'); };
                $lines[] = '  Solicitud #' . $request['id'] . ' · ' . $answer;
                $lines[] = '  Total: ' . $money($total) . ' · Pagado registrado: ' . $money($paid)
                    . ' · Pagado verificado: ' . ($verified === null ? 'No determinable con el estado actual' : $money($verified))
                    . ' · Saldo según verificación: ' . ($total !== null && $verified !== null
                        ? $money($total - $verified) : 'No determinable hasta verificar el pago') . '.';
                if ($confirmed) { $lines[] = '  Verificación global por estado Confirmado; no existe verificación individual de cada pago.'; }
                if ($validHistory) {
                    $lines[] = '  Movimientos registrados: ' . $count . ' (' . $positives . ' con importe positivo).';
                    if (!$count && $paid !== null && $paid > 0) {
                        $lines[] = '  El acumulado pagado no tiene movimientos detallados asociados.';
                    } elseif ($count && $paid !== null && abs($sum - $paid) > 0.005) {
                        $lines[] = '  El acumulado pagado difiere de la suma de los movimientos; revisar el registro.';
                    }
                } else {
                    $lines[] = '  El registro de movimientos está incompleto o no tiene un formato interpretable.';
                }
                $lines[] = '  Estado de la solicitud: ' . ($request['estado']['descr'] ?? 'Sin estado') . '.';
                if (($request['estado']['slug'] ?? '') === 'confirmado-verificar-pago') {
                    $lines[] = '  Pago pendiente de verificación según el estado de la solicitud.';
                }
            }
            if (count($requests) > 5) {
                $lines[] = '  Se muestran las primeras 5 solicitudes asociadas; hay más y sus pagos no se incluyen en este resumen.';
            }
        }
        if (!$selection['ids']) {
            $lines[] = 'No hay reservas en la selección anterior para consultar pagos.';
        }
        if ($selection['truncado']) {
            $lines[] = 'Se consultan únicamente las reservas mostradas en el listado anterior, limitado a 20.';
        }
        $presentation = $this->presentacionReservas($selection['ids'], 'Pagos de las reservas consultadas', '',
            array_merge([$lines[0]], !$selection['ids'] || $selection['truncado'] ? [end($lines)] : []));
        $presentation['detail_text'] = implode("\n", $lines);
        return ['ok' => true, 'reservas' => array_map(function ($id) { return ['id' => $id]; }, $selection['ids']),
            'truncado' => $selection['truncado'], 'consultado' => $now->format(DATE_ATOM),
            'text' => 'Yii consultó los pagos de las reservas ' . ($selection['ids'] ? '#' . implode(', #', $selection['ids']) : '(selección vacía)')
                . '. El resumen financiero se mostró solo al administrador, sin incluirlo en este contexto.',
            'visible_text' => implode("\n", $lines), 'visible_presentation' => $presentation];
    }

    /** One interpretation of recorded amounts and global verification, shared by tables and payment details. */
    private function analizarPagos(array $request)
    {
        $raw = $request['registro_pagos'];
        if (is_string($raw)) {
            $raw = trim($raw) === '' ? [] : json_decode($raw, true);
        }
        $validHistory = $raw === null && $request['registro_pagos'] === null ? true : is_array($raw);
        $payments = is_array($raw) ? $raw : [];
        $sum = 0.0;
        $positives = 0;
        foreach ($payments as $payment) {
            if (!is_array($payment) || !$this->importeValido($payment['monto'] ?? null)) {
                $validHistory = false;
                continue;
            }
            $amount = (float) $payment['monto'];
            $sum += $amount;
            if ($amount > 0) { $positives++; }
        }
        $paid = $this->importeValido($request['pagado']) ? (float) $request['pagado'] : null;
        $total = $this->importeValido($request['total']) ? (float) $request['total'] : null;
        $confirmed = ($request['estado']['slug'] ?? '') === 'confirmado';
        // No per-payment verification exists: only Confirmado verifies the accumulated amount.
        $verified = $confirmed ? $paid : ($paid === 0.0 && $validHistory && $positives === 0 ? 0.0 : null);
        return ['pagado' => $paid, 'total' => $total, 'verificado' => $verified,
            'historial_valido' => $validHistory, 'cantidad' => count($payments),
            'positivos' => $positives, 'suma' => $sum, 'confirmado' => $confirmed];
    }

    private function importeValido($value)
    {
        return is_numeric($value) && is_finite((float) $value) && (float) $value >= 0;
    }

    /** Only $previous from server-side tool results is trusted, never model/browser history. */
    public function validarTarifas(array $args, $previous = null)
    {
        $detail = array_key_exists('detalle', $args) ? $args['detalle'] : 'total';
        if (!in_array($detail, ['total', 'diario'], true)) {
            throw new \InvalidArgumentException('Detalle de tarifas inválido.');
        }
        unset($args['detalle']);
        $origen = $args['origen'] ?? null;
        $keys = array_keys($args);
        sort($keys);
        if ($origen === 'anteriores') {
            if ($keys !== ['origen'] || !is_array($previous) || empty($previous['ok'])
                || !isset($previous['desde'], $previous['hasta'], $previous['cabanas'])
                || ($previous['periodo_tipo'] ?? '') === 'vigencia') {
                throw new \InvalidArgumentException('Falta una consulta anterior.');
            }
            $range = $this->validarEstadia(['desde' => $previous['desde'], 'hasta' => $previous['hasta']]);
            $range['ids'] = array_values(array_unique(array_map('intval', array_column($previous['cabanas'], 'id'))));
            $range['truncado'] = !empty($previous['truncado']);
            $range['pax'] = $previous['pax_solicitado'] ?? null;
        } elseif ($origen === 'disponibles'
            && ($keys === ['desde', 'hasta', 'origen'] || $keys === ['desde', 'hasta', 'origen', 'pax'])) {
            $availabilityArgs = $args;
            unset($availabilityArgs['origen']);
            $range = $this->validarDisponibilidad($availabilityArgs);
        } elseif ($origen === 'seleccionadas'
            && ($keys === ['desde', 'hasta', 'numeros_cabanas', 'origen'] || $keys === ['numeros_cabanas', 'origen'])) {
            $numbers = $args['numeros_cabanas'];
            if (!is_array($numbers) || !$numbers || count($numbers) > 50
                || array_keys($numbers) !== range(0, count($numbers) - 1)) {
                throw new \InvalidArgumentException('Selección inválida.');
            }
            foreach ($numbers as $number) {
                if (!is_int($number) || $number < 1 || $number > 99) {
                    throw new \InvalidArgumentException('Número de cabaña inválido.');
                }
            }
            $dates = isset($args['desde'], $args['hasta']) ? $args : $previous;
            if (!is_array($dates) || !isset($dates['desde'], $dates['hasta'])) {
                throw new \InvalidArgumentException('Falta el período.');
            }
            if (!isset($args['desde'], $args['hasta']) && ($dates['periodo_tipo'] ?? '') === 'vigencia') {
                throw new \InvalidArgumentException('La vigencia de tarifas no define ingreso y salida de una estadía.');
            }
            $range = $this->validarEstadia(['desde' => $dates['desde'], 'hasta' => $dates['hasta']]);
            $range['numeros'] = array_values(array_unique($numbers));
        } else {
            throw new \InvalidArgumentException('Argumentos de tarifas inválidos.');
        }
        return $range + ['origen' => $origen, 'detalle' => $detail];
    }

    public function validarListadoTarifas(array $args, $previous = null)
    {
        $all = ($args['todas'] ?? false) === true;
        $number = $args['numero_cabana'] ?? null;
        if (array_diff(array_keys($args), ['numero_cabana', 'todas', 'desde', 'hasta', 'usar_contexto', 'periodo_tipo'])
            || ($all && array_key_exists('numero_cabana', $args))
            || (!$all && (!is_int($number) || $number < 1 || $number > 99))
            || (array_key_exists('todas', $args) && $args['todas'] !== true)
            || (array_key_exists('usar_contexto', $args) && !is_bool($args['usar_contexto']))
            || (array_key_exists('periodo_tipo', $args) && !in_array($args['periodo_tipo'], ['vigencia', 'estadia'], true))) {
            throw new \InvalidArgumentException('Indicá una cabaña o todas, sin mezclar selectores.');
        }
        $range = [];
        $source = 'vigentes';
        $type = 'vigencia';
        if (array_key_exists('desde', $args) || array_key_exists('hasta', $args)) {
            $type = $args['periodo_tipo'] ?? 'vigencia';
            $dates = ['desde' => $args['desde'] ?? null, 'hasta' => $args['hasta'] ?? null];
            $range = $type === 'estadia' ? $this->validarEstadia($dates) : $this->validar($dates);
            $source = 'indicado';
        } elseif (($args['usar_contexto'] ?? true) && is_array($previous) && !empty($previous['ok'])
            && isset($previous['desde'], $previous['hasta'])) {
            $type = $previous['periodo_tipo'] ?? 'estadia';
            $dates = ['desde' => $previous['desde'], 'hasta' => $previous['hasta']];
            $range = $type === 'estadia' ? $this->validarEstadia($dates) : $this->validar($dates);
            $source = 'anterior';
        }
        return $range + ['numero_cabana' => $number, 'todas' => $all, 'periodo_fuente' => $source, 'periodo_tipo' => $type];
    }

    public function listarTarifasCabana($identity, array $args, $previous = null)
    {
        Acceso::exigir($identity);
        $range = $this->validarListadoTarifas($args, $previous);
        $cabinsQuery = Cabana::find()->select(['id', 'numero', 'descr', 'activa'])->orderBy(['numero' => SORT_ASC, 'id' => SORT_ASC]);
        if (!$range['todas']) { $cabinsQuery->where(['numero' => $range['numero_cabana']]); }
        $cabins = $cabinsQuery->limit(51)->asArray()->all();
        if (!$cabins && !$range['todas']) { throw new \InvalidArgumentException('La cabaña no existe.'); }
        $cabinsTruncated = count($cabins) > 50;
        $cabins = array_slice($cabins, 0, 50);
        $cabinIds = array_map('intval', array_column($cabins, 'id'));
        $tz = new \DateTimeZone(Yii::$app->timeZone);
        $now = new \DateTimeImmutable('now', $tz);
        $today = $now->format('Y-m-d');
        // Query, rather than ActiveQuery<Tarifa>, preserves each cabin/tariff association when a tariff is shared.
        $query = (new Query())->from(['ct' => CabanaTarifa::tableName()])
            ->innerJoin(['t' => Tarifa::tableName()], 'ct.id_tarifa = t.id')
            ->innerJoin(['c' => Cabana::tableName()], 'c.id = ct.id_cabana')
            ->where(['ct.id_cabana' => $cabinIds, 't.activa' => 1]);
        $days = null;
        $chosenByCabin = [];
        if (isset($range['desde'], $range['hasta'])) {
            $lastDay = $range['hasta'];
            if ($range['periodo_tipo'] === 'estadia') {
                $lastDay = (new \DateTimeImmutable($range['hasta'], $tz))->modify('-1 day')->format('Y-m-d');
                $days = (int) (new \DateTimeImmutable($range['desde'], $tz))->diff(new \DateTimeImmutable($range['hasta'], $tz))->days;
                foreach ($cabinIds as $id) {
                    $chosenByCabin[$id] = array_map('intval', array_column($this->tramosTarifas($id, $range['desde'], $range['hasta'], $days), 'id_tarifa'));
                }
                $comment = 'Período ' . ($range['periodo_fuente'] === 'anterior' ? 'de la consulta anterior' : 'indicado') . ': ingreso '
                    . $this->fechaVisible($range['desde']) . ', salida ' . $this->fechaVisible($range['hasta']) . ' · ' . $days . ' días facturables.';
            } else {
                $comment = 'Vigencia consultada: ' . $this->fechaVisible($range['desde']) . ' al '
                    . $this->fechaVisible($range['hasta']) . ' (ambos días incluidos).';
            }
            $query->andWhere(new Expression('DATE(t.inicio) <= :ultimo AND DATE(t.fin) >= :desde',
                [':ultimo' => $lastDay, ':desde' => $range['desde']]));
        } else {
            $query->andWhere(new Expression('DATE(t.fin) >= :hoy', [':hoy' => $today]));
            $comment = 'Tarifas activas vigentes y futuras desde el ' . $this->fechaVisible($today) . '. Se excluyen las vencidas.';
        }
        $withRates = (clone $query)->select('ct.id_cabana')->distinct()->column();
        $rates = $query->select(['t.id', 't.descr', 't.inicio', 't.fin', 't.valor_dia', 't.min_dias',
            'id_cabana' => 'c.id', 'numero_cabana' => 'c.numero'])
            ->orderBy(['c.numero' => SORT_ASC, 't.inicio' => SORT_ASC, 't.min_dias' => SORT_DESC, 't.id' => SORT_ASC])->limit(21)->all();
        $truncated = count($rates) > 20 || $cabinsTruncated;
        $rates = array_slice($rates, 0, 20);
        $notes = ['Consultado el ' . $now->format('d/m/Y H:i:s P') . '.',
            'Listado de tarifas configuradas: no se consulta disponibilidad ni se calcula un total de estadía.'];
        if ($days === null) { $notes[] = 'Sin fechas de estadía no se elige una tarifa ni se calcula un total.'; }
        else { $notes[] = 'Para cada tramo se elige el mayor min_dias que no supere los ' . $days . ' días de estadía.'; }
        if (array_filter($cabins, function ($c) { return !$c['activa']; })) {
            $notes[] = 'El listado incluye cabañas inactivas; no afirma disponibilidad.';
        }
        $withoutRates = array_filter($cabins, function ($c) use ($withRates) { return !in_array($c['id'], $withRates); });
        if ($withoutRates) { $notes[] = 'Sin tarifas activas que cumplan el filtro: cabañas ' . implode(', ', array_column($withoutRates, 'numero')) . '.'; }
        if (!$rates) { $notes[] = 'No hay tarifas activas asociadas que cumplan el filtro indicado.'; }
        if ($truncated) { $notes[] = 'Listado limitado a 50 cabañas y 20 asociaciones de tarifas; hay más resultados. Acotá la consulta.'; }
        $presentation = ['type' => 'tarifas_configuradas',
            'title' => $range['todas'] ? 'Tarifas de todas las cabañas' : 'Tarifas de ' . $this->nombreCabana($cabins[0]),
            'comment' => $comment, 'notes' => $notes, 'rows' => []];
        $lines = [$presentation['title'], $comment];
        foreach ($rates as &$rate) {
            $chosen = $chosenByCabin[$rate['id_cabana']] ?? [];
            if ($days === null) {
                $criterion = ($range['periodo_fuente'] === 'vigentes'
                    ? (substr($rate['inicio'], 0, 10) > $today ? 'Futura. ' : 'Vigente. ') : 'Coincide con el período consultado. ')
                    . 'Requiere al menos ' . $rate['min_dias'] . ((int) $rate['min_dias'] === 1 ? ' día' : ' días') . ' de estadía.';
            } elseif ((int) $rate['min_dias'] > $days) { $criterion = 'No aplica: el mínimo supera los ' . $days . ' días de estadía.'; }
            elseif (in_array((int) $rate['id'], $chosen, true)) { $criterion = 'Aplicada en el cálculo de esta estadía.'; }
            else { $criterion = $chosen ? 'Elegible por mínimo; no seleccionada.' : 'Elegible por mínimo; falta cobertura completa.'; }
            $rate['criterio'] = $criterion;
            $row = ['recurso' => 'Cabaña ' . $rate['numero_cabana'], 'tarifa' => $rate['descr'],
                'inicio' => $this->fechaVisible(substr($rate['inicio'], 0, 10)),
                'fin' => $this->fechaVisible(substr($rate['fin'], 0, 10)),
                'valor_dia' => $this->importeValido($rate['valor_dia']) ? '$ ' . number_format((float) $rate['valor_dia'], 2, ',', '.') : 'No informado',
                'min_dias' => (string) $rate['min_dias'], 'criterio' => $criterion];
            $presentation['rows'][] = $row;
            $lines[] = $row['recurso'] . ' · #' . $rate['id'] . ' · ' . $row['tarifa'] . ' · ' . $row['inicio'] . ' al ' . $row['fin'] . ' · ' . $row['valor_dia']
                . ' por día · min_dias: ' . $row['min_dias'] . ' · ' . $criterion;
        }
        unset($rate);
        return ['ok' => true, 'cabanas' => array_map(function ($c) { return ['id' => (int) $c['id'], 'numero' => (int) $c['numero']]; }, $cabins),
            'desde' => $range['desde'] ?? null, 'hasta' => $range['hasta'] ?? null, 'periodo_fuente' => $range['periodo_fuente'],
            'periodo_tipo' => $range['periodo_tipo'], 'cabanas_truncadas' => $cabinsTruncated,
            'tarifas' => $rates, 'truncado' => $truncated, 'consultado' => $now->format(DATE_ATOM),
            'text' => implode("\n", array_merge($lines, $notes)), 'visible_presentation' => $presentation];
    }

    /** Assistant-only rule: greatest eligible min_dias for each segment of the full stay. */
    private function tramosTarifas($cabanaId, $desde, $hasta, $dias)
    {
        $rates = Tarifa::find()->alias('t')
            ->innerJoin(['ct' => CabanaTarifa::tableName()], 'ct.id_tarifa = t.id')
            ->where(['ct.id_cabana' => $cabanaId, 't.activa' => 1])
            ->andWhere(['<=', 't.min_dias', $dias])
            ->andWhere(new Expression('DATE(t.inicio) < :salida AND DATE(t.fin) >= :ingreso',
                [':salida' => $hasta, ':ingreso' => $desde]))
            // Preserve existing tie-break: lower daily value for equal min_dias.
            ->orderBy(['t.min_dias' => SORT_DESC, 't.valor_dia' => SORT_ASC, 't.id' => SORT_ASC])
            ->select(['t.id', 't.inicio', 't.fin', 't.valor_dia', 't.min_dias'])->asArray()->all();
        $tz = new \DateTimeZone(Yii::$app->timeZone);
        $borders = [$desde, $hasta];
        foreach ($rates as &$rate) {
            $rate['inicio'] = max($desde, substr($rate['inicio'], 0, 10));
            $rate['fin_exclusivo'] = min($hasta,
                (new \DateTimeImmutable(substr($rate['fin'], 0, 10), $tz))->modify('+1 day')->format('Y-m-d'));
            $borders[] = $rate['inicio'];
            $borders[] = $rate['fin_exclusivo'];
        }
        unset($rate);
        $borders = array_values(array_unique($borders));
        sort($borders);
        $segments = [];
        for ($i = 0; $i < count($borders) - 1; $i++) {
            $start = $borders[$i];
            $end = $borders[$i + 1];
            $chosen = null;
            foreach ($rates as $rate) {
                if ($rate['inicio'] <= $start && $rate['fin_exclusivo'] >= $end) {
                    $chosen = $rate;
                    break;
                }
            }
            if ($chosen === null) { return []; } // Never show partial prices as a complete quote.
            $date = new \DateTimeImmutable($start, $tz);
            $endDate = new \DateTimeImmutable($end, $tz);
            $segmentDays = (int) $date->diff($endDate)->days;
            $segments[] = ['desde' => $start, 'hasta' => $endDate->modify('-1 day')->format('Y-m-d'),
                'id_tarifa' => (int) $chosen['id'], 'min_dias' => (int) $chosen['min_dias'],
                'dias' => $segmentDays, 'valor_dia' => (float) $chosen['valor_dia'],
                'subtotal' => $segmentDays * (float) $chosen['valor_dia']];
        }
        return $segments;
    }

    public function validarInicioReserva(array $args)
    {
        if (!$args || (count($args) === 1 && isset($args['falta'])
            && in_array($args['falta'], ['anio', 'periodo'], true))) {
            return $args;
        }
        return $this->validarEstadia($args);
    }

    /** First step without a cabin selection; only Yii data can become displayed options. */
    public function iniciarReserva($identity, array $args)
    {
        Acceso::exigir($identity);
        $range = $this->validarInicioReserva($args);
        if (!isset($range['desde'])) {
            return ['ok' => true, 'intencion' => 'reservar', 'pendiente' => $range['falta'] ?? 'periodo',
                'text' => ($range['falta'] ?? '') === 'anio'
                    ? '¿Para qué año querés hacer la reserva? Con ese dato voy a consultar las cabañas disponibles y sus precios para que puedas elegir.'
                    : 'Vamos a preparar una reserva. ¿Cuáles son las fechas de ingreso y salida? Indicá día, mes y año; después te mostraré disponibilidad y precios para elegir las cabañas.'];
        }
        $data = $this->consultarTarifas($identity, ['origen' => 'disponibles'] + $range);
        $prompt = $data['cabanas']
            ? 'Para continuar, indicá qué número o números de cabaña querés reservar. Todavía no se guardó ninguna reserva.'
            : 'No hay cabañas disponibles para ese período. Podés indicar otras fechas para continuar.';
        $data['text'] .= "\n" . $prompt;
        $data['visible_presentation']['notes'][] = $prompt;
        $data['intencion'] = 'reservar';
        return $data;
    }

    public function validarPrepararReserva(array $args, $previous = null)
    {
        if (!in_array($args['origen'] ?? '', ['seleccionadas', 'anteriores'], true)
            || array_key_exists('detalle', $args) || array_key_exists('pax', $args)) {
            throw new \InvalidArgumentException('Seleccioná cabañas y un período de estadía.');
        }
        $range = $this->validarTarifas($args, $previous);
        if (!empty($range['truncado'])) {
            throw new \InvalidArgumentException('Seleccioná números concretos; el listado anterior está truncado.');
        }
        return $range;
    }

    /** Prepares a quote only. The provider cannot create records or supply personal/payment data. */
    public function prepararReserva($identity, array $args, $previous = null)
    {
        Acceso::exigir($identity);
        $range = $this->validarPrepararReserva($args, $previous);
        $data = $this->consultarTarifas($identity, $args, $previous);
        $available = $this->consultarDisponibilidad($identity, ['desde' => $range['desde'], 'hasta' => $range['hasta']]);
        $freeIds = array_map('intval', array_column($available['cabanas'], 'id'));
        if (!$data['cabanas']) { throw new \InvalidArgumentException('Seleccioná al menos una cabaña.'); }
        foreach ($data['cabanas'] as $cabin) {
            if (!in_array((int) $cabin['id'], $freeIds, true) || !$cabin['tarifa_completa']
                || !is_finite($cabin['total']) || $cabin['total'] < 0 || (int) $cabin['max_pax'] < 1) {
                throw new \DomainException('No se puede preparar la reserva: alguna cabaña no está disponible o no tiene tarifa completa.');
            }
        }
        $data['visible_presentation']['title'] = 'Preparar reserva · ' . $this->fechaVisible($range['desde']) . ' al ' . $this->fechaVisible($range['hasta']);
        $data['visible_presentation']['notes'][] = 'Todavía no se guardó ninguna reserva. Completá los datos del pasajero y confirmá en el formulario privado. Se permite reservar sin pago inicial.';
        $data['visible_proposal'] = ['origen' => 'seleccionadas', 'desde' => $range['desde'], 'hasta' => $range['hasta'],
            'numeros_cabanas' => array_map('intval', array_column($data['cabanas'], 'numero'))];
        $data['text'] = 'Propuesta de reserva preparada por Yii. Aún no se guardó ninguna reserva. El administrador debe completar y confirmar el formulario privado.';
        return $data;
    }

    public function consultarTarifas($identity, array $args, $previous = null)
    {
        Acceso::exigir($identity);
        $range = $this->validarTarifas($args, $previous);
        $truncated = !empty($range['truncado']);
        if ($range['origen'] === 'disponibles') {
            $availabilityArgs = ['desde' => $range['desde'], 'hasta' => $range['hasta']];
            if ($range['pax'] !== null) { $availabilityArgs['pax'] = $range['pax']; }
            $availability = $this->consultarDisponibilidad($identity, $availabilityArgs);
            $rows = $availability['cabanas'];
            $truncated = $availability['truncado'];
        } else {
            $filter = isset($range['ids']) ? ['id' => $range['ids']] : ['numero' => $range['numeros']];
            $expected = count(isset($range['ids']) ? $range['ids'] : $range['numeros']);
            $rows = Cabana::find()->select(['id', 'numero', 'descr', 'max_pax', 'checkin', 'checkout'])->where($filter)
                ->orderBy(['numero' => SORT_ASC, 'id' => SORT_ASC])->asArray()->all();
            if (count($rows) !== $expected) {
                throw new \InvalidArgumentException('Alguna cabaña ya no existe.');
            }
        }
        $tz = new \DateTimeZone(Yii::$app->timeZone);
        $days = (int) (new \DateTimeImmutable($range['desde'], $tz))->diff(new \DateTimeImmutable($range['hasta'], $tz))->days;
        $now = new \DateTimeImmutable('now', $tz);
        $lines = ['Tarifas del período: ' . $this->fechaVisible($range['desde']) . ' al ' . $this->fechaVisible($range['hasta']) . '.',
            'Días facturables: ' . $days . ' (salida - ingreso; mínimo un día).',
            ($range['detalle'] === 'diario' ? 'Valores por día, detallados por tramo de fechas' : 'Importes totales por cabaña para todo el período')
                . '; calculados el ' . $now->format('d/m/Y H:i:s P') . '.'];
        if ($range['origen'] === 'disponibles') {
            $lines[] = 'Cabañas disponibles al momento de esta consulta; se muestran sus tarifas para el período.';
        }
        if (isset($range['pax'])) { $lines[] = 'Huéspedes solicitados por cabaña: ' . $range['pax'] . '.'; }
        if (!$rows) {
            $lines[] = $range['origen'] === 'disponibles'
                ? 'No hay cabañas disponibles para ese período y capacidad solicitada.'
                : 'No hay cabañas en la selección para consultar tarifas.';
        }
        $presentation = ['type' => 'tarifas', 'title' => $lines[0], 'comment' => $lines[1],
            'notes' => array_slice($lines, 2), 'rows' => []];
        $presentation['notes'][] = 'Promedio diario = total / días facturables; puede diferir del valor de cada día si cambian las tarifas.';
        $presentation['notes'][] = 'Criterio: mayor min_dias que no supere los ' . $days . ' días de estadía, entre tarifas activas vigentes para cada tramo.';
        foreach ($rows as &$row) {
            $row += $this->horariosCabana($row, $range['desde'], $range['hasta']);
            $segments = $this->tramosTarifas((int) $row['id'], $range['desde'], $range['hasta'], $days);
            $covered = !empty($segments);
            $total = $covered ? array_sum(array_column($segments, 'subtotal')) : null;
            $row['tarifa_completa'] = $covered;
            $row['total'] = $covered ? (float) $total : null;
            $row['promedio_diario'] = $covered ? (float) $total / $days : null;
            $row['min_dias_aplicados'] = array_values(array_unique(array_column($segments, 'min_dias')));
            $row['criterio_tarifa'] = !$covered ? 'Sin cobertura válida para todos los días.'
                : (count($row['min_dias_aplicados']) === 1
                    ? 'min_dias aplicado: ' . $row['min_dias_aplicados'][0]
                    : 'min_dias por tramo: ' . implode('; ', array_map(function ($segment) {
                        return $this->fechaVisible($segment['desde']) . ' al ' . $this->fechaVisible($segment['hasta']) . ': ' . $segment['min_dias'];
                    }, $segments)));
            $label = $this->nombreCabana($row)
                . ' · Capacidad máxima: ' . $row['max_pax']
                . ' · Ingreso: ' . $this->fechaVisible($row['ingreso']) . ' · Egreso: ' . $this->fechaVisible($row['egreso']);
            if ($covered && $range['detalle'] === 'diario') {
                $row['tramos_diarios'] = $segments;
                $lines[] = $label . ':';
                foreach ($row['tramos_diarios'] as $segment) {
                    $lines[] = '  ' . $this->fechaVisible($segment['desde']) . ' al ' . $this->fechaVisible($segment['hasta']) . ': $ '
                        . number_format($segment['valor_dia'], 2, ',', '.') . ' por día.';
                }
            } else {
                $lines[] = $label . ' · ' . ($covered ? 'Total: $ ' . number_format((float) $total, 2, ',', '.')
                    : 'Sin tarifa completa para el período.');
            }
            $lines[] = '  ' . $row['criterio_tarifa'];
            $presentation['rows'][] = $this->filaPresentacion($row, $days);
        }
        unset($row);
        if ($truncated) {
            $lines[] = 'Selección limitada a las primeras 50 cabañas mostradas; el resultado está truncado.';
            $presentation['notes'][] = end($lines);
        }
        return ['ok' => true, 'desde' => $range['desde'], 'hasta' => $range['hasta'], 'dias' => $days,
            'origen' => $range['origen'], 'detalle' => $range['detalle'], 'consultado' => $now->format(DATE_ATOM), 'cabanas' => $rows,
            'pax_solicitado' => $range['pax'] ?? null,
            'disponibilidad_consultada' => $range['origen'] === 'disponibles',
            'truncado' => $truncated, 'text' => implode("\n", $lines),
            'visible_presentation' => $range['detalle'] === 'total' ? $presentation : null];
    }
}
