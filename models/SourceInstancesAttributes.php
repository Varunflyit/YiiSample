<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%source_instances_attributes}}".
 *
 * @property int $source_instance_attribute_id
 * @property string|null $source_instance_id
 * @property string|null $attribute_key
 * @property string|null $attribute_value
 * @property string|null $created_at
 * @property string|null $created_by
 * @property string|null $updated_at
 * @property string|null $updated_by
 */
class SourceInstancesAttributes extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%source_instances_attributes}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['source_instance_id', 'attribute_key', 'attribute_value'], 'required'],
            [['source_instance_id', 'attribute_key', 'attribute_value', 'created_by', 'updated_by'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'source_instance_attribute_id' => 'Source Instance Attribute ID',
            'source_instance_id' => 'Source Instance ID',
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

    public static function createSourceInstanceAttributes($la_paramsPanel,$modelSourceInstance)
    {
        $la_result = [];
        $la_paramsPanelAttr = $la_paramsPanel;
        $la_paramsPanelAttrKeys = array_keys($la_paramsPanelAttr);

        for ($i=0; $i < count($la_paramsPanelAttr); $i++) {
             $modelSourceInstanceAttr = self::findOne(['source_instance_id' => $modelSourceInstance->source_instance_id,'attribute_key' => $la_paramsPanelAttrKeys[$i]]);
            if(!$modelSourceInstanceAttr){
                $modelSourceInstanceAttr = new SourceInstancesAttributes();
                $modelSourceInstanceAttr->source_instance_id = $modelSourceInstance->source_instance_id;
            }
            $modelSourceInstanceAttr->attribute_key = $la_paramsPanelAttrKeys[$i];
            $modelSourceInstanceAttr->attribute_value = $la_paramsPanelAttr[$la_paramsPanelAttrKeys[$i]];
            $modelSourceInstanceAttr->save(false);
            $la_result = array_merge($la_result, [$la_paramsPanelAttrKeys[$i] => $la_paramsPanelAttr[$la_paramsPanelAttrKeys[$i]]]);
        }

        return self::viewSourceInstanceAttributes($modelSourceInstance->source_instance_id);
    }

    public static function viewSourceInstanceAttributes($li_sourceInstanceId)
    {
        $la_result = [];
        $la_SourceInstanceAttr = self::find()->where(['source_instance_id' => $li_sourceInstanceId])->asArray()->all();
        foreach ($la_SourceInstanceAttr as $la_SourceInstanceAttr_key => $la_SourceInstanceAttr_value) {
            $la_result[$la_SourceInstanceAttr_value['attribute_key']] = substr_replace($la_SourceInstanceAttr_value['attribute_value'], "****", -4);
        }

        return $la_result;
    }

    public static function getSourceInstanceAttributesById($id){
        $la_sourceInstanceAttr = SourceInstancesAttributes::find()->where(['source_instance_id' => $id])->asArray()->all();
        if($la_sourceInstanceAttr != null){
            $la_headerPayload = $la_headerArr = [];
            foreach ($la_sourceInstanceAttr as $la_sourceInstanceAttr_key => $la_sourceInstanceAttr_value) {
                if ($la_sourceInstanceAttr_value['attribute_key'] != 'storeUrl') {
                    $la_headerPayload[$la_sourceInstanceAttr_key] = $la_sourceInstanceAttr_value['attribute_key'].': '.$la_sourceInstanceAttr_value['attribute_value'];
                }
            }
            return $la_headerPayload;
        }
    }

    public static function deleteSourceInstanceAttributesById($id){
        \Yii::$app
            ->db
            ->createCommand()
            ->delete('{{%source_instances_attributes}}', ['source_instance_id' => $id])
            ->execute();
    }

    public static function getAttrdata()
    {
        $la_sourceInstanceAttr = self::find()->asArray()->all();

        
        if($la_sourceInstanceAttr != null){
            $la_headerPayload = $la_headerArr = [];
            $ls_sourceInstanceId = '';
            foreach ($la_sourceInstanceAttr as $la_sourceInstanceAttr_key => $la_sourceInstanceAttr_value) {
                $li_outerCounter = 0;
                $li_innerCounter = 0;

                if ($ls_sourceInstanceId == '' || $ls_sourceInstanceId == $la_sourceInstanceAttr_value['source_instance_id']) {
                    if ($la_sourceInstanceAttr_value['attribute_key'] != 'storeUrl') {
                        $la_headerPayload[$li_outerCounter][$li_innerCounter] = $la_sourceInstanceAttr_value['attribute_key'].': '.$la_sourceInstanceAttr_value['attribute_value'];
                        $li_innerCounter++;
                    }
                } else {
                    $li_outerCounter++;
                    if ($la_sourceInstanceAttr_value['attribute_key'] != 'storeUrl') {
                        $la_headerPayload[$li_outerCounter][$li_innerCounter] = $la_sourceInstanceAttr_value['attribute_key'].': '.$la_sourceInstanceAttr_value['attribute_value'];
                        $li_innerCounter++;
                    }
                }

                
            }
            return $la_headerPayload;
        }
    }
}
