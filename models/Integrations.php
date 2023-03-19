<?php

namespace app\models;

use app\helpers\StringHelper;
use Yii;

/**
 * This is the model class for table "{{%integrations}}".
 *
 * @property string $integration_id
 * @property string $source_id
 * @property string $name
 * @property string|null $icon
 * @property string|null $description
 * @property int|null $is_custom
 * @property string|null $active_status
 * @property string $channel_platform
 * @property int|null $force_connection_test
 * @property string $created_at
 * @property string|null $created_by
 * @property string|null $updated_at
 * @property string|null $updated_by
 */
class Integrations extends \yii\db\ActiveRecord
{
    const STATUS_DELETED = 'Ar';
    const STATUS_INACTIVE = 'N';
    const STATUS_ACTIVE = 'Y';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%integrations}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['source_id', 'name'], 'required'],
            [['name'], 'unique'],
            [['installed_instances'], 'integer'],
            [['source_id', 'description', 'active_status', 'json_form_schema_file', 'form', 'form_defaults', 'auth_url', 'created_by', 'updated_by'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['name', 'icon', 'channel_platform'], 'string', 'max' => 255],
            ['active_status', 'default', 'value' => self::STATUS_INACTIVE],
            ['is_beta', 'default', 'value' => self::STATUS_ACTIVE],
            ['is_custom', 'default', 'value' => self::STATUS_ACTIVE],
            ['need_auth', 'default', 'value' => self::STATUS_INACTIVE],
            ['force_connection_test', 'default', 'value' => self::STATUS_ACTIVE],
            ['installed_instances', 'default', 'value' => 0],
            ['active_status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE, self::STATUS_DELETED]],
            ['is_beta', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE]],
            ['is_custom', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE]],
            ['force_connection_test', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE]],
            ['need_auth', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE]],

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'integration_id' => 'Integration ID',
            'name' => 'Name',
            'icon' => 'Icon',
            'description' => 'Description',
            'is_custom' => 'Is Custom',
            'visiblilty' => 'Visiblilty',
            'force_connection_test' => 'Force Test Connection',
            'active_status' => 'Active Status',
            'channel_platform' => 'Channel Platform',
            'need_auth' => 'Need Authentication',
            'auth_url' => 'Authentication URL',
            'created_at' => 'Created At',
            'created_by' => 'Created By',
            'updated_at' => 'Updated At',
            'updated_by' => 'Updated By',
        ];
    }

    public function beforeSave($insert) {
        if ($insert) {
            do {
                $this->integration_id = StringHelper::generateGuid(Yii::$app->params['guidLength']);
            } while (self::find()->where(['integration_id' => $this->integration_id])->exists());

            $this->created_by = \Yii::$app->user->identity->id;
            $this->created_at = date('Y-m-d H:i:s');
        } else {
            $this->updated_by = \Yii::$app->user->identity->id;
            $this->updated_at = date('Y-m-d H:i:s');
        }
        return parent::beforeSave($insert);
    }

    public function getSource() {
        return $this->hasOne(Sources::class, ['source_id' => 'source_id']);
    }

    public function del() {
        $this->active_status = self::STATUS_DELETED;
        return $this->save();
    }

    public function getrestrictedTocompanies() {
        return $this->hasMany(CompanyIntegrationRestrictedTo::className(), ['integration_id' => 'integration_id']);
    }

    public static function formatIntegrationSingleData($la_data_value){
        $la_integrationData = [
            'restricted_to_companies' => array_column($la_data_value['restrictedTocompanies'] , 'company_id'),
        ];
        $la_integrationData = array_merge($la_data_value,$la_integrationData);
        return $la_integrationData;
    }

    public static function getIntegrations($la_params)
    {
        $la_result = [];
        $li_pageNumber = isset($la_params["page"]) ? $la_params["page"] : 0;
        $li_item_per_page = isset($la_params['size']) ?  $la_params['size'] : Yii::$app->params['pageSize'];
      
        $li_totalRecord = self::getIntigrationWithRestrictedCompanies($la_params, $lb_countArray = true);
        
        $li_totalPages = ceil($li_totalRecord / $li_item_per_page);
        $li_offset = $li_pageNumber * $li_item_per_page;
        $la_params['limit'] = $li_item_per_page;
        $la_params['offset'] = $li_offset;
        $la_integrationList = self::getIntigrationWithRestrictedCompanies($la_params);

        $la_result['integrations'] = $la_integrationList;
        $la_result['result_info']['total_records'] = intval($li_totalRecord);
        $la_result['result_info']['total_pages'] = intval($li_totalPages);
        $la_result['result_info']['page_number'] = intval($li_pageNumber);
        $la_result['result_info']['item_per_page'] = intval($la_params['limit']);

        return $la_result;        
    }

    public static function getIntigrationWithRestrictedCompanies($la_params,$lb_countArray = false)
    {
        $la_result = [];
        $la_sources = self::find()->where(['!=','integrations.active_status', 'Ar']);

        if (isset($la_params['search']) && $la_params['search'] != '') {
            $la_sources->andWhere(['or',
                ['LIKE', 'name', $la_params['search']],
                ['LIKE', 'integrations.integration_id', $la_params['search']]
            ]);
        } 

        if (isset($la_params['limit']) && $la_params['limit'] > 0) {
            $la_sources->limit($la_params['limit']);
            if (isset($la_params['offset']) && $la_params['offset'] >= 0) {
                $la_sources->offset($la_params['offset']);
            }
        }

        if (isset($la_params['sort']) && $la_params['sort'] !== '') {
            if (isset($la_params['order']) && $la_params['order'] !== '') {
                $order = $la_params['order'];
            } else {
                $order = 'ASC';
            }
            $la_sources->orderBy($la_params['sort'], $order);
        }

        if ($lb_countArray) {
            $li_userCount = $la_sources->count();
            return $li_userCount;
        }

        $la_sources = $la_sources->joinWith(['restrictedTocompanies'])
            ->joinWith(['source' => function (\yii\db\ActiveQuery $query) {
                $query->select([
                    'source_id',
                    'name',
                    'icon',
                    'description',
                    'is_custom',
                    'is_beta',
                    'active_status',
                    'source_platform',
                    'need_auth',
                    'auth_url',
                    'force_connection_test',
                ]);
            }])
            ->select([
                'integrations.integration_id',
                'integrations.source_id',
                'integrations.name',
                'integrations.icon',
                'integrations.description',
                'integrations.is_custom',
                'integrations.is_beta',
                'integrations.active_status',
                'integrations.channel_platform',
                'integrations.need_auth',
                'integrations.auth_url',
                'integrations.force_connection_test',
                'integrations.installed_instances',
                'integrations.created_at',
                'integrations.created_by',
                'integrations.updated_at',
                'integrations.updated_by',
            ])
            ->distinct()
            ->asArray()
            ->all(); 

        foreach ($la_sources as $la_sources_key => $la_sources_value) {
            $la_res = self::formatIntegrationSingleData($la_sources_value);
            unset($la_res['restrictedTocompanies']);
            array_push($la_result, $la_res);
        }    

        return $la_result;
    }
}
