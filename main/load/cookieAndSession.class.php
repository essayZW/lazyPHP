<?php
/**
 * cookie以及session操作相关类
 */
namespace lazy\cookie{
    class Cookie{

        /**
         * 设置一个cookie
         * 调用系统的setcookie函数
         * @return void
         */
        public static function set(){
            call_user_func('setcookie', ...func_get_args());
        }

        /**
         * 得到指定的cookie
         *
         * @param string $name
         * @return void
         */
        public static function get($name){
            return isset($_COOKIE[$name]) ? $_COOKIE[$name] : '';
        }

        /**
         * 判断是否有某个cookie
         *
         * @param string $name
         * @return boolean
         */
        public static function has($name){
            return isset($_COOKIE[$name]);
        }

        /**
         * 删除一个cookie
         *
         * @param [type] $name
         * @return void
         */
        public static function delete($name){
            self::set($name, '', time() - 3600);
        }

        /**
         * 删除所有的cookie
         *
         * @return void
         */
        public static function clear(){
            foreach ($_COOKIE as $key => $value) {
                $this->delete($key);
            }
        }
    }
}

namespace lazy\session{
    class Session{
        static private $startFlag;

        public function __construct(){
            $this->startFlag = session_start();
        }
        /**
         * 开启session会话
         *
         * @return void
         */
        public static function start(){
            if(!self::$startFlag){
                self::$startFlag = session_start();
            }
        }
        /**
         * 设置一个session
         *
         * @param string $name
         * @param string $value
         * @return void
         */
        public static function set($name, $value){
            self::start();
            $_SESSION[$name] = $value;
        }
        /**
         * 得到一个session值
         *
         * @param [type] $name
         * @return void
         */
        public static function get($name){
            self::start();
            return isset($_SESSION[$name]) ? $_SESSION[$name] : '';
        }
        /**
         * 判断是否有一个session
         *
         * @param [type] $name
         * @return boolean
         */
        public static function has($name){
            self::start();
            return isset($_SESSION[$name]);
        }
        /**
         * 删除一个session
         *
         * @param [type] $name
         * @return void
         */
        public static function delete($name){
            self::start();
            if(isset($_SESSION[$name])){
                unset($_SESSION[$name]);
            }
        }
        /**
         * 清除所有的session
         *
         * @return void
         */
        public static function clear(){
            self::start();
            if(isset($_SESSION)){
                $_SESSION = array();
            }
        }
        /**
         *  关闭session会话
         *
         * @return void
         */
        public static function close(){
            self::start();
            if($this->startFlag)
                session_destroy();
        }
    }
}
