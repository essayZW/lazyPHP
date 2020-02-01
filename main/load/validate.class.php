<?php
/**
 * 验证器，可以验证数据的合法性
 * version:1.0
 * Update Info:
 *      1.提供基本的变量验证
 *      2.可以自己自定义验证规则
 *      3.提供自定义的验证错误信息
 *      4.提供部分自带的简单验证规则
 */

 namespace lazy\validate;
 class Validate{
    private $ruleList;          // 验证规则数组
    private $msgList;
    public $methodClass;        // 存储验证方法所在的对象中
    
    private $errorMsg;          // 上次验证的错误信息
    private $isBatch;           // 是否批量验证
    /**
     * 初始化，可以传入验证数据的规则
     *
     * @param array $rule
     */
    public function __construct($rule = []){
        $this->ruleList = $rule;
        $this->methodClass = $this;
        $this->errorMsg = [];
        $this->msgList = [];
        $this->isBatch = false;     // 默认不批量验证
    }

    /**
     * 设置验证器中的验证方法所属的对象
     *
     * @param object $objectName
     * @return void
     */
    public function setObject($objectName){
        if(gettype($this) != gettype($objectName)) return;
        $this->methodClass = $objectName;
    }

    /**
     *  添加自定义错误信息
     * @param mixed $name
     * @param mixed $msg
     * @return void
     */
    public function msg($name, $msg = ''){
        if(gettype($name) == gettype('')){
            $name = [$name => $msg];
        }
        $this->msgList = array_merge($this->msgList, $name);
    }

    /**
     * 设置错误信息
     * @param string $name
     * @param string $method
     * @return void
     */
    public function setMsg($name, $method){
        $str = $name . '.' . $method;
        if(isset($this->msgList[$str])){
            $this->errorMsg = array_merge($this->errorMsg, [$this->msgList[$str]]);
            return;
        }
        // 没有设置具体的变量的错误信息，但是设置了统一的函数信息
        if(isset($this->msgList[$method])){
            $this->errorMsg = array_merge($this->errorMsg, [$this->msgList[$method]]);
            return;
        }
    }

    /**
     * 返回错误信息，若参数是true返回所有的参数信息
     *
     * @param boolean $isArray
     * @return mixed
     */
    public function getErrorMsg($isArray = false){
        $isArray = $this->isBatch;
        if($this->isBatch){
            // 之作用一次
            $this->isBatch = false;
        }
        if($isArray){
            return $this->errorMsg;
        }
        if(isset($this->errorMsg[0])){
            return $this->errorMsg[0];
        }
        else{
            return false;
        }
    }

    public function batch(){
        $this->isBatch = true;
        return $this;
    }
    /**
     * 添加一条或者多条规则
     *
     * @param mixed $ruleName
     * @param string $ruleValue
     * @return void
     */
    public function rule($ruleName, $ruleValue = ''){
        if(gettype($ruleName) == gettype('')){
            $ruleName = [$ruleName => $ruleValue];
        }
        $this->ruleList = array_merge($this->ruleList, $ruleName);
    }

    /**
     * 检查一个单组的数据
     *
     * @param string $name
     * @param mixed $value
     * @return boolean
     */
    public function checkSingle($name, $v){
        // 得到验证规则
        if(!isset($this->ruleList[$name])){
            // 不存在验证规则
            return false;
        }
        $rule = $this->ruleList[$name];
        if(gettype($rule) == gettype('')){
            $rule = explode('|', $rule);
        }
        foreach ($rule as $value) {
            if(strstr($value, ':')){
                $pattern = '/(.+):(.+)/';
                $replacement = "$1|$2";
                $str = preg_replace($pattern, $replacement, $value);
                $paramArr = explode('|', $str);
                $paramArr[1] = explode(',', $paramArr[1]);
                if(!call_user_func_array(array($this->methodClass, $paramArr[0]), array_merge([$v], $paramArr[1]))){
                    $this->setMsg($name, $paramArr[0]);
                    return false;
                }
            }
            else{
                $pattern = '/(.+)/';
                $replacement = "$1";
                $str = preg_replace($pattern, $replacement, $value);
                if(!call_user_func_array(array($this->methodClass, $str), [$v])){
                    $this->setMsg($name, $str);
                    return false;
                }
            }
        }
        return true;
    }
    /**
     * 检查传入的几组数据
     *
     * @param mixed $dataName
     * @param string $dataValue
     * @return boolean
     */
    public function check($dataName, $dataValue = ''){
        if(gettype($dataName) == gettype('')){
            $dataName = [$dataName => $dataValue];
        }
        $flag = true;
        foreach ($dataName as $key => $value) {
            if(!$this->checkSingle($key, $value)){
                $flag = false;
                if(!$this->isBatch)
                    return false;
            }
        }
        return $flag;
    }


    // 下面是负责验证的一些基础函数
    // 使用接口
    use Check;
 }


 trait Check{
     /**
     * 检查是不是整数
     *
     * @param mixed $value
     * @return boolean
     */
    public function integer($value){
        return gettype($value) == gettype(1);
    }


    /**
     * 检查一个值是不是小于等于一个值
     *
     * @param integer $value
     * @param integer $maxValue
     * @return void
     */
    public function max($value, $maxValue){
        $value = (int)$value;
        $maxValue = (int)$maxValue;
        return $value <= $maxValue;
    }

    /**
     * 检查一个值是不是大于等于一个值
     *
     * @param integer $value
     * @param integer $maxValue
     * @return void
     */
    public function min($value, $maxValue){
        $value = (int)$value;
        $maxValue = (int)$maxValue;
        return $value >= $maxValue;
    }

    /**
     * 检查一个值是不是在两个值的中间
     *
     * @param integer $value
     * @param integer $min
     * @param integer $max
     * @return void
     */
    public function between($value, $min, $max){
        $value = (int)$value;
        $min = (int)$min;
        $max = (int)$max;
        return $value >= $min && $value <= $max;
    }
    /**
     * 验证是否相等
     *
     * @param mixed $value
     * @param mixed $value2
     * @return void
     */
    public function equal($value, $value2){
        return $value == $value2;
    }

    /**
     * 验证一个字符串的长度是否在指定范围内
     *
     * @param string $str
     * @param integer $min
     * @param integer $max
     * @return void
     */
    public function lenBetween($str, $min, $max){
        $len = strlen($str);
        return $len >= $min && $len <= $max;
    }

    /**
     * 判断$str2是不是$str1的子串
     *
     * @param string $str1
     * @param string $str2
     * @return void
     */
    public function in($str1, $str2){
        return strstr($str1, $str2);
    }

    /**
     * 验证某个值不在范围内
     *
     * @param [type] $value
     * @param [type] $min
     * @param [type] $max
     * @return void
     */
    public function nowBetween($value, $min, $max){
        if($value > $max && $value < $min){
            return true;
        }
        return false;
    }

 }