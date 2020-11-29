<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "user_prize".
 *
 * @property int $id ID
 * @property int $user_id 用户ID
 * @property int $prize_id 状态id
 * @property int $status 状态
 * @property int $update_time 修改时间
 * @property int $create_time 创建时间
 */
class UserPrize extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_prize';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'prize_id', 'status', 'update_time', 'create_time'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'prize_id' => 'Prize ID',
            'status' => 'Status',
            'update_time' => 'Update Time',
            'create_time' => 'Create Time',
        ];
    }
}
