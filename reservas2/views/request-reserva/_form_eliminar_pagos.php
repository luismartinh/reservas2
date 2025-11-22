<?php
/**
 * @var \app\models\RequestReserva $model
 * @var array $regPagos
 * @var string|null $errorMessage
 */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$debe = (float) $model->total - (float) $model->pagado;
?>

<div class="modal-header">
    <h5 class="modal-title">
        <?= Yii::t('app', 'Eliminar pagos registrados') ?>
    </h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= Yii::t('app', 'Cerrar') ?>"></button>
</div>

<?php $form = ActiveForm::begin([
    'id' => 'form-eliminar-pagos',
    'action' => ['request-reserva/eliminar-pagos', 'id' => $model->id],
    'enableClientValidation' => false,
]); ?>

<div class="modal-body">

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger">
            <?= Html::encode($errorMessage) ?>
        </div>
    <?php endif; ?>

    <p class="text-muted">
        <?= Yii::t('app', 'Seleccione los pagos que desea eliminar. Los montos seleccionados se descontarán del total pagado.') ?>
    </p>

    <div class="mb-2 small">
        <strong><?= Yii::t('app', 'Total') ?>:</strong>
        <?= '$ ' . number_format((float) $model->total, 2, ',', '.') ?><br>
        <strong><?= Yii::t('app', 'Pagado') ?>:</strong>
        <?= '$ ' . number_format((float) $model->pagado, 2, ',', '.') ?><br>
        <strong><?= Yii::t('app', 'Saldo') ?>:</strong>
        <?= '$ ' . number_format($debe, 2, ',', '.') ?>
    </div>

    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead>
                <tr>
                    <th style="width:40px;"></th>
                    <th><?= Yii::t('app', 'Fecha') ?></th>
                    <th><?= Yii::t('app', 'Monto') ?></th>
                    <th><?= Yii::t('app', 'Notas') ?></th>
                    <th><?= Yii::t('app', 'Comprobante') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($regPagos as $idx => $pago): ?>
                    <?php
                    $fechaPago = $pago['fecha'] ?? null;
                    $montoPago = (float) ($pago['monto'] ?? 0);
                    $notasPago = $pago['notas'] ?? null;
                    $archivo = $pago['archivo'] ?? null;

                    $fechaFmt = $fechaPago
                        ? (new \DateTime($fechaPago))->format('d/m/Y H:i')
                        : '-';

                    $urlComprobante = null;
                    if (!empty($archivo) && is_string($archivo)) {
                        $urlComprobante = Yii::$app->urlManager->createUrl([
                            'disponibilidad/ver-comprobante',
                            'hash' => $model->hash,
                            'k' => $idx,
                        ]);
                    }
                    ?>
                    <tr>
                        <td>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="pagosSeleccionados[]"
                                    value="<?= (int) $idx ?>" id="pago-<?= (int) $idx ?>">
                            </div>
                        </td>
                        <td>
                            <label for="pago-<?= (int) $idx ?>" class="mb-0">
                                <?= Html::encode($fechaFmt) ?>
                            </label>
                        </td>
                        <td>
                            <label for="pago-<?= (int) $idx ?>" class="mb-0">
                                <?= '$ ' . number_format($montoPago, 2, ',', '.') ?>
                            </label>
                        </td>
                        <td>
                            <?php if ($notasPago): ?>
                                <span class="text-muted small">
                                    <?= Html::encode($notasPago) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted small">
                                    <?= Yii::t('app', 'Sin notas') ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($urlComprobante): ?>
                                <a href="javascript:void(0);" onclick="window.open('<?= Html::encode($urlComprobante) ?>',
                                       'comprobante_pago',
                                       'width=900,height=700,scrollbars=yes,resizable=yes'); return false;"
                                    class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-file-earmark"></i>
                                    <?= Yii::t('app', 'Ver') ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted small">
                                    <?= Yii::t('app', 'Sin archivo') ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

            </tbody>
        </table>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
        <?= Yii::t('app', 'Cancelar') ?>
    </button>
    <button type="submit" class="btn btn-danger">
        <i class="bi bi-trash3 me-1"></i>
        <?= Yii::t('app', 'Eliminar seleccionados') ?>
    </button>
</div>

<?php ActiveForm::end(); ?>