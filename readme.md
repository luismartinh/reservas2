Instalar desde 0:

Pasos:
(ejecutar)

1- Crea la imagen:
docker compose build php

2- Levantar contenedor:
docker compose up -d
(remover imagenes ya generadas)
docker compose up -d --remove-orphans

3- Instalar yii2 dentro del contenedor:
docker compose run php bash
(Y dentro del contenedor) 

composer create-project --prefer-dist yiisoft/yii2-app-basic basicb5
o 
dentro de basicb5/
composer install 

sacar web/.htaccess
dentro de web/
sudo chmod 777 -R assets/


en /
sudo chmod 777 -R views/
sudo chmod 777 -R assets/

4- Ver la app
http://localhost:8281/basicb5/web/


5- instalar bootstrap5
https://stackoverflow.com/questions/73560764/how-upgrade-yii-bootstrap-3-to-bootstrap-5
https://github.com/yiisoft/yii2-bootstrap5


docker compose run php bash
(Y dentro del contenedor en basicb5/) 
composer remove yiisoft/yii2-bootstrap
composer require --prefer-dist yiisoft/yii2-bootstrap5:"^2.0"
composer require twbs/bootstrap:5.3.3

https://getbootstrap.com/docs/5.3/getting-started/introduction/




Forms components:

https://demos.krajee.com/
https://www.w3schools.com/html/html_entities.asp


Set custom checkbox:

´´´
    echo $form->field($model, 'activo',
    [
        'template' => '{input}&nbsp;{label}{error}{hint}',
        'labelOptions' => ['class' => 'cbx-label']    
    ]
    )->widget(kartik\checkbox\CheckboxX::classname(), [
        'autoLabel'=>false,
        'pluginOptions'=>[
            'threeState'=>false,
            'size'=>'xl',
            'iconChecked'=>'<i class="bi bi-check-square-fill text-success" ></i>',
            'iconUnchecked'=>'<i class="bi bi-dash-square-fill text-danger"></i>',
            'iconNull'=>'<i class="bi bi-exclamation-lg text-danger"></i>'        
            ]
    ]); 
´´´


DateRangePiker:
https://www.daterangepicker.com/#usage