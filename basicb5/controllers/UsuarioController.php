<?php

namespace app\controllers;


use app\config\Niveles;
use app\config\RootMenu;
//use app\models\PuntoVentaUsuarioDefault;
use app\models\Identificador;
use app\models\Notificaciones;
//use app\models\PuntoVenta;
use app\models\Usuario;
use app\models\UsuarioSearch;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\base\InvalidConfigException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use Yii;

use app\controllers\base\UsuarioController as BaseUsuarioController;



/**
 * This is the class for controller "UsuarioController".
 */
class UsuarioController extends BaseUsuarioController
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
                            'actions' => ['index', 'view', 'create', 'update', 'delete', 'setgrupoaccesos', 'setaccesos', 'mi-update'],
                            'roles' => ['@'],
                        ],
                    ]
                ]
            ]
        );
    }


    /**
     * Lists all Usuario models.
     *
     * @throws InvalidConfigException
     * @return string|Response
     */
    public function actionIndex()
    {
        $u = Yii::$app->user->identity;

        $menu = new \app\models\Menu();
        $menu->descr = "Administrar Usuarios";
        $menu->label = "Usuarios";
        $menu->menu = (string) RootMenu::CONFIG;
        $menu->menu_path = "Seguridad/Usuarios";
        $menu->url = Yii::$app->controller->id . '/index';

        $permiso = Identificador::autorizar(
            $u,
            Yii::$app->controller->id . '/index',
            "Administrar Usuarios",
            $menu
        );

        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t("app", $permiso["msg"]));
            return $this->redirect($permiso["redirect"]);
        }




        $searchModel = Yii::createObject(UsuarioSearch::class);
        $dataProvider = $searchModel->searchIndex($this->request->get(), $u->nivel);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }



    /**
     * Creates a new Usuario model.
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
            "Crear usuarios",
            null
        );
        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t("app", $permiso["msg"]));
            return $this->redirect($permiso["redirect"]);
        }


        $model = Yii::createObject(Identificador::class);
        $model->activo = 1;

       // $this->getPuntoVentaDefault($model);
        try {

            if ($model->load($this->request->post())) {
                $hay_errores = false;

                $model->setPassword($model->pwd);

                if ($model->save()) {
                    $model->generateAuthKey();
                    $model->pwd = Yii::$app->security->generateRandomString();
                    Yii::$app->session->set('skip afterSave', '1');

                    if ($model->save()) {

                        /*
                        $ret = $this->savePuntoVentaDefault($model);

                        if ($ret['status'] == 'error') {
                            $model->addError('id_punto_venta_default', $ret['msg']);
                            $hay_errores = true;
                        }
                        */    

                        if (!$hay_errores) {
                            Notificaciones::NotificarANivel(Niveles::SYSADMIN, 'usuario', "Se creo el usuario {$model->login}");
                            Yii::$app->session->setFlash('success', Yii::t("app", 'Se guardo correctamente'));
                            return $this->redirect(['view', 'id' => $model->id]);

                        }
                    }
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
     * Displays a single Usuario model.
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
            "Modificar permisos de usuarios",
            null
        );
        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t("app", $permiso["msg"]));
            return $this->redirect($permiso["redirect"]);
        }

        return $this->render(
            'view',
            [
                'model' => $this->findModel($id),
                'request_get' => $this->request->get(),
                'user' => Yii::$app->user->identity
            ]
        );

    }


    /**
     * Updates an existing Usuario model.
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
            "Modificar usuarios",
            null
        );
        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t("app", $permiso["msg"]));
            return $this->redirect($permiso["redirect"]);
        }


        $model = Identificador::findOne($id);

        $relAttributes = [];
        $relAttributesHidden = [];

        //$this->getPuntoVentaDefault($model);

        if ($model->load($this->request->post())) {

            $hay_errores = false;

            $cambio = $model->isAttributeChanged('pwd');

            //$id_punto_venta_default_cambio = $model->isAttributeChanged('id_punto_venta_default');

            if ($model->save()) {


                /*

                $ret = $this->savePuntoVentaDefault($model);

                if ($ret['status'] == 'error') {
                    $model->addError('id_punto_venta_default', $ret['msg']);
                    $hay_errores = true;
                }

                */



                if ($cambio && !$hay_errores) {
                    $model->setPassword($model->pwd);
                    $model->generateAuthKey();
                    $model->pwd = Yii::$app->security->generateRandomString();
                    Yii::$app->session->set('skip afterSave', '1');

                    if ($model->save()) {
                        Notificaciones::NotificarANivel(Niveles::SYSADMIN, 'usuario', "Se modifico el usuario {$model->login}");
                        Yii::$app->session->setFlash('success', Yii::t("app", 'Se guardo correctamente'));
                        return $this->redirect(['view', 'id' => $model->id]);
                    }


                } else {
                    if (!$hay_errores) {
                        Notificaciones::NotificarANivel(Niveles::SYSADMIN, 'usuario', "Se modifico el usuario {$model->login}");
                        Yii::$app->session->setFlash('success', Yii::t("app", 'Se guardo correctamente'));
                        return $this->redirect(['view', 'id' => $model->id]);
                    }
                }
            }
        }

        return $this->render('update', [
            'model' => $model,
            'nivel' => Yii::$app->user->identity->nivel,
            'user' => Yii::$app->user->identity,
            'relAttributes' => $relAttributes,
            'relAttributesHidden' => $relAttributesHidden,

        ]);
    }

