<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\db\Connection;
use yii\helpers\Console;

class ExportSchemaController extends Controller
{
    public function actionIndex()
    {
        $db = Yii::$app->db;
        $schemaData = [];

        // Obtener todas las tablas de la base de datos
        $tables = $db->createCommand("SHOW TABLES")->queryColumn();

        foreach ($tables as $table) {
            // Obtener la descripción de la tabla
            $columns = $db->createCommand("DESCRIBE `$table`")->queryAll();

            // Guardar solo los valores requeridos
            $schemaData[$table] = array_map(function ($column) {
                return [
                    'Field' => $column['Field'],
                    'Type' => $column['Type'],
                    'Null' => $column['Null'],
                    'Key' => $column['Key'],
                    'Default' => $column['Default'],
                    'Extra' => $column['Extra'],
                ];
            }, $columns);
        }

        // Guardar en un archivo JSON en la carpeta runtime
        $filePath = Yii::getAlias('@runtime/schema.json');
        file_put_contents($filePath, json_encode($schemaData, JSON_PRETTY_PRINT));

        echo "✅ Esquema exportado en: $filePath\n";
    }

    public function actionCreateExportUsuarios()
    {
        $db = Yii::$app->db;
        $sql = "
            SELECT q.usuario, q.login, q.nivel, q.activo, q.grupo_acceso, q.permiso, q.acceso
            FROM (
                SELECT
                    CONCAT(u.nombre, ' ', u.apellido) AS usuario, 
                    u.login, u.nivel, u.activo,
                    ga.descr AS grupo_acceso,
                    a.descr AS permiso, a.acceso
                FROM usuario u
                LEFT JOIN grupos_accesos_usuarios gau ON u.id = gau.id_usuario
                LEFT JOIN grupo_acceso ga ON gau.id_grupo_acceso = ga.id
                LEFT JOIN grupos_accesos_accesos gaa ON ga.id = gaa.id_grupo_acceso
                LEFT JOIN acceso a ON gaa.id_acceso = a.id
                UNION
                SELECT
                    CONCAT(u.nombre, ' ', u.apellido) AS usuario,
                    u.login, u.nivel, u.activo,
                    NULL AS grupo_acceso,
                    a.descr AS permiso, a.acceso
                FROM usuario u
                LEFT JOIN usuarios_accesos ua ON u.id = ua.id_usuario
                LEFT JOIN acceso a ON ua.id_accesos = a.id
            ) q
            WHERE q.acceso IS NOT NULL
        ";

        try {
            // Ejecutar la consulta
            $command = $db->createCommand($sql);
            $result = $command->queryAll();

            // Definir la ruta del archivo JSON
            $filePath = Yii::getAlias('@runtime/export_users.json');

            // Guardar en archivo JSON
            file_put_contents($filePath, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            // Mensaje de éxito
            $this->stdout("Exportación completada. Archivo guardado en: $filePath\n", Console::FG_GREEN);
        } catch (\Exception $e) {
            // Manejo de errores
            $this->stderr("Error: " . $e->getMessage() . "\n", Console::FG_RED);
        }
    }
}
