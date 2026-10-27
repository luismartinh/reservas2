<?php
use app\assets\AppAsset;
use yii\helpers\Html;
AppAsset::register($this);
$this->registerCsrfMetaTags();
$this->registerMetaTag(['charset' => Yii::$app->charset]);
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1']);
?>
<?php $this->beginPage(); ?>
<!doctype html><html lang="es"><head><title><?= Html::encode($this->title) ?></title><?php $this->head(); ?></head>
<body><?php $this->beginBody(); ?><main class="container py-3"><?= $content ?></main><?php $this->endBody(); ?></body></html>
<?php $this->endPage(); ?>
