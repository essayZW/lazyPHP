<?php
/**
 * 控制器的相关操作类
 * version:1.1
 * Update Info:
 *      1.新增调用任意模块\控制器\方法
 *      2.新增实例化一个指定的model
 *      3.新增跳转页面
 *      4.修复BUG
 */

namespace lazy\controller;
use lazy\view\View;
use lazy\validate\Validate;
class Controller extends View{
    protected $validate;
    private $pageCode = '<!DOCTYPE html><html><head><title>{$title}</title><meta charset="utf-8"><script type="text/javascript">window.onload=function(){let endTime={$time};let now=0;let url="{$url}";let block=document.querySelector("#time");let jump=function(){now++;show(endTime-now,block);if(now<endTime){setTimeout(jump,1000)}else{window.location.href=url}};show(endTime,block);setTimeout(jump,1000);function show(num,position){position.innerHTML="还有"+num+"s跳转到:   "+url}};</script><style type="text/css">*{margin:0px;padding:0px}a,a:hover{text-decoration:none}html,body{width:100%;height:100%}h1{padding-left:20px;font-family:simhei,宋体;margin-top:50px;margin-bottom:10px}#time,#click,pre{display:block;padding-left:20px;margin-bottom:10px}#click{width:200px}pre{width:90%;min-height:200px;margin-top:10px;max-width:300px;font-size:110%;word-break:break-word;white-space:pre-wrap}</style></head><body><h1>{$word}</h1><div id="time"></div><a href="{$url}"id="click">点击立即跳转!</a>{if condition="$info != """}<pre>{$info}</pre>{endif/}</body></html>';

    public function __construct(){
        // 实例化一个验证器
        $this->validate = new Validate();
    }
    /**
     * 调用一个其余模块的控制器的方法
     * @param  string $module     模块名
     * @param  string $controller 控制器
     * @param  string $method     方法名
     * @return mixed              执行结果
     */
    public static function callMethod($module, $controller, $method, $from = false){
        $trace = debug_backtrace();
        if(!isset($trace[0]['file'])) {
            // 调用自身
            return;
        }
        //尝试访问对应的模块的类的方法
        $module_path = __APP_PATH__ . $module;
        $controller_path = $module_path . '/controller/';
        if(!file_exists($module_path)){
            $blankModule = \lazy\LAZYConfig::get('error_default_module');
            if(!file_exists(__APP_PATH__ . $blankModule . '/controller/')){
                //模块不存在
                trigger_error("Module $module Not Exists!", E_USER_ERROR);
            }
            $module = $blankModule;
            $controller_path = __APP_PATH__ . $module . '/controller/';
        }

        $controllerPath = $controller_path . $controller . '.php';
        // 空控制器
        $blankController = \lazy\LAZYConfig::get('error_default_controller');
        if(!file_exists($controllerPath)){
            if(!file_exists($controller_path . $blankController . '.php')){
                //控制器不存在
                trigger_error("Controller $controller Not Exists!", E_USER_ERROR);
            }else{
                $controllerPath = $controller_path . $blankController . '.php';
            }
        }

        //引入控制器文件
        require_once($controllerPath);
        //开始执行对应的模块，控制器以及方法
        $controller = 'app\\' . $module . '\controller\\' . $controller;
        if(!class_exists($controller)){
            if(!class_exists('app\\' . $module . '\controller\\' . $blankController)){
                //控制器不存在
                trigger_error("Controller $controller Not Exists!", E_USER_ERROR);
            }
            else{
                $controller = 'app\\' . $module . '\controller\\' . $blankController;
            }
        }

        //实例化一个控制器
        $appController = new $controller;
        if(!method_exists($appController, $method)){
            // 当请求方法不存在时，尝试调用默认方法
            $blankMethod = \lazy\LAZYConfig::get('error_default_method');
            if(!method_exists($appController, $blankMethod)){
                //方法不存在
                trigger_error("Method $method Not Exists!", E_USER_ERROR);
            }
            else{
                $method = $blankMethod;
            }
        }
        if($from){
            //保存本次请求中的模型，控制器，方法信息
            \lazy\request\Request::$module = $module;
            \lazy\request\Request::$controller = (new \ReflectionClass($controller))->getShortName();
            \lazy\request\Request::$method = $method;
        }
        // 记录日志
        \lazy\log\Log::log('Use module: '. $module);
        \lazy\log\Log::log('Use controller: '. \lazy\request\Request::controller());
        \lazy\log\Log::log('Use method: '. $method);
        //得到表单参数列表
        $LAZYCode = new \lazy\code\PHPCodeMethod($appController, $method);
        //调用并将结果返回
        return $LAZYCode->callMethod(\lazy\request\Request::params(), $appController);
    }

    /**
     * 显示一个操作成功的页面
     * @param  string  $info 需要显示的详细信息
     * @param  string  $url  完成后跳转到的URL
     * @param  integer $time 页面停留时间
     */
    protected function success($info = '', $url = false, $time = 3){
        if($url == false){
            $url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $_SERVER['HTTP_HOST'];
        }
        $this->assign([
            'title'=> 'Success',
            'info' => $info,
            'url'  => $url,
            'time' => $time,
            'word' => '操作成功！'
        ]);
        die($this->fetchPart($this->pageCode));
    }

    /**
     * 显示一个操作失败的页面
     * @param  string  $info 需要显示的详细信息
     * @param  string  $url  完成后跳转到的URL
     * @param  integer $time 页面停留时间
     */
    protected function error($info = '', $url = false, $time = 3){
        if($url === false){
            $url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'http://' . $_SERVER['HTTP_HOST'];
        }
        $this->assign([
            'title'=> 'Error',
            'info' => $info,
            'url'  => $url,
            'time' => $time,
            'word' => '操作失败！'
        ]);
        die($this->fetchPart($this->pageCode));
    }

    /**
     * 实例化一个model类，并返回
     * @param  string $name model名称
     * @return [type]       [description]
     */
    protected function model($name = ''){
        if(!$name){
            $name = \lazy\request\Request::controller();
        }
        if(!file_exists(__MODEL__PATH_ . $name . '.php')){
            //模型不存在
            trigger_error("Model $name Not Exists!", E_USER_ERROR);
        }
        require_once(__MODEL__PATH_ . $name . '.php');
        $model = 'app\\' . \lazy\request\Request::module() . '\model\\' . $name;
        $res = new $model;
        //加载配置
        $res->load(require(__DATABASE_CONFIG__));
        return $res;
    }
}
