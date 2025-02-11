<?php

use app\config\Niveles;

/**
 * @var yii\web\View $this
 * @var app\models\Usuario $model
 * @var yii\widgets\ActiveForm $form
 */
?>




<!-- attribute login -->
<?php echo $form->field($model, 'login')->textInput([
    'disabled' => (isset($relAttributes) && isset($relAttributes['login'])),
    'maxlength' => true
]) ?>


<?php

if (!(isset($relAttributesHidden) && isset($relAttributesHidden['nivel']))) {
    echo $form->field($model, 'nivel')->dropDownList(
        Niveles::getNivelesDesde($nivel),
        ['disabled' => (isset($relAttributes) && isset($relAttributes['nivel'])),]

    );
}

?>

<!-- attribute nombre -->
<?php echo $form->field($model, 'nombre')->textInput(['maxlength' => true]) ?>

<!-- attribute apellido -->
<?php echo $form->field($model, 'apellido')->textInput(['maxlength' => true]) ?>

<!-- attribute pwd -->
<?php echo $form->field($model, 'pwd')->widget(
    kartik\password\PasswordInput::classname(),
    [
        'language' => 'es',
        'pluginOptions' => [
            'verdictTitles' => [
                0 => 'Vacia',
                1 => 'Muy pobre',
                2 => 'Pobre',
                3 => 'Normal',
                4 => 'Buena',
                5 => 'Exelente'
            ],
            'verdictClasses' => [
                0 => 'text-muted',
                1 => 'text-danger',
                2 => 'text-warning',
                3 => 'text-info',
                4 => 'text-primary',
                5 => 'text-success'
            ],
        ]
    ]
); ?>


<!-- attribute activo -->

<?php
if (!(isset($relAttributesHidden) && isset($relAttributesHidden['activo']))) {
    echo $form->field($model, 'activo')->checkbox([
        'custom' => true,
        'switch' => true,
        'disabled' => (isset($relAttributes) && isset($relAttributes['activo'])),
    ]);
}
?>



<!-- attribute email -->
<?php echo $form->field(
    $model,
    'email',
    [
        'addon' => ['prepend' => ['content' => '@']]
    ]
)->textInput(['type' => 'email', 'maxlength' => true]) ?>


<!-- attribute codigo -->
<?php
if (!isset($relAttributesHidden) && isset($relAttributesHidden['codigo'])) {
    echo $form->field($model, 'codigo')->textInput([
        'disabled' => (isset($relAttributes) && isset($relAttributes['codigo'])),
        'maxlength' => true
    ]);
}
?>