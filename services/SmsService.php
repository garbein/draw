<?php

namespace app\services;

use Yii;
use app\base\BaseService;
use app\models\Article;
use app\models\User;

/**
 * 短信服务
 * @author zhouyelin
 *
 */
class SmsService extends BaseService
{

    /**
     * 生成并发送短信验证码
     * @param string $mobile
     * @return array
     */
    public static function sendCode($mobile)
    {
        $code = (string)random_int(1000000, 9999999);
        $code = substr($code, 1);
        //验证码10分钟内有效
        Yii::$app->redis->set('draw:smscode:' . $mobile, $code, 'EX', 10 * 60);
        $sms = sprintf('您的验证码为%s。验证码10分钟内有效，请勿泄露或转发他人。', $code);
        if (self::sendSms($mobile, $sms)) {
            return self::success();
        }
        return self::error('验证码发送失败');
    }

    /**
     * 发送短信
     * @param string $mobile
     * @param string $sms
     * @return boolean
     */
    public static function sendSms($mobile, $sms)
    {
        //todo
        return true;
    }

    /**
     * 核验短信验证码
     * @param string $mobile
     * @param string $code
     * @return array
     */
    public static function validateCode($mobile, $code)
    {
        $existCode = Yii::$app->redis->get('draw:smscode:' . $mobile);
        if (!$existCode) {
            return self::error('不存在的验证码');
        }
        if ($existCode === $code) {
            return self::success();
        }
        return self::error('验证码错误');
    }
}