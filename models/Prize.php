<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "prize".
 *
 * @property int $id ID
 * @property string $name 奖品
 * @property int $total_stock 总库存
 * @property int $used_stock 已用库存
 * @property int $draw_percent 中奖概率
 * @property string $rule 规则
 * @property int $status 状态
 * @property int $update_time 修改时间
 * @property int $create_time 创建时间
 */
class Prize extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prize';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['total_stock', 'used_stock', 'draw_percent', 'status', 'update_time', 'create_time'], 'integer'],
            [['name', 'rule'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'total_stock' => 'Total Stock',
            'used_stock' => 'Used Stock',
            'draw_percent' => 'Draw Percent',
            'rule' => 'Rule',
            'status' => 'Status',
            'update_time' => 'Update Time',
            'create_time' => 'Create Time',
        ];
    }
}
