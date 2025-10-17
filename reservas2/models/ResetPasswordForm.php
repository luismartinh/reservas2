<?php
 
namespace app\models;
 
use Yii;
use yii\base\Model;
use yii\base\InvalidParamException;
use kartik\password\StrengthValidator;

/**
 * Password reset form
 */
class ResetPasswordForm extends Model
{
 
    public $password;
    public $repeatpassword;
    public $Login;
 
    /**
     * @var \app\models\User
     */
    private $_user;
 
    /**
     * Creates a form model given a token.
     *
     * @param string $token
     * @param array $config name-value pairs that will be used to initialize the object properties
     * @throws \yii\base\InvalidParamException if token is empty or not valid
     */
    public function __construct($token, $config = [])
    {
        if (empty($token) || !is_string($token)) {
            throw new InvalidParamException(Yii::t('app','La contraseña no puede estar vacia'));
        }
 
        $this->_user = Identificador::findByPasswordResetToken($token);
 
        if (!$this->_user) {
            throw new InvalidParamException(Yii::t('app','Password reset token erroneo'));
        }
 
        parent::__construct($config);
    }
 
    public function attributeLabels()
    {
        return [
            'password'=>"Contraseña",
            'repeatpassword'=>'Repita la contraseña',
                ];
    }
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [

            ['password', 'required'],
            ['repeatpassword', 'required'],
            [['password'], StrengthValidator::className(),
                'min'=>5,'digit'=>0,
                'lower'=>0,'upper'=>0,
                'special'=>0,  
                'userAttribute'=>'Login'
                ],
            [['repeatpassword'], 'compare', 'compareAttribute'=>'password'], 

        ];
    }
 
    /**
     * Resets password.
     *
     * @return bool if password was reset.
     */
    public function resetPassword()
    {
        $user = $this->_user;
        $user->setPassword($this->password);
        $user->removePasswordResetToken();
        return $user->save(false);
    }
 
}


