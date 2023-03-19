<?php

namespace app\modules\v1\controllers;

use yii;
use yii\helpers\ArrayHelper;
use \app\common\components\Utility;
use app\models\Companies;
use app\models\CompanySourceRestrictedTo;
use app\models\CompanyIntegrationRestrictedTo;
use app\models\IntegrationsInstances;
use app\models\SourceInstances;

class CompanyController extends \yii\web\Controller
{
    public function behaviors()
    {
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
                    'actions' => ['create-company','update-company','get-companies','delete-company'],
                    'roles' => ['admin'],
                ]
            ]
        ];


        return $behaviors;
    }

  
    public function actionCreateCompany()
    {
        $la_params = Yii::$app->getRequest()->getBodyParams();
        $modelCompany = new Companies();
        $modelCompany->attributes = $la_params;
        
        if ($modelCompany->save()) {
            $la_sourceData['restrictedToSources'] = [];
            $la_integrationData['restrictedToIntegrations'] = [];
            $lo_params = Yii::$app->getRequest()->getBodyParams();
            if(count($lo_params['restricted_to_sources']) > 0){
                foreach($lo_params['restricted_to_sources'] as $value){
                    $modelCompanySourceRestrictedTo = new CompanySourceRestrictedTo();
                    $modelCompanySourceRestrictedTo->source_id = $value;
                    $modelCompanySourceRestrictedTo->company_id = $modelCompany->company_id;
                    $modelCompanySourceRestrictedTo->save(false);
                    array_push($la_sourceData['restrictedToSources'], $modelCompanySourceRestrictedTo->getAttributes());
                } 
            }

            $la_margeData = array_merge($modelCompany->getAttributes(),$la_sourceData);

            if(count($lo_params['restricted_to_integrations']) > 0){
                foreach($lo_params['restricted_to_integrations'] as $value){
                    $modelCompanyIntegrationRestrictedTo = new CompanyIntegrationRestrictedTo();
                    $modelCompanyIntegrationRestrictedTo->integration_id = $value;
                    $modelCompanyIntegrationRestrictedTo->company_id = $modelCompany->company_id;
                    $modelCompanyIntegrationRestrictedTo->save(false);
                    array_push($la_integrationData['restrictedToIntegrations'], $modelCompanyIntegrationRestrictedTo->getAttributes());
                } 
            }
            $la_margeData = array_merge($la_margeData,$la_integrationData);

            $lo_data = Companies::formatCompanySingleData($la_margeData);
            return Utility::responseSuccess($lo_data);
        } else{
            return Utility::responseError($modelCompany->getFirstErrors(),'',400);
        }
    }

    public function actionUpdateCompany($id)
    {
        $la_params = Yii::$app->getRequest()->getBodyParams();

        $modelCompany = Companies::find()->where(["company_id" => $id])->one();

        $modelCompany->attributes = $la_params;
        
        if ($modelCompany->update()) {
            $la_sourceData['restrictedToSources'] = [];
            $la_integrationData['restrictedToIntegrations'] = [];
            $lo_params = Yii::$app->getRequest()->getBodyParams();
            if(isset($lo_params['restricted_to_sources'])){
                CompanySourceRestrictedTo::deleteSourceRestrictedCompanyByCompanyId($id);
                if(count($lo_params['restricted_to_sources']) > 0){
                    foreach($lo_params['restricted_to_sources'] as $value){
                        $modelCompanySourceRestrictedTo = new CompanySourceRestrictedTo();
                        $modelCompanySourceRestrictedTo->source_id = $value;
                        $modelCompanySourceRestrictedTo->company_id = $id;
                        $modelCompanySourceRestrictedTo->save(false);
                        array_push($la_sourceData['restrictedToSources'], $modelCompanySourceRestrictedTo->getAttributes());
                    } 
                }
            }
            $la_margeData = array_merge($modelCompany->getAttributes(),$la_sourceData);

            if(isset($lo_params['restricted_to_integrations'])){
                CompanyIntegrationRestrictedTo::deleteIntegrationRestrictedCompanyByCompanyId($id);
                if(count($lo_params['restricted_to_integrations']) > 0){
                    foreach($lo_params['restricted_to_integrations'] as $value){
                        $modelCompanyIntegrationRestrictedTo = new CompanyIntegrationRestrictedTo();
                        $modelCompanyIntegrationRestrictedTo->integration_id = $value;
                        $modelCompanyIntegrationRestrictedTo->company_id = $id;
                        $modelCompanyIntegrationRestrictedTo->save(false);
                        array_push($la_integrationData['restrictedToIntegrations'], $modelCompanyIntegrationRestrictedTo->getAttributes());
                    } 
                } 
            }
            if($modelCompany->is_active=='N'){
                SourceInstances::updateAll(['active_status' => 'N'], ['company_id' =>$modelCompany->company_id]);
                IntegrationsInstances::updateAll(['active_status' => 'N'], ['company_id' =>$modelCompany->company_id]);
            }
            $la_margeData = array_merge($la_margeData,$la_integrationData);
            $lo_data = Companies::formatCompanySingleData($la_margeData);
            return Utility::responseSuccess($lo_data);
        } else{
            return Utility::responseError($modelCompany->getFirstErrors(),'',400);
        }

    }

    public function actionDeleteCompany($id)
    {
        $modelCompany = Companies::findOne(["company_id" => $id]);
        if($modelCompany == null){
            return Utility::responseError(
                [
                    'code'    => 'company_not_found',
                    'message' => \Yii::t('app', 'company_not_found'),
                ], 
                '', 
                402
            );
        } 
        if($modelCompany->del()){
            SourceInstances::updateAll(['active_status' => 'AR'], ['company_id' =>$modelCompany->company_id]);
            IntegrationsInstances::updateAll(['active_status' => 'AR'], ['company_id' =>$modelCompany->company_id]);
            return Utility::responseSuccess([],\Yii::t('app', 'company_delete_successs'));
        } else{
            return Utility::responseError($modelCompany->getFirstErrors(),'',400);
        } 

    }

    public function actionGetCompanies()
    {
        $la_result = [];
        $la_params = Yii::$app->getRequest()->getQueryParams();
        $la_params = Utility::filterInputArray($la_params);
        
        $la_result = Companies::getCompanies($la_params);
        
        
        return Utility::responseSuccess($la_result);
    }

}
