<?php


use yii\web\View;
use yii\bootstrap5\Html;
use kartik\grid\GridView;

use kartik\icons\FontAwesomeAsset;
FontAwesomeAsset::register($this);


/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var app\models\AuditoriaTablaSearch $searchModel
 */

$this->title = Yii::t('models', 'Administrar Auditoria');
$this->params['breadcrumbs'][] = $this->title;


?>

<div class="giiant-crud auditoria-tabla-index">



    <?php \yii\widgets\Pjax::begin(['id' => 'pjax-main', 'enableReplaceState' => false, 'linkSelector' => '#pjax-main ul.pagination a, th a']) ?>


    <h1>
        <?= Yii::t('cruds', $this->title) ?>
        <small class="text-muted"><?= Yii::t('cruds', 'registradas:') ?>
        </small>
    </h1>


    <hr />

    <div class="table-responsive">
        <?php


        $columns = [
            [
                'class' => 'kartik\grid\CheckboxColumn',
                'headerOptions' => ['class' => 'kartik-sheet-style'],
                'header' => 'Activar',
                'rowHighlight' => true,
                'rowSelectedClass' => 'table-success',
                'checkboxOptions' => function ($model, $key, $index, $column) {
                    return [
                        'checked' => $model->enabled == "1" ? true : false
                    ];
                },
            ],
            [
                'vAlign' => 'middle',
                'hAlign' => 'left',
                'attribute' => 'tabla',
                'headerOptions' => ['style' => 'text-align:center'],
                'contentOptions' => ['style' => 'text-align:left'],
            ],


            [
                'class' => 'kartik\grid\BooleanColumn',
                'headerOptions' => ['style' => 'text-align:center'],
                'label' => 'Activado?',
                'trueLabel' => 'SI',
                'falseLabel' => 'NO',
                'attribute' => 'enabled',
                'vAlign' => 'middle',
                'width' => '15%',
                'value' => function ($model, $key, $index, $column) {
                    return $model->enabled;
                },

            ],
        ];


        $ind = Html::a(
            '<i class="bi bi-arrow-counterclockwise me-2"></i>' . Yii::t('cruds', 'Reset'),
            ['index'],
            [
                'class' => 'btn btn-outline-secondary btn-default float-end me-2',
                'data-pjax' => '0',
            ]
        );


        echo GridView::widget([
            'id' => 'auditoria-tablas_grid',
            'dataProvider' => $dataProvider,
            'pjax' => true,
            'filterModel' => $searchModel,
            'columns' => $columns,
            'panel' => [
                'before' => $ind,
                'heading' => '<i class="fas fa-book"></i>  tablas a auditar',
                'type' => 'info',
            ],
            'export' => false,
            'rowOptions' => function ($model) {
                return ['data-id' => $model->id]; // Agrega el ID del modelo a la fila
            },
        ]);



        ?>
    </div>

</div>


<?php \yii\widgets\Pjax::end() ?>


<?php


$urla = Yii::$app->request->baseUrl . '/index.php?r=auditoria-tabla/activate';


$initScript2 = <<<JS2
         
        const GL_urla="$urla"; 

        
                
JS2;

$this->registerJs($initScript2, View::POS_HEAD);

$getSelect = <<<JS

$(document).on('change', '.kv-row-checkbox', function() {
    let row = $(this).closest('tr'); // Encuentra la fila del checkbox
    let id = row.data('key'); // Obtiene el ID desde data-id

    let estado = $(this).is(':checked') ? 1 : 0; // Estado: 1 si está marcado, 0 si está desmarcado

    console.log(id, estado, row.attr('class'));
    //1 1 'auditoria-tablas_grid table-success'
    //1 0 'auditoria-tablas_grid-grid'

        // Enviar los datos por AJAX
        $.ajax({
            url: GL_urla, 
            type: 'POST',
            data: {
                id: id,
                estado: estado,
                _csrf: yii.getCsrfToken() // Incluye el token CSRF para protección
            },
            success: function(response) {

                //console.log('Datos enviados con éxito:', response);
                $.pjax.reload({container: '#pjax-main', timeout: false});

            },
            error: function(xhr, status, error) {
                console.log('Error al enviar los datos:', error);
            }
        });
       


});	





JS;

$this->registerJs($getSelect);
