<?php
use yii\helpers\Html;

/** @var int|null $valor */
\app\assets\CalificacionAsset::register($this);
$rating = (int) $valor;
if ($rating < 0 || $rating > 5) {
    echo Html::encode($valor);
    return;
}
$description = $rating ? $rating . ' de 5 estrellas' : 'Sin calificación';
$stars = Html::tag('span', str_repeat('★', $rating), [
    'class' => 'locador-rating-filled', 'aria-hidden' => 'true',
]) . Html::tag('span', str_repeat('☆', 5 - $rating), ['aria-hidden' => 'true']);
echo Html::tag('span', $stars, [
    'class' => 'locador-rating-readonly', 'role' => 'img',
    'aria-label' => $description, 'title' => $description,
]) . ($rating ? '' : Html::tag('span', 'Sin calificación', ['class' => 'text-muted ms-2']));
