<?php

namespace app\assets;

use yii\web\AssetBundle;

class SubmitOverlayAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public $css = [
        'css/submit-overlay.css',
    ];

    public $js = [
        'js/submit-overlay.js',
    ];

    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap5\BootstrapAsset',
    ];
}
