<?php

namespace app\models;

use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use \yii\web\UnauthorizedHttpException;
use \app\common\components\Utility;

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
class OrderLines extends \yii\db\ActiveRecord
{

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%order_lines}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['order_id', 'sku', 'quantiy', 'price_ex_tax'], 'required'],
            [['order_id', 'sku', 'quantiy', 'price_ex_tax'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
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
}
