<?php
namespace app\assets;

class AsistenteAsset extends \yii\web\AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = ['css/asistente.css'];
    public $js = ['js/asistente.js'];
    public $depends = [AppAsset::class];

    public function init()
    {
        parent::init();
        // Version this bundle only: updates uploaded by FTP must replace cached chat scripts.
        foreach (['js', 'css'] as $kind) {
            foreach ($this->$kind as $index => $file) {
                $path = \Yii::getAlias('@app/web/' . $file);
                if (is_file($path)) { $this->{$kind}[$index] = $file . '?v=' . filemtime($path); }
            }
        }
    }

}