/*
    private function getPuntoVentaDefault(&$model)
    {
        if (!$model) {
            return;
        }

        if (!$model->id) {
            return;
        }

        
        $puntoVentaUsuario = PuntoVentaUsuarioDefault::find()->where(['id_usuario' => $model->id])->one();
        $model->id_punto_venta_default = $puntoVentaUsuario ? $puntoVentaUsuario->id_punto_venta : null;
        
    }
*/

    /*
    private function savePuntoVentaDefault($model)
    {
        if (!$model->id_punto_venta_default) {

            $puntoVentaUsuario = PuntoVentaUsuarioDefault::find()->where(['id_usuario' => $model->id])->one();

            if(!$puntoVentaUsuario){
                return [
                    'status' => 'OK',
                    'msg' => "",
                ];
            }

            $puntoVentaUsuario->delete();

            return [
                'status' => 'OK',
                'msg' => "",
            ];
        }

        $punto_venta = PuntoVenta::findOne($model->id_punto_venta_default);
        if (!$punto_venta) {
            return [
                'status' => 'error',
                'msg' => "Error no existe un punto de venta default ",
            ];

        }

        $puntoVentaUsuario = PuntoVentaUsuarioDefault::find()->where(['id_usuario' => $model->id])->one();

        if ($puntoVentaUsuario) {
            $puntoVentaUsuario->id_punto_venta = $model->id_punto_venta_default;
            $puntoVentaUsuario->save();
        } else {
            $puntoVentaUsuario = new PuntoVentaUsuarioDefault;
            $puntoVentaUsuario->id_punto_venta = $model->id_punto_venta_default;
            $puntoVentaUsuario->id_usuario = $model->id;
            $puntoVentaUsuario->save();
        }

        if (!$puntoVentaUsuario->save()) {
            $errorestxt = implode(", ", $puntoVentaUsuario->getErrorSummary(true));
            return [
                'status' => 'error',
                'msg' => "Error al guardar el punto de venta default: $errorestxt",
            ];

        }
        return [
            'status' => 'OK',
            'msg' => "",
        ];

    }
    */    

    /**
     * Updates an existing Usuario model.
     * If update is successful, the browser will be redirected to the 'view' page.
     *
     * @param integer $id
     *
     * @throws NotFoundHttpException
     * @return string|Response
     */
    public function actionMiUpdate()
    {

        $menu = new \app\models\Menu();
        $menu->descr = "Modificar mi perfil";
        $menu->label = "Mi usuario";
        $menu->menu = (string) RootMenu::WEBUSER;
        $menu->menu_path = "Yo";
        $menu->url = Yii::$app->controller->id . '/mi-update';


        $permiso = Identificador::autorizar(
            Yii::$app->user->identity,
            Yii::$app->controller->id . '/mi-update',
            "Modificar mi usuario",
            $menu
        );
        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t("app", $permiso["msg"]));
            return $this->redirect($permiso["redirect"]);
        }


        $model = Identificador::findOne(Yii::$app->user->identity->id);


        $relAttributes['login'] = $model->login;
        $relAttributesHidden['nivel'] = $model->nivel;
        $relAttributesHidden['activo'] = $model->activo;
        $relAttributesHidden['codigo'] = $model->codigo;



        if ($model->load($this->request->post())) {

            $model->login = $model->getOldAttribute('login') ? $model->getOldAttribute('login') : $model->login;
            $model->nivel = $model->getOldAttribute('nivel') ? $model->getOldAttribute('nivel') : $model->nivel;
            $model->activo = $model->getOldAttribute('activo') ? $model->getOldAttribute('activo') : $model->activo;
            $model->codigo = $model->getOldAttribute('codigo') ? $model->getOldAttribute('codigo') : $model->codigo;


            $cambio = $model->isAttributeChanged('pwd');


            if ($model->save()) {
                if ($cambio) {
                    $model->setPassword($model->pwd);
                    $model->generateAuthKey();
                    $model->pwd = Yii::$app->security->generateRandomString();

                    Yii::$app->session->set('skip afterSave', '1');

                    if ($model->save()) {
                        Notificaciones::NotificarANivel(Niveles::SYSADMIN, 'usuario', "Se modifico el usuario {$model->login}");
                        Yii::$app->session->setFlash('success', Yii::t("app", 'Se guardo correctamente'));
                        return $this->goHome();
                    }


                } else {
                    Notificaciones::NotificarANivel(Niveles::SYSADMIN, 'usuario', "Se modifico el usuario {$model->login}");
                    Yii::$app->session->setFlash('success', Yii::t("app", 'Se guardo correctamente'));
                    return $this->goHome();
                }
            }
        }

        return $this->render('update', [
            'model' => $model,
            'nivel' => Yii::$app->user->identity->nivel,
            'relAttributes' => $relAttributes,
            'relAttributesHidden' => $relAttributesHidden,
        ]);
    }


    /**
     * Deletes an existing Usuario model.
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
            "Eliminar usuarios",
            null
        );
        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t("app", $permiso["msg"]));
            return $this->redirect($permiso["redirect"]);
        }

        $modelorig = $this->findModel($id);
        try {
            $this->findModel($id)->delete();
            Notificaciones::NotificarANivel(Niveles::SYSADMIN, 'usuario', "Se elimino el usuario {$modelorig->login}");
            Yii::$app->session->setFlash('success', Yii::t("app", 'Se elimino correctamente'));
        } catch (\Exception $e) {
            Yii::$app->getSession()->addFlash('error', $e->errorInfo[2] ?? $e->getMessage());
            Yii::error("ERROR:" . Yii::$app->controller->id . "/delete " . ($e->errorInfo[2] ?? $e->getMessage()));
        }

        return $this->redirect(['index']);
    }


    public function actionSetgrupoaccesos()
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
            $id_grupo = Yii::$app->request->post('id');
            $estado = Yii::$app->request->post('estado');
            $id_usuario = Yii::$app->request->post('id_usuario');



            $usuario = Usuario::findOne($id_usuario);

            if (!$usuario) {

                return ['success' => false, 'message' => 'error en los datos'];
            }

            $usuario->setGrupoAcceso($id_grupo, ($estado == 1));


            Notificaciones::NotificarANivel(Niveles::SYSADMIN, 'grupos_accesos_usuarios', "Se cambio el grupo de acceso del usuario {$usuario->login}");

            return [
                'success' => true,
                'message' => 'Estado actualizado correctamente',
                'id_grupo' => $id_grupo,
                'estado' => $estado,
                'id_usuario' => $id_usuario,
                'count' => $usuario->getGrupoAccesos()->count()
            ];

        }

        return ['success' => false, 'message' => 'Solicitud no válida'];
    }



    public function actionSetaccesos()
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
            $id_acceso = Yii::$app->request->post('id');
            $estado = Yii::$app->request->post('estado');
            $id_usuario = Yii::$app->request->post('id_usuario');



            $usuario = Usuario::findOne($id_usuario);

            if (!$usuario) {

                return ['success' => false, 'message' => 'error en los datos'];
            }

            $usuario->setAcceso($id_acceso, ($estado == 1));


            Notificaciones::NotificarANivel(Niveles::SYSADMIN, 'usuarios_accesos', "Se cambio el acceso del usuario {$usuario->login}");

            return [
                'success' => true,
                'message' => 'Estado actualizado correctamente',
                'id_acceso' => $id_acceso,
                'estado' => $estado,
                'id_usuario' => $id_usuario,
                'count' => $usuario->getAccesos()->count()
            ];

        }

        return ['success' => false, 'message' => 'Solicitud no válida'];
    }



}
