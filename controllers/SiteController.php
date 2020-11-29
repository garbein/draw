<?php

namespace app\controllers;

use Yii;
use app\base\BaseController;
use app\services\SmsService;
use app\utils\Filter;

class SiteController extends BaseController
{
    public function actionIndex()
    {
        return 'Hello World!';
    }

    public function actionGenCode()
    {
        $fields = [
            'mobile' => ['手机号码', 'mobile', true],
        ];
        $filter = Filter::run($this->post(), $fields);
        if ($filter['code'] !== Filter::OK) {
            return $this->error($filter['message']);
        }
        $result = SmsService::sendCode($filter['data']['mobile']);
        return $this->responseJson($result);
    }

    public function actionCsrfToken()
    {
        return $this->success(['csrfToken' => Yii::$app->request->csrfToken]);
    }
}