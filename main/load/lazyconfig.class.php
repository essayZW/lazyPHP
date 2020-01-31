<?php
/**
 * 读取配置文件的值
 */

namespace lazy;
class LAZYConfig{
    private static $config;

    /**
     * 读取指定配置的值
     * @param  string $name 配置名称
     * @return [type]       [description]
     */
    public static function get($name = ''){
        if(array_key_exists($name, self::$config)){
            return self::$config[$name];
        }
        else{
            return false;
        }
    }

    /**
     * 加载配置文件
     * @return [type] [description]
     */
    public static function load(){
        self::$config = require_once(__LAZY_CONFIG__);
    }
}