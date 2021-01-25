<?php

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
     * 根据已经导入的配置初始化框架
     */
    public static function init() {
        date_default_timezone_set(LAZYConfig::get('default_timezone'));
        $LAZYDebug = new AppDebug();
        $LAZYDebug->getHandler(LAZYConfig::get('app_debug'))
                  ->errorRun(LAZYConfig::get('app_error_run'));
        ini_set('display_errors', LAZYConfig::get('app_debug'));
        require_once(__USER_COMMON__);
        foreach (LAZYConfig::get('extra_file_list') as $value) {
            require_once($value);
        }
        Cookie::init(LAZYConfig::get('cookie'));
    }

    /**
     * 加载配置文件
     * @return [type] [description]
     */
    public static function load($config){
        if(gettype($config) != gettype([])) return;
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
