<?php

namespace app\modules\v1\controllers;

use Yii;
use app\common\components\Utility;
use app\models\LoginForm;
use app\models\User;
use app\models\UserToken;
use app\models\forms\ChangeOwnPassword;
use yii\helpers\ArrayHelper;
use yii\web\UnauthorizedHttpException;

class AuthController extends \yii\web\Controller
{

    // public $modelClass = 'app\models\User';

    public function behaviors()
    {
        $this->enableCsrfValidation = false;
        $behaviors = ArrayHelper::merge(parent::behaviors(), Utility::getCommonBehaviors());
        $behaviors['authenticator'] = [
            'class' => \app\common\components\Auth::className(),
            'except' => ['login', 'options', 'get-user-details-by-token', 'auth-maropost']
        ];

        return $behaviors;
    }

    public function actionOptions()
    {
        return Utility::responseSuccess();
    }

    /**
     * landing
     * @return array
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function actionTest()
    {
        return Utility::responseSuccess(Yii::$app->user->identity);
    }

    /**
     * Login
     */
    public function actionLogin()
    {
        $model = new LoginForm();
        if ($model->load(Yii::$app->getRequest()->getBodyParams(), '') && $loginUser = $model->login()) {
            return Utility::responseSuccess($loginUser, \Yii::t('app', 'login_successful'));
        } 

        return Utility::responseError($model->getFirstErrors(), '', 200);
    }

    /**
     * Get user by token
     */
    public function actionGetUserDetailsByToken($token)
    {
        try {
            $user = User::getUserByAccessToken($token);

            return Utility::responseSuccess($user->userLoginData(UserToken::findOne(['token_value' => $token])), \Yii::t('app', 'successfully_get_user'));
        } catch (UnauthorizedHttpException $ex) {
            return Utility::responseError(
                [
                    'code'    => 'unauthorized_access',
                    'message' => $ex->getMessage(),
                ], 
                'Unauthorized access!', 
                200
            );
        } catch (\Exception $ex) {
            return Utility::responseError(
                [
                    'code'    => 'get_user_failed',
                    'message' => $ex->getMessage(),
                ], 
                'Failed to call getUserDetailsByToken!', 
                400
            );
        }
    }

    /**
     * Logout
     */
    public function actionLogout()
    {
        if (User::logout()) {
            return Utility::responseSuccess([], \Yii::t('app', 'logout_successful'));
        }

        return Utility::responseError(
            [
                'code'    => 'logout_failed',
                'message' => \Yii::t('app', 'logout_failed'),
            ], 
            'Failed to log out the user!', 
            402
        );
    }

    /**
     * Change password
     */
    public function actionChangePassword()
    {
        $result = [];
        $params = Yii::$app->getRequest()->getBodyParams();
        $params = Utility::filterInputArray($params);

        $model          = new ChangeOwnPassword();
        $model->user_id = Yii::$app->user->identity->id;
        if ($model->load(Yii::$app->getRequest()->getBodyParams(), '') && $result = $model->changePassword()) {
            return Utility::responseSuccess($result);
        }
            
        return Utility::responseError($model->getFirstErrors(), '', 400);
    }

    public function actionAuthMaropost($code = "")
    {
        if ($code)
            return Utility::responseSuccess("Code : " . $code);
        else
            return Utility::responseError(
                        [
                            'code'    => 'no_code_found',
                            'message' => \Yii::t('app', 'no_code_found'),
                        ],'',402
                    );
    }
}
