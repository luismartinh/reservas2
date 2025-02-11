<?php

use yii\bootstrap5\Html;
use yii\web\View;
use yii\widgets\DetailView;
use yii\widgets\Pjax;
use yii\bootstrap5\Tabs;

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
    echo DetailView::widget([
        'model' => $model,
        'attributes' => [
            'descr',
        ],
    ]);
    ?>

    <hr />
     
     <?php $this->endBlock(); ?>



    <?php $this->beginBlock('Accesos'); ?>
    <div style='float:right;'>
        <?php
        echo Html::a(
            '<span class="glyphicon glyphicon-list"></span> ' . Yii::t('cruds', 'List All Accesos'),
            ['acceso/index'],
            ['class' => 'btn text-muted btn-xs']
        ) ?>
        <?= Html::a(
            '<span class="glyphicon glyphicon-plus"></span> ' . Yii::t('cruds', 'New Accesos'),
            ['acceso/create'],
            ['class' => 'btn btn-success btn-xs']
        ); ?>
        <?= Html::a(
            '<span class="glyphicon glyphicon-link"></span> ' . Yii::t('cruds', 'Attach Accesos'),
            ['acceso/create', 'GruposAccesosAccesos' => ['id' => $model->id]],
            ['class' => 'btn btn-info btn-xs']
        ) ?>
    </div>
    <div class='clearfix'></div>
    <?php Pjax::begin(['id' => 'pjax-Accesos', 'enableReplaceState' => false, 'linkSelector' => '#pjax-Accesos ul.pagination a, th a']) ?>
    <?=
        '<div class="table-responsive">'
        . \yii\grid\GridView::widget([
            'layout' => '{summary}<div class="text-center">{pager}</div>{items}<div class="text-center">{pager}</div>',
            'dataProvider' => new \yii\data\ActiveDataProvider([
                'query' => $model->getAccesos(),
                'pagination' => [
                    'pageSize' => 20,
                    'pageParam' => 'page-accesos',
                ]
            ]),
            'pager' => [
                'class' => yii\widgets\LinkPager::class,
                'firstPageLabel' => Yii::t('cruds', 'First'),
                'lastPageLabel' => Yii::t('cruds', 'Last')
            ],
            'columns' => [
                [
                    'class' => 'yii\grid\ActionColumn',
                    'template' => '{view} {update}',
                    'contentOptions' => ['nowrap' => 'nowrap'],
                    'urlCreator' => function ($action, $model, $key) {
                    // using the column name as key, not mapping to 'id' like the standard generator
                    $params = is_array($key) ? $key : [$model->primaryKey()[0] => (string) $key];
                    $params[0] = 'acceso' . '/' . $action;
                    $params['Acceso'] = ['id' => $model->primaryKey()[0]];
                    return $params;
                },
                    'buttons' => [

                    ],
                    'controller' => 'acceso'
                ],
                'id',
                'descr',
                'acceso',
                'config',
                'created_by',
                'updated_by',
                'created_at',
                'updated_at',
            ]
        ])
        . '</div>'
        ?>
    <?php Pjax::end() ?>
    <?php $this->endBlock() ?>


    <?php $this->beginBlock('Usuarios'); ?>
    <div style='float:right;'>
        <?php
        echo Html::a(
            '<span class="glyphicon glyphicon-list"></span> ' . Yii::t('cruds', 'List All Usuarios'),
            ['usuario/index'],
            ['class' => 'btn text-muted btn-xs']
        ) ?>
        <?= Html::a(
            '<span class="glyphicon glyphicon-plus"></span> ' . Yii::t('cruds', 'New Usuarios'),
            ['usuario/create'],
            ['class' => 'btn btn-success btn-xs']
        ); ?>
        <?= Html::a(
            '<span class="glyphicon glyphicon-link"></span> ' . Yii::t('cruds', 'Attach Usuarios'),
            ['usuario/create', 'GruposAccesosUsuarios' => ['Id' => $model->id]],
            ['class' => 'btn btn-info btn-xs']
        ) ?>
    </div>
    <div class='clearfix'></div>
    <?php Pjax::begin(['id' => 'pjax-Usuarios', 'enableReplaceState' => false, 'linkSelector' => '#pjax-Usuarios ul.pagination a, th a']) ?>
    <?=
        '<div class="table-responsive">'
        . \yii\grid\GridView::widget([
            'layout' => '{summary}<div class="text-center">{pager}</div>{items}<div class="text-center">{pager}</div>',
            'dataProvider' => new \yii\data\ActiveDataProvider([
                'query' => $model->getUsuarios(),
                'pagination' => [
                    'pageSize' => 20,
                    'pageParam' => 'page-usuarios',
                ]
            ]),
            'pager' => [
                'class' => yii\widgets\LinkPager::class,
                'firstPageLabel' => Yii::t('cruds', 'First'),
                'lastPageLabel' => Yii::t('cruds', 'Last')
            ],
            'columns' => [
                [
                    'class' => 'yii\grid\ActionColumn',
                    'template' => '{view} {update}',
                    'contentOptions' => ['nowrap' => 'nowrap'],
                    'urlCreator' => function ($action, $model, $key) {
                    // using the column name as key, not mapping to 'id' like the standard generator
                    $params = is_array($key) ? $key : [$model->primaryKey()[0] => (string) $key];
                    $params[0] = 'usuario' . '/' . $action;
                    $params['Usuario'] = ['Id' => $model->primaryKey()[0]];
                    return $params;
                },
                    'buttons' => [

                    ],
                    'controller' => 'usuario'
                ],
                'Id',
                'login',
                'nombre',
                'apellido',
                'pwd',
                'Id_session',
                'last_login_time',
                'last_login_ip',
                'codigo',
            ]
        ])
        . '</div>'
        ?>
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
                    'label' => '<small>' . Yii::t('cruds', 'Accesos') . ' <span class="badge badge-default">' . $model->getAccesos()->count() . '</span></small>',
                    'active' => false,
                ],
                [
                    'content' => $this->blocks['Usuarios'],
                    'label' => '<small>' . Yii::t('cruds', 'Usuarios') . ' <span class="badge badge-default">' . $model->getUsuarios()->count() . '</span></small>',
                    'active' => false,
                ],
            ]
        ]
    );
    ?>
</div>