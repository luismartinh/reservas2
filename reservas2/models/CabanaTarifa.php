<?php

namespace app\models;

use \app\models\base\CabanaTarifa as BaseCabanaTarifa;
use yii\db\Expression;
use Yii;

/**
 * This is the model class for table "cabanas_tarifas".
 */
class CabanaTarifa extends BaseCabanaTarifa
{
    /* ==========================================================
     * MÉTODOS DE SELECCIÓN DE TARIFAS DISPONIBLES
     * ========================================================== */

    public static function getTarifasDisponiblesSinSolape($id_cabana)
    {
        // Tarifas no vinculadas y sin solape con las ya asociadas
        $sub1 = self::find()->alias('ct1')
            ->where('ct1.id_tarifa = t.id')
            ->andWhere(['ct1.id_cabana' => $id_cabana]);

        $sub2 = (new \yii\db\Query())
            ->from(['ct2' => 'cabanas_tarifas'])
            ->innerJoin(['t2' => 'tarifas'], 't2.id = ct2.id_tarifa')
            ->where(['ct2.id_cabana' => $id_cabana])
            ->andWhere(new Expression('NOT (t.fin < t2.inicio OR t.inicio > t2.fin)'));

        return Tarifa::find()->alias('t')
            ->where(['not exists', $sub1])
            ->andWhere(['not exists', $sub2])
            ->orderBy(['t.inicio' => SORT_ASC])
            ->all();
    }


    public static function getTarifasDisponibles($id_cabana, $controlar_solape = false)
    {
        $sub1 = self::find()->alias('ct1')
            ->where('ct1.id_tarifa = t.id')
            ->andWhere(['ct1.id_cabana' => $id_cabana]);

        if ($controlar_solape) {
            $sub2 = (new \yii\db\Query())
                ->from(['ct2' => 'cabanas_tarifas'])
                ->innerJoin(['t2' => 'tarifas'], 't2.id = ct2.id_tarifa')
                ->where(['ct2.id_cabana' => $id_cabana])
                ->andWhere(new Expression('NOT (t.fin < t2.inicio OR t.inicio > t2.fin)'));

            return Tarifa::find()->alias('t')
                ->where(['not exists', $sub1])
                ->andWhere(['not exists', $sub2])
                ->orderBy(['t.inicio' => SORT_ASC])
                ->all();
        }

        return Tarifa::find()->alias('t')
            ->where(['not exists', $sub1])
            ->orderBy(['t.inicio' => SORT_ASC])
            ->all();
    }


    /* ==========================================================
     * CÁLCULO DE TOTALES
     * ========================================================== */


    /**
     * Verifica si las tarifas ACTIVAS de la cabaña cubren TODO el período [d, hExcl) sin huecos.
     * $d y $hExcl son DateTime ya normalizados, con $hExcl = (hasta + 1 día).
     */
    private static function tieneCoberturaCompleta(int $idCabana, \DateTime $d, \DateTime $hExcl): bool
    {
        $desdeStr = $d->format('Y-m-d');
        $hastaInclStr = (clone $hExcl)->modify('-1 day')->format('Y-m-d');

        // Traer todas las tarifas ACTIVAS que se solapen con el rango
        $tarifas = Tarifa::find()->alias('t')
            ->innerJoin(['ct' => self::tableName()], 'ct.id_tarifa = t.id')
            ->where(['ct.id_cabana' => $idCabana, 't.activa' => 1])
            ->andWhere(new Expression('NOT (:hasta < DATE(t.inicio) OR :desde > DATE(t.fin))'))
            ->params([':desde' => $desdeStr, ':hasta' => $hastaInclStr])
            ->all();

        if (!$tarifas) {
            return false; // no hay nada que cubra
        }

        // Construir intervalos recortados al rango [d, hExcl) (semi-abierto)
        $intervalos = [];
        foreach ($tarifas as $t) {
            $ti = (new \DateTime(substr($t->inicio, 0, 10)))->setTime(0, 0, 0);
            $tfEx = (new \DateTime(substr($t->fin, 0, 10)))->setTime(0, 0, 0)->modify('+1 day');

            // intersección con [d, hExcl)
            $ini = $ti > $d ? $ti : clone $d;
            $fin = $tfEx < $hExcl ? $tfEx : clone $hExcl;

            if ($ini < $fin) {
                $intervalos[] = [$ini, $fin];
            }
        }

        if (!$intervalos) {
            return false;
        }

        // Ordenar por inicio
        usort($intervalos, fn($a, $b) => $a[0] <=> $b[0]);

        // Recorrer cubriendo desde $cursor = d; si aparece un hueco, no hay cobertura completa.
        $cursor = clone $d;
        foreach ($intervalos as [$ini, $fin]) {
            if ($ini > $cursor) {
                return false; // hueco detectado
            }
            if ($fin > $cursor) {
                $cursor = $fin;
            }
            if ($cursor >= $hExcl) {
                return true; // ya cubrimos todo
            }
        }

        return $cursor >= $hExcl;
    }

