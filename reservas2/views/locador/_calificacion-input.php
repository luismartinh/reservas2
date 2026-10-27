<?php \app\assets\CalificacionAsset::register($this); ?>
<?= $form->field($model, 'calificacion')->radioList([
    5 => '5 estrellas', 4 => '4 estrellas', 3 => '3 estrellas',
    2 => '2 estrellas', 1 => '1 estrella', 0 => 'Sin calificación',
], [
    'class' => 'locador-rating',
    'role' => 'radiogroup',
    'aria-label' => 'Calificación del pasajero',
    'item' => function ($index, $label, $name, $checked, $value) {
        $id = 'locador-calificacion-' . $value;
        return \yii\helpers\Html::radio($name, $checked, [
            'id' => $id, 'value' => $value, 'class' => 'locador-rating-input', 'aria-label' => $label,
        ]) . \yii\helpers\Html::label($value ? '★' : $label, $id, [
            'class' => $value ? 'locador-rating-star' : 'locador-rating-clear', 'title' => $label,
        ]);
    },
])->hint('Elegí de 1 a 5 estrellas o dejá al pasajero sin calificación.') ?>
