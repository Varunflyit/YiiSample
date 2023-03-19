<?php

use app\helpers\UuidHelper;
use app\helpers\Patch;
use app\models\User;

/**
 * Class p220801_124501_internal_user
 *
 * Add internal user
 */
class p220801_124501_internal_user extends Patch
{
    /**
     * @inheritdoc
     */
    public function run()
    {
        $user = User::findByEmail('bjones@wolfgroup.com.au');
        if (empty($user)) {
            $user = new User([
                'email' => 'bjones@wolfgroup.com.au',
                'name' => 'Ben Jones',            
                'role' => 'internal',
                'is_active' => true,
            ]);
        } else {
            var_dump($user->attributes);
        }
        $password = 'ecomm123321';
        $user->setPassword($password);
        $user->generateAccessToken();
        $user->generateAuthKey();
        $user->generatePasswordResetToken();            
        $user->save();
        var_dump($user->errors);
        var_dump($user->id);
    }
}
