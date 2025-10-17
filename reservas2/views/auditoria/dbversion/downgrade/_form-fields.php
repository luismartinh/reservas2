<?php

use app\models\UpdateDBForm;
/**
 * @var yii\web\View $this
 * @var app\models\UpdateDBForm $model
 * @var yii\widgets\ActiveForm $form
 */
?>



<?php 
    echo $form->field($model, 'downgrade_to')->dropDownList($model->getListDropDownDowngrade()); 
?>