    /**
     * Calcula el total de una cabaña en el período [desde, hasta] (ambos inclusive)
     */
    public static function calcularTotalParaCabana(int $idCabana, string $desde, string $hasta): float
    {
        // --- Normalizar a [desde, hasta] inclusivo y además tener hastaExcl (semi-abierto)
        [$d, $hExcl] = self::normalizarRango($desde, $hasta);
        $totalDias = (int) $d->diff($hExcl)->days;  // ya inclusivo (porque hExcl = hasta+1)
        if ($totalDias <= 0) {
            return 0.0;
        }
        $desdeStr = $d->format('Y-m-d');
        $hastaInclStr = (clone $hExcl)->modify('-1 day')->format('Y-m-d');

        // Traer TODAS las tarifas ACTIVAS vinculadas a la cabaña que se solapen (inclusivo)
        $tarifas = Tarifa::find()->alias('t')
            ->innerJoin(['ct' => self::tableName()], 'ct.id_tarifa = t.id')
            ->where(['ct.id_cabana' => $idCabana, 't.activa' => 1])
            ->andWhere(new Expression('NOT (:hasta < DATE(t.inicio) OR :desde > DATE(t.fin))'))
            ->params([':desde' => $desdeStr, ':hasta' => $hastaInclStr])
            ->orderBy(['t.inicio' => SORT_ASC])
            ->all();

        if (!$tarifas) {
            return 0.0;
        }

        // ¿Alguna cubre todo el rango inclusivo? Si sí, elegir UNA según min_dias y aplicar a todo:
        $finIncl = new \DateTime($hastaInclStr);
        $cubridoras = array_filter($tarifas, function (Tarifa $t) use ($d, $finIncl) {
            $ti = new \DateTime(substr($t->inicio, 0, 10));
            $tf = new \DateTime(substr($t->fin, 0, 10));
            return $ti <= $d && $tf >= $finIncl;
        });
        if ($cubridoras) {
            $mejor = self::elegirPorMinDias($cubridoras, $totalDias);
            return $mejor->valor_dia * $totalDias;
        }

        // --- SEGMENTACIÓN: construir límites (fronteras) dentro de [d, hExcl) ---
        // Para cada tarifa, recortar su intervalo a [d, hExcl) en versión semi-abierta
        $segmentBorders = [$d->format('Y-m-d')];
        foreach ($tarifas as $t) {
            $ti = (new \DateTime(substr($t->inicio, 0, 10)))->setTime(0, 0, 0);
            $tf = (new \DateTime(substr($t->fin, 0, 10)))->setTime(0, 0, 0);
            $tfEx = (clone $tf)->modify('+1 day'); // semi-abierto

            // Intersección con [d, hExcl)
            $ini = $ti > $d ? $ti : $d;
            $fin = $tfEx < $hExcl ? $tfEx : $hExcl;

            if ($ini < $fin) {
                $segmentBorders[] = $ini->format('Y-m-d');
                $segmentBorders[] = $fin->format('Y-m-d');
            }
        }
        $segmentBorders[] = $hExcl->format('Y-m-d');

        // Quitar duplicados y ordenar
        $segmentBorders = array_values(array_unique($segmentBorders));
        sort($segmentBorders);

        // --- Recorrer segmentos consecutivos [B[i], B[i+1]) ---
        $total = 0.0;
        for ($i = 0; $i < count($segmentBorders) - 1; $i++) {
            $segIni = (new \DateTime($segmentBorders[$i]))->setTime(0, 0, 0);
            $segFinEx = (new \DateTime($segmentBorders[$i + 1]))->setTime(0, 0, 0);
            $diasSeg = (int) $segIni->diff($segFinEx)->days;
            if ($diasSeg <= 0)
                continue;

            // Tarifas que cubren COMPLETAMENTE este segmento
            $candidatas = [];
            foreach ($tarifas as $t) {
                $ti = (new \DateTime(substr($t->inicio, 0, 10)))->setTime(0, 0, 0);
                $tfEx = (new \DateTime(substr($t->fin, 0, 10)))->setTime(0, 0, 0)->modify('+1 day');
                if ($ti <= $segIni && $tfEx >= $segFinEx) {
                    $candidatas[] = $t;
                }
            }

            if (!$candidatas) {
                // Si no hay ninguna que cubra todo el segmento, saltamos (no debería pasar)
                continue;
            }

            // Elegir exactamente UNA tarifa para el segmento, según la regla de min_dias y el total de la estadía
            $tElegida = self::elegirPorMinDias($candidatas, $totalDias);
            $total += $tElegida->valor_dia * $diasSeg;
        }

        return $total;
    }


