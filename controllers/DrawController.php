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

    public function actionArticle()
    {
        $page = intval($this->get('page', 0));
        $size = intval($this->get('size', 20));
        if (empty($page)) {
            $page = 1;
        }
        if (empty($size)) {
            $size = 20;
        }
        $offset = ($page - 1) * $size;
        return DrawService::getArticleList($offset, $size);
    }

    public function actionExport()
    {
       set_time_limit(0);
       @ini_set('memory_limit','1024M');
       $list = DrawService::exportUserPrize();
       return $this->success($list);
    }

    public function actionDayPrize()
    {
        $accessToken = $this->getAccessToken();
        if (empty($accessToken)) {
            return $this->error('请重新登录');
        }
        $result = DrawService::getDayPrize($accessToken);
        return $this->responseJson($result);
    }
}
