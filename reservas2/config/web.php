<?php

//$params = require __DIR__ . '/params.php';
$params = array_merge(
    require __DIR__ . '/params.php',
    file_exists(__DIR__ . '/params-local.php') ? require __DIR__ . '/params-local.php' : []
);
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'reservas2',
    'basePath' => dirname(__DIR__),
    'bootstrap' => [
        'log',
        'queue',
        function () {

            // Registrar el evento para que siempre se escuche
            //yii\base\Event::on(\app\models\Stock::class, \app\models\Stock::EVENT_STOCK_SAVED, [\app\models\StockListener::class, 'onStockSaved']);
        
            //yii\base\Event::on(\app\models\Stock::class, \app\models\Stock::EVENT_STOCK_DELETED, [\app\models\StockListener::class, 'onStockDeleted']);
        }
    ],
    'layout' => 'app',
    'language' => 'es',
    'sourceLanguage' => 'en-US', // idioma "fuente" de tus claves
    'timeZone' => 'America/Argentina/Buenos_Aires',

    // 👇 Cambia lenguaje por query ?lang= y guarda cookie
    'on beforeRequest' => function () {
        $supported = Yii::$app->params['supportedLanguages'] ?? ['es' => 'Español', 'en' => 'English'];
        $qLang = Yii::$app->request->get('lang');

        if ($qLang && isset($supported[$qLang])) {
            Yii::$app->language = $qLang;
            Yii::$app->response->cookies->add(new \yii\web\Cookie([
                'name' => '_lang',
                'value' => $qLang,
                'httpOnly' => true,
                'expire' => time() + 31536000, // 1 año
            ]));
        } else {
            $cookie = Yii::$app->request->cookies->get('_lang');
            if ($cookie && isset($supported[$cookie->value])) {
                Yii::$app->language = $cookie->value;
            }
        }

        // Alinea formatos de fecha/número/moneda al idioma activo
        Yii::$app->formatter->locale = Yii::$app->language;
    },



    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'HEo8co8QFZ2e0KQ6CYTDa4oxiX03QEJJ',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\Identificador',
            'enableAutoLogin' => true,
        ],
        'formatter' => [
            'class' => yii\i18n\Formatter::class,
            'timeZone' => 'America/Argentina/Salta',       // destino
            'defaultTimeZone' => 'America/Argentina/Salta' // origen (lo que viene de la DB)
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => yii\symfonymailer\Mailer::class,
            // En dev: deja true para NO enviar mails y guardarlos como archivos
            // En prod: ponlo en false para enviar realmente
            'useFileTransport' => false,
            'transport' => [
                'scheme' => 'smtp',
                'host' => $params['smtp.host'],
                'username' => $params['smtp.user'],
                'password' => $params['smtp.pass'],
                'port' => (int) $params['smtp.port'],
                'encryption' => $params['smtp.encryption'],
                // Opcional: para certs self-signed en entornos locales
                'streamOptions' => [
                    'ssl' => [
                        'allow_self_signed' => true,
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ],
                ],
                // 👇 dirección remitente por defecto
                'messageConfig' => [
                    'from' => [$params['senderEmail'] => $params['senderName']],
                ],
            ],
        ],
        'queue' => [
            'class' => yii\queue\db\Queue::class,  // o el tipo de cola que estés utilizando
            'db' => 'db',  // o el componente de base de datos que estés utilizando
            'tableName' => '{{%queue}}',
            'channel' => 'default',
            'ttr' => 60,  // tiempo de trabajo en segundos
            //'retryInterval' => 5,  // intervalo entre reintentos
            'mutex' => \yii\mutex\MysqlMutex::class,  // o el mutex que estés utilizando
        ],

        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        'i18n' => [
            'translations' => [
                // Tus mensajes de aplicación
                'app*' => [
                    'class' => \yii\i18n\PhpMessageSource::class,
                    'basePath' => '@app/messages',
                    'fileMap' => [
                        'app' => 'app.php',
                    ],
                ],
                // 👇 Agregar esto
                'cruds*' => [
                    'class' => \yii\i18n\PhpMessageSource::class,
                    'basePath' => '@app/messages',
                    'fileMap' => ['cruds' => 'cruds.php'],
                ],
                // 👇 agrega estas dos líneas para tus categorías
                'models' => [
                    'class' => \yii\i18n\PhpMessageSource::class,
                    'basePath' => '@app/messages',
                    'fileMap' => ['models' => 'models.php'],
                ],
                'models.plural' => [
                    'class' => \yii\i18n\PhpMessageSource::class,
                    'basePath' => '@app/messages',
                    'fileMap' => ['models.plural' => 'models.plural.php'],
                ],
                'giiant*' => [
                    'class' => \yii\i18n\PhpMessageSource::class,
                    // En versiones recientes la carpeta es src/messages
                    'basePath' => '@vendor/schmunk42/yii2-giiant/src/messages',
                    'sourceLanguage' => 'en-US',
                ],
                // Opcional: sobreescribir mensajes del core si querés
                'yii*' => [
                    'class' => \yii\i18n\PhpMessageSource::class,
                    //'basePath' => '@app/messages',
                    'basePath' => '@yii/messages',   // <— clave
                    'sourceLanguage' => 'en-US',
                ],
            ],
        ],
    ],
    'params' => $params,
    'modules' => [
        'gii' => [
            'class' => 'yii\gii\Module',
        ],
        'gridview' => [
            'class' => '\kartik\grid\Module'
            // enter optional module parameters below - only if you need to  
            // use your own export download action or custom translation 
            // message source
            // 'downloadAction' => 'gridview/export/download',
            // 'i18n' => []
        ]
    ],
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        'allowedIPs' => ['*'],
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;
