<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "user_token".
 *
 * @property int $token_id
 * @property string $user_id
 * @property string $token_value
 * @property int $created_at
 * @property int $expire_at
 *
 * @property User $user
 */
class UserToken extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%user_token%}}';;
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'token_value', 'created_at', 'expire_at'], 'required'],
            [['created_at', 'expire_at'], 'integer'],
            [['user_id', 'token_value'], 'string', 'max' => 255],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'token_id' => 'Token ID',
            'user_id' => 'User ID',
            'token_value' => 'Token Value',
            'created_at' => 'Created At',
            'expire_at' => 'Expire At',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}
