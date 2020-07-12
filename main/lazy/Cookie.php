<?php
/**
 * cookie以及session操作相关类
 */
namespace lazy{
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
                self::delete($key);
            }
        }
    }
}

