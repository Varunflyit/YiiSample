<?php
namespace app\common\components;

use yii\filters\AccessControl;
use app\common\components\Utility;

class Access extends AccessControl
{
    /**
     * @inheritdoc
     */
    public function denyAccess($user)
    {
      	$response = \Yii::$app->response;
		$response->format = \yii\web\Response::FORMAT_JSON;
		$error = Utility::responseError(
                        [
                            'code'    => 'unauthorized',
                            'message' => \Yii::t('app', 'unauthorized'),
                        ], 
                        '', 
                        403
                 );
		$response->data = $error;
    }
}