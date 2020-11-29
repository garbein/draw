<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "user".
 *
 * @property int $id 用户ID
 * @property string $mobile 手机号
 * @property string $access_token access token
 * @property int $update_time 修改时间
 * @property int $create_time 创建时间
 */
class User extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['update_time', 'create_time'], 'integer'],
            [['mobile'], 'string', 'max' => 20],
            [['access_token'], 'string', 'max' => 32],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'mobile' => 'Mobile',
            'access_token' => 'Access Token',
            'update_time' => 'Update Time',
            'create_time' => 'Create Time',
        ];
    }
}
