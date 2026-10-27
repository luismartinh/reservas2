<?php
namespace app\models;

/** Provincia es un selector de formulario; sólo id_localidad se persiste. */
trait UbicacionTrait
{
    private $provinciaSeleccionada = false;

    public function getId_provincia()
    {
        if ($this->provinciaSeleccionada !== false) { return $this->provinciaSeleccionada; }
        if (!$this->id_localidad || !is_scalar($this->id_localidad)) { return null; }
        return Localidad::find()->select('id_provincia')->where(['id' => $this->id_localidad])->scalar() ?: null;
    }

    public function setId_provincia($value) { $this->provinciaSeleccionada = $value; }

    protected function reglasUbicacion()
    {
        return [
            [['id_localidad', 'id_provincia'], 'default', 'value' => null],
            [['id_localidad', 'id_provincia'], 'integer'],
            [['id_localidad'], 'exist', 'targetClass' => Localidad::class, 'targetAttribute' => 'id'],
            [['id_provincia'], 'exist', 'targetClass' => Provincia::class, 'targetAttribute' => 'id'],
            [['id_localidad'], 'validarUbicacion', 'skipOnEmpty' => false],
        ];
    }

    public function validarUbicacion($attribute, $params = [])
    {
        if ($this->hasErrors('id_localidad') || $this->hasErrors('id_provincia')) { return; }
        if ($this->id_provincia !== null && $this->id_provincia !== '') {
            if (!$this->id_localidad) {
                $this->addError('id_localidad', 'Seleccioná una localidad de la provincia indicada.');
            } elseif (!Localidad::find()->where(['id' => $this->id_localidad, 'id_provincia' => $this->id_provincia])->exists()) {
                $this->addError('id_localidad', 'La localidad no pertenece a la provincia indicada.');
            }
        } elseif ($this->id_localidad) {
            $this->addError('id_localidad', 'Seleccioná la provincia correspondiente a la localidad.');
        }
    }
}
