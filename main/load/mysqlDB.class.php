<?php
/**
 * 提供MySQL数据库的基本的增删改查功能
 * version:1.0
 * UPdate Info:
 *      1.数据库的基本增删改查
 *      2.采用预处理模式执行，防止SQL注入
 *      3.提供用户自定义的SQL语句以及SQL模板运行
 *      4.未对联表查询以及别名支持
 */
namespace lazy\DB;
class MysqlDB{
    private $config;            // 配置数组
    private $hostname;          // 服务器名
    private $database;          // 数据库名
    private $username;          // 用户名
    private $password;          // 密码
    private $hostport;          // 端口

    private $con;                       // mysql连接句柄
    private $isCon;                     // MySQL是否连接
    private $affectedRowNum = 0;        // 受影响行数

    // SQL数据类型的定义
    private $typeList = [
        'string'  => 's',
        'double'  => 'd',
        'integer' => 'i',
        'boolean' => 'b'
    ];

    /**
     * 导入数据库配置
     * @param  array  $config 配置数组
     * @return [type]         [description]
     */
    public function load($config){
        $this->config = $config;
        if(isset($config['hostname']))
            $this->hostname = $config['hostname'];
        if(isset($config['database']))
            $this->database = $config['database'];
        if(isset($config['username']))
            $this->username = $config['username'];
        if(isset($config['password']))
            $this->password = $config['password'];
        if(isset($config['hostport']))
            $this->hostport = $config['hostport'];
    }

    /**
     * 连接数据库
     * @param  array  $config 可选，可以覆盖掉当前的一些配置
     * @return [type]         [description]
     */
    public function connect($config = []){
        if($this->isCon) return $this;
        $hostname = isset($config['hostname']) ? $config['hostname'] : $this->hostname;
        $database = isset($config['database']) ? $config['database'] : $this->database;
        $username = isset($config['username']) ? $config['username'] : $this->username;
        $password = isset($config['password']) ? $config['password'] : $this->password;
        $hostport = isset($config['hostport']) ? $config['hostport'] : $this->hostport;
        // 开始连接
        $con = mysqli_connect($hostname, $username, $password, $database, $hostport);
        $this->con = $con ? $con : false;
        if($this->con){
            $this->isCon = true;
        }
        else{
            $this->isCon = false;
        }
        return $this;
    }

    /**
     * 关闭一个数据库连接
     * @return [type] [description]
     */
    public function close(){
        if(!$this->isCon) return $this;
        $this->isCon = false;
        mysqli_close($this->con);
        $this->prepareValueArr = [];
    }
    /**
     * 拼装SQL模板
     * @param  string $type  SQL语句类型
     * @return [type] [description]
     */
    public function buildTemplate($type){
        $type = strtoupper($type);
        // 初始化sql语句
        $this->sql = '';
        if($type == 'SELECT'){
            // 选择语句类型
            $this->sql = "SELECT $this->sqlVolumn FROM $this->tableName" .
                        $this->getWhereSql() .
                        "$this->sqlSort $this->sqlLimit";
            // 预处理sql语句
            return $this->assign($this->sql, $this->prepareValueArr);
        }
        else if($type == 'INSERT'){
            // 插入语句类型
            $this->sql = "INSERT INTO $this->tableName $this->sqlInsertField VALUES $this->sqlInsertValue";
            //预处理
            return $this->assign($this->sql, $this->prepareValueArr);
        }
        else if($type == 'DELETE'){
            // 删除语句类型
            $this->sql = "DELETE FROM $this->tableName" . $this->getWhereSql();
            // 预处理
            return $this->assign($this->sql, $this->prepareValueArr);
        }
        else if($type == 'UPDATE'){
            // 更新语句类型
            $this->sql = "UPDATE $this->tableName SET $this->sqlUpdateField ". $this->getWhereSql();
            // 预处理
            return $this->assign($this->sql, $this->prepareValueArr);
        }
    }

    /**
     * 预处理sql语句
     * @return [type] [description]
     */
    public function assign($sql_template, $dataArr){
        $stmt = mysqli_prepare($this->con, $sql_template);
        if(!$stmt){
            // sql预处理错误
            trigger_error('SQL Prepare Error!', E_USER_WARNING);
        }
        $type = '';
        foreach ($dataArr as $key => $value) {
            $type .= $this->typeList[gettype($value)];
        }
        if(count($dataArr)){
            mysqli_stmt_bind_param($stmt, $type, ...$dataArr);
        }
        return $stmt;
    }

    /**
     * 运行准备好的模板
     * @param  [type] $stmt [description]
     * @return [type]       [description]
     */
    public function execute($stmt){
        //运行
        $res = mysqli_stmt_execute($stmt);
        //取回结果
        $stmt_res = mysqli_stmt_get_result($stmt);
        if(gettype($stmt_res) != gettype(true)){
            //保存结果
            $res = [];
            //已经有的条数
            while($row = mysqli_fetch_assoc($stmt_res)){
                $res = array_merge($res, [$row]);
            }
        }
        // 更新受影响行数
        $this->affectedRowNum = mysqli_affected_rows($this->con);
        mysqli_stmt_close($stmt);
        return $res;
    }

