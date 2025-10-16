<?php

use app\models\UpdateDBForm;
/**
 * @var yii\web\View $this
 * @var app\models\UpdateDBForm $model
 * @var yii\widgets\ActiveForm $form
 */
?>


<?php 
    echo $form->field($model, 'update_from')->dropDownList($model->getListDropDown()); 
?>

<?php 
    echo $form->field($model, 'update_to')->dropDownList($model->getListDropDown()); 
?>

