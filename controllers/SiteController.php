<?php

namespace app\controllers;

use app\common\components\Utility;
use yii\helpers\ArrayHelper;
use yii\web\Controller;

class SiteController extends Controller
{
    /**
     * Displays homepage.
     *
     * @return string
     */
    public function behaviors() {
        $this->enableCsrfValidation = false; 
        $behaviors = ArrayHelper::merge(parent::behaviors(), Utility::getCommonBehaviors());

        $behaviors['access'] = [
                    'class' => \app\common\components\Access::className(),
                    'rules' => [
                        [
                            'allow' => true,
                            'actions' => ['index','error']
                        ]
                    ]
                ];

        return $behaviors;
    }
    
    public function actionIndex()
    {
        return $this->redirect(APP_URL);

    }

    public function actionError()
    {
        return Utility::responseError(
            [
                'code'    => 'page_not_found',
                'message' => \Yii::t('app', 'page_not_found'),
            ], 
            'The requested page does not exist!', 
            404
        );
    }
}
