<?php

/** @var yii\web\View $this */
/** @var \DateTimeImmutable $start1 */
/** @var \DateTimeImmutable $start2 */
/** @var array $tarifaItems */
/** @var array $cabanasDropdown */
/** @var array $tarifasDropdown */
/** @var int|null $selectedCabanaId */
/** @var int|null $selectedTarifaId */
/** @var int $year */
/** @var int $month */

use app\helpers\CalendarHelper;
use yii\bootstrap5\Html;
use yii\bootstrap5\Modal;
use yii\helpers\Json;
use yii\helpers\Url;

$this->title = 'Calendario de tarifas';
$calendarUrl = Url::to(['/tarifa/calendario']);
$calendarUrlJs = Json::htmlEncode($calendarUrl);
$baseMonth = new \DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
$prevStart = $baseMonth->modify('-1 month');
$nextStart = $baseMonth->modify('+1 month');
$currentStart = new \DateTimeImmutable(date('Y-m-01'));

$navParams = [];
if ($selectedCabanaId) {
    $navParams['id_cabana'] = $selectedCabanaId;
}
if ($selectedTarifaId) {
    $navParams['id_tarifa'] = $selectedTarifaId;
}
?>

<div class="tarifa-calendario">
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            Buscar tarifas
        </div>
        <div class="card-body">
            <form method="get" action="<?= Html::encode($calendarUrl) ?>" id="filtros-tarifa-calendario">
                <?= Html::hiddenInput('year', $year) ?>
                <?= Html::hiddenInput('month', $month) ?>
                <div class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label" for="filtro-id-cabana">Cabaña</label>
                        <?= Html::dropDownList(
                            'id_cabana',
                            $selectedCabanaId,
                            $cabanasDropdown,
                            [
                                'id' => 'filtro-id-cabana',
                                'class' => 'form-select',
                                'prompt' => 'Seleccione una cabaña',
                            ]
                        ) ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="filtro-id-tarifa">Tarifa</label>
                        <?= Html::dropDownList(
                            'id_tarifa',
                            $selectedTarifaId,
                            $tarifasDropdown,
                            [
                                'id' => 'filtro-id-tarifa',
                                'class' => 'form-select',
                                'prompt' => 'Seleccione una tarifa',
                                'disabled' => empty($tarifasDropdown),
                            ]
                        ) ?>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if (!$selectedCabanaId): ?>
        <div class="alert alert-info">
            Seleccione una cabaña para visualizar sus tarifas en el calendario.
        </div>
    <?php else: ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h3 mb-0">
                    <i class="bi bi-calendar3-week me-2"></i>
                    <?= Html::encode($this->title) ?>
                </h1>
                <small class="text-muted">
                    Navegue por los meses para visualizar las tarifas de la cabaña seleccionada.
                </small>
            </div>
</div>

        <div class="d-flex justify-content-between align-items-center gap-3 mb-3 flex-wrap">
            <div class="btn-group">
                <?= Html::button(
                    '<i class="bi bi-plus-lg me-2"></i>Agregar nueva tarifa',
                    [
                        'type' => 'button',
                        'class' => 'btn btn-success',
                        'id' => 'btn-agregar-tarifa-calendario',
                        'data-id-cabana' => (int) $selectedCabanaId,
                    ]
                ) ?>
            </div>

            <div class="btn-group">
                <?= Html::a(
                    '<i class="bi bi-chevron-left"></i>',
                    array_merge(['calendario', 'year' => $prevStart->format('Y'), 'month' => $prevStart->format('m')], $navParams),
                    ['class' => 'btn btn-outline-secondary', 'title' => 'Mes anterior']
                ) ?>
                <?= Html::a(
                    '<i class="bi bi-house-door"></i>',
                    array_merge(['calendario', 'year' => $currentStart->format('Y'), 'month' => $currentStart->format('m')], $navParams),
                    ['class' => 'btn btn-outline-secondary', 'title' => 'Volver al mes actual']
                ) ?>
                <?= Html::a(
                    '<i class="bi bi-chevron-right"></i>',
                    array_merge(['calendario', 'year' => $nextStart->format('Y'), 'month' => $nextStart->format('m')], $navParams),
                    ['class' => 'btn btn-outline-secondary', 'title' => 'Mes siguiente']
                ) ?>
            </div>
        </div>

        <?php if (empty($tarifaItems)): ?>
            <div class="alert alert-warning">
                No hay tarifas asociadas a la cabaña seleccionada dentro del rango mostrado.
            </div>
        <?php endif; ?>

        <!-- Los 2 meses -->
        <div class="row">
            <div class="col-md-6">
                <?= CalendarHelper::renderTarifaMonth($start1, $tarifaItems) ?>
            </div>
            <div class="col-md-6">
                <?= CalendarHelper::renderTarifaMonth($start2, $tarifaItems) ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
