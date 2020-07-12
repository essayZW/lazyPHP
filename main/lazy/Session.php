<?php
namespace lazy{
    class Session{
        /**
         * 开启session会话
         *
         * @return void
         */
        public static function start(){
            if(!isset($_SESSION)){
                session_start();
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
            if(isset($_SESSION))
                session_destroy();
        }
    }
}
