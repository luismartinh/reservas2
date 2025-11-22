<?php

namespace app\models;

use \app\models\base\Reserva as BaseReserva;
use yii\db\Expression;

/**
 * This is the model class for table "reservas".
 */
class Reserva extends BaseReserva
{

    /**
     * Verifica si hay solape entre una reserva y otra.
     * Un solape se produce cuando una reserva nueva se encuentra en el rango de fechas de otra reserva
     * de la misma cabaña. Es decir, si la nueva reserva se encuentra en el rango [desde, hasta) de otra
     * reserva, se considera un solape.
     * 
     * @param string $desdeYmd fecha de inicio de la reserva en formato 'Ymd'
     * @param string $hastaYmd fecha de fin de la reserva en formato 'Ymd'
     * @return \yii\db\ActiveQuery
     */
    public static function cabanasLibres($desdeYmd, $hastaYmd)
    {

        $query = Cabana::find()->alias('c');
        $query->andWhere(['c.activa' => 1]);

        // Subconsulta: reserva(s) de la misma cabaña que se SOLAPEN con [desde, hasta)
        // No hay solape si:  nueva_hasta <= r.desde  OR  nueva_desde >= r.hasta
        // Hay solape si NO se cumple lo anterior:
        //     NOT ( :hasta <= r.desde OR :desde >= r.hasta )
        $subSolape = (new \yii\db\Query())
            ->from(['rc' => ReservaCabana::tableName()])
            ->innerJoin(['r' => Reserva::tableName()], 'r.id = rc.id_reserva')
            ->where('rc.id_cabana = c.id')
            ->andWhere(new Expression('NOT (:hasta <= r.desde OR :desde >= r.hasta)'))
            ->params([
                ':desde' => $desdeYmd,
                ':hasta' => $hastaYmd,
            ]);

        // Mantener SOLO cabañas para las cuales NO exista una reserva solapada
        $query->andWhere(['not exists', $subSolape]);

        return $query;

    }



    /**
     * Verifica si todas las cabañas de una solicitud de reserva estan reservadas
     * 
     * @param \app\models\RequestReserva $reservaReq solicitud de reserva
     * @return bool true si todas las cabañas estan reservadas, false en caso contrario
     */
    public static function estanReservadas($reservaReq)
    {


        if (!$reservaReq) {
            return true;
        }

        if (!$reservaReq->requestCabanas) {
            return true;
        }

        if (count($reservaReq->requestCabanas) == 0) {
            return true;
        }

        // -------------------------------------------------------------
        // VALIDACIÓN DE SOLAPAMIENTO DE FECHAS PARA CADA CABAÑA
        // -------------------------------------------------------------

        $nuevoDesde = $reservaReq->desde;  // Y-m-d H:i:s
        $nuevoHasta = $reservaReq->hasta;  // Y-m-d H:i:s

        foreach ($reservaReq->requestCabanas as $rc) {

            $idCabana = $rc->id_cabana;

            // Buscar reservas existentes que se solapen
            $existeSolape = \app\models\ReservaCabana::find()
                ->alias('rca')
                ->joinWith(['reserva r'])
                ->where(['rca.id_cabana' => $idCabana])
                ->andWhere([
                    'or',
                    ['between', 'r.desde', $nuevoDesde, $nuevoHasta],
                    ['between', 'r.hasta', $nuevoDesde, $nuevoHasta],
                    [
                        'and',
                        ['<=', 'r.desde', $nuevoDesde],
                        ['>=', 'r.hasta', $nuevoHasta],
                    ],
                ])
                ->exists();

            if ($existeSolape) {
                return true;
            }
        }

        return false;

    }



    public static function estanYaReservadas($desdeYmdHis, $hastaYmdHis, $idsCabanas)
    {

        // -------------------------------------------------------------
        // VALIDACIÓN DE SOLAPAMIENTO DE FECHAS PARA CADA CABAÑA
        // -------------------------------------------------------------

        $nuevoDesde = $desdeYmdHis;  // Y-m-d H:i:s
        $nuevoHasta = $hastaYmdHis;  // Y-m-d H:i:s

        foreach ($idsCabanas as $idCabana) {

            // Buscar reservas existentes que se solapen
            $existeSolape = \app\models\ReservaCabana::find()
                ->alias('rca')
                ->joinWith(['reserva r'])
                ->where(['rca.id_cabana' => $idCabana])
                ->andWhere([
                    'or',
                    ['between', 'r.desde', $nuevoDesde, $nuevoHasta],
                    ['between', 'r.hasta', $nuevoDesde, $nuevoHasta],
                    [
                        'and',
                        ['<=', 'r.desde', $nuevoDesde],
                        ['>=', 'r.hasta', $nuevoHasta],
                    ],
                ])
                ->exists();

            if ($existeSolape) {
                return true;
            }
        }

        return false;

    }

}