Modal::begin([
    'id' => 'modal-detalle-tarifa',
    'title' => 'Editar tarifa',
    'size' => Modal::SIZE_LARGE,
]);

echo '<div id="modal-detalle-tarifa-body"></div>';

Modal::end();

$tarifasUrl = Url::to(['tarifas-por-cabana']);
$tarifasUrlJs = Json::htmlEncode($tarifasUrl);
$editarTarifaBaseUrlJs = Json::htmlEncode(Url::to(['editar-calendario-tarifa']));
$crearTarifaBaseUrlJs = Json::htmlEncode(Url::to(['crear-calendario-tarifa']));
$selectedTarifaIdJs = Json::htmlEncode((string) ($selectedTarifaId ?? ''));

$js = <<<JS
(function() {
    var form = document.getElementById('filtros-tarifa-calendario');
    var cabanaSelect = document.getElementById('filtro-id-cabana');
    var tarifaSelect = document.getElementById('filtro-id-tarifa');
    var tarifasUrl = {$tarifasUrlJs};
    var editarTarifaBaseUrl = {$editarTarifaBaseUrlJs};
    var crearTarifaBaseUrl = {$crearTarifaBaseUrlJs};
    var calendarUrl = {$calendarUrlJs};
    var selectedTarifaId = {$selectedTarifaIdJs};

    if (!form || !cabanaSelect || !tarifaSelect) {
        return;
    }

    function resetTarifas(placeholder) {
        tarifaSelect.innerHTML = '';
        var option = document.createElement('option');
        option.value = '';
        option.textContent = placeholder;
        tarifaSelect.appendChild(option);
        tarifaSelect.value = '';
        tarifaSelect.disabled = true;
    }

    function goToCalendar() {
        var params = new URLSearchParams();
        params.set('year', form.querySelector('input[name="year"]').value);
        params.set('month', form.querySelector('input[name="month"]').value);

        if (cabanaSelect.value) {
            params.set('id_cabana', cabanaSelect.value);
        }

        if (tarifaSelect.value) {
            params.set('id_tarifa', tarifaSelect.value);
        }

        window.location.href = calendarUrl + (calendarUrl.indexOf('?') === -1 ? '?' : '&') + params.toString();
    }

    function loadTarifas(cabanaId, selectedId) {
        if (!cabanaId) {
            resetTarifas('Seleccione una tarifa');
            return;
        }

        resetTarifas('Cargando tarifas...');

        $.getJSON(tarifasUrl, {id_cabana: cabanaId})
            .done(function(response) {
                resetTarifas('Seleccione una tarifa');

                if (!response || !Array.isArray(response.results) || response.results.length === 0) {
                    return;
                }

                response.results.forEach(function(item) {
                    var option = document.createElement('option');
                    option.value = item.id;
                    option.textContent = item.text;
                    tarifaSelect.appendChild(option);
                });

                tarifaSelect.disabled = false;

                if (selectedId) {
                    tarifaSelect.value = String(selectedId);
                }
            })
            .fail(function() {
                resetTarifas('No se pudieron cargar las tarifas');
            });
    }

    function openTarifaModal(tarifaId) {
        var url = editarTarifaBaseUrl + (editarTarifaBaseUrl.indexOf('?') === -1 ? '?' : '&') + 'id=' + encodeURIComponent(tarifaId);

        $('#modal-detalle-tarifa-body').html('<div class="text-center py-4 text-muted">Cargando...</div>');
        $('#modal-detalle-tarifa').modal('show');

        $.get(url)
            .done(function(html) {
                $('#modal-detalle-tarifa-body').html(html);
            })
            .fail(function() {
                $('#modal-detalle-tarifa-body').html('<div class="alert alert-danger mb-0">No se pudo cargar el formulario de edición.</div>');
            });
    }

    function openCrearTarifaModal(cabanaId) {
        var url = crearTarifaBaseUrl + (crearTarifaBaseUrl.indexOf('?') === -1 ? '?' : '&') + 'id_cabana=' + encodeURIComponent(cabanaId);

        $('#modal-detalle-tarifa-body').html('<div class="text-center py-4 text-muted">Cargando...</div>');
        $('#modal-detalle-tarifa').modal('show');

        $.get(url)
            .done(function(html) {
                $('#modal-detalle-tarifa-body').html(html);
            })
            .fail(function() {
                $('#modal-detalle-tarifa-body').html('<div class="alert alert-danger mb-0">No se pudo cargar el formulario de alta.</div>');
            });
    }

    $(document).on('click', '.js-tarifa-detalle', function() {
        var button = $(this);
        openTarifaModal(button.data('tarifa-id'));
    });

    $(document).on('click', '#btn-agregar-tarifa-calendario', function() {
        var cabanaId = $(this).data('id-cabana');
        if (!cabanaId) {
            return;
        }
        openCrearTarifaModal(cabanaId);
    });

    $(document).on('submit', '#tarifa-calendario-modal-form', function(e) {
        e.preventDefault();
        var formEl = $(this);
        var action = formEl.attr('action');
        var data = formEl.serialize();

        $.post(action, data)
            .done(function(response) {
                if (response && response.success) {
                    window.location.reload();
                    return;
                }

                if (response && response.redirect) {
                    window.location.href = response.redirect;
                    return;
                }

                if (response && response.html) {
                    $('#modal-detalle-tarifa-body').html(response.html);
                    return;
                }

                $('#modal-detalle-tarifa-body').html('<div class="alert alert-danger mb-0">No se pudo guardar la tarifa.</div>');
            })
            .fail(function() {
                $('#modal-detalle-tarifa-body').html('<div class="alert alert-danger mb-0">Ocurrió un error al guardar la tarifa.</div>');
            });
    });

    cabanaSelect.addEventListener('change', function() {
        selectedTarifaId = '';
        loadTarifas(this.value, null);
        window.setTimeout(function() {
            goToCalendar();
        }, 50);
    });

    tarifaSelect.addEventListener('change', function() {
        goToCalendar();
    });

    if (cabanaSelect.value) {
        loadTarifas(cabanaSelect.value, selectedTarifaId);
    }
})();
JS;

