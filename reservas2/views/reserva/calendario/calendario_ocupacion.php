<?php
/** @var yii\web\View $this */
/** @var \DateTimeImmutable $start1 */
/** @var \DateTimeImmutable $start2 */
/** @var array $calendarData */
/** @var \app\models\Cabana[] $cabanas */
/** @var array $cabanaColors */
/** @var array $selectedCabanas */
/** @var int|null $selectedLocadorId */
/** @var string $selectedLocadorText */
/** @var string $codigo_reserva */



use yii\bootstrap5\Html;
use yii\bootstrap5\Modal;
use yii\helpers\Json;
use yii\helpers\Url;
use app\helpers\CalendarHelper;

$this->title = Yii::t('app', 'Calendario de ocupación');
$this->params['breadcrumbs'][] = $this->title;

// Navegación prev/next
$prevStart = $start1->modify('-1 month');
$nextStart = $start1->modify('+1 month');
?>

<div class="reserva-calendario-ocupacion">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-calendar3-week me-2"></i>
                <?= Html::encode($this->title) ?>
            </h1>
            <small class="text-muted">
                <?= Yii::t('app', 'Visualice la ocupación de las cabañas por día y acceda al detalle de cada reserva.') ?>
            </small>
        </div>

        <div class="btn-group">
            <?= Html::a(
                '<i class="bi bi-chevron-left"></i>',
                ['calendario-ocupacion', 'year' => $prevStart->format('Y'), 'month' => $prevStart->format('m')],
                ['class' => 'btn btn-outline-secondary', 'title' => Yii::t('app', 'Mes anterior')]
            ) ?>
            <?= Html::a(
                '<i class="bi bi-house-door"></i>',
                ['calendario-ocupacion'],
                ['class' => 'btn btn-outline-secondary', 'title' => Yii::t('app', 'Volver al mes actual')]
            ) ?>
            <?= Html::a(
                '<i class="bi bi-chevron-right"></i>',
                ['calendario-ocupacion', 'year' => $nextStart->format('Y'), 'month' => $nextStart->format('m')],
                ['class' => 'btn btn-outline-secondary', 'title' => Yii::t('app', 'Mes siguiente')]
            ) ?>
        </div>
    </div>

    <!-- Filtros -->
    <?= $this->render('_filtros_calendario', [
        'cabanas' => $cabanas,
        'selectedCabanas' => $selectedCabanas,
        'start1' => $start1,
        'actionRoute' => ['calendario-ocupacion'],
        'selectedLocadorId' => $selectedLocadorId ?? null,
        'selectedLocadorText' => $selectedLocadorText ?? '',
        'codigo_reserva' =>$codigo_reserva ?? null,
    ]) ?>

    <!-- Leyenda de colores -->
    <?= $this->render('_leyenda_cabanas', [
        'cabanas' => $cabanas,
        'cabanaColors' => $cabanaColors,
    ]) ?>

    <!-- Los 2 meses -->
    <div class="row">
        <div class="col-md-6">
            <?= CalendarHelper::renderMonth($start1, $calendarData) ?>
        </div>
        <div class="col-md-6">
            <?= CalendarHelper::renderMonth($start2, $calendarData) ?>
        </div>
    </div>
</div>

<?php
// Modal para seguimiento
Modal::begin([
    'id' => 'modal-seguimiento',
    'title' => '<i class="bi bi-card-checklist me-2"></i>' . Yii::t('app', 'Seguimiento de reserva'),
    'size' => Modal::SIZE_EXTRA_LARGE,
]);

echo '<div id="modal-seguimiento-body" style="min-height:400px;"></div>';

Modal::end();

// URL base de seguimiento (sin hash)
$seguimientoBaseUrl = Url::to(['disponibilidad/seguimiento']);
$seguimientoBaseUrlJs = Json::htmlEncode($seguimientoBaseUrl);

$js = <<<JS
(function() {
    var baseUrl = {$seguimientoBaseUrlJs};

    $(document).on('click', '.reserva-link', function(e) {
        e.preventDefault();
        var hash = $(this).data('hash');
        
        if (!hash) {
            alert('No se encontró el seguimiento para esta reserva.');
            return;
        }

        var url = baseUrl + '&hash=' + encodeURIComponent(hash);

        var iframeHtml =
            '<div class="ratio ratio-16x9">' +
                '<iframe src="' + url + '" class="w-100 h-100 border-0" loading="lazy"></iframe>' +
            '</div>';

        $('#modal-seguimiento-body').html(iframeHtml);
        $('#modal-seguimiento').modal('show');
    });
})();
JS;

$this->registerJs($js);

$css = <<<CSS
.calendar-table {
    table-layout: fixed;
    font-size: 0.8rem;
}

.calendar-day {
    height: 110px;
    vertical-align: top;
    background-color: var(--bs-body-bg);
}

.calendar-day.empty {
    background-color: var(--bs-secondary-bg-subtle);
}

.calendar-day .day-number {
    text-align: right;
}

.reservas-list .badge {
    font-size: 0.7rem;
    white-space: nowrap;
}

/* Glow especial para reservas con estado 'confirmado-verificar-pago' */
.reserva-pendiente-pago {
    box-shadow: 0 0 8px 2px rgba(255,255,0,0.7);
    border: 2px solid #fff;
}

.calendario-mes .card-header {
    text-transform: capitalize;
}
CSS;

$this->registerCss($css);
?>