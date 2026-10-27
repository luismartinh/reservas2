<?php
namespace app\assets;

class CalificacionAsset extends \yii\web\AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public function init()
    {
        parent::init();
        $this->css = ['css/calificacion.css?v=' . filemtime(\Yii::getAlias('@app/web/css/calificacion.css'))];
    }
}
