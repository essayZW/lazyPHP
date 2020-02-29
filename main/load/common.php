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
                            require_once($key);
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
     * 加载指定的第三方类库
     *
     * @return void
     */
    function Vendor($name, $objectName = '', $param = []){
        $name = str_replace('.', '/', $name) . '.php';
        log\Log::log('Extend ' . __EXTEND_PATH__ .  $name .' loaded!');
        require_once(__EXTEND_PATH__ . $name);
        if($objectName !== ''){
            log\Log::info('Extend Use Class: ' . $objectName);
            $object = new \ReflectionClass($objectName);
            $object = $object->newInstanceArgs($param);
            return $object;
        }
    }

    /**
     * 获得相对路径, 得到b相对于a的相对路径
     */
    function getRelativelyPath($a, $b)
    {
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
                log\Log::error($error_msg. ' in '. $error_file. ' on line '. $error_line);
            }
            else if($error_no == E_WARNING || $error_no == E_USER_WARNING){
                log\Log::warn($error_msg. ' in '. $error_file. ' on line '. $error_line);
            }
            else if($error_no == E_NOTICE || $error_no == E_USER_NOTICE){
                log\Log::notice($error_msg. ' in '. $error_file. ' on line '. $error_line);
            }
            else{
                log\Log::info($error_msg. ' in '. $error_file. ' on line '. $error_line);
            }
            // 日志写入内存
            log\Log::save();
            log\Log::line();
        }
        /**
         * 系统S数据库日志记录接口
         *
         * @param [type] $sql
         * @return void
         */
        protected function sqlLog($sql, $data = []){
            if($data){
                log\Log::sql('[prepare] ' . $sql . "\r\n" . var_export($data, true));
            }
            else{
                log\Log::sql($sql);
            }
        }
    }
    
}