    /**
     * Calcula totales para muchas cabañas. Si falta cobertura en alguna, devuelve -1 para esa cabaña.
     * $desde y $hasta en 'd/m/Y' | 'd-m-Y' | 'Y-m-d' (se normaliza a inclusivo).
     */
    public static function calcularTotalesParaCabanas(array $idsCabana, string $desde, string $hasta): array
    {
        [$d, $hExcl] = self::normalizarRango($desde, $hasta); // ya tenés este helper → [d, hasta+1)

        $out = [];
        foreach ($idsCabana as $id) {
            $id = (int) $id;

            // 1) Chequear cobertura completa: si NO hay, marcar -1
            if (!self::tieneCoberturaCompleta($id, $d, $hExcl)) {
                $out[$id] = -1;
                continue;
            }

            // 2) Cobertura OK → calcular total con tu lógica actual
            $out[$id] = self::calcularTotalParaCabana($id, $desde, $hasta);
        }
        return $out;
    }


    /* ==========================================================
     * HELPERS INTERNOS
     * ========================================================== */

    private static function normalizarRango(string $desde, string $hasta): array
    {
        $d = \DateTime::createFromFormat('d/m/Y', $desde)
            ?: \DateTime::createFromFormat('d-m-Y', $desde)
            ?: \DateTime::createFromFormat('Y-m-d', $desde);

        $h = \DateTime::createFromFormat('d/m/Y', $hasta)
            ?: \DateTime::createFromFormat('d-m-Y', $hasta)
            ?: \DateTime::createFromFormat('Y-m-d', $hasta);

        if (!$d || !$h)
            throw new \InvalidArgumentException('Rango inválido');

        // Inicio y fin normalizados a 00:00
        $d->setTime(0, 0, 0);
        $h->setTime(0, 0, 0);

        // Inclusivo: hasta +1 día
        $h->modify('+1 day');

        return [$d, $h];
    }


    /**
     * Elige la tarifa ideal según la cantidad de días:
     * - Prioriza coincidencia exacta (min_dias == días)
     * - Si no existe, busca la mayor tarifa con min_dias <= días
     * - Si tampoco hay, toma la más cercana (por diferencia mínima)
     * - Si empata, elige la de menor valor_dia
     */
    private static function elegirPorMinDias(array $tarifas, int $dias): Tarifa
    {
        // 1️⃣ Coincidencia exacta
        $exactas = array_filter($tarifas, fn($t) => (int) $t->min_dias === $dias);
        if (!empty($exactas)) {
            usort($exactas, fn($a, $b) => $a->valor_dia <=> $b->valor_dia);
            return $exactas[0];
        }

        // 2️⃣ Menor o igual (la más grande posible sin pasarse)
        $menores = array_filter($tarifas, fn($t) => (int) $t->min_dias <= $dias);
        if (!empty($menores)) {
            usort($menores, function ($a, $b) {
                // si empatan, preferir menor valor_dia
                if ($a->min_dias === $b->min_dias)
                    return $a->valor_dia <=> $b->valor_dia;
                // sino, preferir el mayor min_dias
                return $b->min_dias <=> $a->min_dias;
            });
            return $menores[0];
        }

        // 3️⃣ Si no hay ninguna menor o igual, usar la más cercana (superior)
        usort($tarifas, function ($a, $b) use ($dias) {
            $da = abs($a->min_dias - $dias);
            $db = abs($b->min_dias - $dias);
            if ($da === $db)
                return $a->valor_dia <=> $b->valor_dia;
            return $da <=> $db;
        });

        return $tarifas[0];
    }

}
