<?php

namespace app\controllers;

use app\config\Niveles;
use app\helpers\Utils;
use app\models\Cabana;
use app\models\CabanaTarifa;
use app\models\DisponibilidadSearch;
use app\models\Notificaciones;
use app\models\ParametrosGenerales;
use app\models\RequestReserva;
use app\models\RequestResponse;
use app\models\Reserva;
use Yii;
use yii\base\DynamicModel;
use yii\bootstrap5\Html;
use yii\captcha\CaptchaAction;
use yii\captcha\CaptchaValidator;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class DisponibilidadController extends \yii\web\Controller
{

    public function actions()
    {
        return [
            'captcha' => [
                'class' => CaptchaAction::class,
                'maxLength' => 5,
                'minLength' => 5,
                'transparent' => true,
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    public function actionBuscar()
    {
        $this->layout = 'main';

        $searchModel = Yii::createObject(DisponibilidadSearch::class);
        $dataProvider = $searchModel->search($this->request->get());

        // calcular totales solo si hay rango
        $totales = [];
        if (!empty($searchModel->desde) && !empty($searchModel->hasta)) {
            $ids = array_map(fn($m) => (int) $m->id, $dataProvider->getModels());
            if ($ids) {
                $totales = CabanaTarifa::calcularTotalesParaCabanas($ids, $searchModel->desde, $searchModel->hasta);
            }
        }

        return $this->render('busqueda', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
            'totales' => $totales,     // 👈 pasamos los totales
            'esAdmin' => false
        ]);


    }


    public function actionBuscarEnCabana($id_cabana)
    {

        $this->layout = 'main';

        $cabana = Cabana::findOne($id_cabana);

        if (!$cabana) {
            throw new \yii\web\NotFoundHttpException();
        }

        $searchModel = Yii::createObject(DisponibilidadSearch::class);
        $dataProvider = $searchModel->searchEnCabana($id_cabana, $this->request->get());

        // calcular totales solo si hay rango
        $totales = [];
        if (!empty($searchModel->desde) && !empty($searchModel->hasta)) {
            $ids = array_map(fn($m) => (int) $m->id, $dataProvider->getModels());
            if ($ids) {
                $totales = CabanaTarifa::calcularTotalesParaCabanas($ids, $searchModel->desde, $searchModel->hasta);
            }
        }

        return $this->render('busqueda', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
            'totales' => $totales,     // 👈 pasamos los totales
            'esAdmin' => false,
            'cabana' => $cabana
        ]);


    }


    // Helper para crear el mismo modelo usado en ambas acciones
    private function crearFormModel()
    {
        $formModel = new DynamicModel(['denominacion', 'email', 'nota', 'verifyCode']);

        $formModel->addRule(['denominacion', 'email'], 'required');
        $formModel->addRule(['denominacion'], 'string', ['max' => 100]);
        $formModel->addRule(['email'], 'string', ['max' => 45]);
        $formModel->addRule(['email'], 'email');
        $formModel->addRule(['nota'], 'string', ['max' => 500]);

        // 🔹 Regla captcha
        $formModel->addRule('verifyCode', 'captcha', [
            'captchaAction' => 'disponibilidad/captcha',
            'message' => Yii::t('app', 'El código ingresado no es correcto. Intente nuevamente.'),
        ]);

        return $formModel;
    }

    public function actionSolicitarReserva()
    {

        $this->layout = 'main';

        $ids = Yii::$app->request->post('seleccionadas', []);
        $desde = Yii::$app->request->post('desde');
        $hasta = Yii::$app->request->post('hasta');

        if (empty($ids) || empty($desde) || empty($hasta)) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'Faltan datos de la solicitud.'));
            return $this->redirect(['buscar']);
        }

        $cabanas = Cabana::findAll($ids);

        // 🔹 Modelo para el formulario con captcha
        $formModel = $this->crearFormModel();

        return $this->render('solicitar_reserva', [
            'cabanas' => $cabanas,
            'desde' => $desde,
            'hasta' => $hasta,
            'formModel' => $formModel,
        ]);
    }

    private function limpiarSolicitudesAntiguas($hr_eliminar)
    {
        $estadoPendiente = \app\models\Estado::find()->where(['slug' => 'pendiente-email-verificar'])->one();
        if (!$estadoPendiente) {
            return 0;
        }

        $limite = (new \DateTime("-{$hr_eliminar} hours"))->format('Y-m-d H:i:s');
        return RequestReserva::deleteAll([
            'and',
            ['id_estado' => $estadoPendiente->id],
            ['<', 'fecha', $limite]
        ]);
    }



    public function actionEnviarSolicitudReserva()
    {
        $request = Yii::$app->request;

        // 👉 Datos base del POST
        $ids = (array) $request->post('seleccionadas', []);
        $desdeIn = trim((string) $request->post('desde'));
        $hastaIn = trim((string) $request->post('hasta'));

        // Crear el mismo modelo del formulario
        $formModel = $this->crearFormModel();

        // Cargar POST
        $formModel->load($request->post());

        // Validar
        if (!$formModel->validate()) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'Por favor corrija los errores del formulario.'));

            // Volver a mostrar formulario con errores
            $cabanas = Cabana::findAll($ids);
            return $this->render('solicitar_reserva', [
                'cabanas' => $cabanas,
                'desde' => $desdeIn,
                'hasta' => $hastaIn,
                'formModel' => $formModel,
            ]);
        }

        // Obtención final de valores
        $denominacion = trim($formModel->denominacion);
        $email = mb_strtolower(trim($formModel->email));
        $nota = trim($formModel->nota);


        // 👉 Configuración general RESERVA_CFG
        $cfg = ParametrosGenerales::getParametro('RESERVA_CFG')->valor ?? [];
        $hr_eliminar = (int) ($cfg['max_horas_venc']['request_reserva'] ?? 24);
        $max_reintentos = (int) ($cfg['max_reintentos']['request_reserva'] ?? 5);
        $email_token_expira = (int) ($cfg['max_reintentos']['email_token_expira'] ?? 48);



        // 🧹 Limpieza de solicitudes viejas
        $eliminadas = $this->limpiarSolicitudesAntiguas($hr_eliminar);
        if ($eliminadas > 0) {
            Notificaciones::NotificarANivel(
                Niveles::SYSADMIN,
                'request_reservas',
                "Eliminadas {$eliminadas} request_reservas solicitudes pendientes de más de {$hr_eliminar}h."
            );
            Yii::info("Eliminadas {$eliminadas} solicitudes pendientes de más de {$hr_eliminar}h.", __METHOD__);
        }

        // 📅 Normalizar fechas
        $desdeDate = Utils::normalizarFechaReserva($desdeIn);
        $hastaDate = Utils::normalizarFechaReserva($hastaIn);


        if (!$desdeDate || !$hastaDate) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'Período inválido.'));
            return $this->redirect(['buscar']);
        }

        // ⚖️ RATE-LIMIT por email + obtener id_estado pendiente
        [
            'estadoId' => $estadoId,
            'rateLimitOk' => $rateLimitOk,
            'message' => $rateMsg,
        ] = $this->verificarRateLimitEmail($email, $max_reintentos);

        if (!$rateLimitOk) {
            Yii::$app->session->setFlash('error', $rateMsg);
            return $this->redirect(['buscar']);
        }

        // 🏡 Cabañas seleccionadas
        $cabanas = Cabana::findAll($ids);

        // ⏱ Hora mínima de check-in entre cabañas
        // [$minH, $minM] = $this->obtenerHoraMinimaCheckin($cabanas);
        [$minH, $minM] = Utils::obtenerHoraMinimaCheckin($cabanas);

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
            return $this->redirect(['buscar']);
        }

        // 🛡 Verificar si ya existe una solicitud equivalente reciente
        $duplicada = $this->buscarSolicitudDuplicada($email, $desdeFinal, $hastaFinal, $ids);
        if ($duplicada !== null) {
            Yii::$app->session->setFlash(
                'info',
                Yii::t('app', 'Ya existe una solicitud para este email, fechas y cabañas. Se muestra la existente.')
            );

            return $this->redirect([
                'solicitud-generada',
                'id' => $duplicada->id,
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

            return $this->redirect(['buscar']);
        }


        // 💾 Alta del RequestReserva + RequestCabanas
        $tx = Yii::$app->db->beginTransaction();
        try {
            // token temporal para confirmar email (48hs)
            $token = Yii::$app->security->generateRandomString(48);
            $expira = (new \DateTime("+{$email_token_expira} hours"))->format('Y-m-d H:i:s');

            $reserva = new RequestReserva([
                'fecha' => date('Y-m-d H:i:s'),
                'desde' => $desdeFinal,
                'hasta' => $hastaFinal,
                'denominacion' => $denominacion,
                'email' => $email,
                'total' => array_sum($totales),
                'pax' => 0,
                'hash' => Yii::$app->security->generateRandomString(32),
                'id_estado' => $estadoId,
                'obs' => $nota,
                'email_token' => $token,
                'email_token_expira' => $expira,
            ]);
            if (!$reserva->save()) {
                throw new \Exception(Yii::t('app', 'Error al guardar la solicitud.'));
            }

            $reserva->codigo_reserva = RequestReserva::generateUniqueCodigoReserva($email);
            if (!$reserva->save()) {
                throw new \Exception(Yii::t('app', 'Error al guardar la solicitud.(1)'));
            }

            foreach ($ids as $idCabana) {
                $reqCab = new \app\models\RequestCabana([
                    'id_request' => $reserva->id,
                    'id_cabana' => $idCabana,
                    'valor' => $totales[$idCabana] ?? 0,
                ]);
                if (!$reqCab->save()) {
                    throw new \Exception(Yii::t('app', 'Error al guardar una cabaña.'));
                }

                if ($reserva->obs) {
                    $resp = RequestResponse::newMessage($reserva, $reserva->obs, false);
                    if (!$resp['success']) {
                        throw new \Exception(Yii::t('app', 'Error al guardar un mensaje.'));
                    }


                }

            }

            $tx->commit();

            // ✉️ Enviar email de confirmación (helper)
            $this->enviarMailConfirmacion($reserva, $token);


            // ✅ En vez de render, redirigimos (PRG)
            return $this->redirect([
                'solicitud-generada',
                'hash' => $reserva->hash,
            ]);


        } catch (\Throwable $e) {
            $tx->rollBack();
            Yii::$app->session->setFlash(
                'error',
                Yii::t('app', 'No se pudo crear la solicitud: {m}', ['m' => $e->getMessage()])
            );
            return $this->redirect(['buscar']);
        }
    }

    public function actionSolicitudGenerada($hash)
    {
        $this->layout = 'main';

        $estadoPendiente = \app\models\Estado::find()->where(['slug' => 'pendiente-email-verificar'])->one();
        $estadoId = (int) $estadoPendiente->id;

        $reserva = RequestReserva::find()->where(['hash' => $hash])->one();
        if ($reserva === null) {
            throw new \yii\web\NotFoundHttpException(Yii::t('app', 'Solicitud no encontrada.'));
        }

        if ($reserva->id_estado !== $estadoId) {
            return $this->redirect([
                'seguimiento',
                'hash' => $reserva->hash,
            ]);

        }

        // Podés obtener las cabañas desde la relación, si la tenés:
        // $cabanas = $reserva->requestCabanas ...
        // o si no:
        $cabanas = Cabana::find()
            ->joinWith('requestCabanas rc')
            ->where(['rc.id_request' => $reserva->id])
            ->all();

        // 👉 Configuración general RESERVA_CFG
        $cfg = ParametrosGenerales::getParametro('RESERVA_CFG')->valor ?? [];
        $hr_eliminar = (int) ($cfg['max_horas_venc']['request_reserva'] ?? 24);
        $max_reintentos = (int) ($cfg['max_reintentos']['request_reserva'] ?? 5);
        $email_token_expira_hr = (int) ($cfg['max_reintentos']['email_token_expira'] ?? 48);
        $fecha_expira = new \DateTime($reserva->email_token_expira);

        return $this->render('solicitud_generada', [
            'reserva' => $reserva,
            'cabanas' => $cabanas,
            'hash' => $reserva->hash,
            'email_token_expira_hr' => $email_token_expira_hr,
            'fecha_expira' => $fecha_expira,
        ]);
    }


    /**
     * Busca si ya existe una solicitud "equivalente"
     * (mismo email, desde, hasta y mismas cabañas).
     *
     * @param string $email
     * @param string $desdeFinal Y-m-d H:i:s
     * @param string $hastaFinal Y-m-d H:i:s
     * @param array $idsCabanasSeleccionadas
     * @return \app\models\RequestReserva|null
     */
    protected function buscarSolicitudDuplicada(
        string $email,
        string $desdeFinal,
        string $hastaFinal,
        array $idsCabanasSeleccionadas
    ) {
        if (empty($idsCabanasSeleccionadas)) {
            return null;
        }

        // Normalizamos el array de ids de cabañas del request nuevo
        sort($idsCabanasSeleccionadas);

        // Buscamos reservas con mismo email + rango fechas
        // (opcional: podés filtrar también por id_estado si querés solo PENDIENTE, etc.)
        $posibles = \app\models\RequestReserva::find()
            ->where([
                'email' => $email,
                'desde' => $desdeFinal,
                'hasta' => $hastaFinal,
            ])
            ->orderBy(['id' => SORT_DESC]) // más reciente primero (por si hay varias)
            ->all();

        if (empty($posibles)) {
            return null;
        }

        foreach ($posibles as $reserva) {
            $idsCabanasReserva = ArrayHelper::getColumn($reserva->requestCabanas, 'id_cabana');

            if (empty($idsCabanasReserva)) {
                continue;
            }

            sort($idsCabanasReserva);

            // ✅ Coinciden exactamente los mismos IDs de cabañas
            if ($idsCabanasReserva === $idsCabanasSeleccionadas) {
                return $reserva;
            }
        }

        return null;
    }


    /**
     * Verifica el rate-limit por email para RequestReserva en estado "pendiente-email-verificar".
     *
     * @param string $email          Email normalizado (lowercase, trim).
     * @param int    $maxReintentos  Máximo de solicitudes pendientes permitidas.
     *
     * @return array [
     *    'estadoId'    => int,        // id del estado pendiente (o 1 si no existe)
     *    'rateLimitOk' => bool,       // false si se alcanzó el límite
     *    'message'     => ?string,    // mensaje de error en caso de overflow
     * ]
     */
    protected function verificarRateLimitEmail(string $email, int $maxReintentos): array
    {
        // Buscar estado "pendiente-email-verificar"
        $estadoPendiente = \app\models\Estado::find()
            ->where(['slug' => 'pendiente-email-verificar'])
            ->one();

        // Si no existe el estado, devolvemos un id por defecto (1) y no aplicamos rate limit estricto
        if (!$estadoPendiente) {
            return [
                'estadoId' => 1,
                'rateLimitOk' => true,
                'message' => null,
            ];
        }

        $estadoId = (int) $estadoPendiente->id;

        // Si la config dice 0 o negativo, no aplicamos rate limit
        if ($maxReintentos <= 0) {
            return [
                'estadoId' => $estadoId,
                'rateLimitOk' => true,
                'message' => null,
            ];
        }

        // Contar cuántas solicitudes pendientes tiene este email
        $pendientesCount = (int) \app\models\RequestReserva::find()
            ->where([
                'email' => $email,
                'id_estado' => $estadoId,
            ])
            ->count();

        if ($pendientesCount >= $maxReintentos) {
            $msg = Yii::t(
                'app',
                'Se alcanzó el límite de solicitudes pendientes para este email. 
                Por favor, confirme alguna de las solicitudes enviadas anteriormente o espere antes de volver a intentar.'
            );

            return [
                'estadoId' => $estadoId,
                'rateLimitOk' => false,
                'message' => $msg,
            ];
        }

        return [
            'estadoId' => $estadoId,
            'rateLimitOk' => true,
            'message' => null,
        ];
    }


    /**
     * Envía el mail de confirmación usando la vista mail_confirmacion.
     */
    protected function enviarMailConfirmacion(RequestReserva $reserva, string $token): void
    {
        $confirmUrl = Yii::$app->urlManager->createAbsoluteUrl(['disponibilidad/confirmar-email', 'token' => $token]);
        $trackingUrl = Yii::$app->urlManager->createAbsoluteUrl(['disponibilidad/seguimiento', 'hash' => $reserva->hash]);

        $body = $this->renderPartial('mail_confirmacion', [
            'reserva' => $reserva,
            'confirmUrl' => $confirmUrl,
            'trackingUrl' => $trackingUrl,
        ]);

        $fromEmail = Yii::$app->params['senderEmail'] ?? null;
        $fromName = Yii::$app->params['senderName'] ?? 'Reservas';

        

        if (!$fromEmail) {
            Yii::warning('senderEmail no configurado; no se envía correo de confirmación.', __METHOD__);
            return;
        }


        $bccEmail = Yii::$app->params['bccEmail'] ?? null;

        if(!$bccEmail){
            $ok = Yii::$app->mailer->compose()
                ->setFrom([$fromEmail => $fromName])
                ->setTo($reserva->email)
                ->setSubject(Yii::t('app', 'Confirmación de email - Solicitud de Reserva ') . $reserva->codigo_reserva)
                ->setHtmlBody($body)
                ->send();
            
        }else{
            $ok = Yii::$app->mailer->compose()
                ->setFrom([$fromEmail => $fromName])
                ->setTo($reserva->email)
                ->setBcc($bccEmail)
                ->setSubject(Yii::t('app', 'Confirmación de email - Solicitud de Reserva ') . $reserva->codigo_reserva)
                ->setHtmlBody($body)
                ->send();
        }

        if (!$ok) {
            Yii::warning('Fallo al enviar email de confirmación', __METHOD__);
        }
    }



    public function actionConfirmarEmail($token)
    {
        $reserva = RequestReserva::find()
            ->where(['email_token' => $token])
            ->one();

        if (!$reserva) {
            return $this->render('confirmar_email_resultado', [
                'ok' => false,
                'msg' => Yii::t('app', 'Token inválido o ya utilizado.'),
                'trackingUrl' => null,
            ]);
        }

        // ¿Expiró?
        if ($reserva->email_token_expira && (new \DateTime()) > new \DateTime($reserva->email_token_expira)) {
            return $this->render('confirmar_email_resultado', [
                'ok' => false,
                'msg' => Yii::t('app', 'El enlace de confirmación ha expirado.'),
                'trackingUrl' => null,
            ]);
        }

        // Cambiar estado a "pendiente-email-verificado"
        $estadoVerif = \app\models\Estado::find()->where(['slug' => 'pendiente-email-verificado'])->one();
        if ($estadoVerif) {
            $reserva->id_estado = $estadoVerif->id;
        }

        // invalidar token (one-time)
        $reserva->email_token = null;
        $reserva->email_token_expira = null;

        if (!$reserva->save(false)) {
            return $this->render('confirmar_email_resultado', [
                'ok' => false,
                'msg' => Yii::t('app', 'No se pudo confirmar el email. Intente nuevamente.'),
                'trackingUrl' => null,
                'requestReserva' => $reserva
            ]);
        }

        $trackingUrl = Url::to([
            'request-reserva/index',
            'RequestReservaSearch[id]' => $reserva->id,
        ], true); // true = URL absoluta        

        $botomVerReq = Html::a(
            '<i class="bi bi-box-arrow-up-right me-1"></i>' . Yii::t('app', 'Ver solicitud'),
            $trackingUrl,
            ['class' => 'btn btn-outline-primary', 'target' => '_blank', 'rel' => 'noopener']
        );

        Notificaciones::NotificarANivel(
            [Niveles::SYSADMIN, Niveles::ADMIN, Niveles::OPERATOR],
            'request_reservas',
            "Se confirmo el email de una solicitud.<br>" . $botomVerReq
        );


        $trackingUrl = Yii::$app->urlManager->createAbsoluteUrl(['disponibilidad/seguimiento', 'hash' => $reserva->hash]);

        return $this->render('confirmar_email_resultado', [
            'ok' => true,
            'msg' => Yii::t('app', 'Su email fue verificado correctamente.'),
            'trackingUrl' => $trackingUrl,
        ]);
    }


    public function actionSeguimiento($hash)
    {

        $this->layout = 'main';

        /** @var \app\models\RequestReserva|null $reserva */
        $reserva = RequestReserva::find()
            ->where(['hash' => $hash])
            ->with(['requestCabanas.cabana', 'estado'])
            ->one();

        if (!$reserva) {
            throw new \yii\web\NotFoundHttpException(Yii::t('app', 'Solicitud no encontrada.'));
        }

        // Mapear totales por id_cabana desde request_cabanas
        $totales = [];
        $cabanas = [];
        if (!empty($reserva->requestCabanas)) {
            foreach ($reserva->requestCabanas as $rc) {
                $totales[(int) $rc->id_cabana] = (float) $rc->valor;
                if ($rc->cabana) {
                    $cabanas[] = $rc->cabana;
                }
            }
        }

        // Fechas: desde/hasta vienen con hora (min checkin / 23:59:59) guardadas en la creación
        $desdeDate = new \DateTime(substr($reserva->desde, 0, 10));
        $hastaDate = new \DateTime(substr($reserva->hasta, 0, 10));
        $dias = (int) $desdeDate->diff($hastaDate)->days + 1;

        $fechaIngreso = new \DateTime($reserva->desde);
        $fechaEgreso = new \DateTime($reserva->hasta);
        $creado = new \DateTime($reserva->fecha);

        // Totales de resumen
        $paxAcumulado = 0;
        $totalGeneral = 0.0;
        foreach ($cabanas as $c) {
            $paxAcumulado += (int) $c->max_pax;
            $totalGeneral += $totales[$c->id] ?? 0.0;
        }

        // URL de seguimiento absoluta (esta misma página)
        $trackingUrl = Yii::$app->urlManager->createAbsoluteUrl(['disponibilidad/seguimiento', 'hash' => $hash]);
        // 👉 Configuración general RESERVA_CFG
        $cfg = ParametrosGenerales::getParametro('RESERVA_CFG')->valor ?? [];
        $hr_eliminar = (int) ($cfg['max_horas_venc']['request_reserva'] ?? 24);
        $max_reintentos = (int) ($cfg['max_reintentos']['request_reserva'] ?? 5);
        $email_token_expira = (int) ($cfg['max_reintentos']['email_token_expira'] ?? 48);
        $fecha_expira = (new \DateTime($reserva->fecha))->modify("+{$email_token_expira} hours");
        $confirmar_pago_expira = (int) ($cfg['max_horas_venc']['confirmar_pago'] ?? 48);
        $fecha_confirmar_pago_expira = (new \DateTime($reserva->fecha))->modify("+{$confirmar_pago_expira} hours");


        return $this->render('seguimiento', [
            'reserva' => $reserva,
            'cabanas' => $cabanas,
            'totales' => $totales,
            'desdeDate' => $desdeDate,
            'hastaDate' => $hastaDate,
            'dias' => $dias,
            'fechaIngreso' => $fechaIngreso,
            'fechaEgreso' => $fechaEgreso,
            'paxAcumulado' => $paxAcumulado,
            'totalGeneral' => $totalGeneral,
            'trackingUrl' => $trackingUrl,
            'email_token_expira_hr' => $email_token_expira,
            'fecha_expira' => $fecha_expira,
            'confirmar_pago_expira_hr' => $confirmar_pago_expira,
            'fecha_confirmar_pago_expira' => $fecha_confirmar_pago_expira,
            'showChatButton' => Yii::$app->user->isGuest,

        ]);
    }


    public function actionRegistrarPago($hash)
    {
        $reservaReq = RequestReserva::find()
            ->where(['hash' => $hash])
            ->with(['requestCabanas', 'requestCabanas.cabana', 'estado'])
            ->one();

        if (!$reservaReq) {
            throw new \yii\web\NotFoundHttpException(Yii::t('app', 'Solicitud no encontrada.'));
        }

        // -------------------------------------------
        // 1) Validar estado permitido para registrar pago
        // -------------------------------------------
        $slug = $reservaReq->estado->slug ?? null;
        $estadosPermitidos = ['pendiente-email-contestado', 'pendiente-email-verificado'];

        if (!in_array($slug, $estadosPermitidos, true)) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'La solicitud no se encuentra en un estado que permita registrar el pago.')
            );
        }


        // -------------------------------------------
        // 2) Calcular vencimiento del plazo de pago
        // -------------------------------------------
        $cfg = ParametrosGenerales::getParametro('RESERVA_CFG')->valor ?? [];
        $confirmar_pago_expira_hr = (int) ($cfg['max_horas_venc']['confirmar_pago'] ?? 48);

        // La base es la fecha de creación de la solicitud
        $fecha_confirmar_pago_expira = (new \DateTime($reservaReq->fecha))
            ->modify("+{$confirmar_pago_expira_hr} hours");

        $ahora = new \DateTime();

        if ($ahora > $fecha_confirmar_pago_expira) {
            // Podés tirar Forbidden o redirigir con mensaje; te dejo la opción "amigable"
            Yii::$app->session->setFlash(
                'error',
                Yii::t('app', 'El plazo para registrar el pago ha vencido. La solicitud puede haber sido cancelada.')
            );

            // Redirigimos al seguimiento de la solicitud
            return $this->redirect([
                'disponibilidad/seguimiento',
                'hash' => $reservaReq->hash,
            ]);

        }
        // Cabañas y totales
        $cabanas = [];
        $totales = [];
        foreach ($reservaReq->requestCabanas as $rc) {
            $cabanas[] = $rc->cabana;
            $totales[(int) $rc->id_cabana] = (float) $rc->valor;
        }



        // Fechas para mostrar y cálculo de resumen
        $desdeDate = new \DateTime(substr($reservaReq->desde, 0, 10)); // MISMAS fechas que request_reservas
        $hastaDate = new \DateTime(substr($reservaReq->hasta, 0, 10));
        $dias = (int) $desdeDate->diff($hastaDate)->days + 1;
        $fechaIngreso = new \DateTime($reservaReq->desde); // ya viene con hora min check-in
        $fechaEgreso = new \DateTime($reservaReq->hasta); // ya viene con 23:59:59 (o 23:56:59 según tu lógica)

        $paxAcumulado = 0;
        $totalGeneral = 0.0;
        foreach ($cabanas as $c) {
            $paxAcumulado += (int) $c->max_pax;     // pax = suma de max_pax
            $totalGeneral += ($totales[$c->id] ?? 0.0);
        }

        // Modelo dinámico para form (precargar denominación y email desde request_reserva)
        $form = new DynamicModel([
            'denominacion',
            'documento',
            'email',
            'telefono',
            'domicilio',
            'monto',
            'comprobante',
            'verifyCode', // 👈 nuevo campo para captcha
        ]);
        // Precarga por defecto desde el request (readonly en la vista)
        $form->denominacion = $reservaReq->denominacion;
        $form->email = $reservaReq->email;

        // Si existe un Locador con ese email, precargar TODOS los datos del locador
        $locadorExistente = \app\models\Locador::findOne(['email' => $reservaReq->email]);
        if ($locadorExistente) {
            // Si el locador ya tiene denominación, usamos la del locador para mantener coherencia
            $form->denominacion = $locadorExistente->denominacion ?: $form->denominacion;

            // Email debe coincidir (quedará readonly en la vista)
            $form->email = $locadorExistente->email;

            // Estos campos se precargan para edición
            $form->documento = $locadorExistente->documento;
            $form->telefono = $locadorExistente->telefono;
            $form->domicilio = $locadorExistente->domicilio;
        }

        // Reglas
        $form->addRule(['denominacion', 'documento', 'email', 'telefono'], 'required');
        $form->addRule(['denominacion', 'domicilio'], 'string', ['max' => 100]);
        $form->addRule(['documento', 'email', 'telefono'], 'string', ['max' => 45]);
        $form->addRule(['email'], 'email');

        // Regla de monto: entre 10% y 100% del total
        $minMonto = round($totalGeneral * 0.10, 2);
        $maxMonto = round($totalGeneral, 2);
        $form->addRule(['monto'], function ($attribute) use ($form, $minMonto, $maxMonto) {
            if (!is_numeric($form->$attribute)) {
                $form->addError($attribute, Yii::t('app', 'El monto debe ser numérico.'));
                return;
            }
            $val = (float) $form->$attribute;
            if ($val < $minMonto) {
                $form->addError($attribute, Yii::t('app', 'El monto mínimo es {min}.', [
                    'min' => '$ ' . number_format($minMonto, 2, ',', '.')
                ]));
            }
            if ($val > $maxMonto) {
                $form->addError($attribute, Yii::t('app', 'El monto no puede superar el total {max}.', [
                    'max' => '$ ' . number_format($maxMonto, 2, ',', '.')
                ]));
            }
        });

        // archivo: 1 archivo, imágenes o pdf, máx 5MB (OBLIGATORIO)
        $form->addRule(['comprobante'], 'file', [
            'skipOnEmpty' => false,              // ← ahora es requerido
            'extensions' => ['png', 'jpg', 'jpeg', 'pdf'],
            'checkExtensionByMimeType' => true,
            'maxSize' => 5 * 1024 * 1024,
        ]);

        // 👇 Nueva regla de captcha
        $form->addRule('verifyCode', 'captcha', [
            'captchaAction' => 'disponibilidad/captcha',
            'message' => Yii::t('app', 'El código ingresado no es correcto. Intente nuevamente.'),
        ]);

        // Datos de banco
        $banco = Yii::$app->params['bank'] ?? [
            'banco' => 'Banco Ejemplo',
            'titular' => 'Titular Ejemplo',
            'cbu' => '0000000000000000000000',
            'alias' => 'alias.ejemplo',
            'cuit' => '00-00000000-0',
            'tipo' => 'CC',
            'nro' => '000000/0'
        ];


        if (Yii::$app->request->isPost) {
            $form->load(Yii::$app->request->post());
            $form->comprobante = \yii\web\UploadedFile::getInstance($form, 'comprobante');

            // Validar documento único en Locador
            if (!empty($form->documento)) {
                $docTaken = \app\models\Locador::find()->where(['documento' => $form->documento])->exists();
                if ($docTaken) {
                    $form->addError('documento', Yii::t('app', 'El documento ya está registrado.'));
                }
            }

            // 👇 VALIDACIÓN GENERAL (incluye captcha + file + reglas custom)
            if (!$form->validate()) {

                Yii::$app->session->setFlash(
                    'error',
                    Yii::t('app', 'Por favor corrija los errores del formulario.')
                );

                // Volver a mostrar la vista con los errores del formulario
                return $this->render('registrar_pago', [
                    'reservaReq' => $reservaReq,
                    'cabanas' => $cabanas,
                    'totales' => $totales,
                    'form' => $form,
                    'banco' => $banco,
                    'desdeDate' => $desdeDate,
                    'hastaDate' => $hastaDate,
                    'dias' => $dias,
                    'fechaIngreso' => $fechaIngreso,
                    'fechaEgreso' => $fechaEgreso,
                    'paxAcumulado' => $paxAcumulado,
                    'totalGeneral' => $totalGeneral,
                    'confirmar_pago_expira_hr' => $confirmar_pago_expira_hr,
                    'fecha_confirmar_pago_expira' => $fecha_confirmar_pago_expira,
                ]);
            }

            // 🔽 Si llega hasta acá, el formulario (incluyendo captcha) es válido            


            // Subida temporal pública
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

            // -------------------------------------------------------------
            // LOCADOR: usar existente por email (del request) o crear nuevo
            // -------------------------------------------------------------
            // Tomamos el email precargado (readonly en el form)
            $emailFijo = $reservaReq->email; // viene de $reservaReq->email


            if ($locadorExistente === null) {
                // No existe: crear nuevo
                $locador = new \app\models\Locador();
                $locador->email = $emailFijo; // set inicial
            } else {
                $locador = $locadorExistente;
            }
            // Actualizar (o setear) el resto de campos con los datos del formulario
            // Nota: email queda fijo por política (readonly en la vista)
            $locador->denominacion = $form->denominacion;
            $locador->documento = $form->documento;
            $locador->telefono = $form->telefono;
            $locador->domicilio = $form->domicilio;

            // Usar validadores del modelo (unique documento, unique email, longitudes, etc.)
            if (!$locador->validate()) {
                // Propagar errores de Locador al formulario
                foreach ($locador->getErrors() as $attr => $errs) {
                    foreach ($errs as $err) {
                        // Coinciden los nombres de atributos con el form
                        $form->addError($attr, $err);
                    }
                }

                // Volver a renderizar con errores, SIN transacción ni excepciones
                return $this->render('registrar_pago', [
                    'reservaReq' => $reservaReq,
                    'cabanas' => $cabanas,
                    'totales' => $totales,
                    'form' => $form,
                    'banco' => $banco,
                    'desdeDate' => $desdeDate,
                    'hastaDate' => $hastaDate,
                    'dias' => $dias,
                    'fechaIngreso' => $fechaIngreso,
                    'fechaEgreso' => $fechaEgreso,
                    'paxAcumulado' => $paxAcumulado,
                    'totalGeneral' => $totalGeneral,
                    'confirmar_pago_expira_hr' => $confirmar_pago_expira_hr,
                    'fecha_confirmar_pago_expira' => $fecha_confirmar_pago_expira,
                ]);
            }


            // -------------------------------------------------------------
            // VALIDACIÓN DE SOLAPAMIENTO DE FECHAS PARA CADA CABAÑA
            // -------------------------------------------------------------

            $existeSolape = Reserva::estanReservadas($reservaReq);

            if ($existeSolape) {
                Yii::$app->session->setFlash('error', Yii::t(
                    'app',
                    'La cabaña seleccionada ya se encuentra reservada en este período. ' .
                    'Por favor revise la disponibilidad.'
                ));

                return $this->redirect([
                    'disponibilidad/seguimiento',
                    'hash' => $reservaReq->hash,
                ]);
            }


            $tx = Yii::$app->db->beginTransaction();
            try {

                if (!$locador->save()) {
                    throw new \Exception(Yii::t('app', 'No se pudo guardar el Locador.'));
                }


                // Crear Reserva con mismas fechas que request_reserva y pax = suma de max_pax
                $estadoVerificar = \app\models\Estado::find()->where(['slug' => 'confirmado-verificar-pago'])->one();
                $estadoIdVerificar = $estadoVerificar ? $estadoVerificar->id : 1;

                $reserva = new Reserva([
                    'fecha' => date('Y-m-d H:i:s'),
                    'desde' => $reservaReq->desde,
                    'hasta' => $reservaReq->hasta,
                    'id_locador' => $locador->id,
                    'pax' => max(1, (int) $paxAcumulado), // asegura > 0
                    'id_estado' => $estadoIdVerificar,
                ]);
                if (!$reserva->save()) {
                    throw new \Exception(Yii::t('app', 'No se pudo guardar la Reserva.'));
                }

                // Crear ReservaCabana por cada cabana del request
                foreach ($reservaReq->requestCabanas as $rc) {
                    $rCab = new \app\models\ReservaCabana([
                        'id_reserva' => $reserva->id,
                        'id_cabana' => $rc->id_cabana,
                        'valor' => (float) $rc->valor,
                    ]);
                    if (!$rCab->save()) {
                        throw new \Exception(Yii::t('app', 'No se pudo guardar una Reserva-Cabaña.'));
                    }
                }

                // Cambiar estado del RequestReserva a confirmado-verificar-pago
                $estadoConfVerif = \app\models\Estado::find()->where(['slug' => 'confirmado-verificar-pago'])->one();
                $estadoIdConfVerif = $estadoConfVerif ? $estadoConfVerif->id : $reservaReq->id_estado;

                // registro_pagos: compatible con múltiples comprobantes (append)
                $regPagos = is_array($reservaReq->registro_pagos) ? $reservaReq->registro_pagos : [];
                $entry = [
                    'fecha' => date('Y-m-d H:i:s'),
                    'monto' => (float) $form->monto,
                    'archivo' => $archivoPublicWeb, // por ahora referencia pública temporal
                ];
                $regPagos[] = $entry;

                $reservaReq->id_reserva = $reserva->id;
                $reservaReq->id_estado = $estadoIdConfVerif;
                $reservaReq->pagado = (float) $form->monto;
                $reservaReq->registro_pagos = $regPagos;

                if (!$reservaReq->save()) {
                    throw new \Exception(Yii::t('app', 'No se pudo actualizar el Request de reserva.'));
                }

                $trackingUrl = Url::to([
                    'request-reserva/index',
                    'RequestReservaSearch[id]' => $reservaReq->id,
                ], true); // true = URL absoluta        

                $botomVerReq = Html::a(
                    '<i class="bi bi-box-arrow-up-right me-1"></i>' . Yii::t('app', 'Ver solicitud'),
                    $trackingUrl,
                    ['class' => 'btn btn-outline-primary', 'target' => '_blank', 'rel' => 'noopener']
                );

                Notificaciones::NotificarANivel(
                    [Niveles::SYSADMIN, Niveles::ADMIN, Niveles::OPERATOR],
                    'request_reservas',
                    "Se realizo el pago de una nueva reserva.<br>" . $botomVerReq
                );


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
                        $registros = is_array($reservaReq->registro_pagos) ? $reservaReq->registro_pagos : [];
                        if (!empty($registros)) {
                            $lastIndex = count($registros) - 1;
                            $registros[$lastIndex]['archivo'] = $finalFs; // almacenar ruta privada del FS
                            $reservaReq->registro_pagos = $registros;
                            // Guardar silenciosamente; si falla, no rompemos el flujo
                            @$reservaReq->save(false, ['registro_pagos']);
                        }
                    }
                }


                $reservaReq->refresh();
                RequestReserva::enviarMailCambioEstado($reservaReq);

                $trackingUrl = Yii::$app->urlManager->createAbsoluteUrl([
                    'disponibilidad/seguimiento',
                    'hash' => $reservaReq->hash
                ]);
                return $this->render('pago_registrado', [
                    'reservaReq' => $reservaReq,
                    'trackingUrl' => $trackingUrl,
                ]);

            } catch (\Throwable $e) {
                $tx->rollBack();
                Yii::$app->session->setFlash('error', Yii::t('app', 'No se pudo registrar el pago: {m}', ['m' => $e->getMessage()]));
            }

        }


        return $this->render('registrar_pago', [
            'reservaReq' => $reservaReq,
            'cabanas' => $cabanas,
            'totales' => $totales,
            'form' => $form,
            'banco' => $banco,
            'desdeDate' => $desdeDate,
            'hastaDate' => $hastaDate,
            'dias' => $dias,
            'fechaIngreso' => $fechaIngreso,
            'fechaEgreso' => $fechaEgreso,
            'paxAcumulado' => $paxAcumulado,
            'totalGeneral' => $totalGeneral,
            'confirmar_pago_expira_hr' => $confirmar_pago_expira_hr,
            'fecha_confirmar_pago_expira' => $fecha_confirmar_pago_expira,

        ]);
    }


    public function actionVerComprobante($hash, $k)
    {
        /** @var \app\models\RequestReserva|null $reserva */
        $reserva = \app\models\RequestReserva::find()
            ->where(['hash' => $hash])
            ->one();

        if (!$reserva) {
            throw new \yii\web\NotFoundHttpException(Yii::t('app', 'Solicitud no encontrada.'));
        }

        $registros = is_array($reserva->registro_pagos) ? $reserva->registro_pagos : [];

        // $k es el índice del comprobante en el array
        if (!isset($registros[$k]['archivo']) || empty($registros[$k]['archivo'])) {
            throw new \yii\web\NotFoundHttpException(Yii::t('app', 'Comprobante no encontrado.'));
        }

        $filePath = $registros[$k]['archivo'];

        if (!is_file($filePath)) {
            throw new \yii\web\NotFoundHttpException(Yii::t('app', 'Archivo de comprobante no disponible.'));
        }

        return Yii::$app->response->sendFile(
            $filePath,
            basename($filePath),
            ['inline' => true]   // inline: lo abre en el navegador (pdf/img)
        );
    }


    public function actionConsultaChat($hash)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        /** @var RequestReserva|null $reservaReq */
        $reservaReq = RequestReserva::find()
            ->where(['hash' => $hash])
            ->one();

        if ($reservaReq === null) {
            return [
                'success' => false,
                'message' => Yii::t('app', 'La solicitud no existe.'),
            ];
        }

        // Modelo dinámico para el textarea (consulta del cliente)
        $formModel = new DynamicModel(['response']);
        $formModel->addRule('response', 'required')
            ->addRule('response', 'string', [
                'max' => 500,
                'tooLong' => Yii::t('app', 'El mensaje no puede superar los 500 caracteres.'),
            ]);

        $request = Yii::$app->request;

        if ($request->isPost) {
            if ($formModel->load($request->post()) && $formModel->validate()) {


                $resp = RequestResponse::newMessage($reservaReq, $formModel->response, false);
                if (!$resp['success']) {
                    return $resp;
                }

                $trackingUrl = Url::to([
                    'request-reserva/index',
                    'RequestReservaSearch[id]' => $reservaReq->id,
                ], true); // true = URL absoluta        

                $botomVerReq = Html::a(
                    '<i class="bi bi-box-arrow-up-right me-1"></i>' . Yii::t('app', 'Ver solicitud'),
                    $trackingUrl,
                    ['class' => 'btn btn-outline-primary', 'target' => '_blank', 'rel' => 'noopener']
                );

                Notificaciones::NotificarANivel(
                    [Niveles::SYSADMIN, Niveles::ADMIN, Niveles::OPERATOR],
                    'request_reservas',
                    "Se hizo una nueva consulta en una solicitud.<br>" . $botomVerReq
                );


                // Reset del form para que quede vacío
                $formModel = new DynamicModel(['response']);
                $formModel->addRule('response', 'required')
                    ->addRule('response', 'string', ['max' => 500]);
            }
            // Si hay errores de validación, se mostrarán en el partial
        }

        // Mensajes del chat
        $messages = RequestResponse::find()
            ->where(['id_request' => $reservaReq->id])
            ->orderBy(['fecha' => SORT_ASC, 'id' => SORT_ASC])
            ->all();

        // Acción del form para ESTE contexto (tracking público)
        $chatAction = Url::to(['disponibilidad/consulta-chat', 'hash' => $reservaReq->hash]);

        // Reutilizamos el partial de request-reserva
        $html = $this->renderAjax('@app/views/request-reserva/_chat_consultas', [
            'model' => $reservaReq,
            'messages' => $messages,
            'formModel' => $formModel,
            'canDelete' => false,        // 👈 en público NO se pueden borrar mensajes
            'chatAction' => $chatAction,  // 👈 form del chat va a esta misma action

        ]);

        return [
            'success' => true,
            'html' => $html,
        ];
    }


    public function actionMiReservaBuscar()
    {
        $request = Yii::$app->request;

        // 🔒 Solo POST
        if (!$request->isPost) {
            throw new BadRequestHttpException('Método no permitido.');
        }

        // 🔒 Solo AJAX
        if (!$request->isAjax) {
            throw new ForbiddenHttpException('Acceso no permitidos.');
        }

        Yii::$app->response->format = Response::FORMAT_JSON;

        $codigo = strtoupper(trim((string) $request->post('codigo_reserva')));
        $email = trim((string) $request->post('email'));
        $verifyCode = trim((string) $request->post('verifyCode'));

        // Validaciones básicas
        if ($codigo === '' || $email === '' || $verifyCode === '') {
            return [
                'success' => false,
                'message' => Yii::t('app', 'Completá todos los campos, incluido el código de verificación.'),
            ];
        }

        if (!preg_match('/^[A-Z0-9]{7}$/', $codigo)) {
            return [
                'success' => false,
                'message' => Yii::t('app', 'El código de reserva no tiene un formato válido.'),
            ];
        }

        // 🔐 Validar CAPTCHA
        $validator = new CaptchaValidator([
            'captchaAction' => 'disponibilidad/captcha',
            'message' => Yii::t('app', 'El código de verificación es incorrecto.'),
            'skipOnEmpty' => false,
        ]);

        $error = null;
        if (!$validator->validate($verifyCode, $error)) {
            return [
                'success' => false,
                'message' => $error ?: Yii::t('app', 'El código de verificación es incorrecto.'),
            ];
        }

        // Buscar la reserva
        $req = RequestReserva::find()
            ->andWhere(['codigo_reserva' => $codigo])
            ->andWhere(['email' => $email])
            ->one();

        if (!$req || empty($req->hash)) {
            return [
                'success' => false,
                'message' => Yii::t(
                    'app',
                    'No encontramos una reserva con esos datos. Verificá el código y el email.'
                ),
            ];
        }

        // ✅ OK → devolvemos URL de redirección
        return [
            'success' => true,
            'redirectUrl' => Url::to([
                'disponibilidad/seguimiento',
                'hash' => $req->hash,
            ]),
        ];
    }


}
