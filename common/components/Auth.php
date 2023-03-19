<?php
namespace app\common\components;

use \yii\filters\auth\HttpBearerAuth;
use app\common\components\Utility;

class Auth extends HttpBearerAuth
{
    /**
     * @inheritdoc
     */
    public function handleFailure($response)
    {
      	$response = \Yii::$app->response;
		$response->format = \yii\web\Response::FORMAT_JSON;
		$error = Utility::responseError(
                        [
                            'code'    => 'unauthorized',
                            'message' => \Yii::t('app', 'unauthorized'),
                        ], 
                        '', 
                        401
                 );
		$response->data = $error;
    }
}