<?php

namespace app\models;

use \app\common\components\Utility;
use app\helpers\StringHelper;
use Yii;
use yii\web\NotFoundHttpException;

/**
 * This is the model class for table "{{%sources_instances_sync_options}}".
 *
 * @property int $source_instances_async_option_id
 * @property string $source_instance_id
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
class SourceInstancesSyncOptions extends \yii\db\ActiveRecord
{
    const STATUS_DELETED = 'Ar';
    const STATUS_INACTIVE = 'N';
    const STATUS_ACTIVE = 'Y';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%source_instances_sync_options}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['source_instance_id'], 'required'],
            [['type', 'is_active', 'is_activated', 'is_custom'], 'string'],
            [['created_at', 'updated_at', 'attributes'], 'safe'],
            [['source_instance_id', 'created_by', 'updated_by'], 'string', 'max' => 50],
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
            'source_instances_async_option_id' => 'source Instance Async Option ID',
            'source_instance_id' => 'source Instance ID',
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
                $this->source_instances_async_option_id = StringHelper::generateGuid(Yii::$app->params['guidLength']);
            } while (self::find()->where(['source_instances_async_option_id' => $this->source_instances_async_option_id])->exists());

            $this->is_custom = self::STATUS_INACTIVE;
            $this->created_by = \Yii::$app->user->identity->id;
            $this->created_at = date("Y-m-d H:i:s");
        } else {
            $this->updated_by = \Yii::$app->user->identity->id;
            $this->updated_at = date("Y-m-d H:i:s");
        }
        return parent::beforeSave($insert);
    }
    

    public static function insertSyncOptions($lo_params, $sourceInstanceId){
        foreach($lo_params['sync_options'] as $syncOption){
            $model = new SourceInstancesSyncOptions();
            $model->source_instance_id = $sourceInstanceId;
            $model->key = $syncOption['code'];
            $model->is_active = $syncOption['is_active'];
            $model->is_activated = $syncOption['is_activated'];
            if(array_key_exists("sub_sync_options", $syncOption)){
                $model->attributes = str_replace("\\", "", json_encode($syncOption['sub_sync_options'],JSON_HEX_QUOT));
            }
            $model->save(false);
        }
        return SourceInstancesSyncOptions::formatSyncOption($sourceInstanceId);
    }

    public static function getoptionsById($id)
    {
        $result = [];
        $result = self::find()
            ->select('source_instances_async_option_id,source_instance_id,key,is_active,is_activated,attributes')
            ->where(['source_instances_async_option_id' => $id])
            ->asArray()->one();

        if (!empty($result)) {
            if ($result['attributes'] != '') {
                $attributes = json_decode($result['attributes'],true);
                $result['attributes'] = $attributes;
            } else {
                unset($result['attributes']);
            }
        } else {
            $result = 'FALSE';
        }
        
        

        return $result;
    }

    public static function deleteSourceInstancesSyncOptionsById($id){
        \Yii::$app
            ->db
            ->createCommand()
            ->delete('{{%source_instances_sync_options}}', ['source_instance_id' => $id])
            ->execute();
    }
    
    public static function formatSyncOption($sourceInstanceId){
        $sourceInstancesSyncOptions = SourceInstancesSyncOptions::find()
            ->where([ 'source_instance_id' => $sourceInstanceId])
            ->asArray()
            ->all();
        $syncResult = [];
        $syncSingleResult = [];
        foreach ($sourceInstancesSyncOptions as $syncOptions_key => $syncOptions_value) {
            $syncSingleResult['code'] = $syncOptions_value['key'];
            $syncSingleResult['is_active'] = $syncOptions_value['is_active'];
            $syncSingleResult['is_activated'] = $syncOptions_value['is_activated'];
            $syncSingleResult['is_custom'] = $syncOptions_value['is_custom'];
            if(array_key_exists("attributes", $syncOptions_value)){
                $syncSingleResult['sub_sync_options'] = json_decode($syncOptions_value['attributes']);
            }
            array_push($syncResult, $syncSingleResult);
        }
        return $syncResult;
    }
}
