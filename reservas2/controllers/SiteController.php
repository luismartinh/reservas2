<?php

namespace app\controllers;

use app\models\Cabana;
use Yii;
use yii\base\InvalidParamException;
use yii\filters\AccessControl;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;

class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['logout', 'index-dentro'],
                'rules' => [
                    [
                        'actions' => ['logout', 'index-dentro'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {



        if (Yii::$app->user->isGuest) {
            $this->layout = 'main';
            $cabanas = Cabana::find()->orderBy(['numero' => SORT_ASC])->all();
            return $this->render('index', [
                'cabanas' => $cabanas,
            ]);
        }

        return $this->render('index_dentro');

    }



    public function beforeAction($action)
    {


        if (!parent::beforeAction($action)) {
            return false;
        }


        // Check only when the user is logged in

        if (!Yii::$app->user->isGuest) {

            if (Yii::$app->session['userSessionTimeout'] < time()) {
                Yii::$app->user->logout();
                //return false;
            } else {
                Yii::$app->session->set('userSessionTimeout', time() + Yii::$app->params['sessionTimeoutSeconds']);
                return true;
            }

        } else {

            return true;

        }
    }


    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionLogin()
    {

        Yii::$app->session->set('userSessionTimeout', time() + Yii::$app->params['sessionTimeoutSeconds']);

        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            Yii::$app->user->identity->guardarDatosSesion();
            return $this->goBack();
        }

        $model->password = '';
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return Response|string
     */
    public function actionContact()
    {
        $model = new \app\models\ContactForm();

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {

            // remitente y destinatario
            $fromEmail = Yii::$app->params['senderEmail'] ?? null;
            $fromName = Yii::$app->params['senderName'] ?? 'Reservas';
            $toEmail = Yii::$app->params['adminEmail'] ?? $fromEmail;

            if (!$fromEmail || !$toEmail) {
                Yii::warning('senderEmail o adminEmail no configurados; no se envía correo de contacto.', __METHOD__);
                Yii::$app->session->setFlash(
                    'error',
                    Yii::t('app', 'Ocurrió un problema al enviar tu mensaje. Intentalo más tarde.')
                );
            } else {
                // cuerpo del mail usando una vista parcial, igual que en tus otros controladores
                $body = $this->renderPartial('@app/views/site/mail_contact', [
                    'model' => $model,
                ]);

                $subject = Yii::t('app', 'Nueva consulta desde el sitio: {asunto}', [
                    'asunto' => $model->subject,
                ]);

                $ok = Yii::$app->mailer->compose()
                    ->setFrom([$fromEmail => $fromName])
                    ->setTo($toEmail)
                    ->setReplyTo([$model->email => $model->name]) // útil para responder directo
                    ->setSubject($subject)
                    ->setHtmlBody($body)
                    ->send();

                if ($ok) {
                    Yii::$app->session->setFlash(
                        'success',
                        Yii::t('app', 'Gracias por tu mensaje. Te responderemos a la brevedad.')
                    );
                } else {
                    Yii::warning('Fallo al enviar email de contacto', __METHOD__);
                    Yii::$app->session->setFlash(
                        'error',
                        Yii::t('app', 'No se pudo enviar tu mensaje. Intentalo nuevamente más tarde.')
                    );
                }
            }

            return $this->refresh();
        }

        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout()
    {
        return $this->render('about');
    }


    /**
     * Requests password reset.
     *
     * @return mixed
     */
    public function actionRequestPasswordReset()
    {
        $model = new \app\models\PasswordResetRequestForm();

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {

            if ($model->sendEmail()) {
                Yii::$app->session->setFlash('success', Yii::t('app', 'Vea su correo y siga las instrucciones'));
                return $this->goHome();
            } else {
                Yii::$app->session->setFlash('error', Yii::t('app', "No podemos limpiar su contraseña con este email"));
            }
        }

        return $this->render('requestPasswordResetToken', [
            'model' => $model,
        ]);
    }

    /**
     * Resets password.
     *
     * @param string $token
     * @return mixed
     * @throws BadRequestHttpException
     */
    public function actionResetPassword($token)
    {
        try {
            $model = new \app\models\ResetPasswordForm($token);
        } catch (InvalidParamException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->resetPassword()) {
            Yii::$app->session->setFlash('success', Yii::t('app', 'La nueva contraseña se guardo exitosamente!'));
            return $this->goHome();
        }

        return $this->render('resetPasswordForm', [
            'model' => $model,
        ]);
    }


    public function actionCabana($id)
    {
        $this->layout = 'main';
        $cabana = Cabana::findOne($id);
        if ($cabana === null) {
            throw new NotFoundHttpException('La cabaña solicitada no existe.');
        }

        $numeroCabana = $cabana->numero ?? $cabana->id;

        // ================== IMÁGENES ==================
        $dir = Yii::getAlias('@webroot') . "/images/cabanas/cabana-{$numeroCabana}";
        $urlBase = Yii::getAlias('@web') . "/images/cabanas/cabana-{$numeroCabana}";

        $images = [];
        if (is_dir($dir)) {
            $files = glob($dir . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE) ?: [];
            sort($files);
            foreach ($files as $path) {
                $images[] = $urlBase . '/' . basename($path);
            }
        }

        // Si no hay fotos para esa carpeta, usamos la imagen genérica que ya tenés
        if (empty($images)) {
            $images[] = Yii::getAlias('@web') . '/images/cabanas/exterior-deck.jpeg';
        }

        return $this->render('cabana', [
            'cabana' => $cabana,
            'images' => $images,
        ]);
    }


    public function actionComoLlegar(): string
    {

        $this->layout = 'main';

        // URL normal (para abrir en pestaña nueva)
        $googleMapsUrl = 'https://www.google.com/maps/place/Caba%C3%B1as+Dina+Huapi/@-41.0806163,-71.1850529,17.12z/data=!4m15!1m8!3m7!1s0x961a873ae81165c9:0xb28dab24aee42448!2sDina+Huapi,+R%C3%ADo+Negro!3b1!8m2!3d-41.0759041!4d-71.171024!16s%2Fm%2F07rdtcn!3m5!1s0x961a87f3c2e224c1:0x671b08b3a12a83ae!8m2!3d-41.0812574!4d-71.1849619!16s%2Fg%2F11pzym06c3?entry=ttu&g_ep=EgoyMDI1MTIwMi4wIKXMDSoASAFQAw%3D%3D';

        // URL especial para iframe (embed)
        $embedAddress = 'Cabañas Dina Huapi, Los Cohiues 375, Dina Huapi, Río Negro, Argentina';
        $googleMapsEmbedUrl = 'https://www.google.com/maps?q=' . urlencode($embedAddress) . '&output=embed';

        return $this->render('como-llegar', [
            'googleMapsUrl' => $googleMapsUrl,
            'googleMapsEmbedUrl' => $googleMapsEmbedUrl,
        ]);
    }


    public function actionImagenes(): string
    {
        $this->layout = 'main';

        $dir = Yii::getAlias('@webroot') . '/images/generales';
        $baseUrl = Yii::getAlias('@web') . '/images/generales';

        $images = [];
        if (is_dir($dir)) {
            $files = glob($dir . '/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE) ?: [];
            sort($files);
            foreach ($files as $path) {
                $images[] = $baseUrl . '/' . basename($path);
            }
        }

        return $this->render('imagenes', [
            'images' => $images,
        ]);
    }

}
