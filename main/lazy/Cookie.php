<?php

namespace lazy;
class Cookie{
    static private $settings = [
        // cookie 名称前缀
        'prefix'        => '',
        // cookie 保存时间
        'expire'        => 0,
        // cookie 有效域名
        'domain'        => '',
        // cookie 保存路径
        'path'          => '/',
        // 是否启用安全传输
        'secure'        => false,
        // 是否开启httponly
        'httponly'      => false
    ];

    public static function init($settings = []) {
        foreach ($settings as $key => $value) {
            self::$settings[$key] = $value;
        }
    }
    /**
     * 设置一个cookie
     * 调用系统的setcookie函数
     * @return void
     */
    public static function set($name, $value, $time = false, $path = false, $domain = false, $secure = -1, $httponly = -1){
        $oldset = self::$settings;
        if(\gettype($time) === \gettype([])) {
            self::init($time);
            $time = false;
        }
        if($path === false) $path = self::$settings['path'];
        if($time === false) $time = self::$settings['expire'];
        $time = time() + $time;
        if($domain === false) $domain = self::$settings['domain'];
        if($secure === -1) $secure = self::$settings['secure'];
        if($httponly === -1) $httponly = self::$settings['httponly'];
        \setcookie(self::$settings['prefix'] . $name, $value, $time, $path, $domain, $secure, $httponly);
        self::$settings = $oldset;
    }

    /**
     * 得到指定的cookie
     *
     * @param string $name
     * @return void
     */
    public static function get($name){
        $name = self::$settings['prefix'] . $name;
        return isset($_COOKIE[$name]) ? $_COOKIE[$name] : null;
    }

    /**
     * 判断是否有某个cookie
     *
     * @param string $name
     * @return boolean
     */
    public static function has($name){
        $name = self::$settings['prefix'] . $name;
        return isset($_COOKIE[$name]);
    }

    /**
     * 删除一个cookie
     *
     * @param  $name
     * @return void
     */
    public static function delete($name){
        self::set($name, '', -3600);
    }

    /**
     * 删除所有的cookie
     *
     * @return void
     */
    public static function clear(){
        foreach ($_COOKIE as $key => $value) {
            self::delete($key);
        }
    }
}

