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
use yii\helpers\Html;

/**
 * 用户报名及抽奖服务
 * @author zhouyelin
 *
 */
class DrawService extends BaseService
{

    /**
     * 用户当天抽奖记录缓存key
     * @var string
     */
    const CACEH_KEY_DRAW_RECORD = 'draw:record:';
    
    const CACEH_KEY_PRIZE_USER_NUM = 'prize:user:num:';
    
   /**
    *  用户提交征文报名兼容了已发表过征文直接登录
    * @param array $params
    * @return array
    */
    public static function signup($params)
    {
        $mobile = $params['mobile'] ?? '';
        if (empty($mobile)) {
            return self::error('手机号码不能为空');
        }
        //通过手机号码查询用户
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
            //保存征文
            $articleModel = new Article();
            $articleModel->user_id = $userId;
            $articleModel->content = $params['content'];
            $articleModel->save();
        } else {
            $userId = $user['id'];
            //更新user access_token
            $updated = User::updateAll(['access_token' => $accessToken, 'update_time' => time()], ['id' => $userId]);
            if (!$updated) {
                return self::error('系统错误');
            }
        }
        //存储access token到redis
        self::setAccessTokenCache($userId, $accessToken);
        return self::success(['access_token' => $accessToken]);
    }

    /**
     * 通过随机数和时间生成access_token 更好方案使用jwt
     * @return string
     */
    protected static function genAccessToken()
    {
        return md5(bin2hex(random_bytes(16)) . microtime());
    }

   /**
    * 在redis存储access_token 30天过期
    * @param integer $userId
    * @param string $accessToken
    * @return bool
    */
    protected static function setAccessTokenCache($userId, $accessToken)
    {
        return Redis::getConn()->set('draw:access_token:' . $accessToken, $userId, 'EX', 30 * 86400);
    }

    /**
     * 获取redis里的access_token
     * @param string $accessToken
     * @return string
     */
    protected static function getAccessTokenCache($accessToken)
    {
        return Redis::getConn()->get('draw:access_token:' . $accessToken);
    }

    /**
     * 用户参与抽奖
     * @param string $accessToken
     * @return array|number[]|string[]
     */
    public static function start($accessToken)
    {
        $userId = self::getAccessTokenCache($accessToken);
        if (empty($userId)) {
            return self::error('请重新登录', 1000);
        }
        //一人一天只能抽一次
        if (!self::setDayDrawRecord($userId)) {
            return self::error('一天只能抽一次', 1001);
        }
        //获取所有奖品
        $prizeList = self::getPrizeList();
        if (empty($prizeList)) {
            return self::error('很遗憾未中奖', 1002);
        }
        //随机抽奖
        $prize = self::randomDraw($prizeList);
        if (empty($prize)) {
            return self::error('很遗憾未中奖', 1003);
        }
        //格式化中奖规则
        self::formatPrize($prize);
        //使用规则限制
        $ruleResult = self::handleRule($userId, $prize);
        if (!$ruleResult) {
            return self::error('很遗憾未中奖', 1004);
        }
        //检查奖品库存
        if (!self::checkStock($prize)) {
            return self::error('很遗憾未中奖', 1005);
        }
        //最终中奖后的处理
        self::afterHit($userId, $prize);
        return self::success(['prize_name' => $prize['name']]);
    }

    /**
     * 格式化奖品
     */
    protected static function formatPrize(&$prize)
    {
        $prize['rule'] = empty($prize['rule']) ? [] : json_decode($prize['rule'], true);
        if (!is_array($prize['rule'])) {
            $prize['rule'] = [];
        }
    }

    /**
     * 核验奖品库存
     * @param array $prize
     * @return boolean
     */
    protected static function checkStock($prize)
    {
        $stock = Redis::getConn()->hincrby('prize:stock', $prize['id'], 1);
        if ($stock >= $prize['total_stock'] && empty($prize['rule']['unlimit'])) {
            return false;
        }
        return true;
    }

    /**
     * 中奖后的操作
     */
    protected static function afterHit($userId, $prize)
    {
        //redis有序集合incrby记录用户中奖奖品,方便规则限制
        Redis::getConn()->zincrby(self::CACEH_KEY_PRIZE_USER_NUM . $prize['id'], 1, $userId);
        //在数据库里保存中奖记录
        self::addUserPrize($userId, $prize['id']);
        //更新已中奖品数量
        PrizeService::updateUsedStock($prize['id'], 1);
    }

    /**
     * 使用抽奖规则限制
     * @param integer $userId
     * @param array $prize
     * @return boolean
     */
    protected static function handleRule($userId, $prize)
    {
        if (empty($prize['rule'])) {
            return true;
        }
        $rule = $prize['rule'];
        //奖品每个最多中奖限制
        if (!empty($rule['day_limit'])) {
            $dayLimit = Redis::getConn()->incr(sprintf('prize:day:limit:%s:%s', $prize['id'], date('Ymd')));
            if ($dayLimit > $rule['day_limit']) {
                return false;
            }
        }
        //用户活动期间特定奖品最多中奖限制
        if (!empty($rule['user_limit'])) {
            //有序集合中元素是用户ID,分数是中奖数量
            $score = Redis::getConn()->zscore(self::CACEH_KEY_PRIZE_USER_NUM . $prize['id'], $userId);
            if ($score >= $rule['user_limit']) {
                return false;
            }
        }

        return true;
    }

    /**
     * 记录用户参与当天活动
     * 利用redis集合元素不重复特性记录 
     * @param integer $userId
     * @return bool
     */
    protected static function setDayDrawRecord($userId)
    {
        return Redis::getConn()->sadd(self::CACEH_KEY_DRAW_RECORD . date('Ymd'), $userId);
    }

    /**
     * 随机抽奖
     * @param array $prizeList
     * @return array
     */
    protected static function randomDraw($prizeList)
    {
        $prize = [];
        //随机数最大数
        $max = PrizeService::RANDOM_MAX * 100;
        //生成随机数
        $rand = random_int(1, $max);
        foreach ($prizeList as $item) {
            //随机数如果在奖品中奖区间内则中奖
            if ($rand > $item['low'] && $rand <= $item['high']) {
                $prize = $item;
                break;
            }
        }
        return $prize;
    }

    /**
     * 获取所有奖品优先从redis取
     * @return array
     */
    protected static function getPrizeList()
    {
        return PrizeService::getPrizeListCache();
    }

    /**
     * 记录用户中奖奖品
     * @param integer $userId
     * @param integer $prizeId
     * @return boolean
     */
    protected static function addUserPrize($userId, $prizeId)
    {
        $model = new UserPrize();
        $model->user_id = $userId;
        $model->prize_id = $prizeId;
        return $model->save();
    }

    /**
    * 提取用户征文
    * @param array $condition
    * @param number $offset
    * @param number $limit
    * @return array
    */
    public static function getArticleList($condition, $offset = 0, $limit = 20)
    {
        $query = User::find();
        if ($condition) {
            $query->where($condition);
        }
        $total = $query->count();
        $list = [];
        if ($total) {
            $rows = $query->select(['id', 'mobile'])
                ->orderBy('id desc')
                ->offset($offset)
                ->limit($limit)
                ->asArray()
                ->all();
            if ($rows) {
                $userIds = array_column($rows, 'id');
                $articles = Article::find()
                    ->select(['id', 'user_id', 'content'])
                    ->where(['user_id' => $userIds])
                    ->asArray()
                    ->all();
                if ($articles) {
                    $articles = array_column($articles, null, 'user_id');
                }
                foreach ($rows as $row) {
                    $article = $articles[$row['id']] ?? [];
                    if (empty($article)) {
                        continue;
                    }
                    $content = Html::encode($article['content']);
                    $item = [
                        'mobile' => $row['mobile'],
                        'content_id' => $article['id'],
                        'content' => $content,
                    ];
                    $list[] = $item;
                }
            }
        }
        return ['list' => $list, 'total' => $total];
    }

    /**
     * 获取所有奖品记录
     * @return array
     */
    public static function getUserPrize()
    {
        $query = UserPrize::find()->innerJoin(User::tableName() . ' u', 'u.id = user_id');
        $query->select(['user_id', 'prize_id', 'mobile']);
        $rows = $query->asArray()->all();
        $list = [];
        if ($rows) {
            $prizeList = PrizeService::getPrizeList();
            $prizes = array_column($prizeList, null, 'id');
            foreach ($rows as $row) {
                $item = [
                    'mobile' => $row['mobile'],
                    //'prize_id' => $row['prize_id'],
                    'prize_name' => $prizes[$row['prize_id']]['name'] ?? '',
                ];
                $list[] = $item;
            }
        }
        return $list;
    }

    /**
     * 通过access token获取用户当天是否参与抽奖及中奖结果
     * @param string $accessToken
     * @return array
     */
    public static function getDayPrize($accessToken)
    {
        $userId = self::getAccessTokenCache($accessToken);
        if (empty($userId)) {
            return self::error('请重新登录', 1000);
        }
        //
        $is = Redis::getConn()->sismember(self::CACEH_KEY_DRAW_RECORD . date('Ymd'), $userId);
        $prizeName = '';
        if ($is) {
            $prize = UserPrize::find()->innerJoin(Prize::tableName() . ' p', 'p.id = prize_id')
                ->select(['name'])
                ->where(['user_id' => $userId])
                ->asArray()
                ->one();
            $prizeName = $prize['name'] ?? '';
        }
        $data = [
            'status' => (bool)$is,
            'prize_name' => $prizeName,
        ];
        return self::success($data);
    }
}
