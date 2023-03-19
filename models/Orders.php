<?php

namespace app\models;

use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use \yii\web\UnauthorizedHttpException;
use \app\common\components\Utility;
use app\models\OrderLines;

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
class Orders extends \yii\db\ActiveRecord
{

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%orders}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['company_id', 'sync_integration_instance_id', 'sync_source_instance_id'], 'required'],
            [['company_id', 'sync_integration_instance_id', 'sync_source_instance_id', 'source_order_id', 'sync_log'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'company_id' => 'Company ID',
            'sync_integration_instance_id' => 'Sync From Iintegration Id',
            'sync_to_source_id' => 'Sync To Source Id',
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

    public static function createOrder($ls_source_id, $ls_company_id, $ls_source_instance_id, $integration_instance_id, $la_orderData, $la_logs){
        $model = new Orders();
        $model->company_id = $ls_company_id;
        $model->sync_integration_instance_id = $ls_source_instance_id;
        $model->sync_source_instance_id = $integration_instance_id;
        $model->source_order_id = $ls_source_id;
        $model->sync_log = json_encode($la_logs);
        if($model->save()){
            foreach($la_orderData['OrderLine'] as $orderLine){
                $modelOrderLines = new OrderLines();
                $modelOrderLines->order_id = $model->order_id;
                $modelOrderLines->sku = $orderLine['SKU'];
                $modelOrderLines->quantity = $orderLine['Quantity'];
                $modelOrderLines->price_ex_tax = $orderLine['UnitPrice'];
                $modelOrderLines->save(false);
            }
        }
        return true;
    }
}
