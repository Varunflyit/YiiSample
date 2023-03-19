<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%company_integration_restricted_to}}".
 *
 * @property int $company_integration_restricted_to_id
 * @property string|null $integration_id
 * @property string|null $company_id
 */
class CompanyIntegrationRestrictedTo extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%company_integration_restricted_to}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['integration_id', 'company_id'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'company_integration_restricted_to_id' => 'Company Integration Restricted To ID',
            'integration_id' => 'Integration ID',
            'company_id' => 'Company ID',
        ];
    }

    public static function deleteIntegrationRestrictedCompany($id){
        \Yii::$app
            ->db
            ->createCommand()
            ->delete('company_integration_restricted_to', ['integration_id' => $id])
            ->execute();
    }

    public static function deleteIntegrationRestrictedCompanyByCompanyId($id){
        \Yii::$app
            ->db
            ->createCommand()
            ->delete('company_integration_restricted_to', ['company_id' => $id])
            ->execute();
    }
}

