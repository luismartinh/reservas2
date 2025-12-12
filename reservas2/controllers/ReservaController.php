<?php

namespace app\controllers;

use app\controllers\base\ReservaController as BaseReservaController;

use app\config\RootMenu;
use app\helpers\CalendarHelper;
use app\helpers\Utils;
use app\models\Cabana;
use app\models\CabanaTarifa;
use app\models\DisponibilidadSearch;
use app\models\Identificador;
use app\models\Locador;
use app\models\RequestReserva;
use app\models\RequestResponse;
use app\models\Reserva;
use app\models\ReservaSearch;
use yii\base\DynamicModel;
use yii\filters\AccessControl;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\web\Response;
use Yii;


/**
 * This is the class for controller "ReservaController".
 */
class ReservaController extends BaseReservaController
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
                        'reservar',
                        'solicitar-reserva',
                        'calendario-ocupacion',
                        'locador-autocomplete'
                    ],
                    'rules' => [
                        [
                            'allow' => true,
                            'actions' => [
                                'index',
                                'delete',
                                'cambiar-estado',
                                'reservar',
                                'solicitar-reserva',
                                'calendario-ocupacion',
                                'locador-autocomplete'
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
     * Lists all Reserva models.
     *
     * @throws InvalidConfigException
     * @return string|Response
     */
    public function actionIndex()
    {

        $u = Yii::$app->user->identity;

        $menu = new \app\models\Menu();
        $menu->descr = "Administrar reservas";
        $menu->label = "Solicitudes";
        $menu->menu = (string) RootMenu::ADMIN;
        $menu->menu_path = "Reservas/Reservas";
        $menu->url = Yii::$app->controller->id . '/index';

        $permiso = Identificador::autorizar(
            $u,
            Yii::$app->controller->id . '/index',
            "Administrar reservas",
            $menu
        );

        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t("app", $permiso["msg"]));
            return $this->redirect($permiso["redirect"]);
        }


        $searchModel = Yii::createObject(ReservaSearch::class);
        $dataProvider = $searchModel->searchIndex($this->request->get());

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }


    public function actionDelete($id)
    {
        $permiso = Identificador::autorizar(
            Yii::$app->user->identity,
            Yii::$app->controller->id . '/delete',
            "Eliminar Reerva",
            null
        );
        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t("app", $permiso["msg"]));
            return $this->redirect($permiso["redirect"]);
        }

        $modelOri = $this->findModel($id);
        $now = new \DateTime();



        if ($modelOri->estado->slug == "confirmado" || $modelOri->estado->slug == "confirmado-verificar-pago") {

            if ($reserva = $modelOri) {
                $fechaIngreso = new \DateTime($reserva->desde);
                $fechaEgreso = new \DateTime($reserva->hasta);

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

            }

        }

        try {
            $this->findModel($id)->delete();
            Yii::$app->session->setFlash('success', Yii::t("app", 'Se elimino correctamente'));
        } catch (\Exception $e) {
            Yii::$app->getSession()->addFlash('error', $e->errorInfo[2] ?? $e->getMessage());
            Yii::error("ERROR:" . Yii::$app->controller->id . "/delete " . ($e->errorInfo[2] ?? $e->getMessage()));
        }

        return $this->redirect(['index']);
    }


    public function actionReservar()
    {

        $u = Yii::$app->user->identity;

        $menu = new \app\models\Menu();
        $menu->descr = "Crear reserva";
        $menu->label = "Crear reserva";
        $menu->menu = (string) RootMenu::OPERATOR;
        $menu->menu_path = "Reservas/Crear-Reserva";
        $menu->url = Yii::$app->controller->id . '/reservar';

        $permiso = Identificador::autorizar(
            $u,
            Yii::$app->controller->id . '/reservar',
            "Crear reserva",
            $menu
        );

        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t("app", $permiso["msg"]));
            return $this->redirect($permiso["redirect"]);
        }

        $searchModel = Yii::createObject(DisponibilidadSearch::class);
        $dataProvider = $searchModel->search($this->request->get());

        // calcular totales solo si hay rango
        $totales = [];
        if (!empty($searchModel->desde) && !empty($searchModel->hasta)) {

            $ids = array_map(function ($m) {
                return (int) $m->id;
            }, $dataProvider->getModels());

            if ($ids) {
                $totales = CabanaTarifa::calcularTotalesParaCabanas($ids, $searchModel->desde, $searchModel->hasta);
            }
        }

        return $this->render('@app/views/disponibilidad/busqueda', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
            'totales' => $totales,     // 👈 pasamos los totales
            'esAdmin' => true
        ]);


    }


    public function actionSolicitarReserva($first_post = "1")
    {

        $u = Yii::$app->user->identity;
        $permiso = Identificador::autorizar(
            $u,
            Yii::$app->controller->id . '/reservar',
            "Crear reserva",
            null
        );

        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t("app", $permiso["msg"]));
            return $this->redirect($permiso["redirect"]);
        }

        $ids = Yii::$app->request->post('seleccionadas', []);
        $desde = Yii::$app->request->post('desde');
        $hasta = Yii::$app->request->post('hasta');

        if (empty($ids) || empty($desde) || empty($hasta)) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'Faltan datos de la solicitud.'));
            return $this->redirect(['buscar']);
        }

        $cabanas = Cabana::findAll($ids);




        // ⏱ Hora mínima de check-in entre cabañas
        [$minH, $minM] = Utils::obtenerHoraMinimaCheckin($cabanas);

        $desdeDate = Utils::normalizarFechaReserva($desde);
        $hastaDate = Utils::normalizarFechaReserva($hasta);

        // Construir rango final desde/hasta con hora
        $desdeDT = new \DateTime($desdeDate . ' 00:00:00');
        $desdeDT->setTime($minH, $minM, 0);
        $desdeFinal = $desdeDT->format('Y-m-d H:i:s');

        $hastaFinal = $hastaDate . ' 23:59:59';


        // 💰 Validar precios
        $totales = CabanaTarifa::calcularTotalesParaCabanas($ids, $desdeDate, $hastaDate);
        if (in_array(-1, $totales, true)) {
            Yii::$app->session->setFlash(
                'error',
                Yii::t('app', 'Algunas cabañas no tienen tarifas disponibles para el período seleccionado.')
            );
            return $this->redirect([
                'reserva/reservar',
                'DisponibilidadSearch[periodo]' => "$desde - $hasta",
                'DisponibilidadSearch[desde]' => $desde,
                'DisponibilidadSearch[hasta]' => $hasta,
            ]);

        }

        $paxAcumulado = 0;
        $totalGeneral = 0.0;
        foreach ($cabanas as $c) {
            $paxAcumulado += (int) $c->max_pax;     // pax = suma de max_pax
            $totalGeneral += ($totales[$c->id] ?? 0.0);
        }

        // Modelo dinámico para form (precargar denominación y email desde request_reserva)
        $formModel = new DynamicModel([
            'denominacion',
            'documento',
            'email',
            'telefono',
            'domicilio',
            'monto',
            'nota',
            'comprobante',
        ]);


        // Reglas
        $formModel->addRule(['denominacion', 'documento', 'email', 'telefono'], 'required');
        $formModel->addRule(['denominacion', 'domicilio'], 'string', ['max' => 100]);
        $formModel->addRule(['documento', 'email', 'telefono'], 'string', ['max' => 45]);
        $formModel->addRule(['email'], 'email');
        $formModel->addRule(['nota'], 'string', ['max' => 500]);

        // Regla de monto: entre 10% y 100% del total
        $minMonto = round($totalGeneral * 0.10, 2);
        $maxMonto = round($totalGeneral, 2);
        $formModel->addRule(['monto'], function ($attribute) use ($formModel, $minMonto, $maxMonto) {
            if (!is_numeric($formModel->$attribute)) {
                $formModel->addError($attribute, Yii::t('app', 'El monto debe ser numérico.'));
                return;
            }
            $val = (float) $formModel->$attribute;

            if ($val > $maxMonto) {
                $formModel->addError($attribute, Yii::t('app', 'El monto no puede superar el total {max}.', [
                    'max' => '$ ' . number_format($maxMonto, 2, ',', '.')
                ]));
            }
        });

        // archivo: 1 archivo, imágenes o pdf, máx 5MB 
        $formModel->addRule(['comprobante'], 'file', [
            'skipOnEmpty' => true,
            'extensions' => ['png', 'jpg', 'jpeg', 'pdf'],
            'checkExtensionByMimeType' => true,
            'maxSize' => 5 * 1024 * 1024,
        ]);


        //------------- POST ---------------

        if (Yii::$app->request->isPost && $first_post == "0") {
            $formModel->load(Yii::$app->request->post());
            $formModel->comprobante = \yii\web\UploadedFile::getInstance($formModel, 'comprobante');

            // Validar documento único en Locador
            if (!empty($formModel->documento)) {
                $docTaken = Locador::find()->where(['documento' => $formModel->documento])->exists();
                if ($docTaken) {
                    $formModel->addError('documento', Yii::t('app', 'El documento ya está registrado.'));
                }
            }

            // 👇 VALIDACIÓN GENERAL (incluye  file + reglas custom)
            if (!$formModel->validate()) {

                Yii::$app->session->setFlash(
                    'error',
                    Yii::t('app', 'Por favor corrija los errores del formulario.')
                );

                // Volver a mostrar la vista con los errores del formulario
                return $this->render('crear_reserva', [
                    'cabanas' => $cabanas,
                    'desde' => $desde,
                    'hasta' => $hasta,
                    'formModel' => $formModel,
                ]);
            }

            // 🔽 Si llega hasta acá, el formulario es válido            

            // Subida temporal pública
            $tmpPublicDir = Yii::getAlias('@webroot/uploads_tmp/comprobantes');
            if (!is_dir($tmpPublicDir)) {
                @mkdir($tmpPublicDir, 0775, true);
            }
            $archivoPublicWeb = null;
            $tempPathFs = null;

            if ($formModel->comprobante) {
                $base = 'comprobante_' . date('Ymd_His') . '_' . Yii::$app->security->generateRandomString(8);
                $filename = $base . '.' . $formModel->comprobante->extension;
                $tempPathFs = $tmpPublicDir . DIRECTORY_SEPARATOR . $filename; // FS path
                if ($formModel->comprobante->saveAs($tempPathFs)) {
                    $archivoPublicWeb = '/uploads_tmp/comprobantes/' . $filename; // URL pública temporal
                }
            }

            // -------------------------------------------------------------
            // LOCADOR: usar existente por email (del request) o crear nuevo
            // -------------------------------------------------------------
            // Tomamos el email precargado (readonly en el form)

            $locadorExistente = Locador::findOne(['email' => $formModel->email]);

            $emailFijo = $formModel->email;


            if ($locadorExistente === null) {
                // No existe: crear nuevo
                $locador = new Locador();
                $locador->email = $emailFijo; // set inicial
            } else {
                $locador = $locadorExistente;
            }
            // Actualizar (o setear) el resto de campos con los datos del formulario
            // Nota: email queda fijo por política (readonly en la vista)
            $locador->denominacion = $formModel->denominacion;
            $locador->documento = $formModel->documento;
            $locador->telefono = $formModel->telefono;
            $locador->domicilio = $formModel->domicilio;

            // Usar validadores del modelo (unique documento, unique email, longitudes, etc.)
            if (!$locador->validate()) {
                // Propagar errores de Locador al formulario
                foreach ($locador->getErrors() as $attr => $errs) {
                    foreach ($errs as $err) {
                        // Coinciden los nombres de atributos con el form
                        $formModel->addError($attr, $err);
                    }
                }

                // Volver a renderizar con errores, SIN transacción ni excepciones
                // Volver a mostrar la vista con los errores del formulario
                return $this->render('crear_reserva', [
                    'cabanas' => $cabanas,
                    'desde' => $desde,
                    'hasta' => $hasta,
                    'formModel' => $formModel,
                ]);
            }


            // -------------------------------------------------------------
            // VALIDACIÓN DE SOLAPAMIENTO DE FECHAS PARA CADA CABAÑA
            // -------------------------------------------------------------

            $existeSolape = Reserva::estanYaReservadas($desdeFinal, $hastaFinal, $ids);

            if ($existeSolape) {
                Yii::$app->session->setFlash('error', Yii::t(
                    'app',
                    'La(s) cabaña(s) seleccionada(s) ya se encuentra(n) reservada(s) en este período. ' .
                    'Por favor revise la disponibilidad.'
                ));

                return $this->redirect([
                    'reserva/reservar',
                    'DisponibilidadSearch[periodo]' => "$desde - $hasta",
                    'DisponibilidadSearch[desde]' => $desde,
                    'DisponibilidadSearch[hasta]' => $hasta,
                ]);
            }


            $tx = Yii::$app->db->beginTransaction();
            try {

                if (!$locador->save()) {
                    throw new \Exception(Yii::t('app', 'No se pudo guardar el Locador.'));
                }


                // Crear RequestREserva y Reserva con mismas fechas que request_reserva y pax = suma de max_pax
                $estadoConfirmando = \app\models\Estado::find()->where(['slug' => 'confirmado'])->one();
                $estadoIdConfirmando = $estadoConfirmando ? $estadoConfirmando->id : 1;



                $reqReserva = new RequestReserva([
                    'fecha' => date('Y-m-d H:i:s'),
                    'desde' => $desdeFinal,
                    'hasta' => $hastaFinal,
                    'denominacion' => $formModel->denominacion,
                    'email' => $emailFijo,
                    'total' => array_sum($totales),
                    'pax' => $paxAcumulado,
                    'hash' => Yii::$app->security->generateRandomString(32),
                    'id_estado' => $estadoIdConfirmando,
                    'obs' => $formModel->nota,
                ]);
                if (!$reqReserva->save()) {
                    throw new \Exception(Yii::t('app', 'Error al guardar la solicitud.'));
                }

                foreach ($ids as $idCabana) {
                    $reqCab = new \app\models\RequestCabana([
                        'id_request' => $reqReserva->id,
                        'id_cabana' => $idCabana,
                        'valor' => $totales[$idCabana] ?? 0,
                    ]);
                    if (!$reqCab->save()) {
                        throw new \Exception(Yii::t('app', 'Error al guardar una cabaña.'));
                    }


                    if ($reqReserva->obs) {
                        $resp = RequestResponse::newMessage($reqReserva, $reqReserva->obs, true);
                        if (!$resp['success']) {
                            throw new \Exception(Yii::t('app', 'Error al guardar un mensaje.'));
                        }
                    }
                }


                $reqReserva->codigo_reserva = RequestReserva::generateUniqueCodigoReserva($emailFijo);
                if (!$reqReserva->save()) {
                    throw new \Exception(Yii::t('app', 'Error al guardar la solicitud.(1)'));
                }

                $reqReserva->refresh();

                $reserva = new Reserva([
                    'fecha' => date('Y-m-d H:i:s'),
                    'desde' => $reqReserva->desde,
                    'hasta' => $reqReserva->hasta,
                    'id_locador' => $locador->id,
                    'pax' => max(1, (int) $paxAcumulado), // asegura > 0
                    'id_estado' => $estadoIdConfirmando,
                ]);
                if (!$reserva->save()) {
                    throw new \Exception(Yii::t('app', 'No se pudo guardar la Reserva.'));
                }

                // Crear ReservaCabana por cada cabana del request
                foreach ($reqReserva->requestCabanas as $rc) {
                    $rCab = new \app\models\ReservaCabana([
                        'id_reserva' => $reserva->id,
                        'id_cabana' => $rc->id_cabana,
                        'valor' => (float) $rc->valor,
                    ]);
                    if (!$rCab->save()) {
                        throw new \Exception(Yii::t('app', 'No se pudo guardar una Reserva-Cabaña.'));
                    }
                }

                // registro_pagos: compatible con múltiples comprobantes (append)
                $regPagos = is_array($reqReserva->registro_pagos) ? $reqReserva->registro_pagos : [];
                $entry = [
                    'fecha' => date('Y-m-d H:i:s'),
                    'monto' => (float) $formModel->monto,
                    'archivo' => $archivoPublicWeb, // por ahora referencia pública temporal
                ];
                $regPagos[] = $entry;

                $reqReserva->id_reserva = $reserva->id;
                $reqReserva->id_estado = $estadoIdConfirmando;
                $reqReserva->pagado = (float) $formModel->monto;
                $reqReserva->registro_pagos = $regPagos;

                if (!$reqReserva->save()) {
                    throw new \Exception(Yii::t('app', 'No se pudo actualizar el Request de reserva.'));
                }
                $tx->commit();

                // 🔒 Mover el archivo a carpeta privada y actualizar la ruta
                if ($tempPathFs && $archivoPublicWeb) {
                    $privateDir = Yii::getAlias('@runtime/priv_comprobantes');
                    if (!is_dir($privateDir)) {
                        @mkdir($privateDir, 0775, true);
                    }
                    $finalFs = $privateDir . DIRECTORY_SEPARATOR . basename($tempPathFs);
                    if (@rename($tempPathFs, $finalFs)) {
                        // Actualizar el último entry para que no quede público
                        $registros = is_array($reqReserva->registro_pagos) ? $reqReserva->registro_pagos : [];
                        if (!empty($registros)) {
                            $lastIndex = count($registros) - 1;
                            $registros[$lastIndex]['archivo'] = $finalFs; // almacenar ruta privada del FS
                            $reqReserva->registro_pagos = $registros;
                            // Guardar silenciosamente; si falla, no rompemos el flujo
                            @$reqReserva->save(false, ['registro_pagos']);
                        }
                    }
                }


                RequestReserva::enviarMailCambioEstado($reqReserva);

                $trackingUrl = Yii::$app->urlManager->createAbsoluteUrl([
                    'disponibilidad/seguimiento',
                    'hash' => $reqReserva->hash
                ]);
                return $this->render('reserva_registrada', [
                    'reservaReq' => $reqReserva,
                    'trackingUrl' => $trackingUrl,
                ]);

            } catch (\Throwable $e) {
                $tx->rollBack();
                Yii::$app->session->setFlash('error', Yii::t('app', 'No se pudo registrar la reserva: {m}', ['m' => $e->getMessage()]));
            }

        }


        //-------------- POST ---------------



        return $this->render('crear_reserva', [
            'cabanas' => $cabanas,
            'desde' => $desde,
            'hasta' => $hasta,
            'formModel' => $formModel,
        ]);
    }



    public function actionCalendarioOcupacion()
    {
        $u = Yii::$app->user->identity;

        $menu = new \app\models\Menu();
        $menu->descr = "Administrar reservas por calendario de ocupación";
        $menu->label = "Calendario de ocupación";
        $menu->menu = (string) RootMenu::ADMIN;
        $menu->menu_path = "Reservas/Calendario";
        $menu->url = Yii::$app->controller->id . '/calendario-ocupacion';

        $permiso = Identificador::autorizar(
            $u,
            Yii::$app->controller->id . '/calendario-ocupacion',
            "Administrar reservas por calendario de ocupación",
            $menu
        );

        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t("app", $permiso["msg"]));
            return $this->redirect($permiso["redirect"]);
        }

        $request = Yii::$app->request;

        // year / month desde GET, con default al mes actual
        $year = (int) $request->get('year', date('Y'));
        $month = (int) $request->get('month', date('m'));

        // 1) Rango base (dos meses)
        list($start1, $start2, $fromDate, $toDate) =
            CalendarHelper::buildTwoMonthRange($year, $month);

        // 2) Reservas + filtros (cabañas + locador) delegados al Search
        list($reservas, $selectedCabanas, $idLocador, $locadorLabel, $codigoReserva) =
            ReservaSearch::searchCalendario($request, $fromDate, $toDate);

        // 3) Cabañas para el filtro
        $cabanas = Cabana::find()
            ->orderBy(['descr' => SORT_ASC])
            ->all();

        // 4) Estructura del calendario + colores
        list($calendarData, $cabanaColors) = CalendarHelper::buildCalendarData(
            $reservas,
            $fromDate,
            $toDate,
            $selectedCabanas // 👈 ahora se pasa al helper
        );

        return $this->render('calendario/calendario_ocupacion', [
            'start1' => $start1,
            'start2' => $start2,
            'calendarData' => $calendarData,
            'cabanas' => $cabanas,
            'cabanaColors' => $cabanaColors,
            'selectedCabanas' => $selectedCabanas,
            'selectedLocadorId' => $idLocador,
            'selectedLocadorText' => $locadorLabel,
            'codigo_reserva' => $codigoReserva
        ]);
    }


    public function actionLocadorAutocomplete($q = null)
    {

        $permiso = Identificador::autorizar(
            Yii::$app->user->identity,
            Yii::$app->controller->id . '/reservar',
            "Crear reserva",
            null
        );
        if (!$permiso['auth']) {
            Yii::$app->session->setFlash('danger', Yii::t("app", $permiso["msg"]));
            return $this->redirect($permiso["redirect"]);
        }

        Yii::$app->response->format = Response::FORMAT_JSON;

        $term = trim((string) $q);
        if ($term === '') {
            return ['results' => []];
        }

        $locadores = Locador::find()
            ->andFilterWhere([
                'or',
                ['like', 'denominacion', $term],
                ['like', 'documento', $term],
                ['like', 'email', $term],
            ])
            ->orderBy(['denominacion' => SORT_ASC])
            ->limit(20)
            ->all();

        $results = [];
        foreach ($locadores as $loc) {
            /** @var Locador $loc */
            $parts = [];
            if ($loc->denominacion) {
                $parts[] = $loc->denominacion;
            }
            if ($loc->documento) {
                $parts[] = $loc->documento;
            }
            if ($loc->email) {
                $parts[] = $loc->email;
            }

            $results[] = [
                'id' => (int) $loc->id,
                'text' => implode(' - ', $parts),
            ];
        }

        return ['results' => $results];
    }
}
