<?php

namespace app\models;

use \app\common\components\Utility;
use app\helpers\StringHelper;
use Yii;
use yii\web\NotFoundHttpException;

/**
 * This is the model class for table "{{%integrations_instances_sync_options}}".
 *
 * @property int $integration_instance_async_option_id
 * @property string $integration_instance_id
 * @property string|null $key
 * @property string|null $name
 * @property string|null $created_at
 * @property string|null $is_active
 * @property string|null $is_activated
 * @property string|null $is_custom
 * @property string|null $created_by
 * @property string|null $updated_at
 * @property string|null $updated_by
 */
class IntegrationsInstancesSyncOptions extends \yii\db\ActiveRecord
{
    const STATUS_DELETED = 'Ar';
    const STATUS_INACTIVE = 'N';
    const STATUS_ACTIVE = 'Y';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%integrations_instances_sync_options}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['integration_instance_id'], 'required'],
            [['type', 'is_active', 'is_activated', 'is_custom'], 'string'],
            [['created_at', 'updated_at', 'attributes'], 'safe'],
            [['integration_instance_id', 'created_by', 'updated_by'], 'string', 'max' => 50],
            ['is_active', 'default', 'value' => self::STATUS_INACTIVE],
            ['is_activated', 'default', 'value' => self::STATUS_INACTIVE],
            ['is_custom', 'default', 'value' => self::STATUS_INACTIVE],

            ['is_active', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE]],
            ['is_activated', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE]],
            ['is_custom', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE]],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'integration_instance_async_option_id' => 'Integration Instance Async Option ID',
            'integration_instance_id' => 'Integration Instance ID',
            'key' => 'Key',
            'name' => 'Name',
            'created_at' => 'Created At',
            'is_active' => 'Is Active',
            'is_activated' => 'Is Activated',
            'is_custom' => 'Is Custom',
            'created_by' => 'Created By',
            'updated_at' => 'Updated At',
            'updated_by' => 'Updated By',
        ];
    }

    public function beforeSave($insert) {
        if ($insert) {
            do {
                $this->integration_instance_async_option_id = StringHelper::generateGuid(Yii::$app->params['guidLength']);
            } while (self::find()->where(['integration_instance_async_option_id' => $this->integration_instance_async_option_id])->exists());

            $this->is_custom = self::STATUS_INACTIVE;
            $this->created_by = \Yii::$app->user->identity->id;
            $this->created_at = date("Y-m-d H:i:s");
        } else {
            $this->updated_by = \Yii::$app->user->identity->id;
            $this->updated_at = date("Y-m-d H:i:s");
        }
        return parent::beforeSave($insert);
    }
    

    public static function insertSyncOptions($lo_params, $ls_integrationInstanceId){
        foreach($lo_params['sync_options'] as $la_syncOption){
            $model = new IntegrationsInstancesSyncOptions();
            $model->integration_instance_id = $ls_integrationInstanceId;
            $model->key = $la_syncOption['code'];
            $model->is_active = $la_syncOption['is_active'];
            $model->is_activated = $la_syncOption['is_activated'];
            if(array_key_exists("sub_sync_options", $la_syncOption)){
                $model->attributes = str_replace("\\", "", json_encode($la_syncOption['sub_sync_options'],JSON_HEX_QUOT));
            }
            $model->save(false);
        }
        $la_syncOptionData['integrationsInstancesSyncOptions'] = IntegrationsInstancesSyncOptions::find()
            ->where([ 'integration_instance_id' => $ls_integrationInstanceId])
            ->asArray()
            ->all();
        return $la_syncOptionData;
    }

    public static function getoptionsById($id)
    {
        $la_result = [];
        $la_result = self::find()
            ->select('integration_instance_async_option_id,integration_instance_id,key,is_active,is_activated,attributes')
            ->where(['integration_instance_async_option_id' => $id])
            ->asArray()->one();

        if (!empty($la_result)) {
            if ($la_result['attributes'] != '') {
                $la_attributes = json_decode($la_result['attributes'],true);
                $la_result['attributes'] = $la_attributes;
            } else {
                unset($la_result['attributes']);
            }
        } else {
            $la_result = 'FALSE';
        }
        
        

        return $la_result;
    }

    public static function deleteIntegrationsInstancesSyncOptionsById($id){
        \Yii::$app
            ->db
            ->createCommand()
            ->delete('{{%integrations_instances_sync_options}}', ['integration_instance_id' => $id])
            ->execute();
    }
    public static function getSyncdata($ls_key ,$ls_status)
    {
        $la_result = [];
        $la_syncOptionData = self::find()
            ->select('integration_instance_async_option_id,integration_instance_id,key,is_active,is_activated,attributes')
            ->where(['key' => $ls_key, 'is_active' => $ls_status])
            ->asArray()->all();

        if (!empty($la_syncOptionData)) {
            foreach ($la_syncOptionData as $la_syncOptionData_key => $la_syncOptionData_value) {
                if ($la_syncOptionData_value['attributes'] != '') {
                    $la_attributes = json_decode($la_syncOptionData_value['attributes'],true);
                    $la_syncOptionData_value['sync_option'] = $la_attributes;
                } else {
                    unset($la_syncOptionData_value['attributes']);
                }
                $la_syncOptionData_value['attributes'] = IntegrationsInstancesAttributes::getWoocommerceAuthToken($la_syncOptionData_value['integration_instance_id']);

                array_push($la_result,$la_syncOptionData_value);
            }
        } else {
            $la_result = 'FALSE';
        }
        return $la_result;
    }
}
