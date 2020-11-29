<?php

namespace app\controllers;

use app\base\BaseController;
use app\services\DrawService;
use app\services\SmsService;
use app\utils\Validator;
use app\utils\Filter;

class DrawController extends BaseController
{

    public function actionSignup()
    {
        $fields = [
            'code' => ['短信验证码', 'code', true],
            'mobile' => ['手机号码', 'mobile', true],
            'content' => ['征文内容', 'string', true],
        ];
        $r = $this->post();
        $filter = Filter::run($r, $fields);
        if ($filter['code'] !== Filter::OK) {
            return $this->error($filter['message']);
        }
        SmsService::validateCode($filter['data']['mobile'], $filter['data']['code']);
        $result = DrawService::signup($filter['data']);
        return $this->responseJson($result);
    }

    public function actionStart()
    {
        $accessToken = $this->getAccessToken();
        if (empty($accessToken)) {
            return $this->error('请重新登录');
        }
        $result = DrawService::start($accessToken);
        return $this->responseJson($result);
    }
}
