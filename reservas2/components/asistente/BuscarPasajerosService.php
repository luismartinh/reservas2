<?php
namespace app\components\asistente;

use app\models\Locador;
use app\models\LocadorSearch;
use Yii;

class BuscarPasajerosService
{
    public function validar(array $args)
    {
        if (array_diff(array_keys($args), ['texto', 'campo']) || !is_string($args['texto'] ?? null)
            || mb_strlen(trim($args['texto'])) < (($args['campo'] ?? '') === 'calificacion' ? 1 : 2) || mb_strlen($args['texto']) > 100
            || !is_string($args['campo'] ?? 'todos') || !isset(LocadorSearch::opcionesBusqueda()[$args['campo'] ?? 'todos'])) {
            throw new \InvalidArgumentException('Indicá un texto de 2 a 100 caracteres y un campo de búsqueda válido; para calificación, de 0 a 5.');
        }
        if (($args['campo'] ?? '') === 'calificacion' && !preg_match('/^[0-5]$/D', trim($args['texto']))) {
            throw new \InvalidArgumentException('La calificación debe ser de 1 a 5, o 0 para pasajeros sin calificar.');
        }
    }

    public function consulta($identity, array $args)
    {
        Acceso::exigir($identity);
        $this->validar($args);
        return LocadorSearch::aplicarBusqueda(Locador::find()->with('localidad.provincia'), $args['texto'], $args['campo'] ?? 'todos')
            ->orderBy(['denominacion' => SORT_ASC, 'id' => SORT_ASC]);
    }

    public function buscar($identity, array $args)
    {
        $records = $this->consulta($identity, $args)->limit(21)->all();
        $truncated = count($records) > 20;
        $rows = []; $ids = [];
        foreach (array_slice($records, 0, 20) as $model) {
            $note = (string) $model->obs;
            if (mb_strlen($note) > 200) { $note = mb_substr($note, 0, 200) . '…'; }
            $row = ['pasajero' => '#' . $model->id, 'nombre' => $model->denominacion, 'documento' => $model->documento,
                'telefono' => $model->telefono, 'email' => $model->email, 'direccion' => $model->domicilio ?? '',
                'observaciones' => $note, 'localidad' => $model->localidad->descr ?? '',
                'provincia' => $model->localidad->provincia->descr ?? '', 'calificacion' => (int) $model->calificacion];
            $candidate = array_merge($rows, [$row]);
            if (max(strlen(serialize($candidate)), strlen(json_encode($candidate, JSON_UNESCAPED_UNICODE))) > 12000) {
                $truncated = true; break;
            }
            $rows[] = $row; $ids[] = ['id' => (int) $model->id];
        }
        $notes = ['Consultado el ' . (new \DateTimeImmutable('now', new \DateTimeZone(Yii::$app->timeZone)))->format('d/m/Y H:i:s P') . '.',
            'Observaciones: se muestran hasta 200 caracteres por pasajero. Los datos completos pueden revisarse en su formulario privado.'];
        if (!$rows) { $notes[] = 'No se encontraron pasajeros para la búsqueda indicada.'; }
        if ($truncated) { $notes[] = 'Resultados limitados a ' . count($rows) . ' pasajeros. Acotá la búsqueda para ver los restantes.'; }
        return ['ok' => true, 'pasajeros' => $ids, 'truncado' => $truncated,
            'text' => 'La aplicación consultó pasajeros y mostró los datos sólo al administrador. IDs: '
                . ($ids ? implode(', ', array_column($ids, 'id')) : '(selección vacía)') . '.',
            'visible_presentation' => ['type' => 'pasajeros', 'title' => 'Pasajeros encontrados',
                'comment' => 'Coincidencias parciales por ' . LocadorSearch::opcionesBusqueda()[$args['campo'] ?? 'todos'] . '.',
                'notes' => $notes, 'rows' => $rows]];
    }
}
