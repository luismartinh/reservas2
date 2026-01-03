<style>
    .popover-x {
        display: none;
    }
</style>

<?php

use app\models\Estado;
use app\models\RequestReserva;
use yii\bootstrap5\Html;
use yii\bootstrap5\Modal;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use kartik\grid\GridView;


use kartik\icons\FontAwesomeAsset;
FontAwesomeAsset::register($this);

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var app\models\RequestReservaSearch $searchModel
 */

$this->title = Yii::t('models', 'Administrar Solicitudes');
$this->params['breadcrumbs'][] = $this->title;


?>
<div class="giiant-crud request-reserva-index">




    <h1>
        <?= $this->title ?>
    </h1>

    <?php echo $this->render('_search', ['model' => $searchModel]); ?>

    <hr />

    <div class="table-responsive">
        <?php

        $columns = [

            [
                'class' => 'kartik\grid\ActionColumn',
                'header' => '',
                'template' => "{view} {chat} {delete} ",//{editar-rango}
                'buttons' => [
                    'chat' => function ($url, $model, $key) {
                        $url = Url::to(['request-reserva/chat', 'id' => $model->id]);
                        $options = [
                            'title' => Yii::t('app', 'Consultas y respuestas'),
                            'aria-label' => Yii::t('app', 'Consultas y respuestas'),
                            'style' => 'margin-right: 3px;',
                            'data-pjax' => '0',
                            'class' => 'btn-chat-consultas',
                            'data-url' => $url,
                            'data-bs-toggle' => 'tooltip',
                            'data-bs-placement' => 'top',
                        ];
                        return Html::a('<span class="bi bi-chat-dots" aria-hidden="true"></span>', 'javascript:void(0);', $options);
                    },

                    'view' => function ($url, $model, $key) {

                        $url = Url::toRoute(['disponibilidad/seguimiento', 'hash' => $model->hash]);

                        $options = [
                            'title' => Yii::t('cruds', 'Ver'),
                            'aria-label' => Yii::t('cruds', 'Ver'),
                            'style' => 'margin-right: 3px;',
                            'data-pjax' => '0',
                            'onclick' => "window.open('" . $url . "',"
                                . "'popup','width=1000,height=600,scrollbars=no,resizable=no'); "
                                . "return false;",


                        ];
                        return Html::a('<span class="fas fa-eye" aria-hidden="true"></span>', $url, $options);
                    },
                    'delete' => function ($url, $model, $key) {
                        $options = [
                            'title' => Yii::t('cruds', 'Eliminar'),
                            'aria-label' => Yii::t('cruds', 'Eliminar'),
                            'style' => 'margin-right: 3px;',
                            'data-pjax' => '0',
                            'data-confirm' => Yii::t('cruds', 'Esta seguro de eliminar? Se eliminaran los pagos asociados y la reserva correspomndiente.'),
                        ];
                        return Html::a('<span class="fas fa-trash-alt" aria-hidden="true"></span>', $url, $options);
                    },

                    /*
                    'editar-rango' => function ($url, $model, $key) {
                       

                        $options = [
                            'title' => Yii::t('cruds', 'Cambiar rango'),
                            'aria-label' => Yii::t('cruds', 'Cambiar rango'),
                            'style' => 'margin-right: 3px;',
                            'data-pjax' => '0',

                        ];
                        return Html::a('<span class="fas fa-pen" aria-hidden="true"></span>', $url, $options);
                    },
                    */

                ],
                'urlCreator' => function ($action, $model, $key, $index) {
                    // using the column name as key, not mapping to 'id' like the standard generator
                    $params = is_array($key) ? $key : [$model->primaryKey()[0] => (string) $key];
                    $params[0] = \Yii::$app->controller->id ? \Yii::$app->controller->id . '/' . $action : $action;
                    return Url::toRoute($params);
                },
                'contentOptions' => ['nowrap' => 'nowrap']
            ],
            [
                'vAlign' => 'middle',
                'hAlign' => 'middle',
                'headerOptions' => ['style' => 'text-align:center;with:50px'],
                'contentOptions' => ['class' => 'text-center align-middle'],
                'attribute' => 'id',
                'label' => '#',
                'value' => function ($model, $key, $index, $column) {

                    if ($model->id_reserva != null) {
                        return $model->id . '-' . $model->id_reserva . ' ';
                        ;
                    }
                    return $model->id;
                },

                //'filter' => false,
                'filterInputOptions' => [
                    //'type' => 'number',
                    'class' => 'form-control',
                    'style' => 'width:60px;',
                ],

            ],

            [
                'class' => 'kartik\grid\ExpandRowColumn',
                'width' => '50px',
                'headerOptions' => ['style' => 'text-align:center'],
                'contentOptions' => ['class' => 'text-center align-middle'],
                'expandOneOnly' => true,
                'value' => function ($model, $key, $index, $column) {
                    // por defecto, filas colapsadas
                    return GridView::ROW_COLLAPSED;
                },
                'detail' => function ($model, $key, $index, $column) {
                    /** @var \app\models\RequestReserva $model */
                    return Yii::$app->controller->renderPartial('_request_cabanas', [
                        'model' => $model,
                    ]);
                },
                'expandIcon' => '<i class="bi bi-chevron-right"></i>',
                'collapseIcon' => '<i class="bi bi-chevron-down"></i>',
            ],
            [
                'attribute' => 'fecha',
                'headerOptions' => ['style' => 'text-align:center'],
                'contentOptions' => ['style' => 'text-align:center'],

                'value' => function ($model) {
                    if ($rel = $model) {
                        if ($rel->fecha) {
                            return yii\helpers\Html::encode(date("d-m-y H:i", strtotime($rel->fecha)));
                        }
                        return null;
                    } else {
                        return null;
                    }

                },
                'filter' => false,
            ],
            [
                'attribute' => 'desde',
                'headerOptions' => ['style' => 'text-align:center'],
                'contentOptions' => ['style' => 'text-align:center'],

                'value' => function ($model) {
                    if ($rel = $model) {
                        if ($rel->desde) {
                            return yii\helpers\Html::encode(date("d-m-y H:i", strtotime($rel->desde)));
                        }
                        return null;
                    } else {
                        return null;
                    }

                },

                'filter' => false,
            ],
            [
                'attribute' => 'hasta',
                'headerOptions' => ['style' => 'text-align:center'],
                'contentOptions' => ['style' => 'text-align:center'],

                'value' => function ($model) {
                    if ($rel = $model) {
                        if ($rel->hasta) {
                            return yii\helpers\Html::encode(date("d-m-y H:i", strtotime($rel->hasta)));
                        }
                        return null;
                    } else {
                        return null;
                    }

                },

                'filter' => false,
            ],
            [
                'class' => 'kartik\grid\EditableColumn',
                'attribute' => 'id_estado',
                'headerOptions' => ['style' => 'text-align:center'],
                'contentOptions' => ['style' => 'text-align:left'],
                'readonly' => function ($model, $key, $index, $widget) {

                    return $model->estado->slug == 'pendiente-email-verificar';

                },
                'value' => function ($model) {
                    if ($rel = $model->estado) {
                        $cell = Html::encode($rel->descr);

                        // Reutilizamos la lógica de vencida
                        $res = RequestReserva::vencida($model->id, new \DateTime());
                        if ($res['status'] === 'vencida') {
                            $cell .= '<br><span class="text-danger">' . Html::encode($res['msg']) . '</span>';
                        }


                        return $cell;
                    }
                    return '';
                },
                'format' => 'raw',
                'filter' => false,

                // Opciones de edición inline
                'editableOptions' => function ($model, $key, $index) {
                    return [
                        'header' => Yii::t('app', 'Estado'),
                        'size' => 'md',
                        'inputType' => kartik\editable\Editable::INPUT_DROPDOWN_LIST,
                        'format' => kartik\editable\Editable::FORMAT_BUTTON,
                        'placement' => 'left',
                        'asPopover' => true,
                        'data' => Estado::getSegunEstado($model),
                        'options' => [
                            'prompt' => Yii::t('app', 'Seleccione...'),
                        ],
                        'formOptions' => [
                            // se envía al mismo actionIndex
                            'action' => ['request-reserva/cambiar-estado'],
                            'options' => ['data-pjax' => 0], // evita que el submit del editable pase por pjax
                        ],
                        'pluginEvents' => [
                            // se dispara cuando el editable se guarda correctamente
                            "editableSuccess" => "function(event, val, form, data) {
                                // recargar toda la grilla
                                $.pjax.reload({container:'#pjax-main'});
                            }",
                        ],
                    ];
                },
            ],

            [
                'vAlign' => 'middle',
                'hAlign' => 'left',
                'attribute' => 'denominacion',
                'headerOptions' => ['style' => 'text-align:center'],
                'contentOptions' => ['style' => 'text-align:left'],
                'value' => function ($model) {

                    $datos = '<span class="text-muted">' . Yii::t('app', 'Cliente: ') . '</span ><b>' . yii\helpers\Html::encode($model->denominacion) . '<b>';
                    $datos .= '<br><span class="text-muted">' . Yii::t('app', 'Email: ') . ' </span ><b>' . yii\helpers\Html::encode($model->email) . '<b>';
                    if ($model->codigo_reserva != null) {
                        $datos .= '<br><span class="text-muted">' . Yii::t('app', 'Codigo: ') . ' </span ><b>' . yii\helpers\Html::encode($model->codigo_reserva) . '<b>';
                    }

                    if ($model->obs != null) {
                        $datos .= '<br><span class="text-info">' . Html::encode("TIENE OBSERVACIONES") . '</span>';
                    }

                    return $datos;
                },
                'filter' => false,
                'format' => 'raw',

            ],


            [
                'vAlign' => 'middle',
                'hAlign' => 'right',
                'attribute' => 'total',
                'headerOptions' => ['style' => 'text-align:center'],
                'contentOptions' => ['style' => 'text-align:right'],
                'value' => function ($model) {
                    if ($rel = $model) {

                        $pagado = (double) $rel->pagado;
                        $total = (double) $rel->total;
                        $debe = $total - $pagado;

                        $total_class = "text-success";
                        if ($debe == 0) {
                            $debet = "";

                        } else {
                            $total_class = "";
                            $debet = "
								<div class=\"text-right\"><small class=\"text-danger\">" .
                                yii\helpers\Html::encode(number_format($debe, 2, ".", ","))
                                . "</small></div>
							";

                        }



                        if ($pagado > 0 && $debe > 0) {
                            $pagadot = "
							<div class=\"text-right\"><small class=\"text-success\">- " .
                                yii\helpers\Html::encode(number_format($pagado, 2, ".", ","))
                                . "</small></div>
                            ";

                        }


                        $cell = "
						<div class=\"text-right  $total_class\"><b>" .
                            yii\helpers\Html::encode(number_format($rel->total, 2, ".", ","))
                            . "</b></div>
						$pagadot
						$debet
						";

                        return $cell;


                    } else {
                        return '';
                    }

                },
                'format' => 'raw',
                'pageSummary' => true,
                'enableSorting' => false,
                'filter' => false

            ],
            [
                'header' => Yii::t('app', 'Pagos'),
                'format' => 'raw',
                'vAlign' => 'middle',
                'hAlign' => 'center',
                'headerOptions' => ['style' => 'text-align:center'],
                'value' => function ($model) {

                    $slug = $model->estado->slug ?? null;
                    $debe = (float) $model->total - (float) $model->pagado;
                    $regPagos = is_array($model->registro_pagos) ? $model->registro_pagos : [];
                    $countPagos = count($regPagos);

                    $icons = [];

                    // 🔵 Badge con cantidad de pagos
                    $badge = '';
                    if ($countPagos > 0) {
                        $badge = '<span class="badge bg-primary rounded-pill me-2" 
                        data-bs-toggle="tooltip" 
                        title="' . Yii::t('app', 'Pagos registrados') . '">
                        ' . $countPagos . '
                      </span>';
                    }

                    // 🔹 Icono "Agregar pago"
                    if (
                        in_array($slug, ['confirmado', 'confirmado-verificar-pago', 'pendiente-email-verificado'], true)
                        && $debe > 0
                    ) {
                        $urlAgregar = Url::to(['request-reserva/agregar-pago', 'id' => $model->id]);

                        $icons[] = Html::a(
                            '<i class="bi bi-plus-circle fs-5 text-success"
                    data-bs-toggle="tooltip"
                    data-bs-placement="top"
                    title="' . Yii::t('app', 'Agregar pago') . '"></i>',
                            'javascript:void(0);',
                            [
                                'class' => 'btn-agregar-pago me-2',
                                'style' => 'text-decoration:none;',
                                'data-url' => $urlAgregar,
                                'data-id' => $model->id,
                            ]
                        );
                    }

                    // 🔹 Icono "Eliminar pagos"
                    if (!empty($regPagos)) {
                        $urlEliminar = Url::to(['request-reserva/eliminar-pagos', 'id' => $model->id]);

                        $icons[] = Html::a(
                            '<i class="bi bi-trash3 fs-5 text-danger"
                    data-bs-toggle="tooltip"
                    data-bs-placement="top"
                    title="' . Yii::t('app', 'Eliminar pagos') . '"></i>',
                            'javascript:void(0);',
                            [
                                'class' => 'btn-eliminar-pago',
                                'style' => 'text-decoration:none;',
                                'data-url' => $urlEliminar,
                                'data-id' => $model->id,
                            ]
                        );
                    }

                    if (empty($icons) && $badge === '') {
                        return '';
                    }

                    return '<div class="d-flex justify-content-center align-items-center gap-2">'
                        . $badge . implode('', $icons) .
                        '</div>';
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
            'id' => 'request-reserva_grid',
            'dataProvider' => $dataProvider,
            'pjax' => true,
            'pjaxSettings' => [
                'options' => [
                    'id' => 'pjax-main',   // mismo id que usabas antes
                ],
            ],
            'filterModel' => $searchModel,
            'columns' => $columns,
            'panel' => [
                'before' => $ind,
                'heading' => '<i class="bi bi-patch-question-fill"></i>  ' . Yii::t('cruds', 'Solicitudes Registradas'),
                'type' => 'info',
            ],
            'export' => false,
            'rowOptions' => function ($model) {

                $res = RequestReserva::vencida($model->id, new \DateTime());

                if ($res['status'] == 'vencida') {
                    return ['class' => 'table-danger', 'data-id' => $model->id];
                }



                if ($model->estado->slug === 'confirmado-verificar-pago') {
                    return ['class' => 'table-warning', 'data-id' => $model->id];
                }

                return ['data-id' => $model->id]; // Agrega el ID del modelo a la fila
            },
        ]); ?>
    </div>

