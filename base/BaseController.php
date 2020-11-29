<?php

namespace app\base;

use app\utils\Validator;
use Yii;
use yii\web\Controller;

class BaseController extends Controller
{

    private $_accessToken = '';

    protected function get($name = null, $defaultValue = null)
    {
        return Yii::$app->request->get($name, $defaultValue);
    }

    protected function post($name = null, $defaultValue = null)
    {
        return Yii::$app->request->post($name, $defaultValue);
    }

    protected function getRawBody()
    {
        return Yii::$app->request->getRawBody();
    }
    protected function formatData($code, $message, $data)
    {
        return [
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ];
    }

    protected function responseJson($data) {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        Yii::$app->response->data = $data;
        return Yii::$app->response;
    }
    
    protected function error($message = '', $code = 1, $data = '')
    {
        return $this->responseJson($this->formatData($code, $message, $data));
    }

    protected function success($data = '', $code = 0, $message = '')
    {
        return $this->responseJson($this->formatData($code, $message, $data));
    }

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
}
