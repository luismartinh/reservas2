<?php
/**
 * @var \yii\web\View                 $this
 * @var \app\models\RequestReserva    $model
 * @var \app\models\RequestResponse[] $messages
 * @var \yii\base\DynamicModel        $formModel
 * @var bool                          $canDelete   (opcional, default true)
 * @var string                        $chatAction  (opcional, url del form)
 */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\helpers\Url;
use yii\helpers\Json;

// Defaults
$canDelete = isset($canDelete) ? (bool) $canDelete : true;
$chatAction = isset($chatAction)
    ? $chatAction
    : Url::to(['request-reserva/chat', 'id' => $model->id]);

// Título dinámico para el modal
$chatTitle = Yii::t('app', 'Solicitud #{id} - {denom}', [
    'id' => $model->id,
    'denom' => $model->denominacion,
]) . ' · ' .
    Yii::t('app', 'Email') . ': ' . $model->email;
?>

<style>
    .chat-wrapper {
        max-height: 500px;
        display: flex;
        flex-direction: column;
        background-color: #0b141a;
        border-radius: .5rem;
        padding: .5rem;
    }

    .chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: .25rem .5rem;
        background: url("data:image/svg+xml,%3Csvg width='80' height='80' viewBox='0 0 160 160' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23101d25' fill-opacity='0.6'%3E%3Ccircle cx='20' cy='20' r='2'/%3E%3Ccircle cx='80' cy='40' r='2'/%3E%3Ccircle cx='140' cy='20' r='2'/%3E%3Ccircle cx='40' cy='80' r='2'/%3E%3Ccircle cx='120' cy='100' r='2'/%3E%3Ccircle cx='20' cy='140' r='2'/%3E%3Ccircle cx='80' cy='120' r='2'/%3E%3Ccircle cx='140' cy='140' r='2'/%3E%3C/g%3E%3C/svg%3E") repeat;
        border-radius: .5rem;
    }

    .chat-row {
        display: flex;
        margin-bottom: 4px;
    }

    .chat-left {
        justify-content: flex-start;
    }

    .chat-right {
        justify-content: flex-end;
    }

    .chat-bubble {
        position: relative;
        max-width: 90%;
        padding: 4px 8px;
        border-radius: 10px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, .25);
        font-size: 12px;
        line-height: 1.15;
        color: #fff;
    }

    .chat-left .chat-bubble {
        background-color: #202c33;
        /* consultas (cliente) */
        border-bottom-left-radius: 2px;
    }

    .chat-right .chat-bubble {
        background-color: #005c4b;
        /* respuestas (admin) */
        border-bottom-right-radius: 2px;
    }

    .chat-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 4px;
        font-size: 11px;
        margin: 0;
        padding: 0;
    }

    .chat-meta span {
        display: inline-flex;
        gap: 4px;
        align-items: baseline;
    }

    .chat-text {
        margin: 0;
        padding: 0;
        font-size: 18px;
        line-height: 1.25;
        text-align: left;
        white-space: pre-line;
    }

    .chat-delete {
        color: #cfd8dc;
        cursor: pointer;
        font-size: 13px;
        text-decoration: none;
    }

    .chat-delete:hover {
        color: #ff6b6b;
    }

    .chat-footer {
        margin-top: 6px;
    }

    .chat-footer .chat-input-row {
        display: flex;
        gap: 6px;
        align-items: flex-center;
    }

    .chat-footer .chat-input-row textarea {
        resize: vertical;
    }
</style>

<div class="chat-wrapper">

    <div class="chat-messages" id="chat-messages-container">
        <?php if (empty($messages)): ?>
            <div class="text-muted small text-center my-3">
                <?= Yii::t('app', 'Aún no hay mensajes en esta solicitud.') ?>
            </div>
        <?php else: ?>

            <?php foreach ($messages as $msg): ?>
                <?php
                $isResponse = ((int) $msg->is_response === 1);
                $rowClass = $isResponse ? 'chat-right' : 'chat-left';
                $label = $isResponse
                    ? Yii::t('app', 'Respuesta')
                    : Yii::t('app', 'Consulta');
                $fechaFmt = Yii::$app->formatter->asDatetime($msg->fecha, 'php:d/m/Y H:i');
                $deleteUrl = Url::to(['request-reserva/eliminar-mensaje-chat', 'id' => $msg->id]);
                ?>
                <div class="chat-row <?= $rowClass ?>">
                    <div class="chat-bubble">
                        <div class="chat-meta">
                            <span>
                                <span class="chat-label"><?= Html::encode($label) ?></span>
                                <span class="chat-date"><?= Html::encode($fechaFmt) ?></span>
                            </span>

                            <?php if ($canDelete): ?>
                                <a href="javascript:void(0);" class="chat-delete" data-url="<?= Html::encode($deleteUrl) ?>"
                                    title="<?= Yii::t('app', 'Eliminar mensaje') ?>">
                                    <i class="bi bi-trash"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="chat-text">
                            <?= Html::encode($msg->response) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

        <?php endif; ?>
    </div>

    <div class="chat-footer">
        <?php $form = ActiveForm::begin([
            'id' => 'form-chat-consultas',
            'action' => $chatAction, // 👈 configurable según contexto
            'options' => [
                'data-pjax' => 0,
            ],
        ]); ?>

        <div class="chat-input-row">
            <?= $form->field($formModel, 'response', [
                'options' => ['class' => 'mb-0 flex-grow-1'],
                'template' => "{input}\n{error}",
            ])->textarea([
                        'rows' => 2,
                        'maxlength' => 500,
                        'placeholder' => Yii::t('app', 'Escriba aquí su mensaje (máx. 500 caracteres)...'),
                    ])->label(false) ?>

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-send"></i>
            </button>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>

<?php
$deleteConfirm = Yii::t('app', '¿Seguro que desea eliminar este mensaje?');
$msgErrorDel = Yii::t('app', 'No se pudo eliminar el mensaje.');
$chatTitleJs = Json::htmlEncode($chatTitle);
$canDeleteJs = $canDelete ? 'true' : 'false';

$js = <<<JS
(function() {
    var canDelete = {$canDeleteJs};

    // Setear título del modal con datos de la solicitud
    var \$modal = $('#modal-chat');
    if (\$modal.length) {
        \$modal.find('.modal-title').text($chatTitleJs);
    }

    // Scroll al final apenas se carga el chat
    var cont = document.getElementById('chat-messages-container');
    if (cont) {
        cont.scrollTop = cont.scrollHeight;
    }

    // Eliminar mensaje (solo si está permitido)
    if (canDelete) {
        $(document).off('click', '.chat-delete').on('click', '.chat-delete', function(e) {
            e.preventDefault();
            var btn = $(this);
            var url = btn.data('url');

            if (!confirm('$deleteConfirm')) {
                return;
            }

            $.post(url, function(resp) {
                if (resp && resp.success && resp.html) {
                    $('#modal-chat-content').html(resp.html);
                } else {
                    alert(resp && resp.message ? resp.message : '$msgErrorDel');
                }
            }).fail(function() {
                alert('$msgErrorDel');
            });
        });
    }
})();
JS;

$this->registerJs($js);
?>