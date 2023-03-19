<?php

namespace app\models;

use Yii;
use yii\base\Model;

/**
 * LoginForm is the model behind the login form.
 *
 * @property User|null $user This property is read-only.
 *
 */
class LoginForm extends Model
{
    public $email;
    public $password;
    public $rememberMe = true;

    private $_user = false;
    Const EXPIRE_TIME = 86400; //token expiration time, 1 day valid

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            // username and password are both required
            [['email', 'password'], 'required'],
            // rememberMe must be a boolean value
            ['rememberMe', 'boolean'],
            // password is validated by validatePassword()
            ['password', 'validatePassword'],
        ];
    }

    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     *
     * @param string $attribute the attribute currently being validated
     * @param array $params the additional name-value pairs given in the rule
     */
    public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();

            if (!$user || !$user->validatePassword($this->password)) {
                $this->addError($attribute, \Yii::t('app', 'invalid_credentials'));
            }
        }
    }

    /**
     * Logs in a user using the provided username and password.
     * @return bool whether the user is logged in successfully
     */
    public function login()
    {
        if ($this->validate()) {
            if ($user = $this->getUser()) {
                $tokenModel = new UserToken();
                $tokenModel->user_id = $user->id;
                $tokenModel->created_at = time();
                $tokenModel->expire_at = $tokenModel->created_at + static::EXPIRE_TIME;
                $tokenModel->token_value = $user->generateAccessToken();
                
                if (!$tokenModel->save()) {
                    $this->addError('username', \Yii::t('app', 'cannot_genrate_access_token'));
                }

                return $user->userLoginData($tokenModel);
            } else {
                $this->addError('username', \Yii::t('app', 'user_not_found_inactive'));
            }
        }

        return;
    }
    

    /**
     * Finds user by [[username]]
     *
     * @return User|null
     */
    public function getUser()
    {
        if ($this->_user === false) {
            $this->_user = User::findByEmail($this->email);
        }

        return $this->_user;
    }
}
