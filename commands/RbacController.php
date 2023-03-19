<?php
namespace app\commands;

use Yii;
use yii\console\Controller;

class RbacController extends Controller
{
    public function actionInit()
    {
        $auth = Yii::$app->authManager;
        // $auth->removeAll();

        // add "createCompany" permission
        // $createCompany = $auth->createPermission('createCompany');
        // $createCompany->description = 'Create a company';
        // $auth->add($createCompany);

        // // add "updateCompany" permission
        // $updateCompany = $auth->createPermission('updateCompany');
        // $updateCompany->description = 'Update company';
        // $auth->add($updateCompany);

         // add "User" role
         $user = $auth->createRole('user');
         $auth->add($user);

        // add "masterUser" role and give this role the "viewCompany" permissions
        // as well as the permissions of the "user" role
        $masterUser = $auth->createRole('masterUser');
        $auth->add($masterUser);
        $auth->addChild($masterUser, $user);

        // add "Admin" role and give this role the "createCompany", "updateCompany" permissions
        // as well as the permissions of the "user" role
        $admin = $auth->createRole('admin');
        $auth->add($admin);
        $auth->addChild($admin, $masterUser);

        // add "Superadmin" role
        // as well as the permissions of the "admin" role
        $superAdmin = $auth->createRole('superAdmin');
        $auth->add($superAdmin);
        $auth->addChild($superAdmin, $admin);

        // $auth->assign($superAdmin, 1);
    }
}