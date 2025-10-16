<?php

namespace app\models;

use Yii;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\HttpException;



/**
 * This is the model class for table "usuario".
 */
class Identificador extends Usuario implements \yii\web\IdentityInterface
{

    /**
     * @inheritDoc
     */
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'activo' => "1"]);
    }

    /**
     * @inheritDoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        //$id = self::_getIdFromToken($token);

        //return static::findOne(['id_session' => $id, 'activo' => "1"]);

        return static::findOne(['access_token' => $token, 'activo' => "1"]);

    }

    /**
     * @inheritDoc
     */
    public function getAuthKey()
    {
        //return $this->pwd;
        return $this->auth_key;
    }

    /**
     * @inheritDoc
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @inheritDoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }


    /**
     * Finds user by username
     *
     * @param  string      $username
     * @return Identificador|null
     */
    public static function findByUsername($username)
    {
        return self::findOne(['login' => $username, 'activo' => "1"]);
    }


    /**
     * Validates password
     *
     * @param  string  $password password to validate
     * @return boolean if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
        //return $this->pwd === $password;
    }

    public function username()
    {
        //return $this->nombre.' '.$this->apellido;
        return $this->login;
    }

    public function guardarDatosSesion()
    {


        if (($u = self::findOne($this->id)) == null) {
            return false;
        }

        $u->last_login_time = new \yii\db\Expression('NOW()');
        $u->last_login_ip = Yii::$app->request->getUserIP();
        if (isset($_SESSION['__id'])) {
            $u->id_session = json_encode($_SESSION['__id']);
        }
        Yii::$app->session->set('skip afterSave', '1');

        return $u->save();


    }

    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }



    private static function _getIdFromToken($token)
    {

        try {
            $data = Yii::$app->getSecurity()->decryptByPassword(base64_decode($token), Yii::$app->params['idAPP']);
            $datas = explode("|", $data);


            if ($datas[1] != Yii::$app->getRequest()->getUserIP()) {
                return false;
            }

            //echo round(abs($to_time - $from_time) / 60,2). " minute"; public static $expire_tk_minutes=24*60;
            if (round(abs(strtotime("now") - $datas[2]) / 60, 2) > (double) (Yii::$app->params['expireSessionMin'])) {
                return false;
            }


            return $datas[0];

        } catch (\Exception $e) {
            return false;
        }

    }




    /**
     * Autorizar usuario
     *
     * @param  Identificador $user
     * @param  string $permiso
     * @param  string $descr
     * @param  Menu|null $menu
     * @return array
     */

    public static function autorizar($user, $permiso, $descr, $menu)
    {


        $acceso = Acceso::find()->where(['acceso' => $permiso])->one();
        if (!$acceso) {
            $acceso = new Acceso();
            $acceso->acceso = $permiso;
            $acceso->descr = $descr;
            if (!$acceso->save()) {
                return [
                    "auth" => false,
                    "status" => "error",
                    "error" => $acceso->getErrorSummary(true),
                    "msg" => "ERROR No se pudo crear el acceso",
                    "redirect" => ""
                ];
            }
        }


        if ($menu) {
            $menu_saved = Menu::find()->where(['menu' => $menu->menu, 'menu_path' => $menu->menu_path])->one();
            if (!$menu_saved) {
                if (!$menu->save()) {
                    return [
                        "auth" => false,
                        "status" => "error",
                        "error" => $menu->getErrorSummary(true),
                        "msg" => "ERROR No se pudo crear el menu",
                        "redirect" => ""
                    ];
                }

            } else {
                $menu = $menu_saved;
            }

            if ($acceso->id_menu != $menu->id) {
                $acceso->id_menu = $menu->id;
                if (!$acceso->save()) {
                    return [
                        "auth" => false,
                        "status" => "error",
                        "error" => $acceso->getErrorSummary(true),
                        "msg" => "ERROR No se pudo asociar el menu al acceso",
                        "redirect" => ""
                    ];
                }

            }

        }


        $accesos = $user->accesos;

        foreach ((array)$accesos as $acceso) {
            if ($acceso->acceso == $permiso) {
                return [
                    "auth" => true,
                    "status" => "ok",
                    "msg" => "Acceso OK1",
                    "redirect" => ""
                ];
            }
        }

        $grupos = $user->grupoAccesos;

        foreach ((array)$grupos as $grupo) {
            if ($grupo->tienePermiso($acceso)) {
                return [
                    "auth" => true,
                    "status" => "ok",
                    "msg" => "Acceso OK2",
                    "redirect" => ""
                ];

            }
        }

        return [
            "auth" => false,
            "status" => "ok",
            "msg" => "Acceso DENEGADO",
            "redirect" => Url::toRoute('site/login')
        ];

    }


    public static function autorizarPorNivel($user, $nivel)
    {
        if ($user->nivel <= $nivel) {
            return [
                "auth" => true,
                "status" => "ok",
                "msg" => "Acceso OK",
                "redirect" => ""
            ];
        } else {
            return [
                "auth" => false,
                "status" => "ok",
                "msg" => "Acceso DENEGADO",
                "redirect" => Url::toRoute('site/login')
            ];
        }
    }



    public static function isPasswordResetTokenValid($token)
    {
        if (empty($token)) {
            return false;
        }
        $timestamp = (int) substr($token, strrpos($token, '_') + 1);
        $expire = Yii::$app->params['user.passwordResetTokenExpire'];
        return $timestamp + $expire >= time();
    }
    public function generatePasswordResetToken()
    {
        $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    public static function findByPasswordResetToken($token)
    {
        if (!static::isPasswordResetTokenValid($token)) {
            return null;
        }
        return static::findOne([
            'password_reset_token' => $token,
            'activo' => "1"
        ]);
    }


    public function removePasswordResetToken()
    {
        $this->password_reset_token = null;
    }


}