</div>



<?php
Modal::begin([
    'title' => Yii::t('app', 'Agregar pago'),
    'id' => 'modal-agregar-pago',
    'size' => Modal::SIZE_DEFAULT,
]);
echo '<div id="modal-agregar-pago-content"></div>';

echo '<div class="modal-loading-overlay" id="modal-agregar-pago-loading">
        <div class="text-center">
            <div class="spinner-border" role="status" aria-hidden="true"></div>
            <div class="mt-2"><small>'.Yii::t('app','Procesando...').'</small></div>
        </div>
      </div>';

Modal::end();


Modal::begin([
    'id' => 'modal-pago',
    'title' => '<h5 id="modal-pago-title" class="modal-title"></h5>',
    'size' => Modal::SIZE_LARGE,
]);
?>
<div id="modal-pago-body"></div>
<?php Modal::end();




Modal::begin([
    'id' => 'modal-chat',
    'title' => '<span class="modal-title"></span>',
    'size' => Modal::SIZE_LARGE,
    'dialogOptions' => [
        'class' => 'modal-dialog modal-xl modal-dialog-centered chat-modal-dialog',
    ],
]);
?>
<div id="modal-chat-content"></div>
<?php Modal::end(); ?>



<?php
$titleAgregarPago = Yii::t('app', 'Agregar pago');
$titleEliminarPagos = Yii::t('app', 'Eliminar pagos');

