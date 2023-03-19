<?php

namespace app\modules\v1\controllers;


use yii;
use yii\helpers\ArrayHelper;
use \app\common\components\Utility;
use app\models\Companies;
use \app\models\User;
use \app\models\IntegrationsInstancesSyncOptions;
use \app\models\IntegrationsInstances;
use \app\models\SourceInstances;
use yii\db\Query;
use \app\models\forms\ChangePassword;

class AdminController extends \yii\web\Controller
{
    public function behaviors() {
        $this->enableCsrfValidation = false; 
        $behaviors = ArrayHelper::merge(parent::behaviors(), Utility::getCommonBehaviors());
        $behaviors['authenticator'] = [
            'class' => \app\common\components\Auth::className(),
        ];

        $behaviors['access'] = [
            'class' => \app\common\components\Access::className(),
            'rules' => [
                [
                    'allow' => true,
                    'actions' => [
                        'create-user',
                        'get-users',
                        'change-password',
                        'update-user',
                        'view-source-integration-syncoption'
                    ],
                    'roles' => ['admin'],
                ]
            ]
        ];

        return $behaviors;
    }

    public function actionCreateUser() {
        $la_result = [];
        $la_params = Yii::$app->getRequest()->getBodyParams();
        $la_params = Utility::filterInputArray($la_params);

        $la_result = User::createUserWithRole($la_params);
        return $la_result;
    }

    public function actionGetUsers() {
        $la_result = [];
        $la_params = Yii::$app->getRequest()->getQueryParams();
        $la_params = Utility::filterInputArray($la_params);

        $la_result = User::getUserList($la_params);

        return Utility::responseSuccess($la_result);
    }

    public function actionChangePassword($id)
    {
        $la_result = [];
        $la_params = Yii::$app->getRequest()->getBodyParams();
        $la_params = Utility::filterInputArray($la_params);

        $model = new ChangePassword();
        $model->user_id = $id;

        if ($model->load(Yii::$app->getRequest()->getBodyParams(), '') && $la_result = $model->changePassword()) {
            return Utility::responseSuccess($la_result);
        } else {
            return Utility::responseError($model->getFirstErrors(),'',400);
        }
    }

    public function actionUpdateUser($id) {
        $la_result = [];
        $la_params = Yii::$app->getRequest()->getBodyParams();
        $la_params = Utility::filterInputArray($la_params);

        $la_result = User::updateUser($la_params,$id);

        return $la_result;
    }

    public function actionViewSourceIntegrationSyncoption($id)
    {
        $la_result = [];

        $la_syncOption = IntegrationsInstancesSyncOptions::getoptionsById($id);
        
        if ($la_syncOption == "FALSE") {
            return Utility::responseError(
                [
                    'code'    => 'sync_not_found',
                    'message' => \Yii::t('app', 'sync_not_found'),
                ],'',402);
        } else {
            $la_integration = IntegrationsInstances::getIntegrationsInstances($la_syncOption['integration_instance_id']);
            $la_source = SourceInstances::getSourceInstanceDataById($la_integration['source_instance_id']);

            unset($la_integration['source_instance_id']);
            unset($la_syncOption['integration_instance_id']);
            $la_result['source'] = $la_source;
            $la_result['integration'] = $la_integration;
            $la_result['sync_options'] = $la_syncOption;
        }
        
        return Utility::responseSuccess($la_result);
    }


}
