<?php
use yii\helpers\Html;
use yii\helpers\Json;
use yii\bootstrap5\ActiveForm;
$operation = $draft['proposal']['operacion'] ?? 'datos';
$this->title = $operation === 'calificacion' ? 'Calificar pasajero y editar nota' : 'Editar datos del pasajero';
$action = ['asistente/pasajero', 'borrador' => $draft['token'] ?? ''];
if ($modal) { $action['modal'] = '1'; }
?>
<h1 class="h5"><?= Html::encode($this->title) ?></h1>
<p class="small text-body-secondary">Formulario privado. Los datos y cambios no se envían a la IA. Revisá al pasajero y confirmá antes de guardar. Vence a los 15 minutos.</p>
<?php if ($error): ?><p class="alert alert-danger" role="alert"><?= Html::encode($error) ?></p><?php endif ?>
<?php if (!empty($draft['completed'])): ?>
    <p class="alert alert-success">Datos del pasajero #<?= (int) $draft['completed'] ?> guardados.</p>
    <?= Html::a('Volver al chat', ['asistente/index'], ['target' => '_top', 'class' => 'btn btn-primary']) ?>
    <?php if ($modal && $estado):
        $message = Json::htmlEncode(['tipo' => 'asistente:pasajero-guardado', 'token' => $draft['token'], 'estado' => $estado]);
        $this->registerJs('if (window.parent !== window) { window.parent.postMessage(' . $message . ', window.location.origin); }');
    endif ?>
<?php elseif (!empty($draft['token'])): ?>
    <details class="mb-3"<?= !$model ? ' open' : '' ?>>
        <summary>Buscar pasajero por sus datos, dirección, observaciones o ubicación</summary>
        <?= Html::beginForm($action, 'post', ['class' => 'mt-2']) ?>
        <?= Html::hiddenInput('fase', 'buscar') ?>
        <?= Html::dropDownList('campo', 'todos', \app\models\LocadorSearch::opcionesBusqueda(), ['class' => 'form-select mb-2', 'aria-label' => 'Buscar en']) ?>
        <?= Html::textInput('buscar', '', ['class' => 'form-control mb-2', 'maxlength' => 100, 'required' => true, 'aria-label' => 'Buscar pasajero']) ?>
        <p class="small text-body-secondary">Coincidencias parciales. Para calificación: 1 a 5; 0 para pasajeros sin calificar.</p>
        <?= Html::submitButton('Buscar pasajero', ['class' => 'btn btn-outline-primary']) ?>
        <?= Html::endForm() ?>
    </details>
    <?php if ($model): ?>
        <p><strong>Pasajero #<?= (int) $model->id ?> · <?= Html::encode($model->denominacion) ?></strong><br>Documento: <?= Html::encode($model->documento) ?></p>
        <?php if (!empty($draft['review'])): ?>
            <h2 class="h6">Revisar los valores a guardar</h2>
            <table class="table table-sm"><tbody>
            <?php foreach ($draft['review']['valores'] as $field => $value): ?>
                <tr><th><?= Html::encode($model->getAttributeLabel($field)) ?></th><td>
                <?php if ($field === 'calificacion'): ?>
                    <?= $this->render('@app/views/locador/_calificacion', ['valor' => $value]) ?>
                <?php else:
                    if ($field === 'id_localidad') { $value = $value ? (\app\models\Localidad::findOne($value)->descr ?? '') : 'Sin localidad'; }
                    if ($field === 'id_provincia') { $value = $value ? (\app\models\Provincia::findOne($value)->descr ?? '') : 'Sin provincia'; }
                    echo nl2br(Html::encode($value ?? ''));
                endif ?></td></tr>
            <?php endforeach ?></tbody></table>
            <?= Html::beginForm($action, 'post') ?>
            <?= Html::hiddenInput('fase', 'confirmar') ?>
            <?= Html::submitButton('Confirmar y guardar cambios', ['class' => 'btn btn-primary']) ?>
            <?= Html::endForm() ?>
            <?= Html::beginForm($action, 'post', ['class' => 'mt-2']) ?>
            <?= Html::hiddenInput('fase', 'editar') ?>
            <?= Html::submitButton('Volver a editar', ['class' => 'btn btn-outline-secondary']) ?>
            <?= Html::endForm() ?>
        <?php else: ?>
            <?php $form = ActiveForm::begin(['action' => $action]); ?>
            <?= Html::hiddenInput('fase', 'revisar') ?>
            <?= $form->errorSummary($model) ?>
            <?php if ($operation === 'calificacion'): ?>
                <?= $this->render('@app/views/locador/_calificacion-input', ['form' => $form, 'model' => $model]) ?>
            <?php else: ?>
                <?php foreach (['denominacion', 'documento', 'email', 'telefono', 'domicilio'] as $field): ?>
                    <?= $form->field($model, $field)->textInput(['maxlength' => true]) ?>
                <?php endforeach ?>
                <?= $this->render('@app/views/locador/_ubicacion', ['form' => $form, 'model' => $model]) ?>
            <?php endif ?>
            <?= $form->field($model, 'obs')->textarea(['rows' => 5])->label('Nota del pasajero (observaciones)')
                ->hint('Se edita el texto existente; al guardar se reemplaza por el contenido de este campo.') ?>
            <?= Html::submitButton('Revisar cambios', ['class' => 'btn btn-primary']) ?>
            <?php ActiveForm::end(); ?>
        <?php endif ?>
        <?= Html::beginForm($action, 'post', ['class' => 'mt-3']) ?>
        <?= Html::hiddenInput('fase', 'recargar') ?>
        <?= Html::submitButton('Recargar datos', ['class' => 'btn btn-outline-secondary']) ?>
        <?= Html::endForm() ?>
    <?php else: ?>
        <p class="small text-body-secondary">Hasta 20 coincidencias. Si no aparece el pasajero, acotá la búsqueda.</p>
        <?php if ($candidates): ?>
            <?= Html::beginForm($action, 'post') ?>
            <?= Html::hiddenInput('fase', 'seleccionar') ?>
            <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Elegir</th><th>Pasajero</th><th>Documento</th><th>Contacto</th><th>Dirección</th><th>Observaciones</th><th>Localidad · Provincia</th><th>Calificación</th></tr></thead><tbody>
            <?php foreach ($candidates as $candidate): ?>
                <tr><td><?= Html::radio('id_locador', false, ['value' => $candidate->id, 'required' => true, 'aria-label' => 'Elegir pasajero ' . $candidate->id]) ?></td>
                <td>#<?= (int) $candidate->id ?> · <?= Html::encode($candidate->denominacion) ?></td>
                <td><?= Html::encode($candidate->documento) ?></td><td><?= Html::encode($candidate->telefono . ' · ' . $candidate->email) ?></td>
                <td><?= Html::encode($candidate->domicilio) ?></td>
                <td><?= Html::encode(mb_strlen((string) $candidate->obs) > 200 ? mb_substr($candidate->obs, 0, 200) . '…' : $candidate->obs) ?></td>
                <td><?= Html::encode($candidate->ubicacionDescripcion) ?></td>
                <td><?= $this->render('@app/views/locador/_calificacion', ['valor' => $candidate->calificacion]) ?></td></tr>
            <?php endforeach ?></tbody></table></div>
            <?= Html::submitButton('Editar pasajero seleccionado', ['class' => 'btn btn-primary']) ?>
            <?= Html::endForm() ?>
        <?php else: ?><p>No hay un pasajero seleccionado. Buscá e identificá a la persona para continuar.</p><?php endif ?>
    <?php endif ?>
<?php endif ?>
