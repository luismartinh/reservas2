<?php

namespace app\controllers;

use app\config\Niveles;
use app\config\RootMenu;
use app\jobs\BackupJob;
use app\models\Auditoria;
use app\models\AuditoriaSearch;
use app\models\Identificador;
use app\models\Notificaciones;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\base\InvalidConfigException;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\Request;
use yii\web\Response;
use Yii;



use app\controllers\base\AuditoriaController as BaseAuditoriaController;

/**
 * This is the class for controller "AuditoriaController".
 */
class AuditoriaController extends BaseAuditoriaController
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return ArrayHelper::merge(
            parent::behaviors(),
            [
                'access' => [
                    'class' => AccessControl::class,
                    'rules' => [
                        [
                            'allow' => true,
                            'actions' => [
                                'index',
                                'view',
                                'create',
                                'update',
                                'delete',
                                'ver-detalle',
                                'delete-todas',
                                'backup',
                                'download-backup',
                                'update-db',
                                'rollback-update'
                            ],
                            'roles' => ['@'],
                        ],
                    ]
                ]
            ]
        );
    }

    /**
     * Lists all Auditoria models.
     *
     * @throws InvalidConfigException
     * @return string|Response
     */
    public function actionIndex()
    {

        $menu = new \app\models\Menu();
        $menu->descr = "Ver Auditoria cambios";
        $menu->label = "Auditoria";
        $menu->menu = (string) RootMenu::CONFIG;
        $menu->menu_path = "Seguridad/Auditoria";
        $menu->url = Yii::$app->controller->id . '/index';

        $permiso = Identificador::autorizar(
            Yii::$app->user->identity,
            Yii::$app->controller->id . '/index',
            "Ver Auditoria cambios",
            $menu
        );

        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t("app", $permiso["msg"]));
            return $this->redirect($permiso["redirect"]);
        }

        $searchModel = Yii::createObject(AuditoriaSearch::class);
        $dataProvider = $searchModel->searchIndex($this->request->get());

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }

    /**
     * Displays a single Auditoria model.
     *
     * @param integer $id
     *
     * @throws NotFoundHttpException
     * @return string
     */
    public function actionView($id)
    {
        throw new HttpException(403, "Forbidden");

    }

    /**
     * Creates a new Auditoria model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     *
     * @throws yii\base\InvalidConfigException
     * @return string|Response
     */
    public function actionCreate()
    {

        throw new HttpException(403, "Forbidden");
    }

    /**
     * Updates an existing Auditoria model.
     * If update is successful, the browser will be redirected to the 'view' page.
     *
     * @param integer $id
     *
     * @throws NotFoundHttpException
     * @return string|Response
     */
    public function actionUpdate($id)
    {
        throw new HttpException(403, "Forbidden");
    }

    /**
     * Deletes an existing Auditoria model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     *
     * @param integer $id
     *
     * @throws \Throwable
     * @return Response
     */
    public function actionDelete($id)
    {
        throw new HttpException(403, "Forbidden");
    }


    public function actionVerDetalle()
    {

        $permiso = Identificador::autorizar(
            Yii::$app->user->identity,
            Yii::$app->controller->id . '/index',
            "Ver Auditoria cambios",
            null
        );

        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t("app", $permiso["msg"]));
            return $this->redirect($permiso["redirect"]);
        }

        if (isset($_POST['expandRowKey'])) {


            $model = $this->findModel($_POST['expandRowKey']);


            return Yii::$app->controller->renderPartial('_verDetalle', [
                'model' => $model,
            ]);

        } else {
            return '<div class="alert alert-danger">No se encontraron pagos</div>';
        }
    }


    /**
     * Deletes an existing  model.
     * If deletion is successful, 
     *
     * @param integer $id
     *
     * @throws \Throwable
     * @return Response
     */
    public function actionDeleteTodas()
    {


        $permiso = Identificador::autorizar(
            Yii::$app->user->identity,
            Yii::$app->controller->id . '/delete-todas',
            "Auditoria eliminar todas",
            null
        );

        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t("app", $permiso["msg"]));
            return $this->redirect($permiso["redirect"]);
        }


        try {
            Auditoria::deleteAll();
            Notificaciones::NotificarANivel(Niveles::SYSADMIN, 'parametros_generales', "Se eliminaron todas las auditorias (" . Yii::$app->user->identity->login . ")");
            Yii::$app->session->setFlash('success', Yii::t("app", 'Se elimino correctamente'));
        } catch (\Exception $e) {
            Yii::error("ERROR:" . Yii::$app->controller->id . "/delete-todas " . ($e->errorInfo[2] ?? $e->getMessage()));
            Yii::$app->getSession()->addFlash('error', $e->errorInfo[2] ?? $e->getMessage());
        }

        return $this->redirect(['index']);
    }



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


        if (BackupJob::isWorking()) {
            Yii::$app->session->setFlash('warning', Yii::t("app", 'El proceso de backup ya se encuentra en ejecución'));
            return $this->goBack();
        }
        // Definir el nombre del archivo de backup
        $backupFile = Yii::getAlias('@runtime') . "/backup_db_" . Yii::$app->user->identity->id . ".sql";

        $job = new \app\jobs\BackupJob([
            'backupFile' => $backupFile,
            'user_id' => Yii::$app->user->identity->id
        ]);

        // Crear y poner el job en la cola
        Yii::$app->queue->push(new \app\jobs\BackupJob([
            'backupFile' => $backupFile,
            'user_id' => Yii::$app->user->identity->id
        ]));

        // Retornar una respuesta indicando que el proceso ha comenzado
        Yii::$app->session->setFlash('success', Yii::t("app", 'El proceso de backup ha comenzado. Recibirás una notificación para descargarlo una vez esté listo'));
        return $this->goBack();

    }

    public function actionDownloadBackup($filename)
    {

        $permiso = Identificador::autorizar(
            Yii::$app->user->identity,
            Yii::$app->controller->id . '/backup',
            "Auditoria backup de la base de datos",
            null
        );

        $filePath = Yii::getAlias('@runtime') . '/' . $filename;

        if (file_exists($filePath)) {
            return Yii::$app->response->sendFile($filePath, $filename, [
                'mimeType' => 'application/octet-stream',
                'inline' => false, // Esto forza la descarga
            ])->on(Response::EVENT_AFTER_SEND, function ($event) {
                unlink($event->data);  // Eliminar el archivo temporal después de enviarlo
            }, $filePath);
            ;
        } else {
            //throw new NotFoundHttpException('El archivo no existe.');
            Yii::$app->session->setFlash('danger', Yii::t("app", 'El archivo ya fue descargado'));
            return $this->goBack();

        }
    }



    public function actionUpdateDb()
    {

        $menu = new \app\models\Menu();
        $menu->descr = "Actualizar la version de la base de datos";
        $menu->label = "UpgradeDB";
        $menu->menu = (string) RootMenu::CONFIG;
        $menu->menu_path = "Seguridad/UpgradeDB";
        $menu->url = Yii::$app->controller->id . '/update-db';

        $permiso = Identificador::autorizar(
            Yii::$app->user->identity,
            Yii::$app->controller->id . '/update-db',
            "Auditoria actualizar la version de la base de datos",
            $menu
        );

        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t("app", $permiso["msg"]));
            return $this->redirect($permiso["redirect"]);
        }


        $model = new \app\models\UpdateDBForm();
        $model->scenario = 'upgrade';

        $model->setDefault();

        $processed = [];

        try {

            if ($model->load($this->request->post())) {


                $resp = $model->actualizar();

                if ($resp['st'] == 'ok') {
                    $processed = $resp['processed'];
                    $cant = count($processed);
                    Notificaciones::NotificarANivel(Niveles::SYSADMIN, 'parametros_generales', "Se ejecutaron $cant actualizaciones actual:{$model->last_update} ");
                    Yii::$app->session->setFlash('success', Yii::t("app", "Se ejecutaron $cant actualizaciones actual:{$model->last_update} "));
                } else {
                    $model->addError('_exception', $resp['msg']);
                }

            }
            if (!Yii::$app->request->isPost) {
                $model->load($this->request->get());
            }
        } catch (\Exception $e) {
            $model->addError('_exception', $e->errorInfo[2] ?? $e->getMessage());
            Yii::error("ERROR:" . Yii::$app->controller->id . "/update-db " . ($e->errorInfo[2] ?? $e->getMessage()));
        }
        return $this->render('dbversion/update', ['model' => $model, 'processed' => $processed]);


    }


    public function actionRollbackUpdate()
    {


        $permiso = Identificador::autorizar(
            Yii::$app->user->identity,
            Yii::$app->controller->id . '/rollback-update',
            "Auditoria deshacer actualizaciones la version de la base de datos",
            null
        );

        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t("app", $permiso["msg"]));
            return $this->redirect($permiso["redirect"]);
        }

        $model = new \app\models\UpdateDBForm();
        $model->scenario = 'downgrade';

        $model->setDefaultDowngrade();

        $processed = [];

        try {

            if ($model->load($this->request->post())) {


                $resp = $model->downgrade();

                if ($resp['st'] == 'ok') {
                    $processed = $resp['processed'];
                    $cant = count($processed);
                    Notificaciones::NotificarANivel(Niveles::SYSADMIN, 'parametros_generales', "Se ejecutaron $cant rollback actual:{$model->last_update} ");
                    Yii::$app->session->setFlash('success', Yii::t("app", "Se ejecutaron $cant rollback actual:{$model->last_update} "));
                } else {
                    $model->addError('_exception', $resp['msg']);
                }

            }
            if (!Yii::$app->request->isPost) {
                $model->load($this->request->get());
            }
        } catch (\Exception $e) {
            $model->addError('_exception', $e->errorInfo[2] ?? $e->getMessage());
            Yii::error("ERROR:" . Yii::$app->controller->id . "/rollback-update " . ($e->errorInfo[2] ?? $e->getMessage()));
        }
        return $this->render('dbversion/downgrade/update', ['model' => $model, 'processed' => $processed]);


    }

}
