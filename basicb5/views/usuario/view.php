<?php


use app\models\GrupoAccesoUsuario;
use app\models\UsuarioAcceso;
use yii\bootstrap5\Html;
use yii\web\View;
use yii\widgets\DetailView;
use yii\widgets\Pjax;
use yii\bootstrap5\Tabs;

use kartik\icons\FontAwesomeAsset;
FontAwesomeAsset::register($this);

/**
 * @var yii\web\View $this
 * @var app\models\Usuario $model
 */

$this->title = Yii::t('models', 'Ver Usuario');
$this->params['breadcrumbs'][] = ['label' => Yii::t('models.plural', 'Usuario'), 'url' => ['index']];
$this->params['breadcrumbs'][] = Yii::t('cruds', 'Ver');
$user_id = $model->id;
$modelUsuario = $model;

?>
<div class="giiant-crud usuario-view">

    <h1>
        <?= $this->title ?>

        <small class="text-muted">
            <?= Html::encode($model->login) ?>
        </small>
    </h1>


    <hr />

    <?php $this->beginBlock('app\models\Usuario'); ?>


    <?php
    $niveles = app\config\Niveles::getNiveles();
    echo DetailView::widget([
        'model' => $model,
        'attributes' => [
            'login',
            [
                'attribute' => 'nivel',
                'value' =>  $niveles[$model->nivel] ,
            ],
            'nombre',
            'apellido',
            [
                'attribute' => 'last_login_time',
                'label' => 'Desde',
                'value' => $model->last_login_time != null ? date("d-M-Y H:i", strtotime($model->last_login_time)) : '',
            ],
            [
                'attribute' => 'activo',
                'value' => $model->activo == '1' ? 'Si' : 'No',
            ],
            'email:email',
            'last_login_ip',
            'codigo',
        ],
    ]);
    ?>


    <hr />

    <?php $this->endBlock(); ?>




    <?php $this->beginBlock('GrupoAccesos'); ?>


    <?php Pjax::begin(['id' => 'pjax-GrupoAccesos', 'enableReplaceState' => false, 'linkSelector' => '#pjax-GrupoAccesos ul.pagination a, th a']) ?>

    <?php
    $searchGrModel = Yii::createObject(app\models\GrupoAccesoSearch::class);

    $dataGrProvider = $searchGrModel->searchUsuario($request_get, $model->id,$user->nivel);
    ?>

    <div class="table-responsive mt-2">
        <?php
        echo kartik\grid\GridView::widget([
            'id' => 'grupoaccesos-grid',
            'layout' => '{summary}<div class="text-center">{pager}</div>{items}<div class="text-center">{pager}</div>',
            'dataProvider' => $dataGrProvider,
            'filterModel' => $searchGrModel,
            'pager' => [
                'class' => yii\widgets\LinkPager::class,
                'firstPageLabel' => Yii::t('cruds', 'First'),
                'lastPageLabel' => Yii::t('cruds', 'Last')
            ],
            'columns' => [
                [
                    'class' => 'kartik\grid\CheckboxColumn',
                    'headerOptions' => ['class' => 'kartik-sheet-style'],
                    'header' => 'Asignar',
                    'rowHighlight' => true,
                    'rowSelectedClass' => 'table-success',
                    'checkboxOptions' => function ($model, $key, $index, $column) use ($modelUsuario) {

                        $userGrupo = GrupoAccesoUsuario::find()
                            ->where(['id_usuario' => $modelUsuario->id, 'id_grupo_acceso' => $model->id])->one();

                        if ($userGrupo != null) {
                            return [
                                'checked' => true, // Si activo es 1, el checkbox estará marcado
                            ];

                        } else {
                            return [
                                'checked' => false, // Si activo es 1, el checkbox estará marcado
                            ];

                        }


                    },
                ],
                [
                    'vAlign' => 'middle',
                    'hAlign' => 'left',
                    'label' => 'Grupos de Acceso',
                    'attribute' => 'descr',
                    'headerOptions' => ['style' => 'text-align:center'],
                    'contentOptions' => ['style' => 'text-align:left'],
                ],
                [
                    'vAlign' => 'middle',
                    'hAlign' => 'middle',
                    'attribute' => 'nivel',
                    'headerOptions' => ['style' => 'text-align:center'],
                    'contentOptions' => ['style' => 'text-align:center'],
                    'value' => function ($model) {
                        if ($rel = $model) {
                            $niveles = app\config\Niveles::getNiveles();
                            return yii\helpers\Html::encode($niveles[$rel->nivel]);
                        } else {
                            return '';
                        }

                    },
                    'filter' => Html::activeDropDownList(
                        $searchGrModel,
                        'nivel',
                        app\config\Niveles::getNiveles(),
                        ['class' => 'form-control', 'prompt' => 'Select']
                    ),
                    'format' => 'raw',

                ],


                [
                    'class' => 'kartik\grid\BooleanColumn',
                    'headerOptions' => ['style' => 'text-align:center'],
                    'label' => 'Esta Asignado?',
                    'trueLabel' => 'SI',
                    'falseLabel' => 'NO',
                    'attribute' => 'esUsuarioGrupo',
                    'vAlign' => 'middle',
                    'width' => '15%',
                    'value' => function ($model, $key, $index, $column) use ($modelUsuario) {

                        $userGrupo = GrupoAccesoUsuario::find()
                            ->where(['id_usuario' => $modelUsuario->id, 'id_grupo_acceso' => $model->id])->one();

                        if ($userGrupo != null) {
                            return 1;
                        } else {

                            return 0;

                        }


                    },

                ],

            ]
        ])
            ?>
    </div>
    <?php Pjax::end() ?>


    <?php $this->endBlock() ?>










    <?php $this->beginBlock('Accesos'); ?>


    <?php Pjax::begin(['id' => 'pjax-Accesos', 'enableReplaceState' => false, 'linkSelector' => '#pjax-Accesos ul.pagination a, th a']) ?>

    <?php
    $searchAModel = Yii::createObject(app\models\AccesoSearch::class);

    $dataAProvider = $searchAModel->searchUsuario($request_get, $model->id,$user);
    ?>

    <div class="table-responsive mt-2">
        <?php
        echo kartik\grid\GridView::widget([
            'id' => 'accesos-grid',
            'layout' => '{summary}<div class="text-center">{pager}</div>{items}<div class="text-center">{pager}</div>',
            'dataProvider' => $dataAProvider,
            'filterModel' => $searchAModel,
            'pager' => [
                'class' => yii\widgets\LinkPager::class,
                'firstPageLabel' => Yii::t('cruds', 'First'),
                'lastPageLabel' => Yii::t('cruds', 'Last')
            ],
            'columns' => [
                [
                    'class' => 'kartik\grid\CheckboxColumn',
                    'headerOptions' => ['class' => 'kartik-sheet-style'],
                    'header' => 'Asignar',
                    'rowHighlight' => true,
                    'rowSelectedClass' => 'table-success',
                    'checkboxOptions' => function ($model, $key, $index, $column) use ($modelUsuario) {

                        $userAcceso = UsuarioAcceso::find()
                            ->where(['id_usuario' => $modelUsuario->id, 'id_accesos' => $model->id])->one();

                        if ($userAcceso != null) {
                            return [
                                'checked' => true, // Si activo es 1, el checkbox estará marcado
                            ];

                        } else {
                            return [
                                'checked' => false, // Si activo es 1, el checkbox estará marcado
                            ];

                        }


                    },
                ],
                [
                    'vAlign' => 'middle',
                    'hAlign' => 'left',
                    'label' => 'Accesos',
                    'attribute' => 'descr',
                    'headerOptions' => ['style' => 'text-align:center'],
                    'contentOptions' => ['style' => 'text-align:left'],
                ],


                [
                    'class' => 'kartik\grid\BooleanColumn',
                    'headerOptions' => ['style' => 'text-align:center'],
                    'label' => 'Esta Asignado?',
                    'trueLabel' => 'SI',
                    'falseLabel' => 'NO',
                    'attribute' => 'esAccesoUsuario',
                    'vAlign' => 'middle',
                    'width' => '15%',
                    'value' => function ($model, $key, $index, $column) use ($modelUsuario) {

                        $userAcceso = UsuarioAcceso::find()
                            ->where(['id_usuario' => $modelUsuario->id, 'id_accesos' => $model->id])->one();

                        if ($userAcceso != null) {
                            return 1;
                        } else {

                            return 0;

                        }


                    },

                ],

            ]
        ])
            ?>
    </div>
    <?php Pjax::end() ?>


    <?php $this->endBlock() ?>



    <?php
    echo Tabs::widget(
        [
            'id' => 'relation-tabs',
            'encodeLabels' => false,
            'items' => [
                [
                    'label' => '<b>' . \Yii::t(
                        'cruds',
                        '# {primaryKey}',
                        ['primaryKey' => Html::encode($model->id)]
                    ) . '</b>',
                    'content' => $this->blocks['app\models\Usuario'],
                    'active' => true,
                ],
                [
                    'content' => $this->blocks['GrupoAccesos'],
                    'label' => '<small>' . Yii::t('cruds', 'Grupos de Accesos asignados: ')
                        . ' <span class="badge bg-secondary" id="id_grupos_count">' . $model->getGrupoAccesos()->count()
                        . '</span></small>',
                    'active' => false,
                ],
                [
                    'content' => $this->blocks['Accesos'],
                    'label' => '<small>' . Yii::t('cruds', 'Accesos asignados: ')
                        . ' <span class="badge bg-secondary" id="id_accesos_count">' . $model->getAccesos()->count()
                        . '</span></small>',
                    'active' => false,
                ],

            ]
        ]
    );
    ?>
