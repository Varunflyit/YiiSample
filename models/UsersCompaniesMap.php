<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "users_companies_map".
 *
 * @property int $account_id
 * @property string $company_id
 * @property string $user_id
 */
class UsersCompaniesMap extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%users_companies_map}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['company_id', 'user_id'], 'required'],
            [['company_id', 'user_id'], 'string', 'max' => 50],
            ['company_id', 'exist', 'targetClass' => '\app\models\Companies', 'targetAttribute' => 'company_id', 'message' => 'Company is not exist.'],
            ['company_id', 'exist', 'targetClass' => '\app\models\Companies', 'targetAttribute' => 'company_id', 'filter' => ['is_active' => 'Y'], 'message' => 'This is not an active company.'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'account_id' => 'Account ID',
            'company_id' => 'Company ID',
            'user_id' => 'User ID',
        ];
    }

    public static function createUsersCompaniesMapByCompanyIds($la_companies,$li_userId) 
    {
        $la_result = [];
        if(!isset($la_companies) || count($la_companies) == 0)
            return;

        foreach ($la_companies as $li_companyId) {
            $ls_sqlCompany = "SELECT `company_id` FROM {{%companies}} WHERE `company_id` = :companyId";
            $ls_CompanyId = Yii::$app->db->createCommand($ls_sqlCompany)->bindValues(["companyId" => $li_companyId])->queryScalar();

            if($li_companyId){
                $lo_usersCompaniesMap = new UsersCompaniesMap();
                $lo_usersCompaniesMap->user_id = $li_userId;
                $lo_usersCompaniesMap->company_id = $li_companyId;
                if(!$lo_usersCompaniesMap->save())
                    array_push($la_result,$lo_usersCompaniesMap->getFirstErrors());
            }
        }
        return $la_result;
    }
    
    public static function updateUsersCompaniesMapByCompanyIds($la_companies,$li_userId)
    {
        if(!isset($la_companies) || count($la_companies) == 0)
            return;
        
        UsersCompaniesMap::deleteAll('user_id = :user_id', [':user_id' => $li_userId]);

        foreach ($la_companies as $li_companyId) {
            $ls_sqlCompany = "SELECT `company_id` FROM {{%companies}} WHERE `company_id` = :companyId";
            $ls_CompanyId = Yii::$app->db->createCommand($ls_sqlCompany)->bindValues(["companyId" => $li_companyId])->queryScalar();

            if($ls_CompanyId){
                $lo_usersCompaniesMap = new UsersCompaniesMap();
                $lo_usersCompaniesMap->user_id = $li_userId;
                $lo_usersCompaniesMap->company_id = $ls_CompanyId;
                $lo_usersCompaniesMap->save();
            }
        }
    }

    public static function getCompanyIdsByUserId($la_userIds)
    {
        $lo_formatUserIds = function ($userId) { return "'".trim($userId)."'"; };
        $la_userIds = array_map($lo_formatUserIds, $la_userIds);
        $ls_userIds = implode(',', $la_userIds);
        $ls_sqlCompanyDetails = "SELECT company_id FROM {{%users_companies_map}} WHERE user_id IN ($ls_userIds)";
        $la_companyIds = Yii::$app->db->createCommand($ls_sqlCompanyDetails)->queryAll();
        return array_column($la_companyIds, 'company_id');
    }

    public static function getCompanyInfoByUserId($la_userIds) {
        $lo_formatUserIds = function ($userId) { return "'".trim($userId)."'"; };
        $la_userIds = array_map($lo_formatUserIds, $la_userIds);
        $ls_userIds = implode(',', $la_userIds);

        $ls_sqlCompanyDetails = "SELECT
                c.company_id,
                c.company_name,
                c.note,
                c.timezone,
                c.allow_beta
            FROM
                users_companies_map ucp
            LEFT JOIN companies c ON ucp.company_id = c.company_id
            WHERE
                user_id IN ($ls_userIds)
        ";

        $arrResult = Yii::$app->db->createCommand($ls_sqlCompanyDetails)->queryAll();
        return $arrResult;
    }
}

