<?php

namespace app\models\forms;

use Yii;
use yii\base\Model;
use \app\common\components\Utility;

/**
 * LoginForm is the model behind the login form.
 *
 * @property User|null $user This property is read-only.
 *
 */
class ChangeOwnPassword extends Model
{
    public $user_id;
    public $new_password;
    public $old_password;
    public $confirm_password;

    private $_user = false;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            
            [['old_password', 'new_password', 'confirm_password'], 'required'],

            ['confirm_password', 'compare', 'compareAttribute'=>'new_password', 'message'=>"Your new password and confirmation password do not match"], 
            // password is validated by validatePassword()
            ['old_password', 'validatePassword'],
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

            if (!$user || !$user->validatePassword($this->old_password)) {
                $this->addError($attribute, \Yii::t('app', 'old_password_incorrect'));
            }
        }
    }

    /**
     * change password of logged in user.
     * @return bool whether the password for the user is updated successfully
     */
    public function changePassword()
    {
        if ($this->validate()) {
            if ($user = $this->getUser()) {
                $user->setPassword($this->new_password);

                if ($user->save(false)) {
                    return \Yii::t('app', 'password_change_successfully');
                }
            }
        }
        
        return false;
    }

    /**
     * Finds user by [[userid]]
     *
     * @return User|null
     */
    public function getUser()
    {
        
        if ($this->_user === false) {
            $this->_user = \app\models\User::findIdentity($this->user_id);
        }

        // print_r($this->_user); die;
        return $this->_user;
    }
}
