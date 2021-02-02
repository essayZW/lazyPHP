<?php

namespace lazy;

use lazy\Exception\LAZYException;
use lazy\Exception\BaseException;

class AppDebug{
    // 使用框架提供的log接口记录日志
    use \lazy\logMethod;

    // 不显示变量列表
    public $uninclude = ['this'];
    private $levelTips;
    private $errorMessage;
    private $errorFile;
    private $errorLine;
    private $environment;
    private $errorTrace;

    private $lineNum = 9;       //显示错误行上下的代码行数

    private static $errorRun = false;          //发生致命错误后是否继续运行
    private static $debug = false;
    /**
     * 设置系统异常处理
     */
    public function getHandler($debug = false){
        self::$debug = $debug;
        set_error_handler(function ($error_no, $error_msg, $error_file, $error_line) {
            if(method_exists($this, 'errorLog')){
                $this->errorLog($error_no, $error_msg, $error_file, $error_line);
            }

            $defaultExceptionName = LAZYConfig::get("default_exception_class");
            if(class_exists($defaultExceptionName) && method_exists($defaultExceptionName, 'BuildFromPHPException')) {
                $exception = new $defaultExceptionName($error_msg, 500);
            }
            else {
                $exception = new LAZYException($error_msg, 500);
            }
            $errorPage = $exception->getErrorPage(self::$debug);
            if($errorPage != null) {
                $this->throwError($errorPage, $exception->getCode(), $exception->getResponseType());
            }
            else {
                if(self::$debug == false) {
                    $this->throwError("<!DOCTYPE html><head><title>An error occurred</title><meta charset=\"UTF-8\"><head><body><h1>Error</h1><div>运行过程中出现了一个错误</div></body>");
                }
                else {
                    $this->setLevel($error_no)
                        ->setErrorMsg($error_msg)
                        ->setErrorFile($error_file)
                        ->setErrorLine($error_line)
                        ->setErrorEnv(get_defined_vars())
                        ->setErrorTrace($exception->getTraceAsString());
                    $this->throwError($this->build(), $exception->getCode(), $exception->getResponseType());
                }
            }
            return true;
        });
        set_exception_handler(function($exception){
            if (method_exists($this, 'errorLog')) {
                $this->errorLog(E_ERROR, $exception->getMessage(), $exception->getFile(), $exception->getLine(), get_defined_vars());
            }
            if(! $exception instanceof BaseException) {
                $defaultExceptionName = LAZYConfig::get("default_exception_class");
                if(class_exists($defaultExceptionName) && method_exists($defaultExceptionName, 'BuildFromPHPException')) {
                    $exception = call_user_func(array($defaultExceptionName, 'BuildFromPHPException'), $exception);
                }
                else {
                    $exception = LAZYException::BuildFromPHPException($exception);
                }
            }
            $errorPage = $exception->getErrorPage(self::$debug);
            if($errorPage != null) {
                $this->throwError($errorPage, $exception->getCode(), $exception->getResponseType());
            }
            else {
                if(self::$debug == false) {
                    $this->throwError("<!DOCTYPE html><head><title>An error occurred</title><meta charset=\"UTF-8\"><head><body><h1>Error</h1><div>运行过程中出现了一个错误</div></body>");
                }
                else {
                    $exceptionClassName = (new \ReflectionClass($exception))->getShortName();
                    $errorPage = $this->setLevel($exception->getCode(), $exceptionClassName)
                        ->setErrorEnv($exception->getEnvInfo())
                        ->setErrorFile($exception->getFile())
                        ->setErrorLine($exception->getLine())
                        ->setErrorMsg($exception->getMessage())
                        ->setErrorTrace($exception->getTraceAsString())
                        ->build();
                    $this->throwError($errorPage, $exception->getCode(), $exception->getResponseType());
                }
            }
            return true;
        });
        return $this;
    }

    /**
     * 设置报错级别
     * @param integer $errorNo 错误级别
     */
    public function setLevel($errorNo, $errorName = 'Unkonw Type Error'){
        switch ($errorNo) {
            case E_ERROR:
                $this->levelTips = 'PHP Error';
                break;
            case E_WARNING:
                $this->levelTips = 'PHP Warning';
                break;
            case E_NOTICE:
                $this->levelTips = 'PHP Notice';
                break;
            case E_DEPRECATED:
                $this->levelTips = 'PHP Deprecated';
                break;
            case E_USER_ERROR:
                $this->levelTips = 'User Error';
                break;
            case E_USER_WARNING:
                $this->levelTips = 'User Warning';
                break;
            case E_USER_NOTICE:
                $this->levelTips = 'User Notice';
                break;
            case E_USER_DEPRECATED:
                $this->levelTips = 'User Deprecated';
                break;
            case E_STRICT:
                $this->levelTips = 'PHP Strict';
                break;
            default:
                $this->levelTips = $errorName;
                break;
        }
        return $this;
    }

