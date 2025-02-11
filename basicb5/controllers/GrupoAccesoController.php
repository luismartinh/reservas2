<?php

namespace app\controllers;
use app\config\Niveles;
use app\config\RootMenu;
use app\models\GrupoAcceso;
use app\models\GrupoAccesoSearch;
use app\models\Identificador;
use app\models\Notificaciones;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\base\InvalidConfigException;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Request;
use yii\web\Response;
use Yii;


use app\controllers\base\GrupoAccesoController as BaseGrupoAccesoController;

/**
 * This is the class for controller "GrupoAccesoController".
 */
class GrupoAccesoController extends BaseGrupoAccesoController
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
                    'only' => ['index', 'view', 'create', 'update', 'delete', 'setusuario', 'setacceso'],
                    'rules' => [
                        [
                            'allow' => true,
                            'actions' => ['index', 'view', 'create', 'update', 'delete', 'setusuario', 'setacceso'],
                            'roles' => ['@'],
                            'matchCallback' => function ($rule, $action) {
                                return true;
                            }

                        ],
                    ]
                ]
            ]
        );
    }

    /**
     * Lists all GrupoAcceso models.
     *
     * @throws InvalidConfigException
     * @return string|Response
     */
    public function actionIndex()
    {

        $u = Yii::$app->user->identity;

        $menu = new \app\models\Menu();
        $menu->descr = "Administrar Grupos de acceso";
        $menu->label = "Grupos de acceso";
        $menu->menu = (string) RootMenu::CONFIG;
        $menu->menu_path = "Seguridad/Grupos";
        $menu->url = Yii::$app->controller->id . '/index';

        $permiso = Identificador::autorizar(
            $u,
            Yii::$app->controller->id . '/index',
            "Administrar Grupos de acceso",
            $menu
        );

        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t("app", $permiso["msg"]));
            return $this->redirect($permiso["redirect"]);
        }

        $searchModel = Yii::createObject(GrupoAccesoSearch::class);
        $dataProvider = $searchModel->searchIndex($this->request->get(), $u->nivel);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }

    /**
     * Displays a single GrupoAcceso model.
     *
     * @param integer $id
     *
     * @throws NotFoundHttpException
     * @return string|Response
     */
    public function actionView($id)
    {

        $permiso = Identificador::autorizar(
            Yii::$app->user->identity,
            Yii::$app->controller->id . '/view',
            "Modificar permisos de grupos de acceso",
            null
        );
        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t("app", $permiso["msg"]));
            return $this->redirect($permiso["redirect"]);
        }

        return $this->render('view', [
            'model' => $this->findModel($id),
            'request_get' => $this->request->get(),
            'user' => Yii::$app->user->identity
        ]);

    }

    /**
     * Creates a new GrupoAcceso model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     *
     * @throws yii\base\InvalidConfigException
     * @return string|Response
     */
    public function actionCreate()
    {
        $permiso = Identificador::autorizar(
            Yii::$app->user->identity,
            Yii::$app->controller->id . '/create',
            "Crear grupos de acceso",
            null
        );
        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t("app", $permiso["msg"]));
            return $this->redirect($permiso["redirect"]);
        }

        $model = Yii::createObject(GrupoAcceso::class);
        try {

            if ($model->load($this->request->post())) {

                if ($model->save()) {
                    Notificaciones::NotificarANivel(Niveles::SYSADMIN, 'grupo_acceso', "Se creo el grupo {$model->descr}");
                    Yii::$app->session->setFlash('success', Yii::t("app", 'Se guardo correctamente'));
                    return $this->redirect(['view', 'id' => $model->id]);
                }
            }
            if (!Yii::$app->request->isPost) {
                $model->load($this->request->get());
            }
        } catch (\Exception $e) {
            $model->addError('_exception', $e->errorInfo[2] ?? $e->getMessage());
            Yii::error("ERROR:" . Yii::$app->controller->id . "/create " . ($e->errorInfo[2] ?? $e->getMessage()));
        }
        return $this->render('create', ['model' => $model, 'nivel' => Yii::$app->user->identity->nivel]);
    }

    /**
     * Updates an existing GrupoAcceso model.
     * If update is successful, the browser will be redirected to the 'view' page.
     *
     * @param integer $id
     *
     * @throws NotFoundHttpException
     * @return string|Response
     */
    public function actionUpdate($id)
    {

        $permiso = Identificador::autorizar(
            Yii::$app->user->identity,
            Yii::$app->controller->id . '/update',
            "Modificar grupos de acceso",
            null
        );
        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t("app", $permiso["msg"]));
            return $this->redirect($permiso["redirect"]);
        }

        $model = $this->findModel($id);

        if ($model->load($this->request->post())) {

            if ($model->save()) {
                Notificaciones::NotificarANivel(Niveles::SYSADMIN, 'grupo_acceso', "Se modifico el grupo {$model->descr}");
                Yii::$app->session->setFlash('success', Yii::t("app", 'Se guardo correctamente'));
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->render('update', ['model' => $model, 'nivel' => Yii::$app->user->identity->nivel]);
    }

    /**
     * Deletes an existing GrupoAcceso model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     *
     * @param integer $id
     *
     * @throws \Throwable
     * @return Response
     */
    public function actionDelete($id)
    {
        $permiso = Identificador::autorizar(
            Yii::$app->user->identity,
            Yii::$app->controller->id . '/delete',
            "Eliminar grupos de acceso",
            null
        );
        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t("app", $permiso["msg"]));
            return $this->redirect($permiso["redirect"]);
        }

        $modelOri = $this->findModel($id);

        try {
            $this->findModel($id)->delete();
            Notificaciones::NotificarANivel(Niveles::SYSADMIN, 'grupo_acceso', "Se elimino el grupo {$modelOri->descr}");
            Yii::$app->session->setFlash('success', Yii::t("app", 'Se elimino correctamente'));
        } catch (\Exception $e) {
            Yii::$app->getSession()->addFlash('error', $e->errorInfo[2] ?? $e->getMessage());
            Yii::error("ERROR:" . Yii::$app->controller->id . "/delete " . ($e->errorInfo[2] ?? $e->getMessage()));
        }

        return $this->redirect(['index']);
    }



    public function actionSetusuario()
    {
        Yii::$app->response->format = Yii\web\Response::FORMAT_JSON;

        $permiso = Identificador::autorizar(
            Yii::$app->user->identity,
            Yii::$app->controller->id . '/view',
            "Modificar permisos de usuarios",
            null
        );

        if (!$permiso['auth']) {
            return ['success' => false, 'message' => $permiso["msg"]];
        }


        if (Yii::$app->request->isPost) {
            $id_usuario = Yii::$app->request->post('id');
            $estado = Yii::$app->request->post('estado');
            $id_grupo = Yii::$app->request->post('id_grupo');



            $grupo = GrupoAcceso::findOne($id_grupo);

            if (!$grupo) {

                return ['success' => false, 'message' => 'error en los datos'];
            }

            $grupo->setUsuario($id_usuario, ($estado == 1));

            Notificaciones::NotificarANivel(Niveles::SYSADMIN, 'grupos_accesos_usuarios', "Se modificaron los usuarios en {$grupo->descr}");


            return [
                'success' => true,
                'message' => 'Estado actualizado correctamente',
                'id_grupo' => $id_grupo,
                'estado' => $estado,
                'id_usuario' => $id_usuario,
                'count' => $grupo->getUsuarios()->count()
            ];

        }

        return ['success' => false, 'message' => 'Solicitud no válida'];
    }



    public function actionSetacceso()
    {


        $permiso = Identificador::autorizar(
            Yii::$app->user->identity,
            Yii::$app->controller->id . '/view',
            "Modificar permisos de usuarios",
            null
        );

        if (!$permiso['auth']) {
            return ['success' => false, 'message' => $permiso["msg"]];
        }

        Yii::$app->response->format = Yii\web\Response::FORMAT_JSON;

        if (Yii::$app->request->isPost) {
            $id_acceso = Yii::$app->request->post('id');
            $estado = Yii::$app->request->post('estado');
            $id_grupo = Yii::$app->request->post('id_grupo');


            $grupo = GrupoAcceso::findOne($id_grupo);

            if (!$grupo) {

                return ['success' => false, 'message' => 'error en los datos'];
            }

            $grupo->setAcceso($id_acceso, ($estado == 1));

            Notificaciones::NotificarANivel(Niveles::SYSADMIN, 'grupos_accesos_accesos', "Se modificaron los accesos en {$grupo->descr}");

            return [
                'success' => true,
                'message' => 'Estado actualizado correctamente',
                'id_acceso' => $id_acceso,
                'estado' => $estado,
                'id_grupo' => $id_grupo,
                'count' => $grupo->getAccesos()->count()
            ];

        }

        return ['success' => false, 'message' => 'Solicitud no válida'];
    }


}
