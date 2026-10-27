<?php
namespace app\components;

use app\models\RequestReserva;
use Yii;

/** Shared locking read for the current payment record, inside the caller's transaction. */
class PagosReserva
{
    public static function bloquear($id)
    {
        if (!Yii::$app->db->transaction || !Yii::$app->db->transaction->isActive) {
            throw new \LogicException('Se requiere una transacción.');
        }
        $command = RequestReserva::find()->where(['id' => $id])->createCommand();
        $params = $command->params;
        $command->setSql($command->getSql() . ' FOR UPDATE')->bindValues($params);
        $row = $command->queryOne();
        if (!$row) { throw new \DomainException('La solicitud ya no existe.'); }
        $model = new RequestReserva();
        RequestReserva::populateRecord($model, $row);
        $model->afterFind();
        return $model;
    }

    public static function reubicarArchivo($id, $oldPath, $newPath)
    {
        $tx = Yii::$app->db->beginTransaction();
        try {
            $request = self::bloquear($id);
            $payments = $request->registro_pagos ?? [];
            foreach ($payments as &$payment) {
                if (($payment['archivo'] ?? null) === $oldPath) { $payment['archivo'] = $newPath; }
            }
            unset($payment);
            $request->registro_pagos = $payments;
            if (!$request->save(false, ['registro_pagos'])) { throw new \RuntimeException('No se pudo actualizar el comprobante.'); }
            $tx->commit();
        } catch (\Throwable $e) {
            if ($tx->isActive) { $tx->rollBack(); }
            throw $e;
        }
    }

    public static function exigirSinCambios(RequestReserva $snapshot)
    {
        $current = self::bloquear($snapshot->id);
        foreach (['pagado', 'total', 'registro_pagos', 'id_estado', 'id_reserva'] as $field) {
            if ($current->$field != $snapshot->$field) {
                throw new \DomainException('Los pagos o el estado cambiaron. Recargá la solicitud antes de continuar.');
            }
        }
        return $current;
    }
}
