<?php
/**
 * 关于网络请求参数表单的获取操作的类
 * version: 1.4.1
 * Update Info:
 *      1.新增获取host,url,referer信息
 *      2.修改get,post,files函数，可以获取指定值
 *      3.新增存储URL中的pathinfo参数
 */
namespace lazy\request;
class Request{
    public static $pathParamStr;

    public static $module;
    public static $controller;
    public static $method;

    public static $rmodule;
    public static $rcontroller;
    public static $rmethod;
    /**
     * 返回本次请求的请求方法
     * @return string 请求方法，若没有则返回false
     */
    public static function getMethod(){
        if(isset($_SERVER['REQUEST_METHOD'])){
            return $_SERVER['REQUEST_METHOD'];
        }
        return false;
    }


    /**
     * 判断请求方法是否在支持列表中
     * @param  string  $method 请求方法
     * @param  mixed  $list   支持的列表
     * @return boolean         [description]
     */
    public static function isExists($method, $list = 'ALL'){
        if(gettype($list) == gettype('')){
            $list = [$list];
        }
        if(in_array('ALL', $list)){
            //支持所有的方法
            return true;
        }
        return in_array(strtoupper($method), $list);
    }

    /**
     * 得到所有的get数据
     * @param mixed $name 需要得到的具体数据名
     * @return mixed get表单
     */
    public static function get($name = false){
        if($name != false){
            return isset($_GET[$name]) ? $_GET[$name] : false;
        }
        return $_GET;
    }

    /**
     * 得到所有的post数据
     * @return mixed post表单
     */
    public static function post($name = false){
        if($name != false){
            return isset($_POST[$name]) ? $_POST[$name] : false;
        }
        return $_POST;
    }

    /**
     * 得到所有的文件表单信息
     * @return mixed [description]
     */
    public static function files($name = false){
        if($name != false){
            return isset($_FILES[$name]) ? $_FILES[$name] : false;
        }
        return $_FILES;
    }

    /**
     * 得到所有的表单信息
     * @return array [description]
     */
    public static function params(){
        return array_merge(self::get(), self::post(), self::files());
    }

    /**
     * 获得制定名字的参数值
     * @param  string $name [description]
     * @return [type]       [description]
     */
    public static function param($name = ''){
        if(!array_key_exists($name, self::params())){
            return false;
        }
        return self::params()[$name];
    }
    /**
     * 判断指定方法的表单名是否存在
     * @param  string  $name   表单名
     * @param  string  $method 方法名
     * @return boolean         [description]
     */
    public static function has($name = '', $method = ''){
        $method = strtoupper($method);
        if(!array_key_exists($name, self::params())){
            return false;
        }
        if($method == 'GET'){
            if(!array_key_exists($name, self::get())){
                return false;
            }
        }
        if($method == 'POST'){
            if(!array_key_exists($name, self::post())){
                return false;
            }
        }
        return true;
    }

    /**
     * 得到请求的url
     * @return [type] [description]
     */
    public static function url(){
        return $_SERVER['PHP_SELF'];
    }
    /**
     * 得到请求的host
     * @return [type] [description]
     */
    public static function host(){
        return $_SERVER['HTTP_HOST'];
    }

    /**
     * 获得请求的查询参数
     */
    public static function query(){
        return $_SERVER['QUERY_STRING'];
    }
    /**
     * 得到pathinfo中的信息
     * @return [type] [description]
     */
    public static function pathParam(){
        return self::$pathParamStr;
    }

    /**
     * 得到请求的ueer-agent头
     * @return [type] [description]
     */
    public static function referer(){
        return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : false;
    }
    /**
     * 判断是不是IP地址
     *
     * @param [type] $str
     * @return boolean
     */
    public static function is_ip($str){
        $ip=explode('.',$str);
        for($i=0;$i<count($ip);$i++){  
            if($ip[$i]>255){  
                return false;  
            }  
        }  
        return preg_match('/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/',$str);  
    }
    /**
     * 得到请求者的IP地址
     *
     * @return void
     */
    public static function ip(){
        $ip = 'Unknowm IP Address!';
        if(!empty($_SERVER['HTTP_CLIENT_IP'])){
            return self::is_ip($_SERVER['HTTP_CLIENT_IP'])?$_SERVER['HTTP_CLIENT_IP']:$ip;
        }elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
            return self::is_ip($_SERVER['HTTP_X_FORWARDED_FOR'])?$_SERVER['HTTP_X_FORWARDED_FOR']:$ip;
        }else{
            return self::is_ip($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:$ip;
        }
    }

