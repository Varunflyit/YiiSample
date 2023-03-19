<?php

namespace app\models;

use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use \yii\web\UnauthorizedHttpException;
use \app\common\components\Utility;
use Ramsey\Uuid\Uuid;

/**
 * This is the model class for table "{{%source_instances}}".
 *
 * @property string|null $company_id
 * @property string|null $source_instance_id
 * @property string|null $integration_instance_id
 * @property string|null $sync_status
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class SyncQueue extends \yii\db\ActiveRecord
{
    const STATUS_DELETED = 'Ar';
    const STATUS_INACTIVE = 'N';
    const STATUS_ACTIVE = 'Y';


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%sync_queue}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['company_id', 'source_instance_id', 'integration_instance_id', 'sync_status'], 'required'],
            [['company_id', 'source_instance_id', 'integration_instance_id', 'sync_status'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            ['sync_status', 'default', 'value' => self::STATUS_INACTIVE],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'company_id' => 'Company ID',
            'source_instance_id' => 'Source Instance ID',
            'integration_instance_id' => 'Integration Instance_id Status',
            'sync_status' => 'Sync Status',
        ];
    }

    public function beforeSave($insert) {
        if ($insert) {
            $this->created_at = date('Y-m-d H:i:s');
        } else {
            $this->updated_at = date('Y-m-d H:i:s');
        }
        return parent::beforeSave($insert);
    }

    public static function getAllSyncQueueData($ls_company_id, $ls_source_instance_id, $ls_integration_instance_id){
        return SyncQueue::find()
            ->where(['company_id' => $ls_company_id, 'source_instance_id' => $ls_source_instance_id, 'integration_instance_id' => $ls_integration_instance_id])
            ->asArray()
            ->all();
    }

}
