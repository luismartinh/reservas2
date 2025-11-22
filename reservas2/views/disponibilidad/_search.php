<?php

use kartik\daterange\DateRangePicker;
use kartik\form\ActiveForm;
use yii\bootstrap5\Html;
use yii\web\JsExpression;


/**
 * @var yii\web\View $this
 * @var app\models\DisponibilidadSearch $searchModel
 * @var kartik\form\ActiveForm $form
 * @var bool $esAdmin
 */

$frmAction = $esAdmin ? ['reserva/reservar'] : ['disponibilidad/buscar'];
?>

<div class="disponibilidad-search">

	<?php $form = ActiveForm::begin([
		'action' => $frmAction,
		'method' => 'get',
		'options' => [
			'id' => 'form-busqueda',
			'data-pjax' => 1,   // <- importante
		],
	]); ?>


	<div class="row">
		<div class="col-md-6">
			<?php



			echo $form->field($model, 'periodo', [
				'addon' => ['prepend' => ['content' => '<i class="bi bi-calendar-range-fill"></i>']],
				'options' => ['class' => 'drp-container mb-2']
			])->widget(DateRangePicker::classname(), [
						'startAttribute' => 'desde', // atributo real “desde”
						'endAttribute' => 'hasta',   // atributo real “hasta”
						'useWithAddon' => true,
						'convertFormat' => true,
						'includeMonthsFilter' => true,
						'bsVersion' => '5.x',
						'pluginOptions' => [
							'locale' => ['format' => 'd-m-Y'],
							// 👇 ESTA ES LA CLAVE: solo desde hoy en adelante
							'minDate' => new JsExpression('moment().startOf("day")'),							
						],
						'language' => Yii::$app->language, // es, en, pt-BR
						'options' => [
							//'data-pjax' => '0',
							'autocomplete' => 'off',
							'placeholder' => 'seleccione rango...'
						]

					]);

			?>


		</div>



		<div class="col-md-6">

			<div class="form-group d-flex align-items-end" style="margin-top:32px;">
				<?= Html::submitButton(
					'<i class="bi bi-search me-2"></i>' . Yii::t('cruds', 'Buscar'),
					['class' => 'btn btn-primary me-2']
				) ?>

				<?= Html::a(
					'<i class="bi bi-arrow-counterclockwise me-2"></i>' . Yii::t('cruds', 'Reiniciar'),
					['buscar'], // acción del controlador que renderiza el search
					[
						'class' => 'btn btn-outline-secondary',
						'data-pjax' => 1, // 🔹 IMPORTANTE: recarga el bloque PJAX vacío
					]
				) ?>
			</div>


		</div>

	</div>


	<div class="row">



	</div>


	<?php ActiveForm::end(); ?>

</div>