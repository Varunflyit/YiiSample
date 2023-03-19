<?php

namespace app\models;

use \app\common\components\Utility;
use \yii\web\UnauthorizedHttpException;
use app\helpers\StringHelper;
use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\web\IdentityInterface;

/**
 * User model
 *
 * @property integer $id
 * @property string $username
 * @property string $password_hash
 * @property string $password_reset_token
 * @property string $verification_token
 * @property string $email
 * @property string $auth_key
 * @property integer $status
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $password write-only password
 */
class User extends ActiveRecord implements IdentityInterface
{
    const STATUS_DELETED = 'Ar';
    const STATUS_INACTIVE = 'N';
    const STATUS_ACTIVE = 'Y';

    const USER_ROLES_SUPER_ADMIN = 'superAdmin';
    const USER_ROLES_MASTER_USER = 'masterUser'; 
    const USER_ROLES_ADMIN = 'admin';
    const USER_ROLES_USER = 'user';
    const USER_ROLES = [
        self::USER_ROLES_SUPER_ADMIN,
        self::USER_ROLES_MASTER_USER,
        self::USER_ROLES_ADMIN,
        self::USER_ROLES_USER
    ];

    var $user_token;
    public $password;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%user}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            //            TimestampBehavior::className(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'email',], 'required'],
            [['email'], 'email'],
            [['email'], 'unique'],
            ['active_status', 'default', 'value' => self::STATUS_INACTIVE],
            ['active_status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE, self::STATUS_DELETED]],
            [['email', 'name', 'password_hash', 'password_reset_token', 'note'], 'string', 'max' => 255],
        ];
    }
    public function beforeSave($insert)
    {
        if ($insert) {
            do {
                $this->id = StringHelper::generateGuid(Yii::$app->params['guidLength']);
            } while (self::find()->where(['id' => $this->id])->exists());
        }
        return parent::beforeSave($insert);
    }

    /**
     * Find User by Access token
     * 
     * @param $token 
     * 
     * @return User|false
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        $userByToken = UserToken::find()->where(['token_value' => $token])->andWhere(['>', 'expire_at', strtotime('now')])->one();

        if ($userByToken && !empty($userByToken->user)) return $userByToken->user;

        return false;
    }

    /**
     * Get User by Access token
     * 
     * @param $token 
     * 
     * @return User|false
     */
    public static function getUserByAccessToken($token)
    {
        $user = self::findIdentityByAccessToken($token);

        if (!$user) throw new UnauthorizedHttpException('invalid_or_expired_token');

        if ('Y' !== $user->active_status) throw new UnauthorizedHttpException('inactive_user');

        return $user;
    }

    public function getCompanys()
    {
        return $this->hasMany(UsersCompaniesMap::className(), ['user_id' => 'id']);
    }
    public function getTokens()
    {
        return $this->hasMany(UserToken::className(), ['user_id' => 'id']);
    }


    /**
     * {@inheritdoc}
     */
    public static function findIdentity($id)
    {
        return self::findOne(['id' => $id, 'active_status' => self::STATUS_ACTIVE]);
    }

    /**
     * {@inheritdoc}
     */
    // public static function findIdentityByAccessToken($token, $type = null)
    // {
    //    throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
    // }

    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username, 'active_status' => self::STATUS_ACTIVE]);
    }

    public static function findByEmail($email)
    {
        return static::findOne(['email' => $email, 'active_status' => self::STATUS_ACTIVE]);
    }

    public static function findByAccessToken($token)
    {
        if (self::findIdentityByAccessToken($token)) {
            $ls_sqlUserDetails = "SELECT U.id,email,name,UT.expire_at,AA.item_name AS role FROM {{%user}} U INNER JOIN {{%auth_assignment}} AA ON U.id = AA.user_id INNER JOIN {{%user_token}} UT ON U.id = UT.user_id AND UT.token_value = '$token'";
            $la_userDetails = Yii::$app->db->createCommand($ls_sqlUserDetails)->queryOne();

            $la_companyIds = UsersCompaniesMap::getCompanyIdsByUserId([$la_userDetails['id']]);
            $la_userDetails = array_merge($la_userDetails, ['companies' => $la_companyIds]);

            return Utility::responseSuccess(['user' => $la_userDetails, 'access_token' => $token, 'ttl' => $la_userDetails['expire_at'] - time()]);
        }
    }
    /**
     * Finds user role 
     *
     * @param string $UserId
     * @return static|null
     */
    public static function findUserRole($UserId)
    {
        $userRoleSql = "SELECT AA.item_name AS role FROM {{%user}} U INNER JOIN {{%auth_assignment}} AA ON U.id = AA.user_id where U.id = '$UserId'";
        $userRole = Yii::$app->db->createCommand($userRoleSql)->queryOne();
        return $userRole['role'];
    }

    /**
     * Return public data
     */
    public function userLoginData($userToken)
    {
        $user = $this->toArray();

        // remove fields that contain sensitive information
        unset(
            $user['password_hash'],
            $user['password_reset_token'],
            $user['access_token'],
            $user['auth_key'],
            $user['note'],
            $user['active_status'],
            $user['created_at'],
            $user['updated_at']
        );

        $role = self::getUserRole($user['id']);

        $companyInfo = UsersCompaniesMap::getCompanyInfoByUserId([$user['id']]);

        $user_data = array_filter(ArrayHelper::merge(
            $user,
            [
                'expire_at' => $userToken->expire_at,
                'role'      => $role,
                'companies' => $companyInfo,
            ],
        ));

        return array_merge($user_data, [
            'access_token' => $userToken->token_value,
            'ttl'          => $userToken->expire_at - time(),
        ]);
    }

    /**
     * Finds user by password reset token
     *
     * @param string $token password reset token
     * @return static|null
     */
    public static function findByPasswordResetToken($token)
    {
        if (!static::isPasswordResetTokenValid($token)) {
            return null;
        }

        return static::findOne([
            'password_reset_token' => $token,
            'active_status' => self::STATUS_ACTIVE,
        ]);
    }

    /**
     * Finds user by verification email token
     *
     * @param string $token verify email token
     * @return static|null
     */
    public static function findByVerificationToken($token)
    {
        return static::findOne([
            'verification_token' => $token,
            'active_status' => self::STATUS_INACTIVE
        ]);
    }

    /**
     * Finds out if password reset token is valid
     *
     * @param string $token password reset token
     * @return bool
     */
    public static function isPasswordResetTokenValid($token)
    {
        if (empty($token)) {
            return false;
        }

        $timestamp = (int) substr($token, strrpos($token, '_') + 1);
        $expire = Yii::$app->params['user.passwordResetTokenExpire'];
        return $timestamp + $expire >= time();
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * {@inheritdoc}
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }


    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * Generates new password reset token
     */
    public function generatePasswordResetToken()
    {
        $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    public function generateEmailVerificationToken()
    {
        $this->verification_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Removes password reset token
     */
    public function removePasswordResetToken()
    {
        $this->password_reset_token = null;
    }


    public function generateAccessToken()
    {
        $access_token = Yii::$app->security->generateRandomString();
        return $access_token;
    }

    /**
     * Logout
     */
    public static function logout()
    {
        $getHeaders = Yii::$app->request->headers;

        $userToken  = str_replace("Bearer ","", $getHeaders['authorization']);
        $tokenModel = UserToken::findOne(['token_value' => $userToken]);

        return empty($tokenModel) ? null : $tokenModel->delete();
    }

    public static function changeUserPassword($la_postData)
    {
        $la_post = \app\common\components\Utility::filterInputArray($la_postData);
        $return = [];

        if ($la_post['password'] == '')
            $return['errors']['password'] = [\Yii::t('app', 'password_not_blank')];

        if (trim($la_post['new_password']) == '')
            $return['errors']['new_password'] = [\Yii::t('app', 'new_password_not_blank')];

        if (strlen(trim($la_post['new_password'])) < 6)
            $return['errors']['new_password'] = [\Yii::t('app', 'new_password_error')];

        if ($la_post['new_password'] != $la_post['repeat_password'])
            $return['errors']['repeat_password'] = [\Yii::t('app', 'new_password_repeat_error')];

        if (isset($return['errors']) && count($return['errors']) > 0) {
            return $return;
        } else {
            $model = User::find()
                ->where("id =" . Yii::$app->user->identity->id . " AND password ='" . md5($la_post['password']) . "'")
                ->one();
            if (isset($model)) {
                $model->password = md5(trim($la_post['new_password']));
                if ($model->save(FALSE)) {
                    $return['data'] = trim($la_post['new_password']);
                    $return['error'] = \Yii::t('app', 'success');
                } else {
                    $return['error'] = \Yii::t('app', 'password_not_change');
                }
            } else {
                $return['error'] = \Yii::t('app', 'password_incorrect');
            }
            return $return;
        }
    }
    public static function createUserWithRole($la_params)
    {
        $user_role = array_keys(Yii::$app->authManager->getRolesByUser(Yii::$app->user->id))[0];

        $la_result = [];
        if ($la_params['role'] == self::USER_ROLES_SUPER_ADMIN && $user_role != 'superAdmin') {
            return Utility::responseError(
                [
                    'code'    => 'create_admin_not_allow',
                    'message' => \Yii::t('app', 'create_admin_not_allow'),
                ],'',402);
        }

        $modelUser = new User();
        $modelUser->email = $la_params['email'];
        $modelUser->name = $la_params['name'];
        $modelUser->note = $la_params['note'];
        $modelUser->created_at = time();
        $modelUser->setPassword($la_params['password']);
        $modelUser->active_status = $la_params['is_active'];
        if ($modelUser->save()) {

            //Assiging user role
            User::assignRoleByUserId($modelUser->id, $la_params['role']);

            $la_params['role'] = isset($la_params['role']) && $la_params['role'] != '' ? $la_params['role'] : 'user';
            //Creating company users map
            $la_companyResult = UsersCompaniesMap::createUsersCompaniesMapByCompanyIds($la_params['companies'], $modelUser->id);

            if (!empty($la_companyResult)) {
                return Utility::responseError($la_companyResult,'',400);
            }

            $la_result = [
                'id' => $modelUser->id,
                'email' => $modelUser->email,
                'name' => $modelUser->name,
                'note' => $modelUser->note,
                'active_status' => $modelUser->active_status,
                'role' => $la_params['role']
            ];

            $companyInfo = UsersCompaniesMap::getCompanyInfoByUserId([$modelUser->id]);
            $la_result['companies'] = $companyInfo;

            return Utility::responseSuccess($la_result);
        } else {
            return Utility::responseError($modelUser->getFirstErrors(),'',400);
        }
    }

    public static function assignRoleByUserId($li_userId, $ls_role = "user")
    {
        if (!in_array($ls_role, self::USER_ROLES))
            return "No role found";

        $auth = \Yii::$app->authManager;
        $role = $auth->getRole($ls_role);
        $auth->revokeAll($li_userId);
        $auth->assign($role, $li_userId);
    }

    public static function getUserRoleByUserId($li_userId)
    {
        $auth = \Yii::$app->authManager;
        return $ls_userRole = array_keys($auth->getRolesByUser($li_userId))[0];
    }

    public static function updateUser($la_params, $id)
    {
        $la_result = [];
        $modelUser = self::findOne($id);
        if (empty($modelUser))
            return Utility::responseError(
                [
                    'code'    => 'user_not_found',
                    'message' => \Yii::t('app', 'user_not_found'),
                ],'',402);

        $ls_userRole = self::getUserRoleByUserId($modelUser->id);
        if (!$modelUser)
            return Utility::responseError(
                [
                    'code'    => 'user_not_found',
                    'message' => \Yii::t('app', 'user_not_found'),
                ],'',402);
        if (isset($la_params['name'])) {
            $modelUser->name = $la_params['name'];
        }
        if (isset($la_params['note'])) {
            $modelUser->note = $la_params['note'];
        }
        if (isset($la_params['active_status'])) {
            $modelUser->active_status = $la_params['active_status'];
        }
        if (isset($la_params['email'])) {
            $emailCount = self::find()->where(["=",'email',$la_params['email']])->andWhere(["!=",'id', $modelUser->id])->count();
            if($emailCount>0){
                return Utility::responseError(
                    [
                        'code'    => 'email_exist',
                        'message' => \Yii::t('app', 'email_exist'),
                    ],'',402);
            }
            $modelUser->email = $la_params['email'];
        }

        if ($modelUser->save()) {
            if (isset($la_params['role'])) {
                $role = self::getUserRole($modelUser->id);
                if ($role !== $la_params['role']) {
                    User::assignRoleByUserId($modelUser->id, $la_params['role']);
                }
            }

            if (isset($la_params['companies'])) {
                //updating company users map
                $la_companyResult = UsersCompaniesMap::updateUsersCompaniesMapByCompanyIds($la_params['companies'], $modelUser->id);

                if (!empty($la_companyResult)) {
                    return Utility::responseError($la_companyResult,'',400);
                }
            }

            $role = self::getUserRole($modelUser->id);

            $la_result = [
                'id' => $modelUser->id,
                'email' => $modelUser->email,
                'role' => $role,
                'name' => $modelUser->name,
                'note' => $modelUser->note,
                'active_status' => $modelUser->active_status
            ];

            $companyInfo = UsersCompaniesMap::getCompanyInfoByUserId([$modelUser->id]);
            $la_result['companies'] = $companyInfo;

            return Utility::responseSuccess($la_result);
        } else {
            return Utility::responseError($modelUser->getFirstErrors(),'',400);
        }
    }

    public static function getUserList($la_params)
    {
        $la_result = [];
        $li_pageNumber = isset($la_params["page"]) ? $la_params["page"] : 0;
        $li_item_per_page = isset($la_params['size']) ?  $la_params['size'] : Yii::$app->params['pageSize'];

        $li_totalRecord = self::getUsersWithRoleAndCompanies($la_params, $lb_countArray = true);

        $li_totalPages = ceil($li_totalRecord / $li_item_per_page);
        $li_offset = $li_pageNumber * $li_item_per_page;
        $la_params['limit'] = $li_item_per_page;
        $la_params['offset'] = $li_offset;
        $la_userList = self::getUsersWithRoleAndCompanies($la_params);

        $la_result['users'] = $la_userList;
        $la_result['result_info']['total_records'] = intval($li_totalRecord);
        $la_result['result_info']['total_pages'] = intval($li_totalPages);
        $la_result['result_info']['page_number'] = intval($li_pageNumber);
        $la_result['result_info']['item_per_page'] = intval($la_params['limit']);

        return $la_result;
    }

    public static function getUsersWithRoleAndCompanies($la_params, $lb_countArray = false)
    {
        $la_result = [];
        $la_users = self::find()
            ->select('id,name,email,note,active_status,auth_assignment.item_name AS role')
            ->innerJoin('auth_assignment', 'auth_assignment.user_id = user.id');

        if (isset($la_params['search']) && $la_params['search'] != '') {
            $la_users->where(['LIKE', 'name', $la_params['search']]);
            $la_users->orWhere(['id' => $la_params['search']]);
            $la_users->orWhere(['LIKE', 'email', $la_params['search']]);
        }

        if (isset($la_params['limit']) && $la_params['limit'] > 0) {
            $la_users->limit($la_params['limit']);
            if (isset($la_params['offset']) && $la_params['offset'] >= 0) {
                $la_users->offset($la_params['offset']);
            }
        }

        if (isset($la_params['sort']) && $la_params['sort'] !== '') {
            if (isset($la_params['order']) && $la_params['order'] !== '') {
                $order = $la_params['order'];
            } else {
                $order = 'ASC';
            }
            $la_users->orderBy($la_params['sort'], $order);
        }

        if ($lb_countArray) {
            $li_userCount = $la_users->count();
            return $li_userCount;
        }

        $la_users = $la_users->distinct()
            ->asArray()
            ->all();

        $la_result = self::getCompaniesFromUsers($la_users);

        // $la_result = self::getCompaniesIdColumn($la_result, $la_users);

        return $la_result;
    }

    public static function getCompaniesFromUsers($la_users)
    {
        $arrUserIds = array_column($la_users, 'id');
        $arrCompanies = self::find()
            ->select('user.id as user_id, companies.company_id as company_id, companies.company_name as company_name')
            ->leftJoin('users_companies_map', 'users_companies_map.user_id = user.id')
            ->leftJoin('companies', 'users_companies_map.company_id = companies.company_id')
            ->where(['in', 'user.id', $arrUserIds])
            ->andWhere(['not', ['companies.company_id' => null]])
            ->asArray()
            ->all();

        $arrUserCompanies = [];
        foreach ($arrCompanies as $company_info) {
            $arrUserCompanies[$company_info['user_id']][] = [
                'company_id'    =>  $company_info['company_id'],
                'company_name'  =>  $company_info['company_name'],
            ];
        }

        foreach ($la_users as $key => $user) {
            $user_id = $user['id'];
            if (isset($arrUserCompanies[$user_id])) {
                $user['companies'] = $arrUserCompanies[$user_id];
            } else {
                $user['companies'] = [];
            }
            
            $la_users[$key] = $user;
        }

        return $la_users;
    }

    /**
     * Get current User
     */
    public static function getCurrentUser()
    {
        return self::findOne(Yii::$app->user->id);
    }

    /**
     * Get current User Role
     */
    public static function getUserRole($userId = null)
    {
        $user = empty($userId) ? self::getCurrentUser() : self::findOne($userId);

        if (empty($user)) return null;

        $roles = array_keys(Yii::$app->authManager->getRolesByUser($user->id));

        return empty($roles) ? null : current($roles);
    }
}
