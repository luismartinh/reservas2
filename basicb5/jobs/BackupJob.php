<?php
namespace app\jobs;

use app\config\Niveles;
use app\models\Notificaciones;
use Yii;
use yii\base\BaseObject;
use yii\bootstrap5\Html;
use yii\queue\JobInterface;

class BackupJob extends BaseObject implements JobInterface
{
    public $backupFile; // El nombre del archivo de backup
    public $user_id;

    public static $lockFilePath;


    public static function isWorking()
    {
        self::$lockFilePath = Yii::getAlias('@runtime') . "/backup_db.lock";

        return file_exists(self::$lockFilePath);

    }

    public static function resetLockFile()
    {

        self::$lockFilePath = Yii::getAlias('@runtime') . "/backup_db.lock";

        if (file_exists(self::$lockFilePath)) {
            unlink(self::$lockFilePath);
        }

    }

    public static function createLockFile()
    {

        self::$lockFilePath = Yii::getAlias('@runtime') . "/backup_db.lock";
        if (!file_exists(self::$lockFilePath)) {
            file_put_contents(self::$lockFilePath, '1');
        }
    }


    public function execute($queue)
    {

        if (BackupJob::isWorking()) {
            Yii::error("Backup ya en proceso");
            return;
        }

        BackupJob::createLockFile();

        // Obtén la conexión de la base de datos
        $db = Yii::$app->db;

        // Abre el archivo de respaldo para escribir
        $file = fopen($this->backupFile, 'w');

        // Desactivar la verificación de claves foráneas
        fwrite($file, "SET FOREIGN_KEY_CHECKS=0;\n\n");

        // Obtén todas las tablas de la base de datos
        $tables = $db->createCommand('SHOW TABLES')->queryColumn();

        // Escribir la estructura de cada tabla y los datos
        foreach ($tables as $table) {
            // Escribir el DROP TABLE IF EXISTS para eliminar la tabla si existe
            fwrite($file, "DROP TABLE IF EXISTS `{$table}`;\n");

            // Escribir la estructura de la tabla (CREATE TABLE)
            $createTableQuery = $db->createCommand("SHOW CREATE TABLE `{$table}`")->queryOne();
            fwrite($file, $createTableQuery['Create Table'] . ";\n\n");

            // Escribir los datos de la tabla (INSERT INTO)
            $rows = $db->createCommand("SELECT * FROM `{$table}`")->queryAll();
            foreach ($rows as $row) {
                $columns = array_keys($row);
                //$values = array_map([$db, 'quoteValue'], array_values($row)); // Escapar los valores

                // Manejo de valores NULL y cadenas vacías correctamente
                $values = array_map(function ($value) use ($db) {
                    if ($value === null) {
                        return 'NULL'; // Se escribe NULL sin comillas
                    } elseif ($value === '') {
                        return "''"; // Se mantiene la cadena vacía con comillas simples
                    } else {
                        return $db->quoteValue($value); // Escapa otros valores normalmente
                    }
                }, array_values($row));

                $insertQuery = "INSERT INTO `{$table}` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $values) . ");\n";
                fwrite($file, $insertQuery);
            }

            fwrite($file, "\n\n");
        }

        // Reactivar la verificación de claves foráneas
        fwrite($file, "SET FOREIGN_KEY_CHECKS=1;\n");

        // Cerrar el archivo de respaldo
        fclose($file);

        BackupJob::resetLockFile();

        $msg = $this->_getMensajeListo();

        Notificaciones::Nueva([$this->user_id], 'parametros_generales', $msg, $this->backupFile);
    }

    private function _getMensajeListo()
    {

        $filename = basename($this->backupFile);
        $date = (new \DateTime())->format('d-m-Y H:i:s');

        $lnk = '<a class="btn btn-primary" href="/basicb5/web/index.php?r=auditoria%2Fdownload-backup&filename=' . $filename . '" data-pjax="0">Descargar Backup</a>';
        $ret = "Backup listo. Fecha: $date $lnk";

        return $ret;
    }
}
