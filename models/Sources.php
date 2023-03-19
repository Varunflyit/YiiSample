<?php

namespace app\models;

use \app\common\components\Utility;
use app\helpers\StringHelper;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "sources".
 *
 * @property int $source_id
 * @property string $name
 * @property string|null $icon
 * @property string|null $description
 * @property int|null $is_custom
 * @property int|null $is_beta
 * @property string|null $active_status
 * @property string $source_platform
 * @property int|null $need_auth
 * @property int|null $auth_url
 * @property int|null $force_connection_test
 * @property int|null $created_at
 * @property string|null $created_by
 * @property int|null $updated_at
 * @property string|null $updated_by
 */
class Sources extends \yii\db\ActiveRecord
{

    const STATUS_DELETED = 'Ar';
    const STATUS_INACTIVE = 'N';
    const STATUS_ACTIVE = 'Y';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%sources}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'source_platform'], 'required'],
            [['name'], 'unique'],
            [['created_by', 'updated_by', 'description', 'active_status','auth_url','form'], 'string'],
            [['installed_instances'], 'integer'],
            [['created_at', 'updated_at', 'source_platform'], 'safe'],
            [['name', 'icon', 'source_platform'], 'string', 'max' => 255],
            ['active_status', 'default', 'value' => self::STATUS_INACTIVE],
            ['is_custom', 'default', 'value' => self::STATUS_INACTIVE],
            ['is_beta', 'default', 'value' => self::STATUS_INACTIVE],
            ['force_connection_test', 'default', 'value' => self::STATUS_ACTIVE],
            ['need_auth', 'default', 'value' => self::STATUS_INACTIVE],
            ['installed_instances', 'default', 'value' => 0],

            ['active_status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE, self::STATUS_DELETED]],
            ['is_custom', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE]],
            ['is_beta', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE]],
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
            'source_id' => 'Source ID',
            'name' => 'Name',
            'icon' => 'Icon',
            'description' => 'Description',
            'is_custom' => 'Is Custom',
            'is_beta' => 'Is Beta',
            'active_status' => 'Active Status',
            'source_platform' => 'Source Form',
            'force_connection_test' => 'Force Connection Test',
            'need_auth' => 'Need Authentication',
            'auth_url' => 'Authentication URL',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'created_by' => 'Created By',
            'updated_by' => 'Updated By',
        ];
    }

    public function beforeSave($insert) {
        if ($insert) {
            do {
                $this->source_id = StringHelper::generateGuid(Yii::$app->params['guidLength']);
            } while (self::find()->where(['source_id' => $this->source_id])->exists());

            $this->created_by = \Yii::$app->user->identity->id;
            $this->created_at = date("Y-m-d H:i:s");
        } else {
            $this->updated_by = \Yii::$app->user->identity->id;
            $this->updated_at = date("Y-m-d H:i:s");
        }
        return parent::beforeSave($insert);
    }

    public function del() {
      $this->active_status = self::STATUS_DELETED;
      return $this->save();
    }

    public function getrestrictedToCompanies() {
        return $this->hasMany(CompanySourceRestrictedTo::class, ['source_id' => 'source_id']);
    }

    public function getsourceInstance() {
        return $this->hasMany(SourceInstances::class, ['source_id' => 'source_id']);
    }

    public static function formatSourceSingleData($la_data_value){
        $la_sourceData = [
            'restricted_to_companies' => array_column($la_data_value['restrictedToCompanies'] , 'company_id'),
        ];
        $la_sourceData = array_merge($la_data_value,$la_sourceData);
        return $la_sourceData;
    }

    public static function getSources($params)
    {
        $result       = [];
        $pageNumber   = ArrayHelper::getValue($params, 'page', 0);
        $itemPerPage  = ArrayHelper::getValue($params, 'size', Yii::$app->params['pageSize']);

        $li_totalRecord = self::getSourcesWithRestrictedCompanies($params, $countArray = true);

        $totalPages       = ceil($li_totalRecord / $itemPerPage);
        $offset           = $pageNumber * $itemPerPage;
        $params['limit']  = $itemPerPage;
        $params['offset'] = $offset;

        $sourceList   = self::getSourcesWithRestrictedCompanies($params);

        $result['result']['sources'] = $sourceList;
        $result['result_info']['total_records'] = intval($li_totalRecord);
        $result['result_info']['total_pages']   = intval($totalPages);
        $result['result_info']['page_number']   = intval($pageNumber);
        $result['result_info']['item_per_page'] = intval($params['limit']);

        return $result;        
    }

    public static function getSourcesWithRestrictedCompanies($params, $countArray = false)
    {
        $result  = [];
        $sources = self::find()->andWhere(['!=','active_status', 'Ar']);

        if (isset($params['search']) && $params['search'] != '') {
            $sources->andWhere(['or',
                ['LIKE', 'sources.name', $params['search']],
                ['LIKE', 'sources.source_id', $params['search']],
                ['LIKE', 'sources.source_platform', $params['search']]
            ]);
        } 

        if (isset($params['limit']) && $params['limit'] > 0) {
            $sources->limit($params['limit']);
            if (isset($params['offset']) && $params['offset'] >= 0) {
                $sources->offset($params['offset']);
            }
        }

        if (isset($params['sort']) && $params['sort'] !== '') {
            if (isset($params['order']) && $params['order'] !== '') {
                $order = $params['order'];
            } else {
                $order = 'ASC';
            }
            $sources->orderBy($params['sort'], $order);
        }

        if ($countArray) {
            return $sources->count();
        }

        $sources = $sources->joinWith(['restrictedToCompanies'])
            ->select([
                'sources.source_id',
                'sources.name',
                'sources.icon',
                'sources.description',
                'sources.is_custom',
                'sources.is_beta',
                'sources.active_status',
                'sources.source_platform',
                'sources.need_auth',
                'sources.auth_url',
                'sources.force_connection_test',
                'sources.installed_instances',
                'sources.created_at',
                'sources.created_by',
                'sources.updated_at',
                'sources.updated_by',
            ])
            ->distinct()
            ->asArray()
            ->all(); 

        foreach ($sources as $sourcesKey => $sourcesValue) {
            $la_res = self::formatSourceSingleData($sourcesValue);
            unset($la_res['restrictedToCompanies']);
            array_push($result, $la_res);
        }    

        return $result;
    }

    public static function getAllSourcesAndInstancesByCompany($params)
    {
        $result = [['instances' => [], 'available' => []]];

        // Get Source Instances
        $sourceInstances = SourceInstances::find()
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
            ->where(['company_id' => $params['company_id']])
            ->asArray()
            ->all();
        $sourceInstanceIds = array_column($sourceInstances, 'source_id');

        foreach ($sourceInstances as $sourceInstancesValue) {
            /** 
             * TODO: remove below code when there is already consistencies, 
             *       currently we put this code to avoid error when source instance
             *       is still available but source is not found
             */
            if (!isset($sourceInstancesValue['source'])) continue;
            /** end of additional code */

            $curResult = [
                'source_instance_id' => ArrayHelper::getValue($sourceInstancesValue, 'source_instance_id'),
                'source_id' => ArrayHelper::getValue($sourceInstancesValue, 'source_id'),
                'name' => ArrayHelper::getValue($sourceInstancesValue, 'name'),
                'active_status' => ArrayHelper::getValue($sourceInstancesValue, 'active_status'),
                'connection_status' => ArrayHelper::getValue($sourceInstancesValue, 'connection_status'),
                'last_connection_time' => ArrayHelper::getValue($sourceInstancesValue, 'last_connection_time')                
            ];
            $curResult['source']=$sourceInstancesValue['source'];
            $result[0]['instances'][] = $curResult;
        }

        // Get Available Sources
        $allSourcesNew = [];

        $allSources = self::find()
            ->select('source_id,name,icon,description,is_beta,source_platform,need_auth,auth_url')
            ->andWhere(['active_status' => 'Y'])
            ->asArray()
            ->all();

        $restrictedSources = CompanySourceRestrictedTo::find()
            ->where(['company_id' => $params['company_id']])
            ->asArray()
            ->all();
        $restrictedSources = array_column($restrictedSources, 'source_id');
        if (empty($restrictedSources)) {
            // if empty means allow all sources
            $restrictedSources = array_column($allSources, 'source_id');
        } 

        foreach ($allSources as $allSourcesValue) {
            /** TODO: below is future code, until we allow multiple instances of one source, we should not use below code. */
            // $allSourcesValue['is_installed'] = in_array($allSourcesValue['source_id'], $sourceInstanceIds) ? 'Y' : 'N';

            // if (in_array($allSourcesValue['source_id'], $restrictedSources)) {
            //     array_push($allSourcesNew, $allSourcesValue);
            // }  
            /** end of future code */

            if (in_array($allSourcesValue['source_id'], $restrictedSources) && !in_array($allSourcesValue['source_id'], $sourceInstanceIds)) {
                $allSourcesValue['is_installed'] = 'N';
                array_push($allSourcesNew, $allSourcesValue);
            }    
        }

        $result[0]['available'] = $allSourcesNew;

        return Utility::responseSuccess(['sources' => $result]);
    }
}
