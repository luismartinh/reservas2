<?php

use yii\bootstrap5\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var \app\models\Cabana $model */
/** @var array $fotos */

$this->title = Html::encode($model->nombre ?? $model->denominacion ?? 'Cabaña');

$actionBuscar = Url::to(['disponibilidad/buscar']);
?>
<div class="container py-5">

    <!-- Migas de pan -->
    <nav aria-label="breadcrumb" class="small mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="<?= Url::to(['site/index']) ?>">
                    <?= Yii::t('app', 'Inicio') ?>
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?= $actionBuscar ?>">
                    <?= Yii::t('app', 'Cabañas') ?>
                </a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <?= Html::encode($this->title) ?>
            </li>
        </ol>
    </nav>

    <!-- Título -->
    <div class="mb-4">
        <h1 class="h2 fw-semibold mb-1">
            <?= Html::encode($this->title) ?>
        </h1>
        <p class="text-muted mb-0">
            <?= Yii::t('app', 'Cabaña ubicada en Dina Huapi, frente al lago Nahuel Huapi.') ?>
        </p>
    </div>

    <div class="row gy-4">
        <!-- GALERÍA -->
        <div class="col-lg-7">
            <?php if (!empty($fotos)): ?>
                <?php $fotoPrincipal = $fotos[0]; ?>
                <div class="mb-3">
                    <div class="ratio ratio-4x3 rounded-4 overflow-hidden shadow-sm">
                        <img src="<?= Html::encode($fotoPrincipal) ?>" alt="<?= Html::encode($this->title) ?>"
                            class="w-100 h-100" style="object-fit: cover;">
                    </div>
                </div>

                <?php if (count($fotos) > 1): ?>
                    <div class="row g-2">
                        <?php foreach (array_slice($fotos, 1) as $foto): ?>
                            <div class="col-4">
                                <div class="ratio ratio-4x3 rounded-3 overflow-hidden">
                                    <img src="<?= Html::encode($foto) ?>" alt="<?= Html::encode($this->title) ?>"
                                        class="w-100 h-100" style="object-fit: cover;">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div
                    class="ratio ratio-4x3 rounded-4 overflow-hidden bg-secondary bg-opacity-10 d-flex align-items-center justify-content-center">
                    <span class="text-muted">
                        <?= Yii::t('app', 'Próximamente fotos de la cabaña') ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>

        <!-- INFO / RESUMEN -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 mb-3">
                <div class="card-body">
                    <h2 class="h5 fw-semibold mb-3">
                        <?= Yii::t('app', 'Descripción de la cabaña') ?>
                    </h2>

                    <p class="mb-3">
                        <?= nl2br(Html::encode($model->descripcion_larga ?? $model->descripcion ?? Yii::t('app', 'Cabaña totalmente equipada para tu estadía en la Patagonia.'))) ?>
                    </p>

                    <ul class="list-unstyled small text-muted mb-0">
                        <?php if (!empty($model->max_pax)): ?>
                            <li class="mb-1">
                                <i class="bi bi-people-fill me-1 text-primary"></i>
                                <strong><?= Yii::t('app', 'Capacidad:') ?></strong>
                                <?= Yii::t('app', 'Hasta {n} personas', ['n' => $model->max_pax]) ?>
                            </li>
                        <?php endif; ?>

                        <?php if (!empty($model->dormitorios)): ?>
                            <li class="mb-1">
                                <i class="bi bi-door-open me-1 text-primary"></i>
                                <strong><?= Yii::t('app', 'Dormitorios:') ?></strong>
                                <?= Html::encode($model->dormitorios) ?>
                            </li>
                        <?php endif; ?>

                        <?php if (!empty($model->banos)): ?>
                            <li class="mb-1">
                                <i class="bi bi-droplet-half me-1 text-primary"></i>
                                <strong><?= Yii::t('app', 'Baños:') ?></strong>
                                <?= Html::encode($model->banos) ?>
                            </li>
                        <?php endif; ?>

                        <li class="mb-1">
                            <i class="bi bi-geo-alt-fill me-1 text-primary"></i>
                            <strong><?= Yii::t('app', 'Ubicación:') ?></strong>
                            <?= Yii::t('app', 'Dina Huapi, a minutos de Bariloche, frente al lago Nahuel Huapi.') ?>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <h2 class="h5 fw-semibold mb-3">
                        <?= Yii::t('app', 'Disponibilidad y reservas') ?>
                    </h2>
                    <p class="small text-muted mb-3">
                        <?= Yii::t('app', 'Las tarifas dependen de la temporada (alta, media y baja). Consultá el valor total según tus fechas en el buscador de disponibilidad.') ?>
                    </p>

                    <div class="d-grid gap-2">
                        <a href="<?= $actionBuscar ?>" class="btn btn-primary btn-lg">
                            <i class="bi bi-calendar-check me-2"></i>
                            <?= Yii::t('app', 'Ver disponibilidad') ?>
                        </a>

                        <span class="small text-muted text-center">
                            <?= Yii::t('app', 'El proceso es simple: buscás fechas, seleccionás la cabaña y enviás tu solicitud. Luego confirmás por correo y registrás el pago.') ?>
                        </span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>