<?php

namespace app\services;

use Yii;
use app\base\BaseService;
use app\components\Redis;
use app\models\Article;
use app\models\Prize;
use app\models\User;
use app\models\UserDraw;
use app\models\UserPrize;
use yii\db\Expression;

/**
 * 奖品service
 * @author zhouyelin
 *
 */
class PrizeService extends BaseService
{

    /**
     * 用来抽奖生成的随机数的基数
     * @var integer
     */
    const RANDOM_MAX = 1000;

    const CACHE_PRIZE_LIST = 'prize:list';

    /**
     * 获取所有奖品优先从缓存取如果缓存没有从db取
     * @return array
     */
    public static function getPrizeListCache()
    {
        $data = [];
        //从redis取缓存数据
        $cache = Redis::getConn()->get(self::CACHE_PRIZE_LIST);
        if ($cache) {
            $data = json_decode($cache, true);
        }
        if (empty($data)) {
            // 从数据取所有奖品
            $list = self::getPrizeList();
            $data  = [];
            $high = 0;
            $num = 0;
            foreach ($list as $item) {
                //计算概率区间
                $num = self::RANDOM_MAX * $item['draw_percent'];
                $item['low'] =  $high + 1;
                $item['high'] = $high + $num;
                $high = $item['high'];
                $data [] = $item;
            }
            //缓存在redis
            Redis::getConn()->set(self::CACHE_PRIZE_LIST, json_encode($data));
        }
        return $data ;
    }

    /**
     * 从数据库查询奖品列表
     * @return array
     */
    public static function getPrizeList()
    {
        return Prize::find()
            ->select(['id', 'name', 'total_stock', 'used_stock', 'draw_percent', 'rule'])
            //->where(['status' => 1])
            ->orderBy('draw_percent')
            ->asArray()
            ->all();
    }

    /**
     * 更新奖品库存
     * @param integer $id
     * @param integer $stock
     * @return number
     */
    public static function updateUsedStock($id, $stock)
    {
        $condition = [
            'id' => $id,
        ];
        $attributes = [
            'used_stock' => new Expression('used_stock + ' . $stock),
            'update_time' => time(),
        ];
        return Prize::updateAll($attributes, $condition);
    }
}
