<?php
namespace app\components\asistente;

/** Small server-owned state for collecting dates. Never infers an unspecified year. */
class FlujoReserva
{
    public static function inicio($text)
    {
        return preg_match('/\b(?:hacer|ingresar|registrar|crear|realizar)\s+(?:(?:una|nueva|la)\s+)*reserva\b|\bquiero\s+(?:una\s+)?(?:nueva\s+)?reserva\b|\bquiero\s+reservar\b/iu', $text) === 1;
    }

    public static function continuar($text, array $flow, \DateTimeImmutable $now)
    {
        $start = self::inicio($text);
        // Explicit cabin choices must reach preparar_reserva with the trusted selection/period.
        if ($start && preg_match('/\bcaba[ñn]as?\s*(?:n[º°o.]?\s*)?#?\s*\d|\banteriores\b|\b(?:la|las)\s+\d/iu', $text)) { return null; }
        $waiting = in_array($flow['etapa'] ?? '', ['periodo', 'anio'], true);
        if (!$start && !$waiting) { return null; }
        $input = trim(mb_strtolower($text, 'UTF-8'));
        if ($start) {
            $flow = ['etapa' => 'periodo']; // A new request does not inherit unrelated dates.
            preg_match('/\b(?:hacer|ingresar|registrar|crear|realizar)\s+(?:(?:una|nueva|la)\s+)*reserva\b|\bquiero\s+(?:una\s+)?(?:nueva\s+)?reserva\b|\bquiero\s+reservar\b/iu', $input, $match, PREG_OFFSET_CAPTURE);
            $input = trim(substr($input, $match[0][1] + strlen($match[0][0])));
        }
        if (preg_match('/^(?:cancelar|dejalo|dejá|olvidalo|no,? gracias|no quiero continuar)[.!]?$/u', $input)) {
            return ['flow' => ['etapa' => 'cerrado'], 'text' => 'Dejamos la preparación de la reserva. No se guardó ninguna reserva.'];
        }
        $input = trim($input, " \t\n\r\0\x0B.?!");
        $input = preg_replace('/^(?:(?:para|desde|el|del|entre)\s+)+/u', '', $input);
        $months = ['enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4, 'mayo' => 5, 'junio' => 6,
            'julio' => 7, 'agosto' => 8, 'septiembre' => 9, 'setiembre' => 9, 'octubre' => 10, 'noviembre' => 11, 'diciembre' => 12];
        $monthPattern = implode('|', array_keys($months));
        $parsed = false;
        if (preg_match('/^(\d{1,2})(?:\s+de\s+(' . $monthPattern . '))?\s+(?:al|hasta(?:\s+el)?|y)\s+(\d{1,2})\s+de\s+(' . $monthPattern . ')(?:\s+(?:de(?:l)?\s+)?(?:año\s+)?([1-9]\d{3}))?$/u', $input, $m)) {
            $flow['dia_desde'] = (int) $m[1]; $flow['mes_desde'] = $months[$m[2] ?: $m[4]];
            $flow['dia_hasta'] = (int) $m[3]; $flow['mes_hasta'] = $months[$m[4]];
            if (!empty($m[5])) { $flow['anio'] = (int) $m[5]; }
            $parsed = true;
        } elseif (preg_match('/^(?:(?:para|el|en|año|de|del)\s+)*([1-9]\d{3})$/u', $input, $m)) {
            $flow['anio'] = (int) $m[1]; $parsed = true;
        } elseif (preg_match('/^(?:este|el corriente|el actual|corriente)\s+año$/u', $input)) {
            $flow['anio'] = (int) $now->format('Y'); $parsed = true;
        } elseif (preg_match('/^(?:el\s+)?próximo\s+año$/u', $input)) {
            $flow['anio'] = (int) $now->format('Y') + 1; $parsed = true;
        } elseif (preg_match('/^(\d{4}-\d{2}-\d{2})\s+(?:al|hasta(?:\s+el)?)\s+(\d{4}-\d{2}-\d{2})$/D', $input, $m)) {
            return ['flow' => ['etapa' => 'seleccion'], 'args' => ['desde' => $m[1], 'hasta' => $m[2]]];
        } elseif (preg_match('/^(\d{1,2})\/(\d{1,2})\/([1-9]\d{3})\s+(?:al|hasta(?:\s+el)?)\s+(\d{1,2})\/(\d{1,2})\/([1-9]\d{3})$/D', $input, $m)) {
            return ['flow' => ['etapa' => 'seleccion'], 'args' => [
                'desde' => sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]),
                'hasta' => sprintf('%04d-%02d-%02d', $m[6], $m[5], $m[4])]];
        }
        if (!$parsed && (!$start || ($input !== '' && $input !== 'y'))) { return null; } // Other requests/formats use the normal interpreter.
        if (isset($flow['dia_desde'], $flow['anio'])) {
            return ['flow' => ['etapa' => 'seleccion'], 'args' => [
                'desde' => sprintf('%04d-%02d-%02d', $flow['anio'], $flow['mes_desde'], $flow['dia_desde']),
                'hasta' => sprintf('%04d-%02d-%02d', $flow['anio'], $flow['mes_hasta'], $flow['dia_hasta'])]];
        }
        if (isset($flow['dia_desde'])) {
            $flow['etapa'] = 'anio';
            return ['flow' => $flow, 'text' => sprintf(
                'Tengo el período del %02d/%02d al %02d/%02d. ¿Para qué año querés hacer la reserva? Todavía no consulté disponibilidad ni precios.',
                $flow['dia_desde'], $flow['mes_desde'], $flow['dia_hasta'], $flow['mes_hasta'])];
        }
        $flow['etapa'] = 'periodo';
        return ['flow' => $flow, 'text' => isset($flow['anio'])
            ? 'Año de la reserva: ' . $flow['anio'] . '. ¿Qué día y mes ingresás y qué día y mes salís?'
            : 'Vamos a preparar una reserva. ¿Cuáles son las fechas de ingreso y salida? Indicá día, mes y año.'];
    }

    public static function pregunta(array $flow)
    {
        if (($flow['etapa'] ?? '') === 'anio' && isset($flow['dia_desde'])) {
            return sprintf('Para continuar con la reserva del %02d/%02d al %02d/%02d, confirmá el año.',
                $flow['dia_desde'], $flow['mes_desde'], $flow['dia_hasta'], $flow['mes_hasta']);
        }
        return 'Seguimos preparando la reserva. Indicá las fechas de ingreso y salida con día, mes y año para consultar disponibilidad y precios.';
    }
}
