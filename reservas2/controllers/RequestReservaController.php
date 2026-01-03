<?php

namespace app\controllers;

use app\controllers\base\RequestReservaController as BaseRequestReservaController;

use app\config\RootMenu;
use app\helpers\Utils;
use app\models\Identificador;
use app\models\RequestReserva;
use app\models\RequestReservaSearch;
use app\models\RequestResponse;
use app\models\Reserva;
use yii\base\DynamicModel;
use yii\bootstrap5\Html;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\base\InvalidConfigException;
use yii\helpers\Url;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use Yii;
use yii\web\UploadedFile;

/**
 * This is the class for controller "RequestReservaController".
 */
class RequestReservaController extends BaseRequestReservaController
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
                    'only' => [
                        'index',
                        'delete',
                        'cambiar-estado',
                        'agregar-pago'
                        ,
                        'eliminar-pagos',
                        'chat',
                        'eliminar-mensaje-chat',
                        'editar-rango'

                    ],
                    'rules' => [
                        [
                            'allow' => true,
                            'actions' => [
                                'index',
                                'delete',
                                'cambiar-estado',
                                'agregar-pago',
                                'eliminar-pagos',
                                'chat',
                                'eliminar-mensaje-chat',
                                'editar-rango'
                            ],
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
     * Lists all RequestReserva models.
     *
     * @throws InvalidConfigException
     * @return string|Response
     */
    public function actionIndex()
    {

        $u = Yii::$app->user->identity;

        $menu = new \app\models\Menu();
        $menu->descr = "Administrar Solicitudes reservas";
        $menu->label = "Solicitudes";
        $menu->menu = (string) RootMenu::ADMIN;
        $menu->menu_path = "Reservas/Solicitudes";
        $menu->url = Yii::$app->controller->id . '/index';

        $permiso = Identificador::autorizar(
            $u,
            Yii::$app->controller->id . '/index',
            "Administrar Solicitudes reservas",
            $menu
        );

        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t("app", $permiso["msg"]));
            return $this->redirect($permiso["redirect"]);
        }


        $searchModel = Yii::createObject(RequestReservaSearch::class);
        $dataProvider = $searchModel->searchIndex($this->request->get());

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }


    /**
     * Deletes an existing RequestReserva model.
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
            "Eliminar Solicitud",
            null
        );
        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t("app", $permiso["msg"]));
            return $this->redirect($permiso["redirect"]);
        }

        $modelOri = $this->findModel($id);
        $now = new \DateTime();


        if (
            $modelOri->estado->slug == "pendiente-email-verificar"
            || $modelOri->estado->slug == "pendiente-email-contestado"
            || $modelOri->estado->slug == "pendiente-email-verificado"
        ) {

            $res = RequestReserva::vencida($modelOri->id, $now);

            if ($res['status'] == 'OK') {
                Yii::$app->session->setFlash('danger', Yii::t(
                    "app",
                    'No se puede eliminar una solicitud pendiente, esta sin vencimientos'
                ));
                return $this->redirect(['index']);
            }
        }

        if ($modelOri->estado->slug == "confirmado" || $modelOri->estado->slug == "confirmado-verificar-pago") {

            if ($reserva = $modelOri->reserva) {
                $fechaIngreso = new \DateTime($reserva->desde);
                $fechaEgreso = new \DateTime($reserva->hasta);

                /*
                // Verifica si NOW está entre Ingreso y Egreso (inclusive)
                //$estaDentro = $now >= $fechaIngreso && $now <= $fechaEgreso;
                $estaDentro = $now <= $fechaEgreso;

                if ($estaDentro) {
                    Yii::$app->session->setFlash('danger', Yii::t(
                        "app",
                        'No se puede eliminar una solicitud pendiente, la reserva esta en curso'
                    ));
                    return $this->redirect(['index']);
                }
                */    

                $reserva->delete();

            }

        }

        try {
            //$this->findModel($id)->delete();
            $modelOri->delete(); // esto dispara beforeDelete() y elimina comprobantes
            Yii::$app->session->setFlash('success', Yii::t("app", 'Se elimino correctamente'));
        } catch (\Exception $e) {
            Yii::$app->getSession()->addFlash('error', $e->errorInfo[2] ?? $e->getMessage());
            Yii::error("ERROR:" . Yii::$app->controller->id . "/delete " . ($e->errorInfo[2] ?? $e->getMessage()));
        }

        return $this->redirect(['index']);
    }


    public function actionCambiarEstado()
    {
        $permiso = Identificador::autorizar(
            Yii::$app->user->identity,
            Yii::$app->controller->id . '/update',
            "Modificar Solicitud",
            null
        );
        if (!$permiso['auth']) {
            return $this->asJson([
                'output' => '',
                'message' => Yii::t("app", $permiso["msg"]),
            ]);
        }

        $request = Yii::$app->request;

        // --- Validación inicial ---
        if (!$request->post('hasEditable')) {
            return $this->asJson([
                'output' => '',
                'message' => Yii::t('app', 'Petición inválida.'),
            ]);
        }

        // --- Identificadores enviados por EditableColumn ---
        $id = $request->post('editableKey');        // PK del registro
        $attribute = $request->post('editableAttribute');  // normalmente "id_estado"

        /** @var \app\models\RequestReserva|null $model */
        $model = RequestReserva::find()
            ->where(['id' => $id])
            ->with(['estado', 'reserva', 'requestCabanas.cabana'])
            ->one();

        if ($model === null) {
            return $this->asJson([
                'output' => '',
                'message' => Yii::t('app', 'El registro solicitado no existe.'),
            ]);
        }

        // --- Capturar el valor enviado ---
        // Llega como: RequestReserva[0][id_estado] => 6
        $postedAll = $request->post('RequestReserva', []);

        // Tomar la primera fila (independientemente del índice numérico)
        $postedRow = [];
        if (is_array($postedAll) && !empty($postedAll)) {
            $postedRow = current($postedAll);
        }

        // Estructura correcta para load()
        $postData = ['RequestReserva' => $postedRow];

        $out = ['output' => '', 'message' => ''];

        $tx = Yii::$app->db->beginTransaction();
        try {
            // --- Guardar nuevo estado en RequestReserva ---
            if (!($model->load($postData) && $model->save())) {
                throw new \Exception(
                    Yii::t('app', 'No se pudo actualizar el estado. Por favor, intente nuevamente.')
                );
            }

            // 🔄 Muy importante: recargar modelo para limpiar relaciones cacheadas (estado, etc.)
            $model->refresh();

            // Estado NUEVO (después del cambio)
            $nuevoEstado = $model->estado;
            $nuevoSlug = $nuevoEstado->slug ?? null;

            // --- Sincronizar con Reserva según el slug ---
            switch ($nuevoSlug) {

                // Estos estados NO deben tener reserva asociada
                case 'pendiente-email-verificar':
                case 'pendiente-email-verificado':
                case 'rechazado':
                    $this->eliminarReservaAsociada($model);
                    RequestReserva::enviarMailCambioEstado($model);
                    break;

                // Nada extra
                case 'pendiente-email-contestado':
                    // no hacer nada con Reserva
                    break;

                // Estos estados deben reflejarse en Reserva
                case 'confirmado-verificar-pago':
                case 'confirmado':
                    $this->sincronizarReservaDesdeRequest($model, $nuevoSlug);
                    //$this->enviarMailCambioEstado($model);
                    RequestReserva::enviarMailCambioEstado($model);
                    break;

                default:
                    // Otros estados: no hacemos nada especial
                    break;
            }

            $tx->commit();

            // Reconstruir el contenido de la celda para devolverlo al GridView
            if ($rel = $model->estado) {
                $cell = Html::encode($rel->descr);

                $res = RequestReserva::vencida($model->id, new \DateTime());
                if ($res['status'] === 'vencida') {
                    $cell .= '<br><span class="text-danger">' . Html::encode($res['msg']) . '</span>';
                }

                $out['output'] = $cell;
            }

        } catch (\Throwable $e) {
            if ($tx->isActive) {
                $tx->rollBack();
            }

            $out['output'] = '';
            $out['message'] = $e instanceof \yii\base\UserException
                ? $e->getMessage()
                : Yii::t('app', 'No se pudo actualizar el estado: {m}', ['m' => $e->getMessage()]);
        }

        return $this->asJson($out);
    }

    /**
     * Devuelve el id de Estado a partir de su slug, o null si no existe.
     */
    protected function getEstadoIdPorSlug(string $slug): ?int
    {
        $e = \app\models\Estado::find()->where(['slug' => $slug])->one();
        return $e ? (int) $e->id : null;
    }

    /**
     * Genera un valor numérico aleatorio único para un campo de Locador
     * (por ejemplo documento o telefono).
     */
    protected function generarValorUnicoLocador(string $campo, int $len = 8): string
    {
        $min = (int) pow(10, $len - 1);
        $max = (int) pow(10, $len) - 1;

        for ($i = 0; $i < 20; $i++) {
            $val = (string) random_int($min, $max);
            $exists = \app\models\Locador::find()
                ->where([$campo => $val])
                ->exists();
            if (!$exists) {
                return $val;
            }
        }

        // Fallback muy poco probable
        return (string) (time() . rand(100, 999));
    }

    /**
     * Elimina la Reserva asociada (y sus ReservaCabanas) y desliga el RequestReserva.
     */
    protected function eliminarReservaAsociada(RequestReserva $req): void
    {
        if (!$req->id_reserva) {
            return;
        }

        /** @var \app\models\Reserva|null $reserva */
        $reserva = $req->reserva;
        if ($reserva) {
            // borrar detalle
            foreach ($reserva->reservaCabanas as $rc) {
                $rc->delete();
            }
            $reserva->delete();
        }

        $req->id_reserva = null;
        $req->save(false, ['id_reserva']);
    }

    /**
     * Sincroniza una Reserva (crear/actualizar) a partir de un RequestReserva
     * según el slug indicado ('confirmado-verificar-pago' o 'confirmado').
     */
    protected function sincronizarReservaDesdeRequest(RequestReserva $model, string $slugReserva): void
    {
        $estadoReservaId = $this->getEstadoIdPorSlug($slugReserva);
        if (!$estadoReservaId) {
            throw new \Exception(
                Yii::t('app', 'No se encontró el estado de reserva para "{slug}".', ['slug' => $slugReserva])
            );
        }

        /** @var \app\models\Reserva|null $reserva */
        $reserva = $model->reserva;

        if ($reserva) {
            // Ya existe: solo actualizar estado
            $reserva->id_estado = $estadoReservaId;
            if (!$reserva->save()) {
                throw new \Exception(Yii::t('app', 'No se pudo actualizar la Reserva asociada.'));
            }
            return;
        }

        // --- No existe reserva: crearla en base al RequestReserva actual ---

        // 1) LOCADOR a partir del email
        $locador = \app\models\Locador::findOne(['email' => $model->email]);

        if ($locador === null) {
            $locador = new \app\models\Locador();
            $locador->denominacion = $model->denominacion ?: $model->email;
            $locador->email = $model->email;

            // Generar documento y teléfono únicos
            $locador->documento = $this->generarValorUnicoLocador('documento', 8);
            $locador->telefono = $this->generarValorUnicoLocador('telefono', 8);
            $locador->domicilio = ''; // opcional, vacío

            if (!$locador->save()) {
                throw new \Exception(Yii::t('app', 'No se pudo crear el Locador asociado.'));
            }
        }

        // 2) Calcular pax a partir de las cabañas del request
        $paxAcumulado = 0;
        if (!empty($model->requestCabanas)) {
            foreach ($model->requestCabanas as $rc) {
                if ($rc->cabana && $rc->cabana->max_pax) {
                    $paxAcumulado += (int) $rc->cabana->max_pax;
                }
            }
        }
        if ($paxAcumulado <= 0) {
            $paxAcumulado = 1;
        }

        $existeSolape = Reserva::estanReservadas($model);
        if ($existeSolape) {
            throw new \Exception(Yii::t('app', 'Ya existen reservas para alguna de las cabañas.'));
        }

        // 3) Crear Reserva
        $reserva = new Reserva([
            'fecha' => date('Y-m-d H:i:s'),
            'desde' => $model->desde,
            'hasta' => $model->hasta,
            'id_locador' => $locador->id,
            'pax' => $paxAcumulado,
            'id_estado' => $estadoReservaId,
            'obs' => Yii::t('app', 'Reserva creada desde request {id}.', ['id' => $model->id]),
        ]);

        if (!$reserva->save()) {
            throw new \Exception(Yii::t('app', 'No se pudo guardar la Reserva.'));
        }

        // 4) Crear ReservaCabana por cada cabana del request
        if (!empty($model->requestCabanas)) {
            foreach ($model->requestCabanas as $rc) {
                $rCab = new \app\models\ReservaCabana([
                    'id_reserva' => $reserva->id,
                    'id_cabana' => $rc->id_cabana,
                    'valor' => (float) $rc->valor,
                ]);
                if (!$rCab->save()) {
                    throw new \Exception(Yii::t('app', 'No se pudo guardar una Reserva-Cabaña.'));
                }
            }
        }

        // 5) Vincular RequestReserva con la nueva Reserva
        $model->id_reserva = $reserva->id;
        if (!$model->save(false, ['id_reserva'])) {
            throw new \Exception(Yii::t('app', 'No se pudo asociar la Reserva a la solicitud.'));
        }
    }


    public function actionAgregarPago($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;


        $permiso = Identificador::autorizar(
            Yii::$app->user->identity,
            Yii::$app->controller->id . '/update',
            "Modificar Solicitud",
            null
        );
        if (!$permiso['auth']) {
            return [
                'success' => false,
                'message' => Yii::t("app", $permiso["msg"]),
            ];
        }


        /** @var \app\models\RequestReserva|null $reservaReq */
        $reservaReq = RequestReserva::find()
            ->with(['estado'])
            ->where(['id' => $id])
            ->one();

        if ($reservaReq === null) {
            return [
                'success' => false,
                'message' => Yii::t('app', 'La solicitud no existe.'),
            ];
        }

        $slug = $reservaReq->estado->slug ?? null;
        $debe = (float) $reservaReq->total - (float) $reservaReq->pagado;

        // 1) Validar estado permitido
        if (!in_array($slug, ['confirmado', 'confirmado-verificar-pago', 'pendiente-email-verificado'], true)) {
            return [
                'success' => false,
                'message' => Yii::t('app', 'Solo se pueden agregar pagos cuando la solicitud está confirmada o pendiente de verificación de pago.'),
            ];
        }

        // 2) Validar que aún haya saldo
        if ($debe <= 0) {
            return [
                'success' => false,
                'message' => Yii::t('app', 'No hay saldo pendiente. No se puede agregar más pagos.'),
            ];
        }

        // 3) Modelo dinámico para el form del modal
        $form = new DynamicModel(['monto', 'comprobante']);
        $form->monto = null;
        $form->comprobante = null;

        // Reglas:
        // monto: requerido, numérico > 0 y <= debe
        $form->addRule('monto', 'required')
            ->addRule('monto', 'number', [
                'min' => 0.01,
                'max' => $debe,
                'tooSmall' => Yii::t('app', 'El monto debe ser mayor que 0.'),
                'tooBig' => Yii::t('app', 'El monto no puede superar el saldo pendiente ({debe}).', [
                    'debe' => '$ ' . number_format($debe, 2, ',', '.'),
                ]),
            ]);

        // comprobante: opcional, imágenes o pdf, máx 5MB
        $form->addRule('comprobante', 'file', [
            'skipOnEmpty' => true,
            'extensions' => ['png', 'jpg', 'jpeg', 'pdf'],
            'checkExtensionByMimeType' => true,
            'maxSize' => 5 * 1024 * 1024,
        ]);

        // notas: opcional, string máx 500 chars
        $form->addRule('notas', 'string', ['max' => 500]);

        $request = Yii::$app->request;

        if ($request->isPost) {
            $form->load($request->post());
            $form->comprobante = UploadedFile::getInstance($form, 'comprobante');

            if ($form->validate()) {
                // === Manejo de archivo igual que en tu otro action ===
                $tmpPublicDir = Yii::getAlias('@webroot/uploads_tmp/comprobantes');
                if (!is_dir($tmpPublicDir)) {
                    @mkdir($tmpPublicDir, 0775, true);
                }
                $archivoPublicWeb = null;
                $tempPathFs = null;

                if ($form->comprobante) {
                    $base = 'comprobante_' . date('Ymd_His') . '_' . Yii::$app->security->generateRandomString(8);
                    $filename = $base . '.' . $form->comprobante->extension;
                    $tempPathFs = $tmpPublicDir . DIRECTORY_SEPARATOR . $filename; // FS path
                    if ($form->comprobante->saveAs($tempPathFs)) {
                        $archivoPublicWeb = '/uploads_tmp/comprobantes/' . $filename; // URL pública temporal
                    }
                }

                $tx = Yii::$app->db->beginTransaction();
                try {
                    // registro_pagos: append
                    $regPagos = is_array($reservaReq->registro_pagos) ? $reservaReq->registro_pagos : [];
                    $entry = [
                        'fecha' => date('Y-m-d H:i:s'),
                        'monto' => (float) $form->monto,
                        'archivo' => $archivoPublicWeb, // por ahora pública
                        'nota' => (string) ($form->notas ?? ''),   // 👈 nueva nota opcional
                    ];
                    $regPagos[] = $entry;

                    // acumular pagado
                    $reservaReq->pagado = (float) $reservaReq->pagado + (float) $form->monto;
                    $reservaReq->registro_pagos = $regPagos;

                    // estado -> 'confirmado' si no lo tenía
                    if ($slug !== 'confirmado') {
                        $estadoConfirmado = \app\models\Estado::find()
                            ->where(['slug' => 'confirmado'])
                            ->one();
                        if ($estadoConfirmado) {
                            $reservaReq->id_estado = $estadoConfirmado->id;
                        }
                    }

                    if (!$reservaReq->save()) {
                        throw new \Exception(Yii::t('app', 'No se pudo actualizar la solicitud de reserva.'));
                    }

                    $this->sincronizarReservaDesdeRequest($reservaReq, 'confirmado');

                    $tx->commit();

                    $reservaReq->refresh();
                    RequestReserva::enviarMailCambioEstado($reservaReq);

                    // === mover archivo a carpeta privada y actualizar registro_pagos ===
                    if ($tempPathFs && $archivoPublicWeb) {
                        $privateDir = Yii::getAlias('@runtime/priv_comprobantes');
                        if (!is_dir($privateDir)) {
                            @mkdir($privateDir, 0775, true);
                        }
                        $finalFs = $privateDir . DIRECTORY_SEPARATOR . basename($tempPathFs);
                        if (@rename($tempPathFs, $finalFs)) {
                            $registros = is_array($reservaReq->registro_pagos) ? $reservaReq->registro_pagos : [];
                            if (!empty($registros)) {
                                $lastIndex = count($registros) - 1;
                                $registros[$lastIndex]['archivo'] = $finalFs; // ruta privada
                                $reservaReq->registro_pagos = $registros;
                                @$reservaReq->save(false, ['registro_pagos']);
                            }
                        }
                    }

                    return [
                        'success' => true,
                        'message' => Yii::t('app', 'Pago agregado correctamente.'),
                    ];
                } catch (\Throwable $e) {
                    $tx->rollBack();
                    return [
                        'success' => false,
                        'message' => Yii::t('app', 'No se pudo agregar el pago: {m}', ['m' => $e->getMessage()]),
                    ];
                }
            }

            // Si hay errores de validación, devolvemos el form renderizado
            return [
                'success' => false,
                'html' => $this->renderAjax('_form_agregar_pago', [
                    'model' => $reservaReq,
                    'formModel' => $form,
                    'debe' => $debe,
                ]),
            ];
        }

        // Primera carga del modal (GET)
        return [
            'success' => true,
            'html' => $this->renderAjax('_form_agregar_pago', [
                'model' => $reservaReq,
                'formModel' => $form,
                'debe' => $debe,
            ]),
        ];
    }


    public function actionEliminarPagos($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $permiso = Identificador::autorizar(
            Yii::$app->user->identity,
            Yii::$app->controller->id . '/update',
            "Modificar Solicitud",
            null
        );
        if (!$permiso['auth']) {
            return [
                'success' => false,
                'message' => Yii::t("app", $permiso["msg"]),
            ];
        }

        /** @var RequestReserva|null $reservaReq */
        $reservaReq = RequestReserva::find()
            ->with(['estado'])
            ->where(['id' => $id])
            ->one();

        if ($reservaReq === null) {
            return [
                'success' => false,
                'message' => Yii::t('app', 'La solicitud no existe.'),
            ];
        }

        $slug = $reservaReq->estado->slug ?? null;
        $regPagos = is_array($reservaReq->registro_pagos) ? $reservaReq->registro_pagos : [];

        // Solo en estados válidos
        if (!in_array($slug, ['confirmado', 'confirmado-verificar-pago', 'pendiente-email-verificado'], true)) {
            return [
                'success' => false,
                'message' => Yii::t('app', 'Solo se pueden eliminar pagos cuando la solicitud está confirmada o pendiente de verificación de pago.'),
            ];
        }

        // Debe haber pagos cargados
        if (empty($regPagos) || (float) $reservaReq->pagado <= 0) {
            return [
                'success' => false,
                'message' => Yii::t('app', 'No hay pagos registrados para eliminar.'),
            ];
        }

        $request = Yii::$app->request;

        // ⚙️ POST: procesar selección
        if ($request->isPost) {
            $seleccion = (array) $request->post('pagosSeleccionados', []);

            if (empty($seleccion)) {
                // Volvemos a enviar el form con un mensaje de error
                return [
                    'success' => false,
                    'html' => $this->renderAjax('_form_eliminar_pagos', [
                        'model' => $reservaReq,
                        'regPagos' => $regPagos,
                        'errorMessage' => Yii::t('app', 'Debe seleccionar al menos un pago para eliminar.'),
                    ]),
                ];
            }

            // Normalizar índices seleccionados
            $indices = array_unique(array_map('intval', $seleccion));
            sort($indices);

            $montoAEliminar = 0.0;
            $archivosAEliminar = [];

            foreach ($indices as $idx) {
                if (!isset($regPagos[$idx])) {
                    continue;
                }
                $pago = $regPagos[$idx];
                $montoAEliminar += (float) ($pago['monto'] ?? 0);

                if (!empty($pago['archivo']) && is_string($pago['archivo'])) {
                    $archivosAEliminar[] = $pago['archivo'];
                }

                unset($regPagos[$idx]);
            }

            // Reindexar array de pagos
            $regPagos = array_values($regPagos);

            $tx = Yii::$app->db->beginTransaction();
            try {
                // Actualizar montos
                $reservaReq->pagado = max(0, (float) $reservaReq->pagado - $montoAEliminar);
                $reservaReq->registro_pagos = $regPagos;

                if (!$reservaReq->save()) {
                    throw new \Exception(Yii::t('app', 'No se pudo actualizar la solicitud de reserva.'));
                }

                $tx->commit();

                // 🔥 Borrar archivos físicos luego del commit
                foreach ($archivosAEliminar as $archivo) {
                    $fsPath = $archivo;

                    // Si en registro_pagos quedó ruta absoluta, la usamos tal cual.
                    // Si fuera solo un basename:
                    if (!file_exists($fsPath)) {
                        $fsPath = Yii::getAlias('@runtime/priv_comprobantes/' . basename($archivo));
                    }

                    if (is_file($fsPath)) {
                        @unlink($fsPath);
                    }
                }

                return [
                    'success' => true,
                    'message' => Yii::t('app', 'Los pagos seleccionados fueron eliminados correctamente.'),
                ];
            } catch (\Throwable $e) {
                $tx->rollBack();
                return [
                    'success' => false,
                    'message' => Yii::t('app', 'No se pudo eliminar los pagos seleccionados: {m}', ['m' => $e->getMessage()]),
                ];
            }
        }

        // 🟢 GET: primera carga del modal
        return [
            'success' => true,
            'html' => $this->renderAjax('_form_eliminar_pagos', [
                'model' => $reservaReq,
                'regPagos' => $regPagos,
                'errorMessage' => null,
            ]),
        ];
    }


    public function actionChat($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $permiso = Identificador::autorizar(
            Yii::$app->user->identity,
            Yii::$app->controller->id . '/update',
            "Modificar Solicitud",
            null
        );
        if (!$permiso['auth']) {
            return [
                'success' => false,
                'message' => Yii::t("app", $permiso["msg"]),
            ];
        }

        /** @var RequestReserva|null $reservaReq */
        $reservaReq = RequestReserva::findOne($id);
        if ($reservaReq === null) {
            return [
                'success' => false,
                'message' => Yii::t('app', 'La solicitud no existe.'),
            ];
        }

        $formModel = new DynamicModel(['response']);
        $formModel->addRule('response', 'required')
            ->addRule('response', 'string', [
                'max' => 500,
                'tooLong' => Yii::t('app', 'El mensaje no puede superar los 500 caracteres.'),
            ]);

        $request = Yii::$app->request;

        if ($request->isPost) {
            if ($formModel->load($request->post()) && $formModel->validate()) {

                $msg = new RequestResponse();
                $msg->id_request = $reservaReq->id;
                $msg->fecha = date('Y-m-d H:i:s');
                $msg->response = $formModel->response;
                $msg->is_response = 1; // respuesta interna / admin

                if (!$msg->save()) {
                    return [
                        'success' => false,
                        'message' => Yii::t('app', 'No se pudo guardar el mensaje.'),
                    ];
                }

                $formModel = new DynamicModel(['response']);
                $formModel->addRule('response', 'required')
                    ->addRule('response', 'string', ['max' => 500]);
            }
        }

        $messages = RequestResponse::find()
            ->where(['id_request' => $reservaReq->id])
            ->orderBy(['fecha' => SORT_ASC, 'id' => SORT_ASC])
            ->all();

        $chatAction = Url::to(['request-reserva/chat', 'id' => $reservaReq->id]);

        $html = $this->renderAjax('_chat_consultas', [
            'model' => $reservaReq,
            'messages' => $messages,
            'formModel' => $formModel,
            'canDelete' => true,          // 👈 admin puede borrar
            'chatAction' => $chatAction,   // 👈 form apunta acá
        ]);

        return [
            'success' => true,
            'html' => $html,
        ];
    }

    public function actionEliminarMensajeChat($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $permiso = Identificador::autorizar(
            Yii::$app->user->identity,
            Yii::$app->controller->id . '/update',
            "Modificar Solicitud",
            null
        );
        if (!$permiso['auth']) {
            return [
                'success' => false,
                'message' => Yii::t("app", $permiso["msg"]),
            ];
        }

        /** @var RequestResponse|null $msg */
        $msg = RequestResponse::find()
            ->with('request')
            ->where(['id' => $id])
            ->one();

        if (!$msg || !$msg->request) {
            return [
                'success' => false,
                'message' => Yii::t('app', 'El mensaje no existe o no tiene solicitud asociada.'),
            ];
        }

        $reservaReq = $msg->request;

        if ($msg->delete() === false) {
            return [
                'success' => false,
                'message' => Yii::t('app', 'No se pudo eliminar el mensaje.'),
            ];
        }

        // Volver a armar el chat
        $formModel = new DynamicModel(['response']);
        $formModel->addRule('response', 'required')
            ->addRule('response', 'string', ['max' => 500]);

        $messages = RequestResponse::find()
            ->where(['id_request' => $reservaReq->id])
            ->orderBy(['fecha' => SORT_ASC, 'id' => SORT_ASC])
            ->all();

        $html = $this->renderAjax('_chat_consultas', [
            'model' => $reservaReq,
            'messages' => $messages,
            'formModel' => $formModel,
        ]);

        return [
            'success' => true,
            'html' => $html,
        ];
    }


    /*
    public function actionEditarRango($id)
    {
        $permiso = Identificador::autorizar(
            Yii::$app->user->identity,
            Yii::$app->controller->id . '/editar-rango',
            "Modificar Rango Solicitud",
            null
        );
        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t("app", $permiso["msg"]));
            return $this->redirect($permiso["redirect"]);
        }

        // @var RequestReserva $model 
        $model = RequestReserva::find()
            ->with(['estado', 'reserva', 'requestCabanas.cabana'])
            ->where(['id' => $id])
            ->one();

        if (!$model) {
            throw new NotFoundHttpException(Yii::t('app', 'La solicitud no existe.'));
        }

        if (!$model->reserva) {
            Yii::$app->session->setFlash('danger', Yii::t('app', 'La solicitud no tiene una Reserva asociada.'));
            return $this->redirect(['view', 'id' => $model->id]);
        }

        // Cabañas del request
        $cabanas = [];
        $cabanaIds = [];
        foreach ($model->requestCabanas as $rc) {
            if ($rc->cabana) {
                $cabanas[] = $rc->cabana;
            }
            $cabanaIds[] = (int) $rc->id_cabana;
        }
        $cabanaIds = array_values(array_unique(array_filter($cabanaIds)));

        // Hora mínima de check-in según cabañas (reuso exacto de tu lógica)
        [$minH, $minM] = Utils::obtenerHoraMinimaCheckin($cabanas);

        // Form: solo periodo/desde/hasta (fechas)
        $form = new DynamicModel(['periodo', 'desde', 'hasta']);
        // precargar desde/hasta en d-m-Y para el widget
        $form->desde = $model->reserva->desde ? date('d-m-Y', strtotime($model->reserva->desde)) : null;
        $form->hasta = $model->reserva->hasta ? date('d-m-Y', strtotime($model->reserva->hasta)) : null;
        $form->periodo = ($form->desde && $form->hasta) ? ($form->desde . ' - ' . $form->hasta) : null;

        $form->addRule(['desde', 'hasta'], 'required');
        $form->addRule(['desde', 'hasta'], 'date', ['format' => 'php:d-m-Y']);

        // desde <= hasta (comparando como DateTime)
        $form->addRule('desde', function () use ($form) {
            if (!$form->desde || !$form->hasta)
                return;

            $d = \DateTime::createFromFormat('d-m-Y', $form->desde);
            $h = \DateTime::createFromFormat('d-m-Y', $form->hasta);
            if ($d && $h && $d > $h) {
                $form->addError('desde', Yii::t('app', 'La fecha "Desde" debe ser menor o igual a "Hasta".'));
            }
        });

        // POST
        if (Yii::$app->request->isPost && $form->load(Yii::$app->request->post())) {

            // Normalizar fechas a Y-m-d (sin hora) como pediste
            $desdeDate = Utils::normalizarFechaReserva($form->desde); // devuelve Y-m-d
            $hastaDate = Utils::normalizarFechaReserva($form->hasta); // devuelve Y-m-d

            // Reconstruir datetimes finales
            $desdeDT = new \DateTime($desdeDate . ' 00:00:00');
            $desdeDT->setTime($minH, $minM, 0);
            $desdeFinal = $desdeDT->format('Y-m-d H:i:s');

            $hastaFinal = $hastaDate . ' 23:59:59';

            // Validación de solape (excluyendo esta reserva)
            if (!empty($cabanaIds)) {
                $haySolape = Reserva::estanYaReservadasExcluyendo(
                    $desdeFinal,
                    $hastaFinal,
                    $cabanaIds,
                    (int) $model->reserva->id
                );
                if ($haySolape) {
                    $form->addError('desde', Yii::t('app', 'El nuevo período se superpone con otra reserva existente para alguna de las cabañas.'));
                }
            }

            // Ahora sí: validar reglas del form (requeridos + formato + desde<=hasta)
            if ($form->validate()) {
                $tx = Yii::$app->db->beginTransaction();
                try {
                    // 1) Reserva
                    $model->reserva->desde = $desdeFinal;
                    $model->reserva->hasta = $hastaFinal;

                    if (!$model->reserva->save(false, ['desde', 'hasta'])) {
                        throw new \Exception(Yii::t('app', 'No se pudo actualizar la Reserva.'));
                    }

                    // 2) RequestReserva (consistencia)
                    $model->desde = $desdeFinal;
                    $model->hasta = $hastaFinal;
                    if (!$model->save(false, ['desde', 'hasta'])) {
                        throw new \Exception(Yii::t('app', 'No se pudo actualizar la Solicitud.'));
                    }

                    $tx->commit();
                    Yii::$app->session->setFlash('success', Yii::t('app', 'Período actualizado correctamente.'));
                    return $this->redirect(['disponibilidad/seguimiento', 'hash' => $model->hash]);
                } catch (\Throwable $e) {
                    $tx->rollBack();
                    Yii::error('ERROR RequestReserva/editar-rango: ' . $e->getMessage(), __METHOD__);
                    Yii::$app->session->setFlash('danger', Yii::t('app', 'No se pudo actualizar: {m}', ['m' => $e->getMessage()]));
                }
            }
        }

        // cabañas como modelos Cabana
        $cabanas = [];
        foreach ($model->requestCabanas as $rc) {
            if ($rc->cabana)
                $cabanas[] = $rc->cabana;
        }

        // fechas actuales SOLO fecha (Y-m-d)
        $desde = $model->reserva->desde ? date('Y-m-d', strtotime($model->reserva->desde)) : null;
        $hasta = $model->reserva->hasta ? date('Y-m-d', strtotime($model->reserva->hasta)) : null;

        return $this->render('editar_rango', [
            'model' => $model,
            'formModel' => $form,
            'cabanas' => $cabanas,
            'desde' => $desde,
            'hasta' => $hasta,
        ]);

    }
        */

}