$this->registerJs($js);

$css = <<<CSS
.calendar-table {
    table-layout: fixed;
    font-size: 0.8rem;
}

.calendar-table-tarifas {
    border-collapse: separate;
    border-spacing: 0;
}

.calendar-table-tarifas thead th {
    border-top: 1px solid var(--bs-border-color);
    border-right: 1px solid var(--bs-border-color);
    border-bottom: 1px solid var(--bs-border-color);
}

.calendar-table-tarifas thead th:first-child {
    border-left: 1px solid var(--bs-border-color);
}

.calendar-day {
    height: 88px;
    vertical-align: top;
    background-color: var(--bs-body-bg);
    border-right: 1px solid var(--bs-border-color);
    border-bottom: 1px solid var(--bs-border-color);
}

.calendar-table-tarifas .week-days-row > td:first-child {
    border-left: 1px solid var(--bs-border-color);
}

.calendar-day.empty {
    background-color: var(--bs-secondary-bg-subtle);
}

.calendar-day .day-number {
    text-align: right;
}

.calendario-mes .card-header {
    text-transform: capitalize;
}

.calendar-day-lanes {
    margin-top: 0.45rem;
    display: flex;
    flex-direction: column;
    gap: 0.22rem;
}

.calendar-lane-slot {
    min-height: 1.35rem;
}

.tarifa-badge {
    font-size: 0.7rem;
    white-space: normal;
    line-height: 1.2;
    width: 100%;
    cursor: pointer;
    border-radius: 0.4rem;
}

.tarifa-range-bar {
    display: block;
    width: 100%;
    padding: 0.18rem 0.45rem;
    font-size: 0.72rem;
    line-height: 1.2;
    border-radius: 0.45rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    cursor: pointer;
}

.tarifa-range-piece {
    display: block;
    width: calc(100% + 8px);
    margin-left: -4px;
    margin-right: -4px;
    min-height: 1.3rem;
    padding: 0.14rem 0.35rem;
    font-size: 0.72rem;
    line-height: 1.1;
    border-radius: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    cursor: pointer;
}

.tarifa-range-piece.is-start {
    width: calc(100% + 4px);
    margin-left: 0;
    border-top-left-radius: 0.45rem;
    border-bottom-left-radius: 0.45rem;
}

.tarifa-range-piece.is-end {
    width: calc(100% + 4px);
    margin-right: 0;
    border-top-right-radius: 0.45rem;
    border-bottom-right-radius: 0.45rem;
}
CSS;

$this->registerCss($css);
?>