$msgErrorCargarFormPago = Yii::t('app', 'Error al cargar el formulario de pago.');
$msgErrorProcesarPago = Yii::t('app', 'Error al procesar el pago.');
$msgErrorComunicacion = Yii::t('app', 'Error de comunicación con el servidor.');
$msgErrorGenerico = Yii::t('app', 'Error');
$msgOk = Yii::t('app', 'OK');

$titleChat = Yii::t('app', 'Consultas y respuestas');
$msgErrorChatCargar = Yii::t('app', 'Error al cargar el chat.');
$msgErrorChatEnviar = Yii::t('app', 'Error al enviar el mensaje.');
$msgErrorComunicacion = Yii::t('app', 'Error de comunicación con el servidor.');
$msgErrorGenerico = Yii::t('app', 'Error');
?>



<?php
$js = <<<JS
(function() {


    function setAgregarPagoLoading(isLoading) {
        var modalEl = document.getElementById('modal-agregar-pago');
        if (!modalEl) return;

        var overlay = document.getElementById('modal-agregar-pago-loading');
        if (overlay) {
            overlay.style.display = isLoading ? 'flex' : 'none';
        }

        // deshabilitar inputs/botones dentro del modal
        $('#modal-agregar-pago')
            .find('input, textarea, select, button')
            .prop('disabled', isLoading);

        // bloquear cierre (backdrop + ESC) mientras carga
        var bsModal = bootstrap.Modal.getInstance(modalEl);
        if (bsModal) {
            if (isLoading) {
                // Bootstrap 5.3+: setear opciones dinámicas
                bsModal._config.backdrop = 'static';
                bsModal._config.keyboard = false;
            } else {
                bsModal._config.backdrop = true;
                bsModal._config.keyboard = true;
            }
        }
    }


    // Abrir modal y cargar el form por AJAX
    $(document).on('click', '.btn-agregar-pago', function(e) {
        e.preventDefault();
        var url = $(this).data('url');

        $.ajax({
            url: url,
            type: 'GET',
            success: function(resp) {
                if (resp.success && resp.html) {
                    $('#modal-agregar-pago-content').html(resp.html);
                    //$('#modal-agregar-pago').modal('show');
                    var modalEl = document.getElementById('modal-agregar-pago');
                    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modal.show();

                    // por si quedó algo “trabado” de un intento anterior
                    setAgregarPagoLoading(false);                    
                } else if (resp.message) {
                    alert(resp.message);
                } else {
                    alert("{$msgErrorCargarFormPago}");
                }
            },
            error: function() {
                alert("{$msgErrorCargarFormPago}");
            }
        });
    });

    // Enviar el form del modal por AJAX (con comprobante)
    $(document).on('submit', '#form-agregar-pago', function(e) {
        e.preventDefault();
        var form = this;
        var formData = new FormData(form);

        setAgregarPagoLoading(true);

        $.ajax({
            url: $(form).attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(resp) {
                if (resp.success) {
                    //$('#modal-agregar-pago').modal('hide');
                   // cerrar modal
                    var modalEl = document.getElementById('modal-agregar-pago');
                    var modal   = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();                    
                    // Recargar la grilla (Kartik GridView con pjax=true)
                    $.pjax.reload({container: '#pjax-main'});
                } else if (resp.html) {
                    // Errores de validación: refrescamos el contenido del modal
                    $('#modal-agregar-pago-content').html(resp.html);
                    setAgregarPagoLoading(false);
                } else if (resp.message) {
                    alert(resp.message);
                    setAgregarPagoLoading(false);
                } else {
                    alert("{$msgErrorProcesarPago}");
                    setAgregarPagoLoading(false);
                }
            },
            error: function() {
                alert("{$msgErrorProcesarPago}");
                setAgregarPagoLoading(false);
            }
        });

        return false;
    });

    // Si el modal se cierra por cualquier cosa, aseguramos estado normal
    document.getElementById('modal-agregar-pago')?.addEventListener('hidden.bs.modal', function(){
        setAgregarPagoLoading(false);
    })


})();
JS;

