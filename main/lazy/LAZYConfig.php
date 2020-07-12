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
        if($name == ''){
            return self::$config;
        }
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
    public static function load($config){
        foreach ($config as $key => $value) {
            self::$config[$key] = $value;
        }
    }
    /**
     * 设置配置项
     *
     * @param [type] $name
     * @param [type] $val
     * @return void
     */
    public static function set($name, $val){
        self::$config[$name] = $val;
    }
}