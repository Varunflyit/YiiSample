<?php

namespace app\models;

use Yii;
use yii\helpers\ArrayHelper;
use \app\common\components\Utility;
use yii\web\NotFoundHttpException;

/**
 * This is the model class for table "{{%integrations_instances_attributes}}".
 *
 * @property int $integration_instance_attribute_id
 * @property string $integration_instance_id
 * @property string|null $attribute_key
 * @property string|null $attribute_value
 * @property string|null $created_at
 * @property string|null $created_by
 * @property string|null $updated_at
 * @property string|null $updated_by
 */
class IntegrationsInstancesAttributes extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%integrations_instances_attributes}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['integration_instance_id'], 'required'],
            [['attribute_key', 'attribute_value'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['integration_instance_id', 'created_by', 'updated_by'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'integration_instance_attribute_id' => 'Integration Instance Attribute ID',
            'integration_instance_id' => 'Integration Instance ID',
            'attribute_key' => 'Attribute Key',
            'attribute_value' => 'Attribute Value',
            'created_at' => 'Created At',
            'created_by' => 'Created By',
            'updated_at' => 'Updated At',
            'updated_by' => 'Updated By',
        ];
    }

    public function beforeSave($insert) {
        if ($insert) {
            $this->created_by = isset(\Yii::$app->user->identity->id) ? \Yii::$app->user->identity->id : 0;
            $this->created_at = date("Y-m-d H:i:s");
        } else {
            $this->updated_by = isset(\Yii::$app->user->identity->id) ? \Yii::$app->user->identity->id : 0;
            $this->updated_at = date("Y-m-d H:i:s");
        }
        return parent::beforeSave($insert);
    }

    public static function insertAttributsData($lo_params, $ls_integrationInstanceId){
        $la_attributes = $lo_params['connection'];
        $la_keys = array_keys($la_attributes);
        for($i=0;$i<count($la_keys);$i++){
            $model = self::findOne(['integration_instance_id' => $ls_integrationInstanceId,'attribute_key' => $la_keys[$i]]);
            if(!$model){
                $model = new IntegrationsInstancesAttributes();
                $model->integration_instance_id = $ls_integrationInstanceId;
            }
            $model->attribute_key = $la_keys[$i];
            $model->attribute_value = $la_attributes[$la_keys[$i]];
            $model->save(false);
        }
        $la_attributesData['integrationsInstancesAttributes'] = IntegrationsInstancesAttributes::find()
            ->where([ 'integration_instance_id' => $ls_integrationInstanceId])
            ->asArray()
            ->all();
        
        return $la_attributesData;
    }

    public static function getWoocommerceAuthToken($li_integrationInstanceId)
    {
        $la_integrationInstanceAttr = self::find()->where(['integration_instance_id' => $li_integrationInstanceId])->asArray()->all();
        if (!empty($la_integrationInstanceAttr)) {
            $ls_token = $ls_storeUrl = '';
            foreach ($la_integrationInstanceAttr as $la_integrationInstanceAttr_key => $la_integrationInstanceAttr_value) {
                if ($la_integrationInstanceAttr_value['attribute_key'] == 'auth_token') {
                    
                    $ls_token = $la_integrationInstanceAttr_value['attribute_value'];
                }

                if ($la_integrationInstanceAttr_value['attribute_key'] == 'store_url') {
                    $ls_storeUrl = rtrim($la_integrationInstanceAttr_value['attribute_value'],"/");
                }
            }
            return ['auth_token' => $ls_token, 'store_url' => $ls_storeUrl];
        } else {
            return false;
        }
    }

    public static function setWoocommerceAuthToken($li_integrationInstanceAttrId,$li_integrationInstanceId,$response)
    {
        if ($li_integrationInstanceAttrId != '') {
            $modelIntegrationsInstancesAttr = self::findOne($li_integrationInstanceAttrId);
            $modelIntegrationsInstancesAttr->attribute_value = $response['token'];
            $modelIntegrationsInstancesAttr->update();
        } else {
            $modelIntegrationsInstancesAttr = new IntegrationsInstancesAttributes;
            $modelIntegrationsInstancesAttr->integration_instance_id = $li_integrationInstanceId;
            $modelIntegrationsInstancesAttr->attribute_key = 'auth_token';
            $modelIntegrationsInstancesAttr->attribute_value = $response['token'];
            $modelIntegrationsInstancesAttr->save();
        }
        return $modelIntegrationsInstancesAttr;
    }
    
    public static function deleteIntegrationsInstanceAttributesById($id){
        \Yii::$app
            ->db
            ->createCommand()
            ->delete('{{%integrations_instances_attributes}}', ['integration_instance_id' => $id])
            ->execute();
    }

}