$this->registerJs($js);
?>



<?php
$js = <<<JS
// Abrir modal de ELIMINAR pagos
$(document).on('click', '.btn-eliminar-pago', function(e) {
    e.preventDefault();
    var btn = $(this);
    var url = btn.data('url');

    $.get(url, function(resp) {
        if (resp && resp.success) {
            $('#modal-pago-title').text("{$titleEliminarPagos}");
            $('#modal-pago-body').html(resp.html);
            var modal = new bootstrap.Modal(document.getElementById('modal-pago'));
            modal.show();
        } else {
            alert(resp && resp.message ? resp.message : "{$msgErrorGenerico}");
        }
    }).fail(function() {
        alert("{$msgErrorComunicacion}");
    });
});

// Submit del form de eliminar pagos (AJAX)
$(document).on('submit', '#form-eliminar-pagos', function(e) {
    e.preventDefault();
    var form = $(this);
    var url  = form.attr('action');

    $.post(url, form.serialize(), function(resp) {
        if (resp && resp.success) {
            alert(resp.message || "{$msgOk}");
            // Cerrar modal
            var modalEl = document.getElementById('modal-pago');
            var modal   = bootstrap.Modal.getInstance(modalEl);
            if (modal) {
                modal.hide();
            }
            // Recargar grilla
            $.pjax.reload({container: '#pjax-main'});
        } else if (resp && resp.html) {
            // Errores de validación: recargar contenido del modal
            $('#modal-pago-body').html(resp.html);
        } else {
            alert(resp && resp.message ? resp.message : "{$msgErrorGenerico}");
        }
    }).fail(function() {
        alert("{$msgErrorComunicacion}");
    });
});
JS;

