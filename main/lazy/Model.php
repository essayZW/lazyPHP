<?php


namespace lazy;
class Model extends DB\MysqlDB{

    public function __construct(){
        //加载配置
        $this->load(require(__DATABASE_CONFIG__));
        $path = __APP_PATH__ . '\\' . \lazy\Request::module() . '\\database.php';
        $path = str_replace('\\', '/', $path);      
        if(\file_exists($path)){
            $this->load(require($path));
        }
        $this->tableName = (new \ReflectionClass(get_called_class()))->getShortName();      //设置默认表名
    }
}
