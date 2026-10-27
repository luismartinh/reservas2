<?php
namespace app\components\asistente;

interface TranscriptorInterface
{
    /** Returns only editable text, never an executable assistant command. */
    public function transcribir($identity, \yii\web\UploadedFile $audio);
}
