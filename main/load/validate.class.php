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
    
    private $errorMsg;          // 上次验证的错误信息
    private $isBatch;           // 是否批量验证

    private $extendMethod;
    /**
     * 初始化，可以传入验证数据的规则
     *
     * @param array $rule
     */
    public function __construct($rule = []){
        $this->ruleList = $rule;
        $this->errorMsg = [];
        $this->msgList = [];
        $this->isBatch = false;     // 默认不批量验证
        $this->extendMethod = [];
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
    private function setMsg($name, $method){
        $str = $name . '.' . $method;
        if(isset($this->msgList[$str])){
            $this->errorMsg = array_merge($this->errorMsg, [$str => $this->msgList[$str]]);
            return;
        }
        // 没有设置具体的变量的错误信息，但是设置了统一的函数信息
        if(isset($this->msgList[$method])){
            $this->errorMsg = array_merge($this->errorMsg, [$method => $this->msgList[$method]]);
            return;
        }
    }

    public function extend($name, $callback){
        $this->extendMethod = array_merge($this->extendMethod, [
            $name => $callback
        ]);
    }
    /**
     * 返回错误信息，若参数是true返回所有的参数信息
     *
     * @param boolean $isArray
     * @return mixed
     */
    public function getErrorMsg($isArray = false){
        if($isArray){
            return $this->errorMsg;
        }
        foreach ($this->errorMsg as $value) {
            return $value;
        }
        return '';
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
    private function checkSingle($name, $v){
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
            if(gettype($value) == gettype('')){
                $value = explode(':', $value);
                if(isset($value[1])){
                    $param = explode(',', $value[1]);
                    unset($value[1]);
                    $value = array_merge($value, $param);
                }
            }
            if(key_exists($value[0], $this->extendMethod)){
                $callMethod = $this->extendMethod[$value[0]];
            }
            else if(method_exists($this, $value[0])){
                $callMethod = array($this, $value[0]);
            }
            else{
                // 验证方法不存在
                trigger_error('Method not exists', E_USER_NOTICE);
            }
            $param = array_merge([$v], array_slice($value, 1, count($value) - 1));
            if(!call_user_func_array($callMethod, $param)){
                $this->setMsg($name, $value[0]);
                return false;
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
        $this->isBatch = false;
        return $flag;
    }


    // 负责验证的一些基础函数
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
    
    
    public function require($value){
        return $value == true;
    }

    public function lenmax($value, $max){
        return strlen($value) <= $max;
    }

    public function lenmin($value, $min){
        return strlen($value) >= $min;
    }
 }