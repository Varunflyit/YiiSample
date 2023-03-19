<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "test_log".
 *
 * @property int $id
 * @property string $func_name
 * @property string|null $runtimestamp
 * @property int $main_loop_counter
 */
class TestLog extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'test_log';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['func_name', 'main_loop_counter'], 'required'],
            [['runtimestamp'], 'safe'],
            [['main_loop_counter'], 'integer'],
            [['func_name'], 'string', 'max' => 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'func_name' => 'Func Name',
            'runtimestamp' => 'Runtimestamp',
            'main_loop_counter' => 'main_loop_counter',
        ];
    }
}
