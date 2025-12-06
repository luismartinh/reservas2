<?php

namespace app\assets;

use yii\web\AssetBundle;

class PublicPortalAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public $css = [
        'css/bootstrap-icons.min.css',
        'css/reservas-public.css',
    ];

    public $js = [
        'js/reservas-public.js'
    ];

    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap5\BootstrapAsset',
        'yii\bootstrap5\BootstrapPluginAsset',
        'app\assets\BootstrapIconsAsset',
    ];
}