</div>


<?php


$urlga = Yii::$app->request->baseUrl . '/index.php?r=usuario/setgrupoaccesos';
$urla = Yii::$app->request->baseUrl . '/index.php?r=usuario/setaccesos';

$initScript2 = <<<JS2
         
        const GL_urlga="$urlga";     
        const GL_urla="$urla"; 
        const GL_user_id=$user_id;
        
                
JS2;

$this->registerJs($initScript2, View::POS_HEAD);

$getSelect = <<<JS

$(document).on('change', '.kv-row-checkbox', function() {
    let row = $(this).closest('tr'); // Encuentra la fila del checkbox
    let id = row.data('key'); // Obtiene el ID desde data-id


    let estado = $(this).is(':checked') ? 1 : 0; // Estado: 1 si está marcado, 0 si está desmarcado

    console.log(id, estado, row.attr('class'));
    //1 1 'grupoaccesos-grid table-success'
    //1 0 'grupoaccesos-grid'
    //1 1 accesos-grid table-success
    //1 0 'accesos-grid'

    const esDeGrupoAccesos=row.attr('class').toLowerCase().includes("grupoaccesos-grid".toLowerCase());
    const esDeAccesos=row.attr('class').toLowerCase().includes("accesos-grid".toLowerCase());


    if(esDeGrupoAccesos){
        // Enviar los datos por AJAX
        $.ajax({
            url: GL_urlga, 
            type: 'POST',
            data: {
                id_usuario: GL_user_id,
                id: id,
                estado: estado,
                _csrf: yii.getCsrfToken() // Incluye el token CSRF para protección
            },
            success: function(response) {
                $("#id_grupos_count").text(response.count);
                //console.log('Datos enviados con éxito:', response);
                $.pjax.reload({container: '#pjax-GrupoAccesos', timeout: false});

            },
            error: function(xhr, status, error) {
                console.log('Error al enviar los datos:', error);
            }
        });
       
    }

    if(esDeAccesos){
        // Enviar los datos por AJAX
        $.ajax({
            url: GL_urla, 
            type: 'POST',
            data: {
                id_usuario: GL_user_id,
                id: id,
                estado: estado,
                _csrf: yii.getCsrfToken() // Incluye el token CSRF para protección
            },
            success: function(response) {
                $("#id_accesos_count").text(response.count);
                //console.log('Datos enviados con éxito:', response);
                $.pjax.reload({container: '#pjax-Accesos', timeout: false});

            },
            error: function(xhr, status, error) {
                console.log('Error al enviar los datos:', error);
            }
        });
       
    }


});	





JS;

$this->registerJs($getSelect);