    /**
     * 以预处理形式运行SQL语句
     * @param  string $sql  SQL语句
     * @param  array  $data 数据
     * @return [type]       [description]
     */
    public function prepareAndExecute($sql, $data = []){
        //连接
        $this->connect();
        //预处理
        $stmt = $this->assign($sql, $data);
        //运行得到结果
        $res = $this->execute($stmt);
        //关闭
        $this->close();
        return $res;
    }

    /**
     * 执行任意sql语句并返回结果
     * @param  string $sql sql语句
     * @return [type]      [description]
     */
    public function query($sql){
        //连接
        $this->connect();
        $mysqli_res = mysqli_query($this->con, $sql);
        if(gettype($mysqli_res) == gettype(true)){
            $res = $mysqli_res;
        }
        else{
            $res = [];
            while($row = mysqli_fetch_array($mysqli_res)){
                $res = array_merge($res, [$row]);
            }
        }
        $this->affectedRowNum = mysqli_affected_rows($this->con);
        //关闭
        $this->close();
        return $res;
    }

    /**
     * 得到上次SQL语句的受影响条数
     * @return [type] [description]
     */
    public function affectedRows(){
        return $this->affectedRowNum;
    }
    /**
     * 得到where子句
     * @return [type] [description]
     */
    private function getWhereSql(){
        // 先连接所有的and语句
        $andRes = implode(' AND ', $this->sqlAndRanger);
        // 连接所有的OR语句
        $orRes = implode(' OR ', $this->sqlOrRanger);
        //清空
        $this->sqlOrRanger = $this->sqlAndRanger = [];
        if($andRes == '' && $orRes == '') return '';
        if($andRes == '') return ' WHERE ' . $orRes . ' ';
        if($orRes == '') return ' WHERE ' .$andRes . ' ';
        // 连接所有语句并返回
        return ' WHERE ' . implode(' OR ', array($andRes, $orRes)) . ' ';
    }

    /**
     * 选择数据表
     * @param  string $table_name 数据表名称
     * @return [type]             [description]
     */
    public function table($table_name){
        $this->tableName = $table_name;
        return $this;
    }

    // SQL语句
    private $sql = '';              //存储拼装的sql语句
    // SQL语句相关组成变量
    public $tableName = '';         //数据表名称, 可以在子类中更改
    private $sqlVolumn = '';        //数据库查询语句字段名
    private $sqlAndRanger = [];     //限定数据库的影响范围,且连接
    private $sqlOrRanger = [];      //限定数据库的影响范围,或连接
    private $sqlSort = '';          //数据排序方式
    private $sqlLimit = '';         //数据范围
    //SQL模板插槽对应的数组
    private $prepareValueArr = [];


    /**
     * 处理字段名，防止关键字冲突
     * @param  [type] $name [description]
     * @return [type]       [description]
     */
    private function fieldConcersion($name){
        $name = str_replace(' ', '', $name);
        if(preg_match('/`(.*?)`/', $name)){
            return $name;
        }
        if(preg_match('/^(\w*?)\(.+?\)/', $name)){
            return $name;
        }
        return '`'. $name. '`';
    }
    /**
     * 设置SQL语句查询的字段名
     * @param  mixed $volumn [description]
     * @return [type]         [description]
     */
    public function field($volumn){
        if(gettype($volumn) == gettype('')){
            $volumn = array($volumn);
        }
        // 对关键字处理
        $volumn = array_map(function($values){
            return $this->fieldConcersion($values);
        }, $volumn);
        $this->sqlVolumn = implode(',', $volumn);
        return $this;
    }


    /**
     * 限制条件范围，与其他范围为且关系
     * @param  string  $left  字段名
     * @param  string $symbol 条件符号
     * @param  string $right  查询的条件
     * @return [type]         [description]
     */
    public function where($left, $symbol, $right){
        // 对字段名进行关键字处理
        $left = $this->fieldConcersion($left);
        // 放入and数组
        array_push($this->sqlAndRanger, "$left$symbol?");
        //放入插槽数组
        array_push($this->prepareValueArr, $right);
        return $this;
    }

    /**
     * 限制范围条件，与其他条件为或关系
     * @param  string $left   [description]
     * @param  string $symbol [description]
     * @param  string $right  [description]
     * @return [type]         [description]
     */
    public function whereOr($left, $symbol, $right){
        // 对字段名进行关键字处理
        $left = $this->fieldConcersion($left);
        // 放入or数组
        array_push($this->sqlOrRanger, "$left$symbol?");
        //放入插槽数组
        array_push($this->prepareValueArr, $right);
        return $this;
    }

