<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%inventory_product_log}}".
 *
 * @property int $id
 * @property string $company_id
 * @property string $sync_integration_instance_id
 * @property string $sync_source_instance_id
 * @property string $log_type
 * @property string|null $integration_product_id
 * @property string $sku
 * @property string|null $inventory_id
 * @property string $created_at
 * @property string|null $updated_at
 * @property string|null $sync_log
 */
class InventoryProductLog extends \yii\db\ActiveRecord
{
    const INVENTORY_TYPE = 'Inventory';
    const PRODUCT_TYPE = 'Product';
    const LOG_TYPES = [self::INVENTORY_TYPE,self::PRODUCT_TYPE];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%inventory_product_log}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['company_id', 'sync_integration_instance_id', 'sync_source_instance_id', 'log_type', 'created_at'], 'required'],
            [['log_type', 'sync_log'], 'string'],
            ['log_type', 'in', 'range' => self::LOG_TYPES],
            [['created_at', 'updated_at', 'sku'], 'safe'],
            [['company_id', 'sync_integration_instance_id', 'sync_source_instance_id'], 'string', 'max' => 100],
            [['integration_product_id', 'sku', 'inventory_id'], 'string', 'max' => 30],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'company_id' => 'Company ID',
            'sync_integration_instance_id' => 'Sync Integration ID',
            'sync_source_instance_id' => 'Sync To Source ID',
            'log_type' => 'Log Type',
            'integration_product_id' => 'Integration Product ID',
            'sku' => 'Sku',
            'inventory_id' => 'Inventory ID',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'sync_log' => 'Sync Log',
        ];
    }

    public static function setInventoryProductLog($la_params = [])
    {
        if (!empty($la_params)) {
            $modelInventoryLog = new InventoryProductLog();
            $modelInventoryLog->company_id = $la_params['company_id'];
            $modelInventoryLog->sync_integration_instance_id = $la_params['integration_instance_id'];
            $modelInventoryLog->sync_source_instance_id = $la_params['source_instance_id'];
            $modelInventoryLog->log_type = $la_params['log_type'];

            $modelInventoryLog->integration_product_id = (isset($la_params['integration_product_id'])) ? $la_params['integration_product_id'] : '';
            $modelInventoryLog->sku = (isset($la_params['sku'])) ? $la_params['sku'] : '';
            $modelInventoryLog->inventory_id = (isset($la_params['inventory_id'])) ? $la_params['inventory_id'] : '';
            $modelInventoryLog->created_at = date("Y-m-d H:i:s");
            $modelInventoryLog->sync_log = (isset($la_params['sync_log'])) ? $la_params['sync_log'] : '';

            $modelInventoryLog->save(false);
        }
        
    }
}
