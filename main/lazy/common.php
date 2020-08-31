<?php

/**
 * 常用的核心函数文件
 */

namespace lazy{

    /**
     * 引入制定文件夹下的所有PHP文件
     * @param  string $path 文件所在目录
     * @param  rely   $rely 导入文件所需的文件依赖
     * @return [type]       [description]
     */
    function requireAllFileFromDir($path, $rely = []){
        if(!file_exists($path)){
            return false;
        }
        // 打开文件夹
        $handler = opendir($path);
        // 遍历脚本文件夹下的所有文件
        while((($filename = readdir($handler)) !== false)){
            //如果文件为php脚本文件
            if(substr($filename,-4) == '.php' ){
                //检查依赖
                $flag = 0;
                foreach ($rely as $key => $value) {
                    if($value == $filename){
                        $flag = 1;
                        //找到依赖项
                        //尝试先引入依赖
                        if(file_exists($path . $key)){
                            require_once($path . $key);
                            $flag = 2;      //表示有依赖且已经引入依赖
                        }
                        else{
                            $flag = 1;      //表明有依赖但是依赖不满足
                        }
                    }
                }
                if($flag == 1){
                    //依赖未满足
                    continue;
                }
                //将文件包含进来
                require_once($path . $filename);
            }
        }
        // 关闭文件夹
        closedir($handler);
    }
    

    /**
     * 获得相对路径, 得到b相对于a的相对路径
     */
    function getRelativelyPath($a, $b)
    {
        $a = \str_replace('\\', '/', $a);
        $b = \str_replace('\\', '/', $b);
        $a = \str_replace(':', '', $a);
        $b = \str_replace(':', '', $b);
        $a = \str_replace('//', '/', $a);
        $b = \str_replace('//', '/', $b);
        if($a{0} != '/'){
            $a = '/' . $a;
        }
        if($b{0} != '/'){
            $b = '/' . $b;
        }
        $a = explode('/', $a);
        $b = explode('/', $b);
        $c = array_values(array_diff($a, $b));
        $d = array_values(array_diff($b, $a));
        array_pop($c);
        foreach ($c as &$v) {
            $v = '..';
        }
        $arr = array_merge($c, $d);
        $str = implode("/", $arr);
        return $str;
    }

    /**
     * 转化路径为当前系统的正确的路径
     *
     * @param string $path
     * @return string
     */
    function changeFilePath($path) {
        if(DIRECTORY_SEPARATOR == '/') {
            $path = \str_replace('\\', '/', $path);
            $path = preg_replace('/\/{2,}/', '/', $path);
        }
        else if(DIRECTORY_SEPARATOR == '\\'){
            $path = str_replace('/', '\\', $path);
            $path = preg_replace('/\\\{2,}/', '\\', $path);
        }
        return $path;
    }

    trait fileOperation{
        /**
         * 删除文件夹及其下面所有的文件
         *
         * @param [type] $dir
         * @return void
         */
        public static function deldir($dir) {
            //先删除目录下的文件：
            $dh = opendir($dir);
            while ($file = readdir($dh)) {
                if($file != "." && $file!="..") {
                    $fullpath = $dir."/".$file;
                    if(!is_dir($fullpath)) {
                        unlink($fullpath);
                    } else {
                        self::deldir($fullpath);
                    }
                }
            }
            closedir($dh);
            
            //删除当前文件夹：
            if(rmdir($dir)) {
                return true;
            } else {
                return false;
            }

        }
    }


    trait logMethod{
        /**
         * 系统error log接口
         *
         * @param [type] $info
         * @param string $type
         * @return void
         */
        protected function errorLog($error_no, $error_msg, $error_file, $error_line){
            if($error_no == E_ERROR || $error_no == E_USER_ERROR){
                Log::error($error_msg. ' in '. $error_file. ' on line '. $error_line);
            }
            else if($error_no == E_WARNING || $error_no == E_USER_WARNING){
                Log::warn($error_msg. ' in '. $error_file. ' on line '. $error_line);
            }
            else if($error_no == E_NOTICE || $error_no == E_USER_NOTICE){
                Log::notice($error_msg. ' in '. $error_file. ' on line '. $error_line);
            }
            else{
                Log::log($error_msg. ' in '. $error_file. ' on line '. $error_line);
            }
            // 日志写入内存
            Log::save();
            Log::line();
        }
        /**
         * 系统数据库日志记录接口
         *
         * @param [type] $sql
         * @return void
         */
        protected function sqlLog($sql, $data = []){
            if($data){
                Log::sql('[prepare] ' . $sql . "\r\n" . var_export($data, true));
            }
            else{
                Log::sql($sql);
            }
        }
    }

    /**
     * 继承原有的ReflectionFunction
     * 新增方法：
     *      1.根据函数的参数列表匹配参数调用
     */
    class PHPCodeMethod extends \ReflectionMethod{


        /**
         * 对指定函数调用，并匹配对应的参数值
         * @param  array  $params       需要赋值的参数列表
         * @return [type]               [description]
         */
        public function callMethod($params = [], $class){
            $paramList = $this->getParameters();
            $res = array();
            foreach ($paramList as $key => $value) {
                if(array_key_exists($value->name, $params)){
                    $res[$key] = $params[$value->name];
                }
                else{
                    $res[$key] = $value->getDefaultValue();
                }
            }
            return call_user_func_array(array($class, $this->name), $res);
        }
    }

    /**
     * 继承class的反射
     * 新增方法:
     *      1.得到制定类中的
     */
    class PHPCodeClass extends \ReflectionClass{
        /**
         * 得到指定类中的所有public属性以及其值
         * @param  object $object    类名
         * @param  array  $uninclude 排除某些属性
         * @return array            包含所用公用属性的数组
         */
        public static function getAllProtype($object, $uninclude = []){
            $arr = get_object_vars($object);
            foreach ($arr as $key => $value) {
                if(array_search($key, $uninclude) !== false){
                    unset($arr[$key]);
                }
            }
            return $arr;
        }
    }
    
}
