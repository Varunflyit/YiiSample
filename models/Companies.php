<?php

namespace app\models;

use app\helpers\StringHelper;
use Yii;

/**
 * This is the model class for table "companies".
 *
 * @property string $company_id
 * @property string|null $company_name
 * @property string $referrer
 * @property string|null $note
 * @property string|null $timezone
 * @property string $created_at
 * @property string $updated_at
 * @property string $is_active
 * @property float $user_limit
 * @property float $used_user
 * @property float $sku_limit
 * @property float $used_sku
 * @property float $source_limit
 * @property float $used_source
 * @property float $integration_limit
 * @property float $used_integration
 * @property string|null $allow_beta
 * @property string $created_by
 * @property string $updated_by
 */
class Companies extends \yii\db\ActiveRecord
{
    const STATUS_DELETED = 'Ar';
    const STATUS_INACTIVE = 'N';
    const STATUS_ACTIVE = 'Y';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%companies}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['company_name', 'referrer', 'note', 'created_by', 'updated_by', 'timezone'], 'string'],
            [['company_name'], 'unique'],
            [['is_active', 'user_limit', 'sku_limit', 'source_limit', 'integration_limit'], 'required'],
            [['created_at', 'updated_at','referrer', 'created_by', 'updated_by'], 'safe'],
            [['user_limit', 'used_user', 'sku_limit', 'used_sku', 'source_limit', 'used_source', 'integration_limit', 'used_integration'], 'number'],
            [['used_user', 'used_sku', 'used_source', 'used_integration'], 'default', 'value' => 0],
            ['allow_beta', 'default', 'value' => 'N'],

            ['is_active', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE, self::STATUS_DELETED]],
            
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'company_id' => 'Company ID',
            'company_name' => 'Company Name',
            'referrer' => 'Referrer',
            'note' => 'Note',
            'timezone' => 'timezone',
            'created_at' => 'Date Added',
            'updated_at' => 'Date Update',
            'is_active' => 'Is Active',
            'user_limit' => 'User Limit',
            'used_user' => 'Used User',
            'sku_limit' => 'Sku Limit',
            'used_sku' => 'Used Sku',
            'source_limit' => 'Source Limit',
            'used_source' => 'Used Source',
            'integration_limit' => 'Integration Limit',
            'used_integration' => 'Used Integration',
            'allow_beta' => 'Allow Beta',
            'created_by' => 'Created By',
            'updated_by' => 'Updated By',
        ];
    }

    public function beforeSave($insert) {
        if ($insert) {
            do {
                $this->company_id = StringHelper::generateGuid(Yii::$app->params['guidLength']);
            } while (self::find()->where(['company_id' => $this->company_id])->exists());

            $this->created_by = \Yii::$app->user->identity->id;
            $this->created_at = date('Y-m-d H:i:s');
        } else {
            $this->updated_by = \Yii::$app->user->identity->id;
            $this->updated_at = date('Y-m-d H:i:s');
        }
        return parent::beforeSave($insert);
    }


    public function getrestrictedToIntegrations() {
        return $this->hasMany(CompanyIntegrationRestrictedTo::className(), ['company_id' => 'company_id']);
    }

    public function getrestrictedToSources() {
        return $this->hasMany(CompanySourceRestrictedTo::className(), ['company_id' => 'company_id']);
    }
    public function del() {
        $this->active_status = self::STATUS_DELETED;
        return $this->save();
    }
    public static function formatCompanySingleData($value){
        $la_limitData = [];

        $la_userLimitRes = (object)[
            'limit'=>$value['user_limit'], 
            'used'=>$value['used_user']
        ];
        $la_sourcesLimitRes = (object)[
            'limit'=>$value['source_limit'], 
            'used'=>$value['used_source']
        ];
        $la_integrationsLimitRes = (object)[
            'limit'=>$value['integration_limit'], 
            'used'=>$value['used_integration']
        ];
        $la_skusLimitRes = (object)[
            'limit'=>$value['sku_limit'], 
            'used'=>$value['used_sku']
        ];
        
        array_push($la_limitData, ["users" => $la_userLimitRes]);
        array_push($la_limitData, ["sources" => $la_sourcesLimitRes]);
        array_push($la_limitData, ["integrations" => $la_integrationsLimitRes]);
        array_push($la_limitData, ["skus" => $la_skusLimitRes]);
        
        $la_res = array(
            'company_id'=>$value['company_id'],
            'company_name'=>$value['company_name'], 
            'note'=>$value['note'], 
            'timezone'=>$value['timezone'], 
            'referrer'=>$value['referrer'], 
            'is_active'=>$value['is_active'],
            'allow_beta'=>$value['allow_beta'],
            'user_used'=>$value['used_user'],
            'user_limit'=>$value['user_limit'],
            'source_used'=>$value['used_source'],
            'source_limit'=>$value['source_limit'],
            'integration_used'=>$value['used_integration'],
            'integration_limit'=>$value['integration_limit'],
            'sku_used'=>$value['used_sku'],
            'sku_limit'=>$value['sku_limit'],
            // 'limits'=>$la_limitData
        );

        if(array_key_exists("restrictedToSources", $value)){
            $la_sourceData = [
                'restricted_to_sources' => array_column($value['restrictedToSources'] , 'source_id'),
            ];
            $la_res = array_merge($la_res,$la_sourceData);
        }

        if(array_key_exists("restrictedToIntegrations", $value)){
            $la_integrationData = [
                'restricted_to_integrations' => array_column($value['restrictedToIntegrations'] , 'integration_id'),
            ];
            $la_res = array_merge($la_res,$la_integrationData);
        }
        return $la_res;
    }

    public static function getCompanies($la_params)
    {
        $la_result = [];
        $li_pageNumber = isset($la_params["page"]) ? $la_params["page"] : 0;
        $li_item_per_page = isset($la_params['size']) ?  $la_params['size'] : Yii::$app->params['pageSize'];
      
        $li_totalRecord = self::getCompnaniesWithRestricSourceAndIntegation($la_params,$lb_countArray = true);
        
        $li_totalPages = ceil($li_totalRecord / $li_item_per_page);
        $li_offset = $li_pageNumber * $li_item_per_page;
        $la_params['limit'] = $li_item_per_page;
        $la_params['offset'] = $li_offset;
        $la_companyList = self::getCompnaniesWithRestricSourceAndIntegation($la_params);

        $la_result['companies'] = $la_companyList;
        $la_result['result_info']['total_records'] = intval($li_totalRecord);
        $la_result['result_info']['total_pages'] = intval($li_totalPages);
        $la_result['result_info']['page_number'] = intval($li_pageNumber);
        $la_result['result_info']['item_per_page'] = intval($la_params['limit']);

        return $la_result;        
    }

    public static function getCompnaniesWithRestricSourceAndIntegation($la_params, $lb_countArray = false)
    {
        $la_result = [];
        $la_companies = self::find();

        if (isset($la_params['search']) && $la_params['search'] != '') {
            $la_companies->where(['LIKE', 'companies.company_name', $la_params['search']]);
            $la_companies->orWhere(['LIKE', 'companies.company_id', $la_params['search']]);
        } 

        if (isset($la_params['limit']) && $la_params['limit'] > 0) {
            $la_companies->limit($la_params['limit']);
            if (isset($la_params['offset']) && $la_params['offset'] >= 0) {
                $la_companies->offset($la_params['offset']);
            }
        }

        if (isset($la_params['sort']) && $la_params['sort'] !== '') {
            if (isset($la_params['order']) && $la_params['order'] !== '') {
                $order = $la_params['order'];
            } else {
                $order = 'ASC';
            }
            $la_companies->orderBy([
                $la_params['sort']  => $order
            ]);
        }

        if ($lb_countArray) {
            $li_userCount = $la_companies->count();
            return $li_userCount;
        }

        $la_companies = $la_companies->joinWith(['restrictedToIntegrations', 'restrictedToSources'])
            ->distinct()
            ->asArray()
            ->all(); 

        foreach ($la_companies as $la_companies_key => $la_companies_value) {
            $la_formatedCompanySingleData = self::formatCompanySingleData($la_companies_value);
            array_push($la_result, $la_formatedCompanySingleData);
        }

        return $la_result;
    }

}
