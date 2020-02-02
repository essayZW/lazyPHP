<?php
/**
 * 应用debug信息处理类
 * version: 1.2.0
 * Update Info:
 *     1.修正堆栈信息显示不完全问题
 *     2.丰富了本地变量的显示
 *     3.可以自定义去除掉一些不显示本地变量
 *     4.优化了显示
 */
namespace lazy\debug;

class AppDebug{
    // 使用框架提供的log接口记录日志
    use \lazy\logMethod;

    // 不显示变量列表
    public $uninclude = ['errorLine', 'errorMessage', 'levelTips', 'errorFile', 'environment', 'errorTrace', 'error_no', 'error_msg', 'error_file', 'error_line', 'env_info'];
    private $levelTips;
    private $errorMessage;
    private $errorFile;
    private $errorLine;
    private $environment;
    private $errorTrace;

    private $lineNum = 9;       //显示错误行上下的代码行数

    private $errorRun = false;          //发生致命错误后是否继续运行
    /**
     * 捕获所有报错，转为自己处理
     * @return [type] [description]
     */
    public function getHandler($debug = false){
        if($debug == false){
            //开启了debug模式
            set_error_handler(function ($error_no, $error_msg, $error_file, $error_line) {
                if(method_exists($this, 'errorLog')){
                    // 如果该类存在日志记录功能
                    $this->errorLog($error_no, $error_msg, $error_file, $error_line);
                }
                $this->throwError("<!DOCTYPE html><head><title>Error!</title><meta charset=\"UTF-8\"><head><body><h1>Error!</h1><div>网页有错误发生！</div></body>");
                return true;
            }, E_ALL | E_STRICT);
            return $this;
        }
        // 未开启debug模式
        set_error_handler(function ($error_no, $error_msg, $error_file, $error_line, $env_info) {
            if(method_exists($this, 'errorLog')){
                // 如果该类存在日志记录功能
                $this->errorLog($error_no, $error_msg, $error_file, $error_line);
            }
            $this->setLevel($error_no)
                ->setErrorMsg($error_msg)
                ->setErrorFile($error_file)
                ->setErrorLine($error_line)
                ->setErrorEnv(array_merge($env_info, get_defined_vars()))
                ->setErrorTrace((new \Exception)->getTraceAsString());
            $this->throwError($this->build());
            return true;
        }, E_ALL | E_STRICT);
        return $this;
    }

    /**
     * 设置报错级别
     * @param integer $errorNo 错误级别
     */
    public function setLevel($errorNo){
        switch ($errorNo) {
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
                $this->levelTips = 'Unkonw Type Error';
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
     * @param [type] $envinfo [description]
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
        $envInfo = htmlspecialchars(json_encode($this->environment, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));  //获得格式化的JSON信息
        // 获取调用栈
        $e = new \Exception;
        $traceInfo = htmlspecialchars($this->errorTrace);
        return "<!DOCTYPE html><html><head><title>Error!</title><meta charset=\"UTF-8\"><style>
                *{padding:0px;margin:0px;}.line-num{display:inline-block;border-right:1px solid black;width:28px;padding-left:5px;margin-right:5px;}.error-line{background:#F79A9A}body{padding:10px}html{width:100%;height:100%}div{margin-bottom:20px;width:100%;height:30px;line-height:30px}.error-info{background:#F79A9A}pre{background:rgb(243,243,243);border:1px solid black;min-height:30px;width:100%;overflow:auto;margin-bottom:20px;}p{width:100%;height:30px;line-height:30px}p>span{display:inline-block;height:100%;line-height:30px;}.env-info{background:white;font-size:110%;}.trace{font-size:105%;line-height:25px;font-family:simhei;padding-left:3px;}</style></head><body><h1>Error!</h1><br><div>错误信息：</div><h3 class=\"error-info\">$error</h3><br><div>错误级别：$this->levelTips</div><div>错误文件位置：$this->errorFile</div><div>错误代码位置：</div><pre>$errorCodeString</pre><div>堆栈调用信息:</div><pre class=\"trace\">$traceInfo</pre><div>环境变量等信息：</div><pre class=\"env-info\">$envInfo</pre></body></html>";
    }

    /**
     * 抛出一个异常
     * @param  string $info 异常信息
     * @return [type]       [description]
     */
    public function throwError($info = ''){
        http_response_code(500);        //设置HTTP状态值
        if($this->errorRun){
            ob_get_clean();
            echo $info;
        }
        else
            die($info);
    }

    /**
     * 发生非致命错误后是否继续运行
     * @param  boolean $error_run [description]
     * @return [type]             [description]
     */
    public function errorRun($error_run = true){
        $this->errorRun = $error_run;
    }

}
