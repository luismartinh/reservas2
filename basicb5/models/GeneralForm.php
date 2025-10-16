<?php

namespace app\models;

use Yii;
use yii\base\Model;

/**
 * LoginForm is the model behind the login form.
 *
 * @property-read Identificador|null $user
 *
 */
class GeneralForm extends Model
{
    public $question;


    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [

            [['question'], 'required'],
            [['question'], 'safe'],
        ];
    }

}