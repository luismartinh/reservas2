<?php
use app\models\Provincia;
use app\models\Localidad;
use kartik\select2\Select2;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var \yii\widgets\ActiveForm $form */
/** @var \app\models\Locador|\app\models\AsistenteReservaForm|\app\models\ReservaSolicitudForm $model */
\app\assets\UbicacionAsset::register($this);
$busqueda = $busqueda ?? false;
$fieldOptions = $busqueda ? ['options' => ['class' => 'col-md-6 mb-3']] : [];
?>
<div class="ubicacion-selector<?= $busqueda ? ' row g-3' : '' ?>" data-provincia-actual="<?= Html::encode((string) $model->id_provincia) ?>" data-localidades-url="<?= Html::encode(Url::to(['locador/localidades'])) ?>">
    <?= $form->field($model, 'id_provincia', $fieldOptions)->widget(Select2::class, [
        'data' => Provincia::opciones(),
        'language' => 'es',
        'bsVersion' => '5.x',
        'theme' => Select2::THEME_KRAJEE_BS5,
        'options' => [
            'placeholder' => $busqueda ? 'Todas las provincias' : 'Buscar provincia…',
            'class' => 'ubicacion-provincia',
        ],
        'pluginOptions' => [
            'allowClear' => true,
            'minimumResultsForSearch' => 0,
            'selectOnClose' => false,
            'width' => '100%',
        ],
    ]) ?>
    <?= $form->field($model, 'id_localidad', $fieldOptions)->widget(Select2::class, [
        'data' => Localidad::opciones($model->id_provincia),
        'language' => 'es',
        'bsVersion' => '5.x',
        'theme' => Select2::THEME_KRAJEE_BS5,
        'options' => ['placeholder' => 'Buscar localidad…', 'class' => 'ubicacion-localidad'],
        'pluginOptions' => [
            'allowClear' => true,
            'minimumResultsForSearch' => 0,
            'selectOnClose' => false,
            'width' => '100%',
        ],
    ]) ?>
    <p class="ubicacion-estado small" role="status" aria-live="polite"></p>
</div>
