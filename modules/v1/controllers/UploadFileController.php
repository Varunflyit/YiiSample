<?php

namespace app\modules\v1\controllers;
use yii;
use yii\helpers\ArrayHelper;
use \app\common\components\Utility;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class UploadFileController extends \yii\web\Controller
{
    public function behaviors()
    {
        $this->enableCsrfValidation = false; 
        $behaviors = ArrayHelper::merge(parent::behaviors(), Utility::getCommonBehaviors());
        $behaviors['authenticator'] = [
            'class' => \app\common\components\Auth::className()
        ];

        $behaviors['access'] = [
            'class' => \app\common\components\Access::className(),
            'rules' => [
                [
                    'allow' => true,
                    'actions' => ['file-upload'],
                    'roles' => ['@'],
                ]
            ]
        ];

        return $behaviors;
    }

    public function actionFileUpload(){
        $la_params = Yii::$app->getRequest()->getBodyParams();
        $res = Utility::uploadFileToAmazonServer($fileType = 'application/json', $la_params['json_form_schema_file']);
        return Utility::responseSuccess($res);
    }
}