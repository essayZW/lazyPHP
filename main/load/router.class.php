<?php
/**
 * 路由的操作类
 * version:1.2
 * Update Info:
 *      1.更改为正则表达式匹配路由
 */

namespace lazy\router;

class Router{
    private $list = [];     //路由列表

    /**
     * 导入新的路由列表
     * @param  array  $arr 新的路由列表的数组
     * @return integer     新的列表的条目数目
     */
    public function importFromArray($arr = []){
        $this->list = array_merge($this->list, $arr);
        return count($this->list);
    }

    /**
     * 设定指定规则的支持的方法
     * @param string $key    [description]
     * @param string $method [description]
     */
    public function setMethod($key = '', $method = ''){
        if(gettype($method) == gettype('')){
            $method = [$method];
        }
        if(array_key_exists($key, $this->list)){
            if(gettype($this->list[$key]) == gettype('')){
                $this->list[$key] = [$this->list[$key]];
            }
            foreach ($method as $k => $v) {
                if(!in_array($v, $this->list[$key])){
                    array_push($this->list[$key], strtoupper($v));
                }
            }
            return true;
        }
        return false;
    }

    /**
     * 添加一条路由规则
     * @param  array  $rule 数组形式的路由规则，包含一个键以及一个值,可以有多条记录
     * @return integer      返回添加成功的条数
     */
    public function bind($rule = []){
        $num = 0;
        foreach ($rule as $key => $value) {
            if(array_key_exists(strtolower($key), $this->list)){
                continue;
            }
            $this->list[strtolower($key)] = $value;
            $num ++;
        }
        return $num;
    }

    /**
     * 添加支持get请求的规则
     * @param  array  $rule 数组形式的路由规则，包含一个键以及一个值,可以有多条记录
     * @return integer      返回添加成功的条数
     */
    public function get($rule = []){
        $num = 0;
        foreach ($rule as $key => $value) {
            $this->bind([$key => $value]);
            $this->setMethod($key, 'GET');
        }
        return $num;
    }

    /**
     * 添加支持post请求的规则
     * @param  array  $rule 数组形式的路由规则，包含一个键以及一个值,可以有多条记录
     * @return integer      返回添加成功的条数
     */
    public function post($rule = []){
        $num = 0;
        foreach ($rule as $key => $value) {
            $this->bind([$key => $value]);
            $this->setMethod($key, 'POST');
        }
        return $num;
    }


    /**
     * 添加支持delete请求的规则
     * @param  array  $rule 数组形式的路由规则，包含一个键以及一个值,可以有多条记录
     * @return integer      返回添加成功的条数
     */
    public function delete($rule = []){
        $num = 0;
        foreach ($rule as $key => $value) {
            $this->bind([$key => $value]);
            $this->setMethod($key, 'DELETE');
        }
        return $num;
    }

    /**
     * 添加支持put请求的规则
     * @param  array  $rule 数组形式的路由规则，包含一个键以及一个值,可以有多条记录
     * @return integer      返回添加成功的条数
     */
    public function put($rule = []){
        $num = 0;
        foreach ($rule as $key => $value) {
            $this->bind([$key => $value]);
            $this->setMethod($key, 'PUT');
        }
        return $num;
    }

    /**
     * 返回列表
     * @return [type] [description]
     */
    public function showList(){
        return $this->list;
    }

    /**
     * 得到制定的一条路由规则的值
     * @param  [type] $name [description]
     * @return [type]       [description]
     */
    private function getListValue($name){
        if(!array_key_exists($name, $this->list)) return false;
        $res = $this->list[$name];
        if(gettype($res) == gettype([])){
            $res = $res[0];
        }
        return $res;
    }
    /**
     * 返回对应key的路由规则
     * @param  string 路由规则名
     * @return [type] [description]
     */
    public function getRule($key = ''){
        $flag = 0;
        foreach ($this->list as $k => $v) {
            if(preg_match($k, $key)){
                $res = preg_replace($k, $this->getListValue($k), $key);
                $flag = 1;
                break;
            }
        }
        if($flag){
            return $res;
        }
        else{
            return false;
        }
    }

    /**
     * 返回对应key规则支持的请求方法
     * @param  string $key [description]
     * @return [type]      [description]
     */
    public function getMethod($key = ''){
        $flag = 0;
        foreach ($this->list as $k => $v) {
            if(preg_match($k, $key)){
                $flag = 1;
                $key = $k;
                break;
            }
        }
        if(!$flag){
            return false;
        }
        if(gettype($this->list[$key]) == gettype('')){
            return 'ALL';
        }
        $res = [];
        for($i = 1; $i < count($this->list[$key]); $i ++){
            array_push($res, strtoupper($this->list[$key][$i]));
        }
        return $res;
    }
}
