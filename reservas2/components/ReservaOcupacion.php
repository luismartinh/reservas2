<?php
namespace app\components;

use app\models\Cabana;
use app\models\Reserva;
use app\models\ReservaCabana;
use Yii;
use yii\db\Query;

/** Serializes the booking writers on existing cabin rows; requires an active transaction. */
class ReservaOcupacion
{
    public static function bloquear(array $ids)
    {
        if (!Yii::$app->db->transaction || !Yii::$app->db->transaction->isActive) {
            throw new \LogicException('Se requiere una transacción.');
        }
        $ids = array_values(array_unique(array_map('intval', $ids)));
        sort($ids, SORT_NUMERIC);
        if (!$ids) { throw new \InvalidArgumentException('Seleccioná cabañas.'); }
        $query = (new Query())->select('id')->from(Cabana::tableName())->where(['id' => $ids])->orderBy('id');
        if (count(self::actual($query)) !== count($ids)) {
            throw new \DomainException('Alguna cabaña ya no existe.');
        }
    }

    /** A current locking read also sees bookings committed while waiting for the cabin lock. */
    private static function actual(Query $query)
    {
        $command = $query->createCommand();
        $params = $command->params;
        $command->setSql($command->getSql() . ' FOR UPDATE')->bindValues($params);
        return $command->queryAll();
    }

    public static function exigirLibres(array $ids, $desde, $hasta, $horariosCabana = false)
    {
        self::bloquear($ids);
        $query = (new Query())->select('rc.id')->from(['rc' => ReservaCabana::tableName()])
            ->innerJoin(['r' => Reserva::tableName()], 'r.id = rc.id_reserva')
            ->where(['rc.id_cabana' => $ids]);
        if ($horariosCabana) {
            $query->innerJoin(['c' => Cabana::tableName()], 'c.id = rc.id_cabana')
                ->andWhere('NOT (TIMESTAMP(:hasta,c.checkout) <= TIMESTAMP(DATE(r.desde),c.checkin)'
                    . ' OR TIMESTAMP(:desde,c.checkin) >= TIMESTAMP(DATE(r.hasta),c.checkout))',
                    [':desde' => $desde, ':hasta' => $hasta]);
        } else {
            // Preserve the legacy writers' inclusive timestamp rule.
            $query->andWhere(['and', ['<=', 'r.desde', $hasta], ['>=', 'r.hasta', $desde]]);
        }
        if (self::actual($query)) {
            throw new \DomainException('Alguna cabaña ya está reservada en ese período. Consultá nuevamente la disponibilidad.');
        }
    }
}
