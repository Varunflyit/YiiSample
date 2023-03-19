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
class ChangePassword extends Model
{
    public $user_id;
    public $password;

    private $_user = false;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['password'], 'required'],
        ];
    }

    /**
     * change password of logged in user.
     * @return bool whether the password for the user is updated successfully
     */
    public function changePassword()
    {
        if ($this->validate()) {
            if ($user = $this->getUser()) {
                $user->setPassword($this->password);

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
