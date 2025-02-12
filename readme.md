Instalar desde 0:

Pasos:
(ejecutar)

1- Crea la imagen:
docker compose build php

2- Levantar contenedor:
docker compose up -d
(remover imagenes ya generadas)
docker compose up -d --remove-orphans

3- Instalar yii2 dentro del contenedor:
docker compose run php bash
(Y dentro del contenedor) 

composer create-project --prefer-dist yiisoft/yii2-app-basic basicb5
o 
dentro de basicb5/
composer install 

sacar web/.htaccess
dentro de web/
sudo chmod 777 -R assets/


en /
sudo chmod 777 -R views/
sudo chmod 777 -R assets/

4- Ver la app
http://localhost:8281/basicb5/web/


5- instalar bootstrap5
https://stackoverflow.com/questions/73560764/how-upgrade-yii-bootstrap-3-to-bootstrap-5
https://github.com/yiisoft/yii2-bootstrap5


docker compose run php bash
(Y dentro del contenedor en basicb5/) 
composer remove yiisoft/yii2-bootstrap
composer require --prefer-dist yiisoft/yii2-bootstrap5:"^2.0"
composer require twbs/bootstrap:5.3.3

https://getbootstrap.com/docs/5.3/getting-started/introduction/




Forms components:

https://demos.krajee.com/
https://www.w3schools.com/html/html_entities.asp


Set custom checkbox:

´´´
    echo $form->field($model, 'activo',
    [
        'template' => '{input}&nbsp;{label}{error}{hint}',
        'labelOptions' => ['class' => 'cbx-label']    
    ]
    )->widget(kartik\checkbox\CheckboxX::classname(), [
        'autoLabel'=>false,
        'pluginOptions'=>[
            'threeState'=>false,
            'size'=>'xl',
            'iconChecked'=>'<i class="bi bi-check-square-fill text-success" ></i>',
            'iconUnchecked'=>'<i class="bi bi-dash-square-fill text-danger"></i>',
            'iconNull'=>'<i class="bi bi-exclamation-lg text-danger"></i>'        
            ]
    ]); 
´´´


DateRangePiker:
https://www.daterangepicker.com/#usage

JsonEditor:
https://github.com/dmitry-kulikov/yii2-json-editor
composer require kdn/yii2-json-editor "*"


backups desde yii2:

comando cli:
mysqldump --user=root --password=userbt51234 --host=db --databases bt5 --routines --skip-comments > /var/www/html/basicb5/runtime/backup_bt5.sql 2>&1


Debe estar instalado mysql-client en el service de php:

(agregar en DockerFile)
RUN apt-get update && apt-get install -y mysql-client

docker-compose down
docker-compose build
docker-compose up -d


agregar la action en el controller:

´´´
    public function actionBackup()
    {

        $menu = new \app\models\Menu();
        $menu->descr = "Auditoria backup de la base de datos";
        $menu->label = "Backup";
        $menu->menu = (string) RootMenu::CONFIG;
        $menu->menu_path = "Seguridad/Backup";
        $menu->url = Yii::$app->controller->id . '/backup';

        $permiso = Identificador::autorizar(
            Yii::$app->user->identity,
            Yii::$app->controller->id . '/backup',
            "Auditoria backup de la base de datos",
            $menu
        );

        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t("app", $permiso["msg"]));
            return $this->redirect($permiso["redirect"]);
        }


        // Obtén la conexión de la base de datos
        $db = Yii::$app->db;

        // Archivo de backup
        $backupFile = Yii::getAlias('@runtime') . "/backup.sql";

        // Abre el archivo de respaldo para escribir
        $file = fopen($backupFile, 'w');

        // Desactivar la verificación de claves foráneas (evitar problemas con las claves foráneas)
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
                $values = array_map([$db, 'quoteValue'], array_values($row)); // Escapar los valores
                $insertQuery = "INSERT INTO `{$table}` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $values) . ");\n";
                fwrite($file, $insertQuery);
            }

            fwrite($file, "\n\n");
        }

        // Reactivar la verificación de claves foráneas
        fwrite($file, "SET FOREIGN_KEY_CHECKS=1;\n");

        // Cerrar el archivo de respaldo
        fclose($file);

        Notificaciones::NotificarANivel(Niveles::SYSADMIN, 'parametros_generales', "Se descargo un backup en backup_bt5.sql");
        // Retornar el archivo generado para descarga
        return Yii::$app->response->sendFile($backupFile, "backup_bt5.sql", ['mimeType' => 'application/sql'])
            ->on(Response::EVENT_AFTER_SEND, function ($event) {
                unlink($event->data);  // Eliminar el archivo temporal después de enviarlo
            }, $backupFile);
    }
´´´


