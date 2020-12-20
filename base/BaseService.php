<?php

namespace app\base;

use yii\base\BaseObject;

class BaseService extends BaseObject
{

    /**
     * 
     * @param number $code
     * @param string $message
     * @param string $data
     * @return array
     */
    protected static function formatData($code, $message, $data)
    {
        return [
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ];
    }

    /**
     * 
     * @param string $message
     * @param number $code
     * @param string $data
     * @return array
     */
    protected static function error($message = '', $code = 1, $data = '')
    {
        return self::formatData($code, $message, $data);
    }

    /**
     * 
     * @param string $data
     * @param number $code
     * @param string $message
     * @return array
     */
    protected static function success($data = '', $code = 0, $message = '')
    {
        return self::formatData($code, $message, $data);
    }
}
