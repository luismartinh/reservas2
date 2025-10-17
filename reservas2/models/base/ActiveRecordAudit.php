<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ActiveRecordAudit
 *
 * @author martinh
 */

namespace app\models\base;

use Exception;
use Yii;
use app\models\Auditoria;
use yii\web\HttpException;



class ActiveRecordAudit extends \yii\db\ActiveRecord
{


    private $se_borro = false;


    public function afterDelete()
    {
        parent::afterDelete();

        if (!$this->se_borro) {
            $this->_doAudit('delete', null);
        } else {
            $this->se_borro = false;
        }
    }

    public function deleteByPk($pk, $condition = '', $params = array())
    {
        $ret = parent::deleteByPk($pk, $condition, $params);
        $this->_doAudit('deletepk', null);
        $this->se_borro = true;
        return $ret;
    }


    public function afterSave($insert, $changedAttributes)
    {

        if (Yii::$app->session->has('skip afterSave')) {
            Yii::$app->session->remove('skip afterSave');
            return;
        }
        $scenario = strtolower($this->getScenario());

        if ($scenario == "default") {
            $scenario = $insert ? 'insert' : 'update';
        }
        $this->_doAudit($scenario, $changedAttributes);

        parent::afterSave($insert, $changedAttributes);
    }



    private function _doAudit($scenario, $changedAttributes)
    {

        if (Yii::$app->user->isGuest) {
            return true;
        }


        if (!Auditoria::hayQueAuditar($this->tableName())) {
            return false;
        }

        $user = Yii::$app->user->identity;
        $auditoria = new Auditoria();

        $auditoria->tabla = $this->tableName();
        $auditoria->user = $user->login;
        $auditoria->action = $scenario;

        $pk = [
            "old" => $this->oldPrimaryKey,
            "new" => $this->primaryKey
        ];

        $auditoria->pkId = $pk;


        $changes = [];
        if ($scenario == 'update') { // Si es una actualización
            foreach ($changedAttributes as $attribute => $oldValue) {
                $newValue = $this->$attribute;

                $changes[$attribute] = ["old" => $oldValue, "new" => $newValue];
                //Yii::info("El atributo '$attribute' cambió de '$oldValue' a '$newValue'", 'app');
            }
        }

        if ($scenario == 'insert') {

            foreach ((array) $this->attributes as $key => $value) {
                $changes[$key] = ["old" => "", "new" => $this->getAttribute($key)];
            }

        }

        if (count($changes) == 0) {
            $changes = "sin valores";
        }

        $auditoria->changes = $changes;


        if (!$auditoria->save()) {
            Yii::error("ERROR: Auditoria._doAudit() auditoria->save()" . implode(",", $auditoria->getErrorSummary(true)));
            throw new Exception("auditoria save " . implode(",", $auditoria->getErrorSummary(true)));
        }
        return true;
    }

}
