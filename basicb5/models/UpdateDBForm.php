<?php

namespace app\models;

use Yii;
use yii\base\Model;
use function PHPUnit\Framework\throwException;

/**
 * UpdateDBForm
 *
 * @property-read Identificador|null $user
 *
 */
class UpdateDBForm extends Model
{
    public $update_files = [];
    public $update_from;
    public $update_to;



    public $downgrade_to;


    public $last_update;

    public $directory = 'dbversion/';
    public $downgradeDirectory = 'dbversion/down/';


    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [

            [['update_from', 'update_to'], 'required', 'on' => 'upgrade'],
            [['update_from', 'update_to', 'downgrade_to', 'last_update'], 'integer'],
            [['downgrade_to'], 'required', 'on' => 'downgrade'],
            [
                'update_to',
                'compare',
                'compareAttribute' => 'update_from',
                'operator' => '>=',
                'message' => 'El campo "hasta" debe ser mayor o igual a "desde".',
                'on' => 'upgrade'
            ],
            [
                'last_update',
                'compare',
                'compareAttribute' => 'downgrade_to',
                'operator' => '>',
                'message' => 'La version actual debe ser mayor  "hasta".',
                'on' => 'downgrade'
            ],
        ];

    }

    public function attributeLabels()
    {
        return [
            'update_from' => 'Actualizar desde',
            'update_to' => 'Actualizar hasta',
            'last_update' => 'Version actual',
            'downgrade_to' => 'Deshacer hasta',

        ];
    }

    public function attributeHints()
    {
        return [
            'update_from' => 'Indique desde que actualizacion se ejecutara',
            'update_to' => 'Indique hasta que actualizacion se ejecutara',
            'last_update' => 'Version actual en la base de datos',
            'downgrade_to' => 'Indique hasta que version deshacer',

        ];
    }


    public function setDefault()
    {

        $this->last_update = ParametrosGenerales::getParametro("DBVRESION")->valor;
        //$this->last_update = json_decode(ParametrosGenerales::getParametro("DBVRESION")->valor);
        $this->update_from = (int)$this->last_update + 1;


        $this->update_files = $this->_getSqlFiles();

        $this->update_to = $this->update_files[count($this->update_files) - 1]['nombre'];
    }



    public function setDefaultDowngrade()
    {

        $this->last_update = ParametrosGenerales::getParametro("DBVRESION")->valor;

        $this->update_files = $this->_getSqlFilesdowngrade();

        $this->downgrade_to = $this->update_files[count($this->update_files) - 1]['nombre'];
    }

    public function getListDropDown()
    {
        $ret = [];

        foreach ($this->update_files as $file) {
            if ($file['nombre'] <= $this->last_update)
                continue;
            $ret[$file['nombre']] = $file['nombre'];
        }

        return $ret;
    }

    public function getListDropDownDowngrade()
    {
        $ret = [];

        foreach ($this->update_files as $file) {
            if ($file['nombre'] >= $this->last_update)
                continue;
            $ret[$file['nombre']] = $file['nombre'];
        }

        return $ret;
    }


    private function _getSqlFiles()
    {
        $directory = Yii::getAlias('@app/' . $this->directory);
        $files = scandir($directory);
        $sqlFiles = [];

        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
                $sqlFiles[] = $file;
            }
        }

        // Ordenar usando orden natural (numérico)
        natsort($sqlFiles);

        $result = [];
        foreach ($sqlFiles as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            $result[] = ['nombre' => $name, 'archivo' => $directory . $file];
        }

        return $result;

        /*
                [
                    ['nombre' => '1', 'archivo' => 'dbversion/1.sql'],
                    ['nombre' => '2', 'archivo' => 'dbversion/2.sql'],
                    ['nombre' => '3', 'archivo' => 'dbversion/3.sql'],
                    ['nombre' => '10', 'archivo' => 'dbversion/10.sql'],
                ]

        */
    }



    private function _getSqlFilesdowngrade()
    {
        $directory = Yii::getAlias('@app/' . $this->downgradeDirectory);
        $files = scandir($directory);
        $sqlFiles = [];

        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
                $sqlFiles[] = $file;
            }
        }

        // Ordenar usando orden natural (numérico)
        natsort($sqlFiles);

        // Convertir a orden descendente
        $sqlFiles = array_reverse($sqlFiles);

        $result = [];
        foreach ($sqlFiles as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            $result[] = ['nombre' => $name, 'archivo' => $directory . $file];
        }

        return $result;

    }

    public function actualizar()
    {
        $processed = [];

        if (!$this->validate()) {
            return [
                'st' => 'error',
                'msg' => implode(", ", $this->getErrorSummary(true)),
                'processed' => $processed
            ];
        }

        if ($this->update_files[count($this->update_files) - 1]['nombre'] <= $this->last_update) {
            return ['st' => 'error', 'msg' => 'No hay actualizaciones disponibles', 'processed' => $processed];
        }

        $transaction = Yii::$app->db->beginTransaction();



        foreach ($this->update_files as $file) {
            if ($file['nombre'] <= $this->last_update)
                continue;

            if ($file['nombre'] < $this->update_from)
                continue;

            if ($file['nombre'] > $this->update_to)
                continue;


            $res = $this->_ejecutarSqlFile($file['nombre'], $file['archivo']);

            if ($res['st'] == 'error') {

                $transaction->rollBack();
                return ['st' => 'error', 'msg' => $res['msg'], 'processed' => $processed];
            }


            $processed[] = $file['archivo'];
        }

        $transaction->commit();

        $this->setDefault();

        return ['st' => 'ok', 'msg' => 'Actualizacion realizada con exito', 'processed' => $processed];

    }


    public function downgrade()
    {
        $processed = [];

        if (!$this->validate()) {
            return [
                'st' => 'error',
                'msg' => implode(", ", $this->getErrorSummary(true)),
                'processed' => $processed
            ];
        }

        if ($this->update_files[count($this->update_files) - 1]['nombre'] >= $this->last_update) {
            return ['st' => 'error', 'msg' => 'No hay actualizaciones disponibles', 'processed' => $processed];
        }

        $transaction = Yii::$app->db->beginTransaction();



        foreach ($this->update_files as $file) {

            if ($file['nombre'] > $this->last_update)
                continue;


            if ($file['nombre'] <= $this->downgrade_to)
                continue;


            $res = $this->_ejecutarSqlFile($file['nombre'] - 1, $file['archivo']);

            if ($res['st'] == 'error') {

                $transaction->rollBack();
                return ['st' => 'error', 'msg' => $res['msg'], 'processed' => $processed];
            }


            $processed[] = $file['archivo'];
        }

        $transaction->commit();

        $this->setDefault();

        return ['st' => 'ok', 'msg' => 'rollbak realizado con exito', 'processed' => $processed];

    }


    private function _ejecutarSqlFile($version, $archivo)
    {
        //'archivo' => 'dbversion/1.sql

        // Nombre del archivo SQL
        // Ruta completa del archivo SQL
        $filePath = $archivo;

        // Verificar si el archivo existe
        if (!file_exists($filePath)) {
            return ['st' => 'error', 'msg' => "El archivo no existe: $filePath"];
        }

        // Leer el contenido del archivo SQL
        $sql = file_get_contents($filePath);

        if (!$sql) {
            return ['st' => 'error', 'msg' => "Error al leer el archivo: $filePath"];
        }


        try {
            // Ejecutar el SQL
            $rows = Yii::$app->db->createCommand($sql)->execute();

            $error = Yii::$app->db->createCommand("SHOW ERRORS")->queryAll();
            if (!empty($error)) {
                return ['st' => 'error', 'msg' => "Error al ejecutar el script: " . $error[0]['Message']];
            }



            if (!ParametrosGenerales::updateParametro("DBVRESION", $version)) {

                return ['st' => 'error', 'msg' => "Error al actualizar el parametro DBVRESION:"];
            }





        } catch (\Exception $e) {

            return ['st' => 'error', 'msg' => "Error al ejecutar el script: " . $e->getMessage()];
        }

        return ['st' => 'ok', 'msg' => "Script $filePath ejecutado con éxito."];

    }




}