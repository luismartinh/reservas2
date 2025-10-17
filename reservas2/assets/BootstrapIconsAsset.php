<?php
namespace app\assets;

use yii\web\AssetBundle;

class BootstrapIconsAsset extends AssetBundle
{
    public $sourcePath = '@vendor/twbs/bootstrap-icons/font';
    public $css = [
        'bootstrap-icons.css', // Archivo de estilos de Bootstrap Icons
    ];
}
