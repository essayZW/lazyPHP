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
}
