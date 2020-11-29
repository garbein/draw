<?php

namespace app\utils;

class Filter
{
    const OK = 0;
    
    public static function run($r, $fields)
    {
        $data = [];
        $message = '';

        foreach ($fields as $field => $config) {
            [$name, $type, $require] = $config;
            $v = isset($r[$field]) ? $r[$field] : null;
            if ($require && Validator::isEmpty($v)) {
                $message = $name . '不能为空';
                break;
            }

            $method = 'is' . ucfirst($type);
            if (method_exists(Validator::class, $method)) {
                $valid = call_user_func([Validator::class, $method], $v);
                if (!$valid) {
                    $message = $config[0] . '错误';
                    break;
                }
            }

            switch ($type) {
                case 'code':
                case 'mobile':
                case 'token':
                case 'string':
                    $v = trim($v);
                    break;
                case 'integer':
                    $v = intval($v);
                    break;
                default:
                    break;
            }
            
            $data[$field] = $v;
        }

        return [
            'code' => $message ? 1 : 0,
            'data' => $data,
            'message' => $message,
        ];
    }
}
