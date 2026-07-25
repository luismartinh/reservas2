<?php

// NOTE: Make sure this file is not accessible when deployed to production
function isAllowedTestRemoteAddr($remoteAddr)
{
    if (in_array($remoteAddr, ['127.0.0.1', '::1'], true)) {
        return true;
    }

    if (!filter_var($remoteAddr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return false;
    }

    return preg_match('/^(10\.|192\.168\.|172\.(1[6-9]|2\d|3[0-1])\.)/', $remoteAddr) === 1;
}

if (!isAllowedTestRemoteAddr(@$_SERVER['REMOTE_ADDR'])) {
    die('You are not allowed to access this file.');
}

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/test.php';

(new yii\web\Application($config))->run();
