<?php

namespace app\base;

use app\utils\Validator;
use Yii;
use yii\web\Controller;
use app\components\Redis;

/**
 * 定制Controller基类
 * @author zhouyelin
 *
 */
class BaseController extends Controller
{

    /**
     * 用户登录access_token
     * @var string
     */
    private $_accessToken = '';

    /**
     * 获取http get请求参数
     * @param string $name
     * @param mixed $defaultValue
     * @return mixed
     */
    protected function get($name = null, $defaultValue = null)
    {
        return Yii::$app->request->get($name, $defaultValue);
    }

    /**
     * 获取http post请求参数
     * @param string $name
     * @param mixed $defaultValue
     * @return mixed
     */
    protected function post($name = null, $defaultValue = null)
    {
        return Yii::$app->request->post($name, $defaultValue);
    }

    /**
     * 返回http原始请求体
     * @return string
     */
    protected function getRawBody()
    {
        return Yii::$app->request->getRawBody();
    }
    
    /**
     * 格式化请求响应数据
     * @param string $code
     * @param string $message
     * @param mixed $data
     * @return array
     */
    protected function formatData($code, $message, $data)
    {
        return [
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ];
    }

    /**
     * 返回请求响应json数据
     * @param array $data
     * @return \yii\web\Response
     */
    protected function responseJson($data) {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        Yii::$app->response->data = $data;
        return Yii::$app->response;
    }
    
    /**
     * 返回失败请求响应json数据
     * @param string $message
     * @param number $code
     * @param string $data
     * @return \yii\web\Response
     */
    protected function error($message = '', $code = 1, $data = '')
    {
        return $this->responseJson($this->formatData($code, $message, $data));
    }

    /**
     * 返回成功请求响应json数据
     * @param string $data
     * @param number $code
     * @param string $message
     * @return \yii\web\Response
     */
    protected function success($data = '', $code = 0, $message = '')
    {
        return $this->responseJson($this->formatData($code, $message, $data));
    }

    /**
     * 获取http请求头里的authorization
     * @return string
     */
    protected function getAccessToken()
    {
        if (empty($this->_accessToken)) {
            $accessToken = Yii::$app->request->getHeaders()->get('authorization', '');
            if (Validator::isToken($accessToken)) {
                $this->_accessToken = $accessToken;
            }
        }
        return $this->_accessToken;
    }
    
    /**
     * 限制请求频率
     * @param string $key
     */
    protected function limitFrequency($key, $second = 3)
    {
        return !Redis::getConn()->set(Yii::$app->requestedRoute . '/' . $key, 1, 'EX', $second, 'NX');
    }
}
