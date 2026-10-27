<?php
namespace app\assets;

class UbicacionAsset extends \yii\web\AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $depends = [\yii\web\JqueryAsset::class];

    public function init()
    {
        parent::init();
        $this->js = ['js/ubicacion.js?v=' . filemtime(\Yii::getAlias('@app/web/js/ubicacion.js'))];
    }
}
