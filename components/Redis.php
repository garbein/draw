<?php

namespace app\components;

use Yii;

class Redis
{

    private function __construct($config = [])
    {
        if (!empty($config)) {
            Yii::configure($this, $config);
        }
    }

    /**
     * @return yii\redis\Connection
     */
    public static function getConn()
    {
        return Yii::$app->redis;
    }
}
