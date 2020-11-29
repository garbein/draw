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

class PrizeService extends BaseService
{

    const RANDOM_MAX = 1000;

    const CACHE_PRIZE_LIST = 'prize:list';

    public static function getPrizeListCache()
    {
        $data = [];
        $cache = Redis::getConn()->get(self::CACHE_PRIZE_LIST);
        if ($cache) {
            $data = json_decode($cache, true);
        }
        if (empty($data)) {
            $list = self::getPrizeList();
            $data  = [];
            $high = 0;
            $num = 0;
            foreach ($list as $item) {
                $num = self::RANDOM_MAX * $item['draw_percent'];
                $item['low'] =  $high + 1;
                $item['high'] = $high + $num;
                $high = $item['high'];
                $data [] = $item;
            }
            Redis::getConn()->set(self::CACHE_PRIZE_LIST, json_encode($data));
        }
        return $data ;
    }

    public static function getPrizeList()
    {
        return Prize::find()
            ->select(['id', 'name', 'total_stock', 'used_stock', 'draw_percent', 'rule'])
            //->where(['status' => 1])
            ->orderBy('draw_percent')
            ->asArray()
            ->all();
    }

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
