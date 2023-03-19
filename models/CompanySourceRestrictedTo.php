<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%company_source_restricted_to}}".
 *
 * @property int $company_source_restricted_to_id
 * @property string|null $source_id
 * @property string|null $company_id
 */
class CompanySourceRestrictedTo extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%company_source_restricted_to}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['source_id', 'company_id'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'company_source_restricted_to_id' => 'Company Source Restricted To ID',
            'source_id' => 'Source ID',
            'company_id' => 'Company ID',
        ];
    }

    public static function deleteSourceRestrictedCompany($id){
        \Yii::$app
            ->db
            ->createCommand()
            ->delete('company_source_restricted_to', ['source_id' => $id])
            ->execute();
    }

    public static function deleteSourceRestrictedCompanyByCompanyId($id){
        \Yii::$app
            ->db
            ->createCommand()
            ->delete('company_source_restricted_to', ['company_id' => $id])
            ->execute();
    }
}
