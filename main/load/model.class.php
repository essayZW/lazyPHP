<?php

/**
 * model文件，提供对数据库的基本操作
 */

namespace lazy\model;
class Model extends \lazy\DB\MysqlDB{

    public function __construct(){
        $this->tableName = (new \ReflectionClass(get_called_class()))->getShortName();      //设置默认表名
    }
}