    /**
     * 设置报错信息
     * @param string $errorMsg 报错信息
     */
    public function setErrorMsg($errorMsg){
        $this->errorMessage = $errorMsg;
        return $this;
    }

    /**
     * 设置报错所在的文件
     * @param string $errorFile 报错文件名
     */
    public function setErrorFile($errorFile){
        $this->errorFile = $errorFile;
        return $this;
    }

    /**
     * 设置报错所在行号
     * @param integer $errorLine 报错所在行数
     */
    public function setErrorLine($errorLine){
        $this->errorLine = $errorLine;
        return $this;
    }

    /**
     * 环境信息
     * @param array $errorEnv 包含环境信息的数组
     */
    public function setErrorEnv($errorEnv){
        $this->environment = $errorEnv;
         // 添加一些请求信息
        if(!isset($this->environment['_SERVER']))
            $this->environment['_SERVER'] = $_SERVER;
        if(!isset($this->environment['_GET']))
            $this->environment['_GET'] = $_GET;
        if(!isset($this->environment['_POST']))
            $this->environment['_POST'] = $_POST;
        if(!isset($this->environment['_FILES']))
            $this->environment['_FILES'] = $_FILES;
        if(!isset($this->environment['_COOKIES']))
            $this->environment['_COOKIE'] = $_COOKIE;
        foreach ($this->environment as $key => $value) {
            if(array_search($key, $this->uninclude) !== false){
                unset($this->environment[$key]);
            }
        }
        return $this;
    }

    /**
     * 设置堆栈
     */
    public function setErrorTrace($trace_info){
        $this->errorTrace = $trace_info;
        return $this;
    }

    /**
     * 生成报错信息网页代码
     * @return string 网页报错信息代码
     */
    public function build(){
        // 错误信息
        $error = $this->levelTips . ': ' . $this->errorMessage . ' in ' . $this->errorFile . ' on ' . $this->errorLine;
        // 错误的相关代码部分
        $start = $this->errorLine - 1 - $this->lineNum;
        if($start < 0) $start = 0;
        $end = $this->errorLine - 1 + $this->lineNum;
        $fileCodeArray = explode("<br />", substr(highlight_file($this->errorFile, true), 36, -15));
        if($end >= count($fileCodeArray)){
            $end = count($fileCodeArray) - 1;
        }
        for($i = $start; $i <= $end; $i ++){
            if($i == $this->errorLine - 1) continue;
            $fileCodeArray[$i] = '<p><span class="line-num">' . ($i + 1) . '</span>' . $fileCodeArray[$i]. '</p>';
        }
        $fileCodeArray[$this->errorLine - 1] = '<p class="error-line" title="' . $error . '"><span class="line-num">' . $this->errorLine . '</span>' . $fileCodeArray[$this->errorLine - 1]. '</p>';
        $errorCodeString = implode("", array_slice($fileCodeArray, $start, $end - $start + 1));
        // 错误环境信息
        $envInfo = htmlspecialchars(json_encode($this->environment, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        // 获取调用栈
        $traceInfo = htmlspecialchars($this->errorTrace);
        return "<!DOCTYPE html><html><head><title>An error occurred</title><meta charset=\"UTF-8\"><style>
                *{padding:0px;margin:0px;}.line-num{display:inline-block;border-right:1px solid black;width:28px;padding-left:5px;margin-right:5px;}.error-line{background:#F79A9A}body{padding:10px}html{width:100%;height:100%}div{margin-bottom:20px;width:100%;height:30px;line-height:30px}.error-info{background:#F79A9A}pre{background:rgb(243,243,243);border:1px solid black;min-height:30px;width:100%;overflow:auto;margin-bottom:20px;}p{width:100%;height:25px;line-height:25px}p>span{display:inline-block;height:100%;line-height:25px;}.env-info{background:white;font-size:110%;}.trace{font-size:105%;line-height:25px;font-family:simhei;padding-left:3px;}</style></head><body><h1>An error occurred!</h1><br><div>错误信息：</div><h3 class=\"error-info\">$error</h3><br><div>错误级别：$this->levelTips</div><div>错误文件位置：$this->errorFile</div><div>错误代码位置：</div><pre>$errorCodeString</pre><div>堆栈调用信息:</div><pre class=\"trace\">$traceInfo</pre><div>环境变量等信息：</div><pre class=\"env-info\">$envInfo</pre></body></html>";
    }

    /**
     * 抛出一个异常
     * @param  string $info 异常信息
     */
    public function throwError($info = '', $code = 500, $type = "text/html"){
        http_response_code($code);        //设置HTTP状态值
        header("Content-Type:" . $type);
        if(self::$errorRun){
            echo $info;
        }
        else {
            die($info);
        }
    }

    /**
     * 发生非致命错误后是否继续运行
     * @param  boolean $error_run [description]
     */
    public static function errorRun($error_run = true){
        self::$errorRun = $error_run;
    }

}
