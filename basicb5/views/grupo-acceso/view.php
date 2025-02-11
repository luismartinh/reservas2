<?php

use app\models\GrupoAccesoAcceso;
use yii\bootstrap5\Html;
use yii\web\View;
use yii\widgets\DetailView;
use yii\widgets\Pjax;
use yii\bootstrap5\Tabs;

use kartik\icons\FontAwesomeAsset;
FontAwesomeAsset::register($this);

/**
 * @var yii\web\View $this
 * @var app\models\GrupoAcceso $model
 */

$this->title = Yii::t('models', 'Ver Grupo de acceso');
$this->params['breadcrumbs'][] = ['label' => Yii::t('models.plural', 'Grupos de acceso'), 'url' => ['index']];
$this->params['breadcrumbs'][] = Yii::t('cruds', 'Ver');
$grupo_id = $model->id;
$modelGrupo = $model;
?>
<div class="giiant-crud grupo-acceso-view">

    <h1>
        <?= $this->title ?>

        <small class="text-muted">
            <?= Html::encode($model->descr) ?>
        </small>
    </h1>


    <hr />

    <?php $this->beginBlock('app\models\GrupoAcceso'); ?>


    <?php
    $niveles = app\config\Niveles::getNiveles();
    echo DetailView::widget([
        'model' => $model,
        'attributes' => [
            'descr',
            [
                'attribute' => 'nivel',
                'value' => $niveles[$model->nivel],
            ],

        ],
    ]);
    ?>

    <hr />

    <?php $this->endBlock(); ?>







    <?php $this->beginBlock('Accesos'); ?>

    <?php Pjax::begin(['id' => 'pjax-Accesos', 'enableReplaceState' => false, 'linkSelector' => '#pjax-Accesos ul.pagination a, th a']) ?>

    <?php
    $searchAModel = Yii::createObject(app\models\AccesoSearch::class);

    $dataAProvider = $searchAModel->searchGrupo($request_get, $grupo_id,$user);
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
                    'checkboxOptions' => function ($model, $key, $index, $column) use ($modelGrupo) {

                        $grupoAcceso = GrupoAccesoAcceso::find()
                            ->where(['id_grupo_acceso' => $modelGrupo->id, 'id_acceso' => $model->id])->one();

                        if ($grupoAcceso != null) {
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
                    'attribute' => 'esAccesoGrupo',
                    'vAlign' => 'middle',
                    'width' => '15%',
                    'value' => function ($model, $key, $index, $column) use ($modelGrupo) {

                        $grupoAcceso = GrupoAccesoAcceso::find()
                            ->where(['id_grupo_acceso' => $modelGrupo->id, 'id_acceso' => $model->id])->one();

                        if ($grupoAcceso != null) {
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










    <?php $this->beginBlock('Usuarios'); ?>

    <?php Pjax::begin(['id' => 'pjax-Usuarios', 'enableReplaceState' => false, 'linkSelector' => '#pjax-Usuarios ul.pagination a, th a']) ?>

    <?php
    $searchUsModel = Yii::createObject(app\models\UsuarioSearch::class);

    $dataUsProvider = $searchUsModel->searchGrupo($request_get, $model->id,$user->nivel);
    ?>

    <div class="table-responsive mt-2">
        <?php
        echo kartik\grid\GridView::widget([
            'id' => 'usuarios-grid',
            'layout' => '{summary}<div class="text-center">{pager}</div>{items}<div class="text-center">{pager}</div>',
            'dataProvider' => $dataUsProvider,
            'filterModel' => $searchUsModel,
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
                    'checkboxOptions' => function ($model, $key, $index, $column) use ($modelGrupo) {

                        $userGrupo = app\models\GrupoAccesoUsuario::find()
                            ->where(['id_usuario' => $model->id, 'id_grupo_acceso' => $modelGrupo->id])->one();

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
                    'attribute' => 'login',
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
                        $searchUsModel,
                        'nivel',
                        app\config\Niveles::getNiveles(),
                        ['class' => 'form-control', 'prompt' => 'Select']
                    ),
                    'format' => 'raw',

                ],


                [
                    'vAlign' => 'middle',
                    'hAlign' => 'left',
                    'attribute' => 'nombre',
                    'headerOptions' => ['style' => 'text-align:center'],
                    'contentOptions' => ['style' => 'text-align:left'],
                ],

                [
                    'vAlign' => 'middle',
                    'hAlign' => 'left',
                    'attribute' => 'apellido',
                    'headerOptions' => ['style' => 'text-align:center'],
                    'contentOptions' => ['style' => 'text-align:left'],
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
                    'value' => function ($model, $key, $index, $column) use ($modelGrupo) {

                        $userGrupo = app\models\GrupoAccesoUsuario::find()
                            ->where(['id_usuario' => $model->id, 'id_grupo_acceso' => $modelGrupo->id])->one();

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


    <?php
    echo Tabs::widget(
        [
            'id' => 'relation-tabs',
            'encodeLabels' => false,
            'items' => [
                [
                    'label' => '<b>' . \Yii::t('cruds', '# {primaryKey}', ['primaryKey' => Html::encode($model->id)]) . '</b>',
                    'content' => $this->blocks['app\models\GrupoAcceso'],
                    'active' => true,
                ],
                [
                    'content' => $this->blocks['Accesos'],
                    'label' => '<small>' . Yii::t('cruds', 'Accesos asignados: ')
                        . ' <span class="badge bg-secondary" id="id_accesos_count">' . $model->getAccesos()->count()
                        . '</span></small>',
                    'active' => false,
                ],
                [
                    'content' => $this->blocks['Usuarios'],
                    'label' => '<small>' . Yii::t('cruds', 'Usuarios asignados: ')
                        . ' <span class="badge bg-secondary" id="id_usuarios_count">' . $model->getUsuarios()->count()
                        . '</span></small>',
                    'active' => false,
                ],
            ]
        ]
    );
    ?>
</div>


<?php


$urluser = Yii::$app->request->baseUrl . '/index.php?r=grupo-acceso/setusuario';
$urla = Yii::$app->request->baseUrl . '/index.php?r=grupo-acceso/setacceso';

$initScript2 = <<<JS2
         
        const GL_urluser="$urluser";     
        const GL_urla="$urla"; 
        const GL_grupo_id=$grupo_id;
        
                
JS2;

$this->registerJs($initScript2, View::POS_HEAD);

$getSelect = <<<JS

$(document).on('change', '.kv-row-checkbox', function() {
    let row = $(this).closest('tr'); // Encuentra la fila del checkbox
    let id = row.data('key'); // Obtiene el ID desde data-id


    let estado = $(this).is(':checked') ? 1 : 0; // Estado: 1 si está marcado, 0 si está desmarcado

    console.log(id, estado, row.attr('class'));
    //1 1 'usuarios-grid table-success'
    //1 0 'usuarios-grid'
    //1 1 accesos-grid table-success
    //1 0 'accesos-grid'

    const esDeUsuarios=row.attr('class').toLowerCase().includes("usuarios-grid".toLowerCase());
    const esDeAccesos=row.attr('class').toLowerCase().includes("accesos-grid".toLowerCase());


    if(esDeUsuarios){
        // Enviar los datos por AJAX
        $.ajax({
            url: GL_urluser, 
            type: 'POST',
            data: {
                id_grupo: GL_grupo_id,
                id: id,
                estado: estado,
                _csrf: yii.getCsrfToken() // Incluye el token CSRF para protección
            },
            success: function(response) {
                $("#id_usuarios_count").text(response.count);
                //console.log('Datos enviados con éxito:', response);
                $.pjax.reload({container: '#pjax-Usuarios', timeout: false});

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
                id_grupo: GL_grupo_id,
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
