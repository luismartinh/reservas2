<?php

use yii\bootstrap5\Html;
/**
 * @var yii\web\View $this
 * @var app\models\Cabana $model
 * @var yii\widgets\ActiveForm $form
 * @var array $coloresDisponibles
 * @var array $numerosDisponibles  
 */
?>


<!-- attribute descr -->
<?php echo $form->field($model, 'descr')->textInput(['maxlength' => true]) ?>


<!-- attribute checkout -->
<?= $form->field($model, 'checkout')->textInput([
    'type' => 'time',
    'step' => 60,
    'value' => $model->checkout ? date('H:i', strtotime($model->checkout)) : null,
]) ?>



<!-- attribute checkin -->
<?= $form->field($model, 'checkin')->textInput([
    'type' => 'time',
    'step' => 60,
    'value' => $model->checkin ? date('H:i', strtotime($model->checkin)) : null,
]) ?>


<!-- attribute numero -->
<?= $form->field($model, 'numero')->dropDownList(
    $numerosDisponibles,
    [
        'prompt' => Yii::t('app', 'Seleccione un número'),
    ]
) ?>


<!-- attribute max_pax -->
<?php echo $form->field($model, 'max_pax')->textInput(['type' => 'number']) ?>

<div class="row">
    <div class="col-md-4">

        <?php
        // $coloresDisponibles: [ '#hex' => 'Nombre traducido', ... ]
        
        $optionStyles = [];
        foreach ($coloresDisponibles as $hex => $label) {
            $optionStyles[$hex] = [
                'style' => "background:{$hex};color:#000;font-weight:bold;",
            ];
        }
        ?>
        <?php
        // id fijo para el select, así lo usamos en JS
        $fieldId = Html::getInputId($model, 'color_cabana');
        ?>
        <?= $form->field($model, 'color_cabana')->dropDownList(
            $coloresDisponibles, // hex => nombre traducido
            [
                'prompt' => Yii::t('app', 'Seleccione un color'),
                'options' => $optionStyles,
                'style' => 'font-weight:bold;',
            ]
        )->label(Yii::t('app', 'Color de la cabaña'))
            ->hint(Yii::t('app', 'Seleccione el color que identificará esta cabaña en los calendarios.')) ?>


    </div>
    <div class="col-md-4 mt-4">
        <!-- Preview visual del color seleccionado -->
        <div id="<?= $fieldId ?>-preview" class="mt-2" style="display:none;">
            <span class="badge rounded-pill px-3 py-2" style="min-width:120px;">
                <span class="me-2"
                    style="display:inline-block;width:16px;height:16px;border-radius:4px;border:1px solid #000;vertical-align:middle;"></span>
                <span class="preview-text" style="vertical-align:middle;"></span>
            </span>
        </div>

    </div>


</div>

<!-- attribute activa -->
<div class="row">
    <?php
    if (!(isset($relAttributesHidden) && isset($relAttributesHidden['activa']))) {
        echo $form->field($model, 'activa')->checkbox([
            'custom' => true,
            'switch' => true,
            'disabled' => (isset($relAttributes) && isset($relAttributes['activa'])),
        ]);
    }
    ?>
</div>


<!-- attribute caracteristicas -->
<?= $form->field($model, 'caracteristicas')->textarea([
    'maxlength' => true,
    'disabled' => (isset($relAttributes) && isset($relAttributes['caracteristicas'])),
]) ?>


<?php
$js = <<<JS
(function() {
    var select = document.getElementById('$fieldId');
    if (!select) return;

    var preview = document.getElementById('$fieldId-preview');
    if (!preview) return;

    var swatch = preview.querySelector('span:first-child');
    var textEl = preview.querySelector('.preview-text');

    function updateColorPreview() {
        var val = select.value;
        if (!val) {
            preview.style.display = 'none';
            // reset select bg
            select.style.backgroundColor = '';
            select.style.color = '';
            return;
        }

        var optionText = select.options[select.selectedIndex].text;

        // Mostrar el preview
        preview.style.display = 'inline-block';
        swatch.style.backgroundColor = val;
        textEl.textContent = optionText + ' (' + val + ')';

        // Pintar también el propio <select> con el color
        select.style.backgroundColor = val;
        // Si el color es muy oscuro, ponemos texto blanco, si no, negro:
        try {
            var c = val.substring(1); // quitar "#"
            var r = parseInt(c.substr(0,2),16);
            var g = parseInt(c.substr(2,2),16);
            var b = parseInt(c.substr(4,2),16);
            var yiq = ((r*299)+(g*587)+(b*114))/1000;
            select.style.color = (yiq >= 128) ? '#000' : '#fff';
        } catch(e) {
            select.style.color = '#000';
        }
    }

    // Inicializar al cargar (por si ya viene un valor en update)
    updateColorPreview();

    // Actualizar cuando cambia
    select.addEventListener('change', updateColorPreview);
})();
JS;

$this->registerJs($js);
?>