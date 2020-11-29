<?php

namespace app\base;

use yii\base\BaseObject;

class BaseService extends BaseObject
{

    protected static function formatData($code, $message, $data)
    {
        return [
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ];
    }

    protected static function error($message = '', $code = 1, $data = '')
    {
        return self::formatData($code, $message, $data);
    }

    protected static function success($data = '', $code = 0, $message = '')
    {
        return self::formatData($code, $message, $data);
    }
}
