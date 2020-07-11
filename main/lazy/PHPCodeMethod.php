<?php
/**
 * 关于PHP源代码层面的操作，比如函数原型，代码，类原型，代码及其所属文件信息
 */

namespace lazy;


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
                $res[$key] = null;
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