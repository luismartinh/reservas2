<?php

namespace app\models;

use Yii;
use yii\base\Model;

/**
 * ContactForm is the model behind the contact form.
 */
class ContactForm extends Model
{
    public $name;
    public $email;
    public $subject;
    public $body;
    public $verifyCode;


    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            // name, email, subject and body are required
            [['name', 'email', 'subject', 'body'], 'required'],
            // email has to be a valid email address
            ['email', 'email'],
            [['name', 'subject'], 'string', 'max' => 255],
            // 🔹 límite de 500 caracteres en el mensaje
            [
                'body',
                'string',
                'max' => 500,
                'tooLong' => Yii::t('app', 'El mensaje no puede superar los 500 caracteres.')
            ],            
            // verifyCode needs to be entered correctly
            ['verifyCode', 'captcha'],
        ];
    }

    /**
     * @return array customized attribute labels
     */
    public function attributeLabels()
    {
        return [
            'name'       => Yii::t('app', 'Nombre'),
            'email'      => Yii::t('app', 'Email'),
            'subject'    => Yii::t('app', 'Asunto'),
            'body'       => Yii::t('app', 'Mensaje'),
            'verifyCode' => Yii::t('app', 'Código de verificación'),
        ];
    }

    /**
     * Sends an email to the specified email address using the information collected by this model.
     * @param string $email the target email address
     * @return bool whether the model passes validation
     */
    public function contact($email)
    {
        if ($this->validate()) {
            Yii::$app->mailer->compose()
                ->setTo($email)
                ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
                ->setReplyTo([$this->email => $this->name])
                ->setSubject($this->subject)
                ->setTextBody($this->body)
                ->send();

            return true;
        }
        return false;
    }
}
