<?php
namespace app\helpers;

use app\models\RequestReserva;
use app\models\Reserva;
use Yii;
use yii\bootstrap5\Html;

class CalendarHelper
{
    public static $monthsTranslated = [
        1 => 'Enero',
        2 => 'Febrero',
        3 => 'Marzo',
        4 => 'Abril',
        5 => 'Mayo',
        6 => 'Junio',
        7 => 'Julio',
        8 => 'Agosto',
        9 => 'Septiembre',
        10 => 'Octubre',
        11 => 'Noviembre',
        12 => 'Diciembre',
    ];

    public static $daysNames = [
        'Lun',
        'Mar',
        'Mié',
        'Jue',
        'Vie',
        'Sáb',
        'Dom'
    ];

    /**
     * Devuelve un array para selects de meses:
     * [
     *   '01' => 'Enero',
     *   '02' => 'Febrero',
     *   ...
     * ]
     */
    public static function getMonthsForSelect(): array
    {
        $result = [];
        foreach (self::$monthsTranslated as $num => $label) {
            $key = sprintf('%02d', (int) $num); // '01', '02', ...
            $result[$key] = Yii::t('app', $label);
        }
        return $result;
    }

    /**
     * Devuelve días abreviados traducidos:
     * ['Lun', 'Mar', 'Mié', ...]
     */
    public static function getDaysTranslated(): array
    {
        $result = [];
        foreach (self::$daysNames as $abbr) {
            $result[] = Yii::t('app', $abbr);
        }
        return $result;
    }

    /**
     * Devuelve días en formato clave/valor para selects:
     * [
     *   '1' => 'Lun',
     *   '2' => 'Mar',
     *   ...
     * ]
     */
    public static function getDaysForSelect(): array
    {
        $result = [];
        foreach (self::$daysNames as $i => $abbr) {
            $index = $i + 1; // 1=lun...7=dom
            $result[(string) $index] = Yii::t('app', $abbr);
        }
        return $result;
    }

    public static function buildTwoMonthRange($year = null, $month = null)
    {
        if ($year === null || $month === null) {
            $now = new \DateTimeImmutable('now');
            $year = (int) $now->format('Y');
            $month = (int) $now->format('m');
        } else {
            $year = (int) $year;
            $month = (int) $month;
        }

        $start1 = new \DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
        $start2 = $start1->modify('+1 month');
        $end2 = $start2->modify('last day of this month');

        $fromDate = $start1->format('Y-m-d 00:00:00');
        $toDate = $end2->format('Y-m-d 23:59:59');

        return [$start1, $start2, $fromDate, $toDate];
    }


    /**
     * Renderiza un mes del calendario
     */
    public static function renderMonth(\DateTimeImmutable $startOfMonth, array $calendarData)
    {
        ob_start();

        $year = (int) $startOfMonth->format('Y');
        $month = (int) $startOfMonth->format('m');
        $lastDay = (int) $startOfMonth->format('t');
        $firstDow = (int) $startOfMonth->format('N');

        $monthTitle = Yii::t('app', self::$monthsTranslated[(int) $startOfMonth->format('n')]) . ' ' . $year;
        ?>

        <div class="card shadow-sm mb-4 calendario-mes">
            <div class="card-header bg-primary text-white text-center fw-bold text-capitalize">
                <?= Html::encode($monthTitle) ?>
            </div>

            <div class="card-body p-2">
                <table class="table table-bordered table-sm mb-0 calendar-table">
                    <thead>
                        <tr class="text-center small text-muted">
                            <?php foreach (self::getDaysTranslated() as $dName): ?>
                                <th style="width:14%;"><?= Html::encode(Yii::t('app', $dName)) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>

                    <tbody>
                        <tr>
                            <?php
                            // celdas vacías
                            for ($i = 1; $i < $firstDow; $i++) {
                                echo '<td class="calendar-day empty"></td>';
                            }

                            $dow = $firstDow;

                            for ($day = 1; $day <= $lastDay; $day++, $dow++) {

                                if ($dow > 7) {
                                    $dow = 1;
                                    echo '</tr><tr>';
                                }

                                $dateKey = sprintf('%04d-%02d-%02d', $year, $month, $day);
                                $items = $calendarData[$dateKey] ?? [];
                                ?>

                                <td class="calendar-day align-top">
                                    <div class="day-number fw-bold small mb-1"><?= $day ?></div>

                                    <?php if ($items): ?>
                                        <div class="reservas-list d-flex flex-column gap-1">
                                            <?php foreach ($items as $res): ?>
                                                <?= self::renderReservaBadge($res) ?>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                            <?php }

                            // completar fila
                            if ($dow !== 1) {
                                for (; $dow <= 7; $dow++) {
                                    echo '<td class="calendar-day empty"></td>';
                                }
                            }
                            ?>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }


