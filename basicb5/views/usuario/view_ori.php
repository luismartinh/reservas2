<?php

use yii\bootstrap5\Html;
use yii\helpers\Url;
use yii\grid\GridView;
use yii\widgets\DetailView;
use yii\widgets\Pjax;
use yii\bootstrap5\Tabs;

/**
 * @var yii\web\View $this
 * @var app\models\Usuario $model
 */

$this->title = Yii::t('models', 'Ver Usuario');
$this->params['breadcrumbs'][] = ['label' => Yii::t('models.plural', 'Usuario'), 'url' => ['index']];
$this->params['breadcrumbs'][] = Yii::t('cruds', 'Ver');
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
    echo DetailView::widget([
        'model' => $model,
        'attributes' => [
            'login',
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
            ['acceso/create', 'UsuariosAccesos' => ['id' => $model->Id]],
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


    <?php $this->beginBlock('GrupoAccesos'); ?>
    <div style='float:right;'>
        <?php
        echo Html::a(
            '<span class="glyphicon glyphicon-list"></span> ' . Yii::t('cruds', 'List All Grupo Accesos'),
            ['grupo-acceso/index'],
            ['class' => 'btn text-muted btn-xs']
        ) ?>
        <?= Html::a(
            '<span class="glyphicon glyphicon-plus"></span> ' . Yii::t('cruds', 'New Grupo Accesos'),
            ['grupo-acceso/create'],
            ['class' => 'btn btn-success btn-xs']
        ); ?>
        <?= Html::a(
            '<span class="glyphicon glyphicon-link"></span> ' . Yii::t('cruds', 'Attach Grupo Accesos'),
            ['grupo-acceso/create', 'GruposAccesosUsuarios' => ['id' => $model->Id]],
            ['class' => 'btn btn-info btn-xs']
        ) ?>
    </div>
    <div class='clearfix'></div>
    <?php Pjax::begin(['id' => 'pjax-GrupoAccesos', 'enableReplaceState' => false, 'linkSelector' => '#pjax-GrupoAccesos ul.pagination a, th a']) ?>
    <?=
        '<div class="table-responsive">'
        . \yii\grid\GridView::widget([
            'layout' => '{summary}<div class="text-center">{pager}</div>{items}<div class="text-center">{pager}</div>',
            'dataProvider' => new \yii\data\ActiveDataProvider([
                'query' => $model->getGrupoAccesos(),
                'pagination' => [
                    'pageSize' => 20,
                    'pageParam' => 'page-grupoaccesos',
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
                    $params[0] = 'grupo-acceso' . '/' . $action;
                    $params['GrupoAcceso'] = ['id' => $model->primaryKey()[0]];
                    return $params;
                },
                    'buttons' => [

                    ],
                    'controller' => 'grupo-acceso'
                ],
                'id',
                'descr',
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


    <?php $this->beginBlock('TipoUsuarios'); ?>
    <div style='float:right;'>
        <?php
        echo Html::a(
            '<span class="glyphicon glyphicon-list"></span> ' . Yii::t('cruds', 'List All Tipo Usuarios'),
            ['tipo-usuario/index'],
            ['class' => 'btn text-muted btn-xs']
        ) ?>
        <?= Html::a(
            '<span class="glyphicon glyphicon-plus"></span> ' . Yii::t('cruds', 'New Tipo Usuarios'),
            ['tipo-usuario/create'],
            ['class' => 'btn btn-success btn-xs']
        ); ?>
        <?= Html::a(
            '<span class="glyphicon glyphicon-link"></span> ' . Yii::t('cruds', 'Attach Tipo Usuarios'),
            ['tipo-usuario/create', 'UsuarioTiposUsuarios' => ['id' => $model->Id]],
            ['class' => 'btn btn-info btn-xs']
        ) ?>
    </div>
    <div class='clearfix'></div>
    <?php Pjax::begin(['id' => 'pjax-TipoUsuarios', 'enableReplaceState' => false, 'linkSelector' => '#pjax-TipoUsuarios ul.pagination a, th a']) ?>
    <?=
        '<div class="table-responsive">'
        . \yii\grid\GridView::widget([
            'layout' => '{summary}<div class="text-center">{pager}</div>{items}<div class="text-center">{pager}</div>',
            'dataProvider' => new \yii\data\ActiveDataProvider([
                'query' => $model->getTipoUsuarios(),
                'pagination' => [
                    'pageSize' => 20,
                    'pageParam' => 'page-tipousuarios',
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
                    $params[0] = 'tipo-usuario' . '/' . $action;
                    $params['TipoUsuario'] = ['id' => $model->primaryKey()[0]];
                    return $params;
                },
                    'buttons' => [

                    ],
                    'controller' => 'tipo-usuario'
                ],
                'id',
                'descr',
                'url:url',
                'nivel',
                'redirect',
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


    <?php
    echo Tabs::widget(
        [
            'id' => 'relation-tabs',
            'encodeLabels' => false,
            'items' => [
                [
                    'label' => '<b>' . \Yii::t('cruds', '# {primaryKey}', ['primaryKey' => Html::encode($model->Id)]) . '</b>',
                    'content' => $this->blocks['app\models\Usuario'],
                    'active' => true,
                ],
                [
                    'content' => $this->blocks['Accesos'],
                    'label' => '<small>' . Yii::t('cruds', 'Accesos') . ' <span class="badge badge-default">' . $model->getAccesos()->count() . '</span></small>',
                    'active' => false,
                ],
                [
                    'content' => $this->blocks['GrupoAccesos'],
                    'label' => '<small>' . Yii::t('cruds', 'Grupo Accesos') . ' <span class="badge badge-default">' . $model->getGrupoAccesos()->count() . '</span></small>',
                    'active' => false,
                ],
                [
                    'content' => $this->blocks['TipoUsuarios'],
                    'label' => '<small>' . Yii::t('cruds', 'Tipo Usuarios') . ' <span class="badge badge-default">' . $model->getTipoUsuarios()->count() . '</span></small>',
                    'active' => false,
                ],
            ]
        ]
    );
    ?>
</div>