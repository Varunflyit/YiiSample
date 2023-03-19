<?php

namespace app\models;

use \app\common\components\Utility;
use \yii\web\UnauthorizedHttpException;
use app\helpers\StringHelper;
use app\models\Companies;
use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * This is the model class for table "{{%source_instances}}".
 *
 * @property string $source_instance_id
 * @property string|null $company_id
 * @property string|null $source_id
 * @property string|null $active_status
 * @property string|null $connection_status
 * @property string|null $last_connection_time
 * @property string|null $created_at
 * @property string|null $created_by
 * @property string|null $updated_at
 * @property string|null $updated_by
 */
class SourceInstances extends \yii\db\ActiveRecord
{
    const STATUS_DELETED = 'Ar';
    const STATUS_INACTIVE = 'N';
    const STATUS_ACTIVE = 'Y';


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%source_instances}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'company_id', 'source_id'], 'required'],
            ['company_id', 'exist', 'targetClass' => '\app\models\Companies', 'targetAttribute' => 'company_id', 'message' => 'Company is not exist.'],
            ['company_id', 'exist', 'targetClass' => '\app\models\Companies', 'targetAttribute' => 'company_id', 'filter' => ['is_active' => 'Y'], 'message' => 'This is not an active company.'],
            ['source_id', 'exist', 'targetClass' => '\app\models\Sources', 'targetAttribute' => 'source_id', 'message' => 'Source is not exist.'],
            [['name', 'company_id', 'source_id', 'active_status', 'created_by', 'updated_by'], 'string'],
            [['created_at', 'updated_at', 'last_connection_time'], 'safe'],
            ['active_status', 'default', 'value' => self::STATUS_INACTIVE],
            ['active_status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE, self::STATUS_DELETED]],
            ['connection_status', 'default', 'value' => self::STATUS_INACTIVE],
            ['connection_status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE]],
            ['name', 'validateInstanceName'],
            ['source_id', 'validateInstance','on' => 'createSourceInstance'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'source_instance_id' => 'Source Instance ID',
            'company_id' => 'Company ID',
            'source_id' => 'Source ID',
            'active_status' => 'Active Status',
            'connection_status' => 'Connection Status',
            'last_connection_time' => 'Last Connection Time',
            'created_at' => 'Created At',
            'created_by' => 'Created By',
            'updated_at' => 'Updated At',
            'updated_by' => 'Updated By',
        ];
    }

    public function beforeSave($insert) {
        if ($insert) {
            do {
                $this->source_instance_id = StringHelper::generateGuid(Yii::$app->params['guidLength']);
            } while (self::find()->where(['source_instance_id' => $this->source_instance_id])->exists());

            $this->created_by = isset(\Yii::$app->user->identity->id) ? \Yii::$app->user->identity->id : 0;
            $this->created_at = date('Y-m-d H:i:s');
        } else {
            $this->updated_by = isset(\Yii::$app->user->identity->id) ? \Yii::$app->user->identity->id : 0;
            $this->updated_at = date('Y-m-d H:i:s');
        }
        return parent::beforeSave($insert);
    }

    public function validateInstance($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $model = self::find()->where(['company_id' => $this->company_id, 'source_id' => $this->source_id])->one();
            if ($model) {
                $this->addError($attribute, \Yii::t('app', 'source_instance_exist'));
            }
        }
    } 

    public function validateInstanceName($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $model = self::find()->where(['company_id' => $this->company_id, 'name' => $this->name]);
            if(isset($this->source_instance_id)){
                $model = $model->andWhere(['<>','source_instance_id',$this->source_instance_id]);
            }
            $model = $model->one();
            
            if ($model) {
                $this->addError($attribute, \Yii::t('app', 'source_instance_name_exist'));
            }
        }
    }

    public function getSourceAttributes()
    {
        return $this->hasMany(SourceInstancesAttributes::class, ['source_instance_id' => 'source_instance_id']);
    }

    public function getSource()
    {
        return $this->hasOne(Sources::class, ['source_id' => 'source_id']);
    }

    public function getCompany()
    {
        return $this->hasOne(Companies::class, ['company_id' => 'company_id']);
    }
    public function del() {
        $this->active_status = self::STATUS_DELETED;
        return $this->save();
    }
    public static function createSourceInstance($la_params)
    {
        $la_result = [];
        $modelSourceInstance = new SourceInstances();
        $modelSourceInstance->scenario = 'createSourceInstance';
        $modelSourceInstance->attributes = $la_params;

        if ($modelSourceInstance->save()) {
            if($modelSourceInstance->active_status=='Y'){
                $modelSource = Sources::findOne($modelSourceInstance->source_id);
                $modelSource->installed_instances = $modelSource->installed_instances + 1;
                $modelSource->update();
            }
            $modelSource = Sources::findOne($modelSourceInstance->source_id);
            $modelSource->installed_instances = $modelSource->installed_instances + 1;
            $modelSource->update();
            $la_result = $modelSourceInstance->getAttributes(['source_instance_id','source_id','company_id','name','active_status','connection_status','last_connection_time']);
            if(isset($la_params['connection']) && count($la_params['connection']) > 0){
                $la_result['connection'] = SourceInstancesAttributes::createSourceInstanceAttributes($la_params['connection'],$modelSourceInstance);
            }
            if (isset($la_params['sync_options']) && count($la_params['sync_options']) > 0) {
                $la_result['sync_options'] = SourceInstancesSyncOptions::insertSyncOptions($la_params, $modelSourceInstance->source_instance_id);
            }else{
                $la_result['sync_options'] = SourceInstancesSyncOptions::formatSyncOption($modelSourceInstance->source_instance_id);
            } 
            $modelCompany = Companies::findOne($modelSourceInstance->company_id);
            $modelCompany->used_source = $modelCompany->used_source + 1;
            $modelCompany->update();
            return Utility::responseSuccess($la_result);
        } else{
            return Utility::responseError($modelSourceInstance->getFirstErrors(),'',400);
        }

    }

    public static function updateSourceInstance($la_params,$companyId,$id)
    {
        $la_result = [];
        $modelSourceInstance = SourceInstances::findOne(['source_instance_id' => $id, 'company_id' => $companyId]);
        $oldStatus = $modelSourceInstance->active_status;
        $modelSourceInstance->attributes = $la_params;

        if ($modelSourceInstance->update()) {
            if($oldStatus!=$modelSourceInstance->active_status){
                $modelSource = Sources::findOne($modelSourceInstance->source_id);
                if($modelSourceInstance->active_status=='Y'){
                    $modelSource->installed_instances = $modelSource->installed_instances + 1;
                }else{
                    $modelSource->installed_instances = $modelSource->installed_instances - 1;
                }
              
               $modelSource->update();
            }
            $la_result = $modelSourceInstance->getAttributes(['source_instance_id','source_id','company_id','name','active_status','connection_status','last_connection_time']);

            if(isset($la_params['connection']) && count($la_params['connection']) > 0){
                $la_result['connection'] = SourceInstancesAttributes::createSourceInstanceAttributes($la_params['connection'],$modelSourceInstance);
            }else{
               $la_result['connection'] = SourceInstancesAttributes::viewSourceInstanceAttributes($modelSourceInstance->source_instance_id);
            }
            if (isset($la_params['sync_options']) && count($la_params['sync_options']) > 0) {
                SourceInstancesSyncOptions::deleteSourceInstancesSyncOptionsById($modelSourceInstance->source_instance_id);
                $la_result['sync_options'] = SourceInstancesSyncOptions::insertSyncOptions($la_params, $modelSourceInstance->source_instance_id);
            }else{
                $la_result['sync_options'] = SourceInstancesSyncOptions::formatSyncOption($modelSourceInstance->source_instance_id);
            }            
            return Utility::responseSuccess($la_result);
        } else{
            return Utility::responseError($modelSourceInstance->getFirstErrors(),'',400);
        }

    }

    public static function getAllSourcesAndInstancesByCompany($la_params)
    {
        $la_result = [['instances' => [], 'available' => []]];
        $la_sourceInstances = self::find()->joinWith(['source','sourceAttributes'])
                ->where(['company_id' => $la_params['company_id']])
                ->andWhere(['active_status' => 'Y'])
                ->asArray()->all();

        if (!empty($la_sourceInstances)) {
            $la_sourceIds = array_column($la_sourceInstances, 'source_id');

            $la_allSources = Sources::find()->select('source_id,name,icon,description,is_beta')
                    ->where(['not in','source_id', $la_sourceIds])
                    ->andWhere(['active_status' => 'Y'])
                    ->asArray()->all();
            foreach ($la_allSources as $la_allSources_key => $la_allSources_value) {
                $la_allSources[$la_allSources_key]['is_installed'] = 'N';
            }

            foreach ($la_sourceInstances as $la_sourceInstances_key => $la_sourceInstances_value) {
                $la_result[0]['instances'][$la_sourceInstances_key] = [
                    'source_instance_id' => $la_sourceInstances_value['source_instance_id'],
                    'source_id' => $la_sourceInstances_value['source_id'],
                    'name' => $la_sourceInstances_value['name'],
                    'icon' => $la_sourceInstances_value['source']['icon'],
                    'description' => $la_sourceInstances_value['source']['description'],
                    'active_status' => $la_sourceInstances_value['active_status'],
                    'is_beta' => $la_sourceInstances_value['source']['is_beta'],
                ];
                foreach ($la_sourceInstances_value['sourceAttributes'] as $la_sourceInstancesAttr_key => $la_sourceInstancesAttr_value) {
                    if ($la_sourceInstancesAttr_value['attribute_key'] == 'storeUrl') {
                        $la_result[0]['instances'][$la_sourceInstances_key]['source_install_name'] = $la_sourceInstancesAttr_value['attribute_value'];
                    }
                    
                }
            }

            $la_result[0]['available'] = $la_allSources;
            
            return Utility::responseSuccess(['sources' => $la_result]);
        } else {
            return Utility::responseError(
                [
                    'code'    => 'company_not_found',
                    'message' => \Yii::t('app', 'company_not_found'),
                ],'',402);
        }
    }

    public static function getSourceInstanceDataById($id)
    {
        $la_result = self::find()
            ->with(['sourceAttributes'])
            ->select('source_instance_id,source_id,name')
            ->where(['source_instance_id' => $id])->asArray()->one();

        foreach ($la_result['sourceAttributes'] as $la_resultAttr_key => $la_resultAttr_value) {
            $la_result[$la_resultAttr_value['attribute_key']] = $la_resultAttr_value['attribute_value'];
        }
        unset($la_result['sourceAttributes']);
        return $la_result;
    }


    public static function generateRedirectUrl($sourceInstanceId,$companyId)
    {
        $la_result = $la_params = [];
        $ls_sourceoAuthLoginToken = ''; 

        $la_source = self::find()->with(['sourceAttributes'])
            ->where(['company_id' => $companyId,'source_instance_id' => $sourceInstanceId])->asArray()->one();

        if(!empty($la_source)) {
            $la_params[] = "version=2";
            $la_params[] = "redirect_uri=".Yii::$app->params['maropost_redirect_uri'];
            $la_params[] = "response_type=code";

            $la_previousLoginResponse = json_decode($la_source['login_response'],true);

            if (empty($la_previousLoginResponse)) {
                foreach ($la_source['sourceAttributes'] as $la_source_value) {

                    if (in_array($la_source_value['attribute_key'], ['client_id','storeUrl'])) {
                        if ($la_source_value['attribute_key'] == 'storeUrl') {
                            // $la_params[] = 'store_domain='.rtrim($la_source_value['attribute_value'],"/");
                            $la_params[] = 'store_domain=wolfgroup.neto.com.au';
                        } else {
                            $la_params[] = $la_source_value['attribute_key'].'='.$la_source_value['attribute_value'];
                        }
                    }
                }
                $la_result = ['redirect_url' => 'https://api.netodev.com/oauth/v2/auth?'.implode('&', $la_params)];
            } else {
                $la_result = ['previous_login_token' => $la_previousLoginResponse['access_token']];
            }
        } else {
            $la_result = ['attributes_not_found' => \Yii::t('app', 'source_instance_attribute_not_found')];
        }
        return $la_result;
    }

    public static function generateGetTokenUrl($la_payload=[])
    {
        $la_result = $la_params = $la_timeCheckData = $la_authData = [];
        $ls_previousLoginTime = '';

        $la_source = self::find()->with(['sourceAttributes'])
            ->where([
                'company_id' => $la_payload['company_id'],
                'source_instance_id' => $la_payload['source_instance_id']
            ])->asArray()->one();

        if(!empty($la_source)) {
            $la_previousLoginResponse = json_decode($la_source['login_response'],true);

            foreach ($la_source['sourceAttributes'] as $la_source_value) {
                if (in_array($la_source_value['attribute_key'], ['client_id','client_secret'])) {
                    $la_params[$la_source_value['attribute_key']] = $la_source_value['attribute_value'];
                }
            }

            if (empty($la_previousLoginResponse)) {
                if (isset($la_payload['login_code']) && $la_payload['login_code'] != '') {
                    $la_params['code'] = $la_payload['login_code'];
                }
            } else {
                    
                // $la_authData['auth_token'] = $la_previousLoginResponse['access_token'];
                // $la_authData['api_id'] = $la_previousLoginResponse['api_id'];
                $la_params['refresh_token'] = $la_previousLoginResponse['refresh_token'];

                $la_timeCheckData['expires_in'] = $la_previousLoginResponse['expires_in'];
                
                $la_timeCheckData['last_login_time'] = ($la_source_value['updated_at'] != '') ? strtotime($la_source_value['updated_at']) : strtotime($la_source_value['created_at']);
                $ls_previousLoginTime = $la_source['login_expiry_time'];
            }
            $la_params['redirect_uri'] = Yii::$app->params['maropost_redirect_uri'];
            $la_params['grant_type'] = (isset($la_params['refresh_token'])) ? 'refresh_token' : "authorization_code";
            
            if ($la_params['grant_type'] == 'authorization_code' && !isset($la_payload['login_code'])) {
                return $la_result = ['login_code_not_found' => \Yii::t('app', 'login_code_not_found')];
            }


            if ($ls_previousLoginTime !='' && $ls_previousLoginTime <= time()) {
                // echo date("Y-m-d H:i:s",$ls_previousLoginTime)." - ". date("Y-m-d H:i:s",time()).' expired'.PHP_EOL;
                $la_result = ['redirect_url' => Yii::$app->params['maropostGetAuthTokenCurlUrl'], 'bodyparams' => $la_params];
            } else if(!array_key_exists('auth_token',$la_authData)) {
                $la_result = ['redirect_url' => Yii::$app->params['maropostGetAuthTokenCurlUrl'], 'bodyparams' => $la_params];
            } else {
                // echo date("Y-m-d H:i:s",$ls_previousLoginTime)." - ". date("Y-m-d H:i:s",time()).' working'.PHP_EOL;
                $la_result = $la_authData;
            }
        } else {
            $la_result = ['attributes_not_found' => \Yii::t('app', 'source_instance_attribute_not_found')];
        }

        return $la_result;
    }

    public static function setSourceAuthToken($li_sourceInstanceId,$response)
    {
        $modelSourceInstance = self::findOne($li_sourceInstanceId);
        $modelSourceInstance->login_response = json_encode($response);
        $modelSourceInstance->login_expiry_time = time() + $response['expires_in'];
        $modelSourceInstance->update();
    }

}