    /**
     * Renderiza un badge de reserva
     */

    public static function renderReservaBadge(array $res)
    {
        $hash = $res['hash'];


        $reserva_cod = $res['codigo_reserva'] ?? '#' . $res['reserva_id'];

        
        $title = $res['cabana_nombre'] . ' ' . $res['locador'] . ' (' . $reserva_cod . ')';

        $color = $res['color'] ?? '#0d6efd';
        $slug = $res['estado_slug'] ?? null;

        $isPendingPayment = ($slug === 'confirmado-verificar-pago');

        // tooltip
        $tooltip = $isPendingPayment
            ? $title . ' - ' . Yii::t('app', 'Falta confirmar pago')
            : $title;

        // texto visible en el badge
        $label = $isPendingPayment ? '⚠ ' . $res['locador'] : $res['locador'];

        $options = [
            'href' => 'javascript:void(0);',
            'class' => 'badge text-start text-truncate reserva-link',
            'style' => "color:black;background-color:{$color};",
            'title' => $tooltip,
            'data-hash' => $hash,
            'data-reserva-id' => (int) $res['reserva_id'],
        ];

        if ($isPendingPayment) {
            // clase extra para el glow
            $options['class'] .= ' reserva-pendiente-pago';
        }

        return Html::tag(
            'a',
            Html::encode($label),
            $options
        );
    }


    /**
     * @param Reserva[] $reservas
     * @param string    $fromDate
     * @param string    $toDate
     * @param array     $selectedCabanas IDs de cabañas filtradas (opcional)
     * @return array [array $calendarData, array $cabanaColors]
     */
    public static function buildCalendarData(array $reservas, $fromDate, $toDate, array $selectedCabanas = [])
    {
        $calendarData = [];
        $cabanaColors = [];

        // normalizamos los IDs de cabañas a ints para comparar
        $selectedCabIds = [];
        foreach ($selectedCabanas as $sc) {
            if ($sc === '' || $sc === null) {
                continue;
            }
            $selectedCabIds[] = (int) $sc;
        }

        $rangeStart = new \DateTime($fromDate);
        $rangeEnd = new \DateTime($toDate);

        foreach ($reservas as $reserva) {

            // ... tu lógica de hash, fechas, etc. (no la repito para no hacer ruido)
            // supongamos que ya tenés $hash, $resDesde, $resHasta, etc.

            // ejemplo mínimo:
            $resDesde = new \DateTime($reserva->desde);
            $resHasta = new \DateTime($reserva->hasta);

            if ($resDesde < $rangeStart) {
                $resDesde = clone $rangeStart;
            }
            if ($resHasta > $rangeEnd) {
                $resHasta = clone $rangeEnd;
            }

            $resDesde->setTime(0, 0, 0);
            $resHasta->setTime(0, 0, 0);

            if ($resDesde > $resHasta) {
                continue;
            }

            foreach ($reserva->reservaCabanas as $resCab) {
                $cabana = $resCab->cabana;
                if (!$cabana) {
                    continue;
                }

                $cabId = (int) $cabana->id;

                // ⛔️ FILTRO ADICIONAL AQUÍ
                if (!empty($selectedCabIds) && !in_array($cabId, $selectedCabIds, true)) {
                    // si hay filtro de cabañas y esta no está en la lista → saltar
                    continue;
                }

                $color = $cabana->color_cabana ?: '#0d6efd';
                if ($color) {
                    $cabanaColors[$cabId] = $color;
                }

                $hash = $reserva->requestReservas[count($reserva->requestReservas) - 1]->hash ?? null;

                $codigo_reserva = $reserva->requestReservas[count($reserva->requestReservas) - 1]->codigo_reserva ?? null;

                for ($d = clone $resDesde; $d <= $resHasta; $d->modify('+1 day')) {
                    $key = $d->format('Y-m-d');

                    $calendarData[$key][] = [
                        'reserva_id' => $reserva->id,
                        'cabana_id' => $cabId,
                        'codigo_reserva' => $codigo_reserva,
                        'cabana_nombre' => $cabana->descr ?: ('Cabana #' . $cabId),
                        'color' => $color,
                        'hash' => $hash,
                        'estado_slug' => $reserva->estado ? $reserva->estado->slug : null,
                        'locador' => $reserva->locador ? $reserva->locador->denominacion : '',
                    ];
                }
            }
        }

        return [$calendarData, $cabanaColors];
    }

}