    /**
     * 限定查询结果的排序方式,默认升序
     * @param  string $volumn 排序依据的字段名
     * @param  string $type   排序方式，默认升序
     * @return [type]         [description]
     */
    public function order($volumn, $type = 'ASC'){
        $volumn = $this->fieldConcersion($volumn);
        $this->sqlSort = " ORDER BY $volumn $type ";
        return $this;
    }

    /**
     * 限制查询范围
     * @param  [type] $offset [description]
     * @param  [type] $start  [description]
     * @return [type]         [description]
     */
    public function limit($offset, $start = 0){
        $this->sqlLimit = " LIMIT $offset OFFSET $start ";
        return $this;
    }

    /**
     * 根据现有的sql语句查询结果，以数组形式返回
     * @return array 查询的结构集
     */
    public function select($maxnum = -1){
        //连接数据库
        $this->connect();
        //构造sql模板
        $stmt = $this->buildTemplate('select');
        //运行
        $res = $this->execute($stmt);
        //关闭连接
        $this->close();
        if(count($res) == 0) return false;
        $arr = [];
        $num = 0;
        foreach ($res as $key => $value) {
            $arr = array_merge($arr, [$res[$key]]);
            $num ++;
            if($num >= $maxnum && $maxnum != -1) break;
        }
        return $arr;
    }

    /**
     * 选择一条数据
     * @return [type] [description]
     */
    public function find(){
        $res = $this->select(1);
        if($res) return $res[0];
        return false;
    }

    private $sqlInsertField = '';       //插入的数据的字段部分
    private $sqlInsertValue = '';       //插入的数据的新的值
    /**
     * 像数据库中插入表
     * @param mixed $key 若是字符串，表示字段名，则$value是其插入的值,如果是数组，则键是字段，值是插入的值
     * @return [type] [description]
     */
    public function insert($key, $value = ''){
        if(gettype($key) != gettype([])){
            $key = [$key => $value];
        }
        $field = array();   //作用字段
        $newValue = array();    //新的值
        $this->prepareValueArr = [];
        foreach ($key as $k => $v) {
            array_push($field, $this->fieldConcersion($k));
            array_push($newValue, '?');
            //放入预处理数组
            array_push($this->prepareValueArr, $v);
        }
        //拼装
        $this->sqlInsertField = ' (' . implode(', ', $field) . ') ';
        $this->sqlInsertValue = ' (' . implode(', ', $newValue) . ') ';
        //开始准备模板，预处理
        $this->connect();
        //构建SQL语句并预先处理
        $stmt = $this->buildTemplate('insert');
        //赋值并得到结果
        $res = $this->execute($stmt);
        //关闭
        $this->close();
        return $this->affectedRowNum > 0;
    }

    /**
     * 删除指定的数据
     * @return [type] [description]
     */
    public function delete(){
        // 连接
        $this->connect();
        // 准备预处理模板
        $stmt = $this->buildTemplate('delete');
        // 运行
        $res = $this->execute($stmt);
        // 关闭
        $this->close();
        return $this->affectedRowNum > 0;
    }

    private $sqlUpdateField = '';
    /**
     * 更新数据
     * @param  mixed  $name  若是字符串，则代表更新数据的字段名，数组的话，键为字段名，值为新值
     * @param  string $value [description]
     * @return [type]        [description]
     */
    public function update($name, $value = ''){
        if(gettype($name) == gettype('')){
            $name = [$name => $value];
        }
        // 清空
        $this->sqlUpdateField = '';
        // 开始拼接sql块
        $arr = [];
        foreach ($name as $k => $v) {
            array_push($arr, $v);
            $name[$k] = $this->fieldConcersion($k) . '=?';
        }
        // 拼接
        $this->sqlUpdateField = implode(', ', $name);
        // 导入数据
        $this->prepareValueArr = array_merge($arr, $this->prepareValueArr);
        // 连接
        $this->connect();
        // 准备预处理模板
        $stmt = $this->buildTemplate('update');
        // 运行得到结果
        $res = $this->execute($stmt);
        // 关闭
        $this->close();
        return $this->affectedRowNum > 0;
    }

    /**
     * 得到上次操作的SQL语句
     * @return [type] [description]
     */
    public function getSql(){
        return $this->sql;
    }

    /**
     * 得到表中的主键
     * @return [type] [description]
     */
    public function getPrimaryKey($tableName){
        $res = $this->field('column_name')
                    ->table('INFORMATION_SCHEMA.`KEY_COLUMN_USAGE`')
                    ->where('table_name', '=', $tableName)
                    ->where('CONSTRAINT_SCHEMA', '=', $this->database)
                    ->where('constraint_name', '=', 'PRIMARY')
                    ->find();
        return isset($res['column_name']) ? $res['column_name'] : '';
    }
}
