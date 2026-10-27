<?php
namespace app\components\asistente;

/** Cancelling a payment or changing a reservation's status is not a request to delete Reserva. */
class FlujoEliminarReserva
{
    public static function inicio($text)
    {
        return preg_match('/\b(?:eliminar|eliminá|borrar|borrá)\s+(?:(?:una|la|esta|esa|las)\s+)*reservas?\b/iu', $text) === 1;
    }

    public static function continuar($text, array $flow, $previous)
    {
        $start = self::inicio($text);
        if (!$start && ($flow['etapa'] ?? '') !== 'reserva') { return null; }
        if (preg_match('/^(?:cancelar|olvidalo|no quiero continuar)[.!]?$/iu', trim($text))) {
            return ['etapa' => 'cerrado', 'text' => 'Se canceló la preparación de la eliminación. No se eliminó ninguna reserva.'];
        }
        if (preg_match('/\breserva\s*#?\s*\d+\s*(?:,|y|al)\s*#?\s*\d+/iu', $text)
            || preg_match_all('/\breserva\s*#?\s*\d+/iu', $text) > 1) {
            return ['etapa' => 'reserva', 'text' => 'Elegí una sola reserva por su número para revisar su eliminación. No se eliminó ninguna reserva.'];
        }
        $name = FlujoPago::titularSolicitado($text);
        if ($name !== null) { return ['etapa' => 'reserva', 'listar_titular' => $name]; }
        $id = null;
        if (preg_match('/\breserva\s*#?\s*([1-9]\d{0,8})\b/iu', $text, $m)
            || (!$start && preg_match('/^(?:(?:la|para|el|número|numero)\s+)*#?([1-9]\d{0,8})[.!]?$/iu', trim($text), $m))) {
            $id = (int) $m[1];
        } elseif (preg_match('/\banterior\b/iu', $text) && is_array($previous) && !empty($previous['ok'])
            && empty($previous['truncado']) && count($previous['reservas'] ?? []) === 1) {
            $id = (int) $previous['reservas'][0]['id'];
        }
        if ($id !== null) { return ['etapa' => 'confirmacion', 'args' => ['origen' => 'seleccionadas', 'ids_reservas' => [$id]]]; }
        if (!$start) { return null; }
        return ['etapa' => 'reserva', 'text' => '¿Qué reserva se cargó por error? Indicá su número o el titular para buscarla. Mostraré el impacto antes de pedir la confirmación de eliminación.'];
    }
}