    /**
     * 得到网站的根目录,服务器绝对路径
     */
    public static function wwwroot(){
        return $_SERVER['DOCUMENT_ROOT'];
    }
    /**
     * 本次请求的模块
     *
     * @return void
     */
    public static function module($flag = true){
        if(!$flag) return self::$rmodule;
        return self::$module;
    }

    
    public static function controller($flag = true){
        if(!$flag) return self::$rcontroller;
        return self::$controller;
    }
    public static function method($flag = true){
        if(!$flag) return self::$rmethod;
        return self::$method;
    }
    /**
     * 得到请求头
     * @return array 含有请求头的信息
     */
    public static function getRequestHeads(){
        //来源于：https://blog.csdn.net/Zhihua_W/article/details/79259319
        // 忽略获取的header数据。这个函数后面会用到。主要是起过滤作用
        $ignore = array('host','accept','content-length','content-type');
        $headers = array();

        foreach($_SERVER as $key=>$value){
            if(substr($key, 0, 5)==='HTTP_'){
                //这里取到的都是'http_'开头的数据。
                //前去开头的前5位
                $key = substr($key, 5);
                //把$key中的'_'下划线都替换为空字符串
                $key = str_replace('_', ' ', $key);
                //再把$key中的空字符串替换成‘-’
                $key = str_replace(' ', '-', $key);
                //把$key中的所有字符转换为小写
                $key = strtolower($key);

                //这里主要是过滤上面写的$ignore数组中的数据
                if(!in_array($key, $ignore)){
                    $headers[$key] = $value;
                }
            }
        }
        //输出获取到的header
        return $headers;
    }

    /**
     * 得到某一个请求头的值
     * @param  string $name 请求头的名称
     * @return [type]       [description]
     */
    public static function getRequestHead($name){
        if($name == '') return false;
        $res = self::getRequestHeads();
        if(array_key_exists(strtolower($name), $res)){
            return $res[strtolower($name)];
        }
        return false;
    }

    /**
     * 得到pathinfo信息
     * @return string pathinfo信息
     */
    public static function path(){
        if(isset($_SERVER['PATH_INFO'])){
            $pathInfo = $_SERVER['PATH_INFO'];
        }else if(isset($_REQUEST['PATH_INFO'])){
            $pathInfo = $_REQUEST['PATH_INFO'];
        }
        else{
            $pathInfo = '/';
        }
        return $pathInfo;
    }
    /**
     * 判断是不是手机端的请求
     * @return boolean [description]
     */
    public static function isMobile(){
        //来源于：https://blog.csdn.net/misakaqunianxiatian/article/details/52193356
        // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
        if (isset ($_SERVER['HTTP_X_WAP_PROFILE'])){
            return TRUE;
        }
        // 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
        if (isset ($_SERVER['HTTP_VIA'])){
            return stristr($_SERVER['HTTP_VIA'], "wap") ? TRUE : FALSE;// 找不到为flase,否则为TRUE
        }
        // 判断手机发送的客户端标志,兼容性有待提高
        if (isset ($_SERVER['HTTP_USER_AGENT'])) {
            $clientkeywords = array (
                'mobile',
                'nokia',
                'sony',
                'ericsson',
                'mot',
                'samsung',
                'htc',
                'sgh',
                'lg',
                'sharp',
                'sie-',
                'philips',
                'panasonic',
                'alcatel',
                'lenovo',
                'iphone',
                'ipod',
                'blackberry',
                'meizu',
                'android',
                'netfront',
                'symbian',
                'ucweb',
                'windowsce',
                'palm',
                'operamini',
                'operamobi',
                'openwave',
                'nexusone',
                'cldc',
                'midp',
                'wap'
                );
            // 从HTTP_USER_AGENT中查找手机浏览器的关键字
            if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))){
                return TRUE;
            }
        }
        if (isset ($_SERVER['HTTP_ACCEPT'])){ // 协议法，因为有可能不准确，放到最后判断
            // 如果只支持wml并且不支持html那一定是移动设备
            // 如果支持wml和html但是wml在html之前则是移动设备
            if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== FALSE) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === FALSE || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))){
                return TRUE;
            }
        }
        return FALSE;
    }
}
