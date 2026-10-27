<?php
namespace app\components\asistente;

use Yii;
use yii\helpers\FileHelper;
use yii\web\TooManyRequestsHttpException;

/** One stable lock inode per user. flock is released by the OS even after process death. */
class Limites
{
    private $handle;
    private $path;

    public function adquirir($userId)
    {
        $dir = Yii::getAlias('@runtime/asistente-limites');
        FileHelper::createDirectory($dir, 0700);
        $this->path = $dir . '/' . hash('sha256', Yii::$app->id . ':' . $userId);
        $this->handle = @fopen($this->path . '.lock', 'c');
        if ($this->handle === false) {
            throw new \RuntimeException('limits_unavailable');
        }
        if (!flock($this->handle, LOCK_EX | LOCK_NB)) {
            $this->liberar();
            throw new TooManyRequestsHttpException('Ya hay una consulta en curso para tu usuario.');
        }
    }

    public function consumir(array $config, \DateTimeImmutable $now)
    {
        if (!is_resource($this->handle)) {
            throw new \RuntimeException('limits_unlocked');
        }
        $file = $this->path . '.json';
        $state = [];
        if (is_file($file)) {
            $state = json_decode(file_get_contents($file), true);
            if (!is_array($state)) {
                throw new \RuntimeException('limits_corrupt'); // fail closed, never reset a corrupt counter
            }
        }
        $minute = (int) floor($now->getTimestamp() / 60);
        $day = $now->format('Y-m-d');
        $minutes = ($state['minute'] ?? null) === $minute ? (int) $state['minutes'] : 0;
        $days = ($state['day'] ?? null) === $day ? (int) $state['days'] : 0;
        if ($minutes >= $config['porMinuto'] || $days >= $config['porDia']) {
            throw new TooManyRequestsHttpException('Se alcanzó el límite de consultas. Intentá más tarde.');
        }
        $data = json_encode(['minute' => $minute, 'minutes' => $minutes + 1, 'day' => $day, 'days' => $days + 1]);
        // Same directory/filesystem; replace is atomic. A leftover .tmp is safely overwritten next time.
        $tmp = $file . '.tmp';
        if (@file_put_contents($tmp, $data) !== strlen($data) || !@rename($tmp, $file)) {
            throw new \RuntimeException('limits_write_error');
        }
    }

    public function liberar()
    {
        if (is_resource($this->handle)) {
            flock($this->handle, LOCK_UN);
            fclose($this->handle);
        }
        $this->handle = null;
    }

    public function __destruct()
    {
        $this->liberar();
    }
}
