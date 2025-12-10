<?php

use kartik\daterange\DateRangePicker;
use kartik\form\ActiveForm;
use kartik\select2\Select2;
use yii\bootstrap5\Html;


/**
 * @var yii\web\View $this
 * @var app\models\ReservaSearch $model
 * @var kartik\form\ActiveForm $form
 */
?>

<div class="reserva-search">

	<?php $form = ActiveForm::begin([
		'action' => ['index'],
		'method' => 'get',
	]); ?>


	<div class="row">

		<div class="col-md-6">
			<?php
			echo $form->field($model, 'fecha', [
				'addon' => ['prepend' => ['content' => '<i class="bi bi-calendar-range-fill"></i>']],
				'options' => ['class' => 'drp-container mb-2']
			])->widget(DateRangePicker::classname(), [
						'useWithAddon' => true,
						'convertFormat' => true,
						'includeMonthsFilter' => true,
						'bsVersion' => '5.x',
						'pluginOptions' => ['locale' => ['format' => 'd-m-Y']],
						'options' => [
							'data-pjax' => '0',
							'autocomplete' => 'off',
							'placeholder' => 'seleccione rango...'
						]

					])->label('Buscar por creada')->hint('Ingrese un rango de fechas para filtrar fechas');
			?>

		</div>

		<div class="col-md-3">

			<?php echo $form->field($model, 'desde')->textInput(['type' => 'date'])->label('Buscar por reserva desde')->hint('Ingrese la reserva desde para filtrar') ?>

		</div>

		<div class="col-md-3">

			<?php echo $form->field($model, 'hasta')->textInput(['type' => 'date'])->label('Buscar por reserva hasta')->hint('Ingrese la reserva hasta para filtrar') ?>

		</div>



	</div>


	<div class="row">
		<div class="col-md-6">
			<!-- attribute id_locador -->
			<?php
			// Usage with ActiveForm and model
			echo $form->field($model, 'id_locador')->widget(Select2::classname(), [
				'data' => app\models\Locador::getAllLocadoresDropdown(),
				'language' => 'es',
				'bsVersion' => '5.x',
				'theme' => Select2::THEME_KRAJEE_BS5,
				'options' => [
					'placeholder' => 'seleccione...',
				],
				'pluginOptions' => [
					'allowClear' => true,
				],
			])->label('Buscar por Cliente')->hint('Indique el cliente a buscar');
			?>
		</div>

		<div class="col-md-3">
			<?php echo $form->field($model, 'codigo_reserva')->textInput(['maxlength' => true])
				->label('Buscar por codigo de reserva')
				->hint('Ingrese el texto filtrar') ?>
		</div>


		<div class="col-md-3">
			<!-- attribute id_estado -->
			<?php echo
				$form->field($model, 'id_estado')->dropDownList(
					\yii\helpers\ArrayHelper::map(app\models\Estado::find()->all(), 'id', 'descr'),
					[
						'prompt' => Yii::t('cruds', 'Selec..'),
					]
				)->label('Buscar por estado')->hint('seleccione el estado para filtrar'); ?>

		</div>


	</div>


	<div class="row">

		<div class="col-md-6"> </div>				

		<div class="col-md-6 text-end">

			<?= Html::submitButton('<i class="bi bi-search me-2"></i> Buscar', [
				'class' => 'btn btn-primary btn-lg',
			]) ?>
		</div>


		

	</div>




	<?php ActiveForm::end(); ?>

</div>