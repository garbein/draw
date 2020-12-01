<?php

namespace app\controllers;

use app\base\BaseController;
use app\services\DrawService;
use app\services\SmsService;
use app\utils\Validator;
use app\utils\Filter;
use Yii;

class DrawController extends BaseController
{

    /**
     * 报名
     */
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
        if (mb_strlen($filter['data']['content']) > 500) {
            return $this->error('最多500字');
        }
        $result = DrawService::signup($filter['data']);
        return $this->responseJson($result);
    }

    /**
     * 抽奖
     */
    public function actionStart()
    {
        $accessToken = $this->getAccessToken();
        if (empty($accessToken)) {
            return $this->error('请重新登录');
        }
        $result = DrawService::start($accessToken);
        return $this->responseJson($result);
    }

    /**
     * 提取用户征文
     */
    public function actionArticle()
    {
        $mobile = trim($this->get('mobile', ''));
        $page = intval($this->get('page', 0));
        $size = intval($this->get('size', 20));
        $condition = [];
        if ($mobile) {
            $condition['mobile'] = $mobile;
        }
        if (empty($page)) {
            $page = 1;
        }
        if (empty($size)) {
            $size = 20;
        }
        $offset = ($page - 1) * $size;
        $result = DrawService::getArticleList($condition, $offset, $size);
        $result['page'] = $page;
        $result['size'] = $size;
        return $this->success($result);
    }

    /**
     * 导出中奖记录
     */
    public function actionExport()
    {
       $list = DrawService::getUserPrize();
       $content = "手机\t奖品\t\n";
       foreach ($list as $item) {
           $content .= sprintf("%s\t%s\t\n", $item['mobile'], $item['prize_name']);
       }
       $options = [
           'mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
       ];
       return Yii::$app->response->sendContentAsFile($content, 'export' . date('YmdHis') . '.xlsx', $options);
    }

    /**
     * 获取用户是否参与抽奖及中奖结果
     */
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
