<?php

/**
 * model文件，提供对数据库的基本操作
 */

namespace lazy\model;
class Model extends \lazy\DB\MysqlDB{

    public function __construct(){
        $this->tableName = (new \ReflectionClass(get_called_class()))->getShortName();      //设置表名
    }

    /**
     * 得到指定类中的所有public属性以及其值
     * @param  array  $uninclude 排除某些属性
     * @return [type]            [description]
     */
    protected function getAllProtype($uninclude = []){
        $arr = get_object_vars($this);
        foreach ($arr as $key => $value) {
            if(array_search($key, $uninclude) !== false){
                unset($arr[$key]);
            }
        }
        return $arr;
    }

}
