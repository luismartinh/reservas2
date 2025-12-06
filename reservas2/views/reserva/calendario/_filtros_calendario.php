<?php
/**
 * @var \yii\web\View $this
 * @var \app\models\Cabana[] $cabanas
 * @var array $selectedCabanas
 * @var \DateTimeImmutable $start1
 * @var array|string $actionRoute
 * @var int|null $selectedLocadorId
 * @var string $selectedLocadorText
 */

use kartik\select2\Select2;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\helpers\Url;
use app\helpers\CalendarHelper;
use yii\web\JsExpression;

$request = Yii::$app->request;

// valores por defecto tomados del primer mes del calendario
$defaultMonth = $start1->format('m');
$defaultYear = $start1->format('Y');

// si vienen por GET, tienen prioridad
$currentMonth = $request->get('month', $defaultMonth);
// normalizamos el mes a formato '01', '02', ..., '12'
$currentMonth = sprintf('%02d', (int) $currentMonth);


$currentYear = $request->get('year', $defaultYear);


// meses desde el helper (por si mañana cambias los labels)
$months = CalendarHelper::getMonthsForSelect();
?>

<div class="card mb-4">
    <div class="card-body">
        <?php $form = ActiveForm::begin([
            'method' => 'get',
            'action' => $actionRoute,
            'options' => ['class' => 'row'],
        ]); ?>

        <div class="col-md-8">
            <label class="form-label fw-bold">
                <?= Yii::t('app', 'Cabañas') ?>
            </label>
            <select name="cabanas[]" class="form-select" multiple size="5">
                <?php foreach ($cabanas as $cab): ?>
                    <?php
                    $id = (string) $cab->id;
                    $selected = in_array($id, $selectedCabanas, true)
                        || in_array((int) $id, $selectedCabanas, true);
                    ?>
                    <option value="<?= Html::encode($id) ?>" <?= $selected ? 'selected' : '' ?>>
                        <?= Html::encode($cab->descr ?: ('Cabana #' . $cab->id)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small class="text-muted">
                <?= Yii::t('app', 'Mantenga presionada la tecla Ctrl (Cmd en Mac) para seleccionar varias.') ?>
            </small>
        </div>

        <!-- Filtros de mes y año -->
        <div class="col-md-4">

            <div class="row">
                <!-- Mes -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">
                        <?= Yii::t('app', 'Mes base') ?>
                    </label>

                    <select name="month" class="form-select">
                        <?php foreach ($months as $num => $label): ?>
                            <?php $num = sprintf('%02d', (int) $num); ?>
                            <option value="<?= $num ?>" <?= $num == $currentMonth ? 'selected' : '' ?>>
                                <?= Html::encode($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div>
            <div class="row">
                <!-- Año -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">
                        <?= Yii::t('app', 'Año') ?>
                    </label>

                    <input type="number" name="year" class="form-control" value="<?= Html::encode($currentYear) ?>">
                </div>

            </div>

        </div>


        <div class="col-md-10 mt-4">
            <label class="form-label fw-bold">
                <?= Yii::t('app', 'Pasajero / Locador') ?>
            </label>

            <?php
            $locadorUrl = Url::to(['reserva/locador-autocomplete']);

            $selectedLocadorId = $selectedLocadorId ?? null;
            $selectedLocadorText = $selectedLocadorText ?? '';

            if (!$selectedLocadorId) {
                $selectedLocadorId = null;
                $selectedLocadorText = '';
            }

            // data para el valor inicial (si hay uno)
            $locadorData = [];
            if ($selectedLocadorId && $selectedLocadorText) {
                // Select2 y Kartik aceptan data en formato [id => text]
                $locadorData = [$selectedLocadorId => $selectedLocadorText];
            }
            echo Select2::widget([
                'name' => 'id_locador',
                'value' => $selectedLocadorId ?? null,              // ID seleccionado (para mantener filtro)
                'data' => $locadorData,        // texto asociado al ID al cargar
                'options' => [
                    'placeholder' => Yii::t('app', 'Buscar locador...'),
                    'id' => 'filtro-id-locador',
                ],
                'pluginOptions' => [
                    'allowClear' => true,
                    'minimumInputLength' => 2,
                    'ajax' => [
                        'url' => $locadorUrl,
                        'dataType' => 'json',
                        'delay' => 250,
                        'data' => new JsExpression('function(params) { return {q: params.term}; }'),
                        'processResults' => new JsExpression('function(data) { return data; }'),
                    ],
                    // muestra el texto inicial cuando ya tenemos un valor seleccionado
                    'initValueText' => $selectedLocadorText ?? '',
                    //'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
                    //'templateResult' => new JsExpression('function (data) { return data.text; }'),
                    //'templateSelection' => new JsExpression('function (data) { return data.text || data.id; }'),
                ],
            ]);
            ?>

            <small class="text-muted">
                <?= Yii::t('app', 'Escriba nombre, documento o email y seleccione un locador.') ?>
            </small>
        </div>

        <div class="col-12 d-flex justify-content-end mt-2">
            <button type="submit" class="btn btn-primary me-2">
                <i class="bi bi-search"></i> <?= Yii::t('app', 'Filtrar') ?>
            </button>
            <a href="<?= Html::encode(Url::to($actionRoute)) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-counterclockwise"></i> <?= Yii::t('app', 'Reiniciar') ?>
            </a>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>