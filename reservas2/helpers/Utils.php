<?php

namespace app\helpers;

class Utils
{
    public static function DMY2SQLdate($dateDMY, $sep = "/", $hr = "00:00:00")
    {

        $tk = explode($sep, (string) $dateDMY);
        if ($tk === false)
            return '';
        if (count($tk) != 3)
            return '';

        if ($hr)
            return "$tk[2]-$tk[1]-$tk[0] $hr";
        return "$tk[2]-$tk[1]-$tk[0]";
    }



    /**
     * Devuelve la hora mínima [H, i] de checkin entre un conjunto de cabañas.
     */
    public static function obtenerHoraMinimaCheckin(array $cabanas): array
    {
        $minH = 23;
        $minM = 59;
        $found = false;

        $parseHM = function (?string $t): ?array {
            if (!$t) {
                return null;
            }
            $t = trim(str_ireplace(['a. m.', 'p. m.', 'a.m.', 'p.m.'], ['am', 'pm', 'am', 'pm'], $t));
            foreach (['H:i', 'H:i:s', 'g:i a', 'g:i A', 'h:i a', 'h:i A'] as $f) {
                $dt = \DateTime::createFromFormat($f, $t);
                if ($dt instanceof \DateTime) {
                    return [(int) $dt->format('H'), (int) $dt->format('i')];
                }
            }
            $ts = strtotime($t);
            return $ts ? [(int) date('H', $ts), (int) date('i', $ts)] : null;
        };

        foreach ($cabanas as $c) {
            $hm = $parseHM($c->checkin ?? null);
            if ($hm) {
                [$h, $m] = $hm;
                $found = true;
                if ($h < $minH || ($h === $minH && $m < $minM)) {
                    $minH = $h;
                    $minM = $m;
                }
            }
        }

        if (!$found) {
            return [0, 0];
        }

        return [$minH, $minM];
    }



    /**
     * Normaliza una fecha ingresada (d/m/Y, d-m-Y o Y-m-d) a Y-m-d.
     */
    public static function normalizarFechaReserva(?string $v): ?string
    {
        if ($v === null || $v === '') {
            return null;
        }

        $v = trim($v);
        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d'] as $fmt) {
            $d = \DateTime::createFromFormat($fmt, $v);
            if ($d instanceof \DateTime) {
                return $d->format('Y-m-d');
            }
        }
        return null;
    }

}