$this->registerJs($js);
?>


<?php
$js = <<<JS
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
tooltipTriggerList.map(function (tooltipTriggerEl) {
  return new bootstrap.Tooltip(tooltipTriggerEl)
})
JS;

$this->registerJs($js);
?>



<?php
$js = <<<JS
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
tooltipTriggerList.map(function (tooltipTriggerEl) {
  return new bootstrap.Tooltip(tooltipTriggerEl)
})
JS;

$this->registerJs($js);
?>


<?php
$js = <<<JS
// Abrir modal de CHAT
$(document).on('click', '.btn-chat-consultas', function(e) {
    e.preventDefault();
    var btn = $(this);
    var url = btn.data('url');

    $.get(url, function(resp) {
        if (resp && resp.success) {
            $('#modal-chat-content').html(resp.html);
            var modal = new bootstrap.Modal(document.getElementById('modal-chat'));
            modal.show();
        } else {
            alert(resp && resp.message ? resp.message : "{$msgErrorChatCargar}");
        }
    }).fail(function() {
        alert("{$msgErrorComunicacion}");
    });
});

// Enviar mensaje del chat (AJAX)
$(document).on('submit', '#form-chat-consultas', function(e) {
    e.preventDefault();
    var form = $(this);
    var url  = form.attr('action');

    $.post(url, form.serialize(), function(resp) {
        if (resp && resp.success && resp.html) {
            // Reemplazar el contenido del chat (mensajes + form)
            $('#modal-chat-content').html(resp.html);
        } else if (resp && resp.message) {
            alert(resp.message);
        } else {
            alert("{$msgErrorChatEnviar}");
        }
    }).fail(function() {
        alert("{$msgErrorComunicacion}");
    });

    return false;
});
JS;

$this->registerJs($js);
?>

<?php
$css = <<<CSS
.chat-modal-dialog .modal-content {
    max-height: 95vh;   /* casi toda la altura de la ventana */
}


/* Overlay de “cargando” para el modal */
.modal-loading-overlay{
    position:absolute;
    inset:0;
    background:rgba(255,255,255,.75);
    display:none;
    align-items:center;
    justify-content:center;
    z-index: 1060; /* por encima del contenido del modal */
    border-radius: .5rem;
}

.modal-loading-overlay .spinner-border{
    width:3rem;
    height:3rem;
}

/* asegura contexto para overlay */
#modal-agregar-pago .modal-content{
    position: relative;
}



CSS;
$this->registerCss($css);
?>