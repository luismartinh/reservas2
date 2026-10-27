<?php
namespace app\components\asistente;

/** Collects a reservation number and optional literal prefill, without confirming a payment. */
class FlujoPago
{
    public static function eliminar($text)
    {
        return preg_match('/\b(?:anular|anulá|revertir|revertí|cancelar|cancelá|eliminar|eliminá|borrar|borrá)\s+(?:(?:un|el|ese|este|último|ultimo|los)\s+)*pagos?\b/iu', $text) === 1;
    }

    public static function inicio($text)
    {
        return preg_match('/\b(?:cargar|carga|agregar|registrar|ingresar|anotar)\s+(?:(?:un|el|otro|nuevo)\s+)*pago\b/iu', $text) === 1;
    }

    /** Only values explicitly written by the user; no model-generated amount or default verification. */
    public static function precarga($text)
    {
        $prefill = [];
        if (preg_match('/\bpago\s+(?:de\s+)?\$?\s*(\d[\d.,]*)(?:\s+(mil|millón|millon|millones))?(?=\s|$)/iu', trim($text), $match)) {
            $raw = $match[1];
            if (preg_match('/^\d{1,3}(?:\.\d{3})+(?:,\d{1,2})?$/D', $raw)) {
                $raw = str_replace('.', '', $raw);
            }
            if (preg_match('/^\d{1,12}(?:[.,]\d{1,2})?$/D', $raw)) {
                $parts = explode('.', str_replace(',', '.', $raw));
                $cents = (int) $parts[0] * 100 + (int) str_pad($parts[1] ?? '', 2, '0');
                $multiplier = empty($match[2]) ? 1 : (mb_strtolower($match[2], 'UTF-8') === 'mil' ? 1000 : 1000000);
                if ($cents > 0 && $cents <= intdiv(99999999999999, $multiplier)) {
                    $cents *= $multiplier;
                    $prefill['monto'] = sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);
                }
            }
        }
        if (preg_match('/\ben\s+efectivo\b/iu', $text)) { $prefill['medio_pago'] = 'efectivo'; }
        elseif (preg_match('/\b(?:por|en|mediante)\s+transferencia\b/iu', $text)) { $prefill['medio_pago'] = 'transferencia'; }
        elseif (preg_match('/\b(?:por|en|mediante)\s+dep[oó]sito\b/iu', $text)) { $prefill['medio_pago'] = 'deposito'; }
        elseif (preg_match('/\b(?:con|por|en)\s+tarjeta\b/iu', $text)) { $prefill['medio_pago'] = 'tarjeta'; }
        return $prefill;
    }

    public static function titularSolicitado($text)
    {
        if (!preg_match('/\b(?:reservas?\s+(?:a\s+nombre\s+de|del\s+titular|de)|pago\s+(?:a\s+nombre\s+de|de))\s+([\p{L}\p{M}][\p{L}\p{M}\s\x{2019}\x{0027}-]{1,99})[?.!]*$/iu', trim($text), $match)) { return null; }
        $name = trim(preg_replace('/\s+/u', ' ', $match[1]));
        if (preg_match('/\b(?:reserva|anterior|anteriores|hoy|ayer|semana|mes|año|anio|todos|todas|última|ultima|último|ultimo|enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|octubre|noviembre|diciembre)\b/iu', $name)) { return null; }
        return $name;
    }

    public static function continuar($text, array $flow, $previous)
    {
        $adding = self::inicio($text);
        $removing = self::eliminar($text);
        $start = $adding || $removing;
        $operation = $removing ? 'eliminar' : ($adding ? 'agregar' : ($flow['operacion'] ?? 'agregar'));
        if (!$start && ($flow['etapa'] ?? '') !== 'reserva') { return null; }
        if (preg_match('/^(?:cancelar|olvidalo|no quiero continuar)[.!]?$/iu', trim($text))) {
            return ['etapa' => 'cerrado', 'operacion' => $operation, 'text' => 'Se canceló la preparación de la operación del pago. No se aplicaron cambios.'];
        }
        if (preg_match('/\breserva\s*#?\s*\d+\s*(?:,|y|al)\s*#?\s*\d+/iu', $text)
            || preg_match_all('/\breserva\s*#?\s*\d+/iu', $text) > 1) {
            return ['etapa' => 'reserva', 'operacion' => $operation, 'text' => 'Para operar sobre un pago, elegí una sola reserva e indicá su número. No se aplicaron cambios.'];
        }
        $prefill = $operation === 'eliminar' ? [] : array_merge($start ? [] : ($flow['precarga'] ?? []), self::precarga($text));
        $name = self::titularSolicitado($text);
        if ($name === null && isset($flow['titular'])
            && preg_match('/^(?:mostrame|mostrá|muestra|muéstrame|mostrar|ver|listar)\s+(?:las|sus)\s+reservas[?.!]*$/iu', trim($text))) {
            $name = $flow['titular'];
        }
        if ($name !== null) {
            return ['etapa' => 'reserva', 'operacion' => $operation, 'listar_titular' => $name, 'precarga' => $prefill];
        }
        $id = null;
        if (preg_match('/\breserva\s*#?\s*([1-9]\d{0,8})\b/iu', $text, $m)
            || (!$start && preg_match('/^(?:(?:para|el|la|número|numero)\s+)*#?([1-9]\d{0,8})[.!]?$/iu', trim($text), $m))) {
            $id = (int) $m[1];
        } elseif (preg_match('/\banterior(?:es)?\b/iu', $text) && is_array($previous)
            && !empty($previous['ok']) && empty($previous['truncado']) && count($previous['reservas'] ?? []) === 1) {
            $id = (int) $previous['reservas'][0]['id'];
        }
        if ($id !== null) { return ['etapa' => 'confirmacion', 'operacion' => $operation, 'args' => ['origen' => 'seleccionadas', 'ids_reservas' => [$id]], 'precarga' => $prefill]; }
        if (!$start) { return null; }
        if ($operation === 'eliminar') {
            return ['etapa' => 'reserva', 'operacion' => 'eliminar', 'text' => 'Vamos a revisar un pago para eliminarlo. ¿A qué número de reserva corresponde? También podés indicar el titular para buscar sus reservas. Después elegirás el pago y confirmarás su eliminación. Todavía no se eliminó ningún pago.'];
        }
        return ['etapa' => 'reserva', 'operacion' => $operation, 'precarga' => $prefill, 'text' => 'Vamos a cargar un pago. ¿A qué número de reserva corresponde? Podés responder, por ejemplo, «reserva 81». El monto y el comprobante se completan en el formulario privado; todavía no se registró ningún pago.'];
    }
}
