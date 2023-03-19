<?php

namespace app\models;

use app\helpers\StringHelper;
use Ramsey\Uuid\Uuid;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "{{%integrations_instances}}".
 *
 * @property string $integration_instance_id
 * @property string $company_id
 * @property string $source_instance_id
 * @property string $integration_id
 * @property string $name
 * @property string|null $active_status
 * @property string|null $is_custom
 * @property string|null $created_at
 * @property string|null $created_by
 * @property string|null $updated_at
 * @property string|null $updated_by
 */
class IntegrationsInstances extends \yii\db\ActiveRecord
{
    const STATUS_DELETED = 'Ar';
    const STATUS_INACTIVE = 'N';
    const STATUS_ACTIVE = 'Y';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%integrations_instances}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['company_id', 'source_instance_id', 'integration_id', 'name'], 'required'],
            ['company_id', 'exist', 'targetClass' => '\app\models\Companies', 'targetAttribute' => 'company_id', 'message' => 'Company is not exist.'],
            ['company_id', 'exist', 'targetClass' => '\app\models\Companies', 'targetAttribute' => 'company_id', 'filter' => ['is_active' => 'Y'], 'message' => 'This is not an active company.'],
            ['source_instance_id', 'exist', 'targetClass' => '\app\models\SourceInstances', 'targetAttribute' => 'source_instance_id', 'message' => 'Source Instance Not Found.'],
            ['integration_id', 'exist', 'targetClass' => '\app\models\Integrations', 'targetAttribute' => 'integration_id', 'message' => 'Integration Not Found.'],
            [['active_status', 'is_custom'], 'string'],
            [['created_at', 'updated_at','last_connection_time'], 'safe'],
            [['integration_instance_id', 'company_id', 'source_instance_id', 'integration_id', 'created_by', 'updated_by'], 'string', 'max' => 50],
            [['name'], 'string', 'max' => 100],
            ['name', 'unique', 'targetAttribute' => ['name', 'company_id']],
            ['active_status', 'default', 'value' => self::STATUS_INACTIVE],
            ['connection_status', 'default', 'value' => self::STATUS_INACTIVE],
            ['connection_status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE]],
            ['active_status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE, self::STATUS_DELETED]],
        ];
    }
    

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        
        return [
            'integration_instance_id' => 'Integration Instance ID',
            'company_id' => 'Company ID',
            'source_instance_id' => 'Source Instance ID',
            'integration_id' => 'Integration ID',
            'name' => 'Name',
            'active_status' => 'Active Status',
            'is_custom' => 'Is Custom',
            'connection_status' => 'Connection Status',
            'created_at' => 'Created At',
            'created_by' => 'Created By',
            'updated_at' => 'Updated At',
            'updated_by' => 'Updated By',
        ];
    }

    public function beforeSave($insert) {
        if ($insert) {
            do {
                $this->integration_instance_id = StringHelper::generateGuid(Yii::$app->params['guidLength']);
            } while (self::find()->where(['integration_instance_id' => $this->integration_instance_id])->exists());

            $this->created_by = \Yii::$app->user->identity->id;
            $this->created_at = date("Y-m-d H:i:s");

            do {
                $this->prefix = strtoupper(StringHelper::generateGuid(3));
            } while (self::find()->where(['prefix' => $this->prefix])->exists());
        } else {
            $this->updated_by = \Yii::$app->user->identity->id;
            $this->updated_at = date("Y-m-d H:i:s");
        }
        return parent::beforeSave($insert);
    }
    public function del() {
        $this->active_status = self::STATUS_DELETED;
        return $this->save();
    }
    public function getintegrationsInstancesAttributes() {
        return $this->hasMany(IntegrationsInstancesAttributes::class, ['integration_instance_id' => 'integration_instance_id']);
    }

    public function getintegrationsInstancesSyncOptions() {
        return $this->hasMany(IntegrationsInstancesSyncOptions::class, ['integration_instance_id' => 'integration_instance_id']);
    }
    
    public function getSourceInstance() {
        return $this->hasOne(SourceInstances::class, ['source_instance_id' => 'source_instance_id']);
    }

    public static function formatIntegrationsInstancesData($la_integrationsInstancesdata){
        $la_attributesResult = [];
        foreach ($la_integrationsInstancesdata['integrationsInstancesAttributes'] as $la_integrationsAttr_key => $la_integrationsAttr_value) {
            $la_attributesResult[$la_integrationsAttr_value['attribute_key']] = substr_replace($la_integrationsAttr_value['attribute_value'],"****",-4);
        }
        $la_syncResult = [];
        $la_syncSingleResult = [];
        foreach ($la_integrationsInstancesdata['integrationsInstancesSyncOptions'] as $la_syncOptions_key => $la_syncOptions_value) {
            $la_syncSingleResult['code'] = $la_syncOptions_value['key'];
            $la_syncSingleResult['is_active'] = $la_syncOptions_value['is_active'];
            $la_syncSingleResult['is_activated'] = $la_syncOptions_value['is_activated'];
            $la_syncSingleResult['is_custom'] = $la_syncOptions_value['is_custom'];
            if(array_key_exists("attributes", $la_syncOptions_value)){
                $la_syncSingleResult['sub_sync_options'] = json_decode($la_syncOptions_value['attributes']);
            }
            array_push($la_syncResult, $la_syncSingleResult);
        }

        $la_result = array(
            "integration_instance_id" => $la_integrationsInstancesdata['integration_instance_id'],
            "company_id" => $la_integrationsInstancesdata['company_id'],
            "source_instance_id" => $la_integrationsInstancesdata['source_instance_id'],
            "integration_id" => $la_integrationsInstancesdata['integration_id'],
            "name" => $la_integrationsInstancesdata['name'],
            "active_status" => $la_integrationsInstancesdata['active_status'],
            "is_custom" => $la_integrationsInstancesdata['is_custom'],
            "last_connection_time" => $la_integrationsInstancesdata['last_connection_time'],
            "prefix" => $la_integrationsInstancesdata['prefix'],
            "connection_status" => $la_integrationsInstancesdata['connection_status'],
            "connection" => $la_attributesResult,
            "sync_options" => $la_syncResult
        );
        return $la_result;
    }

    public function getintegration() {
        return $this->hasMany(Integrations::class, ['integration_id' => 'integration_id']);
    }

    public static function formatIntegrationInstanceSingle($data) 
    {        
        /** TODO: remove if already consistent, currently, some instances has no integrations  */
        if (!isset($data['integration'][0])) return [];
        /** end here */

        $result = array(
            'instance_id' => ArrayHelper::getValue($data, 'integration_instance_id'),
            'source_instance_id' => ArrayHelper::getValue($data, 'source_instance_id'),
            'source' => ArrayHelper::getValue($data, ['sourceInstance', 'source'], []),
            'integration' => ArrayHelper::getValue($data, ['integration', 0], []),
            'name' => ArrayHelper::getValue($data, 'name'),
            'active_status' => ArrayHelper::getValue($data, 'active_status'),
            'is_custom' => ArrayHelper::getValue($data, 'is_custom'),
            'last_connection_time' => ArrayHelper::getValue($data, 'last_connection_time'),
            'connection_status' => ArrayHelper::getValue($data, 'connection_status'),
            'created_at' => ArrayHelper::getValue($data, 'created_at'),
            'updated_at' => ArrayHelper::getValue($data, 'updated_at'),
        );
        
        
        return $result;
    }
    
    public static function getIntegrationsInstances($id)
    {
        $la_result = IntegrationsInstances::find()
            ->select('integration_instance_id,source_instance_id,integration_id,name')
            ->with(['integrationsInstancesAttributes'])
            ->where(['integration_instance_id' => $id])
            ->asArray()->one();

        foreach ($la_result['integrationsInstancesAttributes'] as $la_resultAttr_key => $la_resultAttr_value) {
            $la_result[$la_resultAttr_value['attribute_key']] = $la_resultAttr_value['attribute_value'];
        }
        unset($la_result['integrationsInstancesAttributes']);

        return $la_result;
    }

    public static function formtIntegrationData($model){
        $la_result = array(
            'integration_id' => $model['integration_id'],
            'source_id' => $model['source_id'],
            'name' => $model['name'],
            'icon' => $model['icon'],
            'description' => $model['description'],
            'is_beta' => $model['is_beta'],
            // These 2 is part of integration_instance, so should not be appear here
            // 'last_connection_time' => $model['last_connection_time'],
            // 'connection_status' => $model['connection_status'],
            // end here
            'need_auth' => $model['need_auth'],
            'auth_url' => $model['auth_url'],
            'is_custom' => $model['is_custom'],
            'created_at' => $model['created_at'],
            'updated_at' => $model['updated_at'],
            'channel_platform' => $model['channel_platform']
        );
        return $la_result;
    }
}
