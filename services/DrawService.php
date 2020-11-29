<?php

namespace app\services;

use Yii;
use app\base\BaseService;
use app\components\Redis;
use app\models\Article;
use app\models\User;
use app\models\UserDraw;
use app\models\UserPrize;

class DrawService extends BaseService
{

    public static function signup($params)
    {
        $mobile = $params['mobile'] ?? '';
        if (empty($mobile)) {
            return self::error('手机号码不能为空');
        }
        $user = User::findOne(['mobile' => $mobile]);
        $userId = 0;
        $accessToken = self::genAccessToken();
        if (empty($user)) {
            if (empty($params['content'])) {
                return self::error('没发表征文哦');
            }
            $userModel = new User();
            $userModel->mobile = $mobile;
            $userModel->access_token = $accessToken;
            $userModel->create_time = time();
            if ($userModel->save()) {
                $userId = $userModel->id;
            } else {
                return self::error('系统错误');
            }
            $articleModel = new Article();
            $articleModel->user_id = $userId;
            $articleModel->content = $params['content'];
            $articleModel->save();
        } else {
            $userId = $user['id'];
            $updated = User::updateAll(['access_token' => $accessToken, 'update_time' => time()], ['id' => $userId]);
            if (!$updated) {
                return self::error('系统错误');
            }
        }
        self::setAccessTokenCache($userId, $accessToken);
        return self::success(['access_token' => $accessToken]);
    }

    protected static function genAccessToken()
    {
        return md5(bin2hex(random_bytes(16)) . microtime());
    }

    protected static function setAccessTokenCache($userId, $accessToken)
    {
        return Redis::getConn()->set('draw:access_token:' . $accessToken, $userId, 'EX', 30 * 86400);
    }

    protected static function getAccessTokenCache($accessToken)
    {
        return Redis::getConn()->get('draw:access_token:' . $accessToken);
    }

    public static function start($accessToken)
    {
        $userId = self::getAccessTokenCache($accessToken);
        if (empty($userId)) {
            return self::error('请重新登录', 1000);
        }
        /* 
        if (!self::setDayDrawRecord($userId)) {
            return self::error('一天只能抽一次', 1001);
        }
         */
        $prizeList = self::getPrizeList();
        if (empty($prizeList)) {
            return self::error('很遗憾未中奖', 1002);
        }
        $prize = self::randomDraw($prizeList);
        if (empty($prize)) {
            return self::error('很遗憾未中奖', 1003);
        }
        self::formatPrize($prize);
        $ruleResult = self::handleRule($userId, $prize);
        if (!$ruleResult) {
            return self::error('很遗憾未中奖', 1004);
        }
        if (!self::checkStock($prize)) {
            return self::error('很遗憾未中奖', 1005);
        }
        self::afterHit($userId, $prize);
        return self::success(['prize_name' => $prize['name']]);
    }

    protected static function formatPrize(&$prize)
    {
        $prize['rule'] = empty($prize['rule']) ? [] : json_decode($prize['rule'], true);
        if (!is_array($prize['rule'])) {
            $prize['rule'] = [];
        }
    }

    protected static function checkStock($prize)
    {
        $stock = Redis::getConn()->hincrby('prize:stock', $prize['id'], 1);
        if ($stock >= $prize['total_stock'] && empty($prize['rule']['unlimit'])) {
            return false;
        }
        return true;
    }

    protected static function afterHit($userId, $prize)
    {
        Redis::getConn()->zincrby('prize:user:num:' . $prize['id'], 1, $userId);
        self::addUserPrize($userId, $prize['id']);
        PrizeService::updateUsedStock($prize['id'], 1);
    }

    protected static function handleRule($userId, $prize)
    {
        if (empty($prize['rule'])) {
            return true;
        }
        $rule = $prize['rule'];
        if (!empty($rule['day_limit'])) {
            $dayLimit = Redis::getConn()->incr(sprintf('prize:day:limit:%s:%s', $prize['id'], date('Ymd')));
            if ($dayLimit > $rule['day_limit']) {
                return false;
            }
        }

        if (!empty($rule['user_limit'])) {
            $score = Redis::getConn()->zscore('prize:user:num:' . $prize['id'], $userId);
            if ($score >= $rule['user_limit']) {
                return false;
            }
        }

        return true;
    }

    protected static function setDayDrawRecord($userId)
    {
        return Redis::getConn()->sadd('draw:record:' . date('Ymd'), $userId);
    }

    protected static function randomDraw($prizeList)
    {
        $prize = [];
        $max = PrizeService::RANDOM_MAX * 100;
        $rand = random_int(1, $max);
        foreach ($prizeList as $item) {
            if ($rand > $item['low'] && $rand <= $item['high']) {
                $prize = $item;
                break;
            }
        }
        return $prize;
    }

    protected static function getPrizeList()
    {
        return PrizeService::getPrizeListCache();
    }

    protected static function addUserPrize($userId, $prizeId)
    {
        $model = new UserPrize();
        $model->user_id = $userId;
        $model->prize_id = $prizeId;
        return $model->save();
    }
}
