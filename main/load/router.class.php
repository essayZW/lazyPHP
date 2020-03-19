<?php
/**
 * 路由的操作类
 */

namespace lazy\router;

class Router{
    static private $list = [];     //路由列表

    /**
     * 导入新的路由列表
     * @param  array  $arr 新的路由列表的数组
     * @return integer     新的列表的条目数目
     */
    public static function importFromArray($arr = []){
        self::$list = array_merge(self::$list, $arr);
        return count(self::$list);
    }

    /**
     * 设定指定规则的支持的方法
     * @param string $key    [description]
     * @param string $method [description]
     */
    public static function setMethod($key = '', $method = ''){
        if(gettype($method) == gettype('')){
            $method = [$method];
        }
        if(array_key_exists($key, self::$list)){
            if(gettype(self::$list[$key]) == gettype('')){
                self::$list[$key] = [self::$list[$key]];
            }
            foreach ($method as $k => $v) {
                if(!in_array($v, self::$list[$key])){
                    array_push(self::$list[$key], strtoupper($v));
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
    public static function bind($rule = [], $val = ''){
        if(gettype($rule) == gettype('')){
            $rule = [$rule => $val];
        }
        $num = 0;
        foreach ($rule as $key => $value) {
            if(array_key_exists(strtolower($key), self::$list)){
                continue;
            }
            self::$list[strtolower($key)] = $value;
            $num ++;
        }
        return $num;
    }

    /**
     * 添加支持get请求的规则
     * @param  array  $rule 数组形式的路由规则，包含一个键以及一个值,可以有多条记录
     * @return integer      返回添加成功的条数
     */
    public  static function get($rule = [], $val = ''){
        if(gettype($rule) == gettype('')){
            $rule = [$rule => $val];
        }
        $num = 0;
        foreach ($rule as $key => $value) {
            self::bind([$key => $value]);
            self::setMethod($key, 'GET');
        }
        return $num;
    }

    /**
     * 添加支持post请求的规则
     * @param  array  $rule 数组形式的路由规则，包含一个键以及一个值,可以有多条记录
     * @return integer      返回添加成功的条数
     */
    public static function post($rule = [], $val = ''){
        if(gettype($rule) == gettype('')){
            $rule = [$rule => $val];
        }
        $num = 0;
        foreach ($rule as $key => $value) {
            self::bind([$key => $value]);
            self::setMethod($key, 'POST');
        }
        return $num;
    }


    /**
     * 添加支持delete请求的规则
     * @param  array  $rule 数组形式的路由规则，包含一个键以及一个值,可以有多条记录
     * @return integer      返回添加成功的条数
     */
    public static function delete($rule = [], $val = ''){
        if(gettype($rule) == gettype('')){
            $rule = [$rule => $val];
        }
        $num = 0;
        foreach ($rule as $key => $value) {
            self::bind([$key => $value]);
            self::setMethod($key, 'DELETE');
        }
        return $num;
    }

    /**
     * 添加支持put请求的规则
     * @param  array  $rule 数组形式的路由规则，包含一个键以及一个值,可以有多条记录
     * @return integer      返回添加成功的条数
     */
    public static function put($rule = [], $val = ''){
        if(gettype($rule) == gettype('')){
            $rule = [$rule => $val];
        }
        $num = 0;
        foreach ($rule as $key => $value) {
            self::bind([$key => $value]);
            self::setMethod($key, 'PUT');
        }
        return $num;
    }

    /**
     * 返回列表
     * @return [type] [description]
     */
    public static function showList(){
        return self::$list;
    }

    /**
     * 得到制定的一条路由规则的值
     * @param  [type] $name [description]
     * @return [type]       [description]
     */
    private static function getListValue($name){
        if(!array_key_exists($name, self::$list)) return false;
        $res = self::$list[$name];
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
    public static function getRule($key = ''){
        $flag = 0;
        foreach (self::$list as $k => $v) {
            if(preg_match('/^\/(.+)\/$/', $k)){
                // 正则表达式模式
                if(preg_match($k, $key)){
                    $res = preg_replace($k, self::getListValue($k), $key);
                    $flag = 1;
                    break;
                }
            }
            else{
                // 普通匹配模式
                if($k == $key){
                    $res = self::getListValue($k);
                    $flag = 1;
                    break;
                }
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
    public static function getMethod($key = ''){
        $flag = 0;
        foreach (self::$list as $k => $v) {
            if(preg_match('/^\/(.+)\/$/', $k)){
                if(preg_match($k, $key)){
                    $flag = 1;
                    $key = $k;
                    break;
                }
            }
            else{
                if($k == $key){
                    $flag = 1;
                    $key = $k;
                    break;
                }
            }
        }
        if(!$flag){
            return false;
        }
        if(gettype(self::$list[$key]) == gettype('')){
            return 'ALL';
        }
        $res = [];
        for($i = 1; $i < count(self::$list[$key]); $i ++){
            array_push($res, strtoupper(self::$list[$key][$i]));
        }
        return $res;
    }
}
