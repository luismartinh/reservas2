<?php
 
namespace app\models;
 
use app\helpers\Utils;
use Yii;
use yii\base\Model;
 
/**
 * Password reset request form
 */
class PasswordResetRequestForm extends Model
{
    public $email;
 
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['email', 'trim'],
            ['email', 'required'],
            ['email', 'email'],
            ['email', 'exist',
                'targetClass' => '\app\models\Usuario',
                'filter' => ['activo'=> "1"], 
                'message' =>Yii::t('app','no hay usuario con ese email') 
            ],
        ];
    }
 
    /**
     * Sends an email with a link, for resetting the password.
     *
     * @return bool whether the email was send
     */
    public function sendEmail()
    {
        /* @var $user User */
        $user = Identificador::findOne([
            'activo'=> "1",
            'email' => $this->email,
        ]);
 
        if (!$user) {
            return false;
        }
 
        if (!Identificador::isPasswordResetTokenValid($user->password_reset_token)) {
            $user->generatePasswordResetToken();
            if (!$user->save()) {
                return false;
            }
        }
        

       Yii::$app->mailer->compose('@app/views/mails/passwordResetToken', ['user' => $user])
       ->setFrom(Yii::$app->params['adminEmail'])
       ->setTo($this->email)
       ->setSubject('Password reset')
       ->send();
   
        return true;
    }
 
}
?>