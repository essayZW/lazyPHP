<?php
namespace lazy;         //顶级命名空间
define("__MAIN_PATH__", __ROOT_PATH__ . '/main/');          //核心文件目录
require_once(__MAIN_PATH__ . "/base.php");                  //引入基础变量加载，环境设置文件
// 日志记录
// 初始化日志类
Log::init(LAZYConfig::get('log_file_path'), LAZYConfig::get('log_file_autoclear'), LAZYConfig::get('log_max_time'));
// 写入日志开头
Log::line();
Log::log("[". date('Y年m月d日H时i分s秒') ."] App Start!");
// 写入请求信息
Log::info('User IP: '. Request::ip());
Log::info('Request Host: '. Request::host());
Log::info('Request Url: ' . Request::url());
Log::info('Query String: '. Request::query());
Log::info('Request Method: '. Request::getMethod());
Log::info('Referer: '. (Request::referer() ? Request::referer() : 'None'));
//解析url
$pathinfo = Request::path();
// 记录pathinfo日志
Log::info('PathInfo: '. $pathinfo);
$accpetMethod = 'ALL';    //默认支持所有请求
$errorPath = __APP_PATH__.  '/'.LAZYConfig::get('error_default_module').'/controller/'. LAZYConfig::get('error_default_controller'). '.php';
//加载路由列表
if(LAZYConfig::get('url_route_on')){
    //开启了路由
    // 记录日志
    Log::info('Router On');
    $routerList = require_once(__ROUTER__);
    Router::importFromArray($routerList);          //将已经有配置文件中的路由列表导入
    $rule = Router::getRule($pathinfo);            //得到对应的记录
    // 记录日志
    Log::info('Matched Router: '. ($rule ? $rule : 'None'));
    $accpetMethod = Router::getMethod($pathinfo);
    if($rule != false) {
        $pathinfo = $rule;
    }
    else if(LAZYConfig::get('url_route_must')){
        if(file_exists($errorPath)){
            $pathinfo = '/'.LAZYConfig::get('error_default_module').'/'. LAZYConfig::get('error_default_controller');
        }
        else{
            // 没有找到对应的路由
            trigger_error('Route not found', E_USER_ERROR);
            exit();
        }
    }
    if($accpetMethod == false) {
        //若路由中不存在则支持所有方法
        $accpetMethod = 'ALL';
    }
}
//检查请求方法是否符合
if(!Request::isExists(Request::getMethod(), $accpetMethod)){
    if(file_exists($errorPath)){
        $pathinfo = '/'.LAZYConfig::get('error_default_module').'/'. LAZYConfig::get('error_default_controller');
    }
    else{
        //请求方法不符合
        trigger_error("Forbidden!", E_USER_ERROR);
        exit();
    }
}
//解析URL
$pathArr = array_filter(explode('/', $pathinfo));
$module = strtolower(array_key_exists(1, $pathArr) ? $pathArr[1] : LAZYConfig::get('default_module'));
$controller = ucwords(strtolower(array_key_exists(2, $pathArr) ? $pathArr[2] : LAZYConfig::get('default_controller')));
$method = strtolower(array_key_exists(3, $pathArr) ? $pathArr[3] : LAZYConfig::get('default_method'));
// 记录日志
Log::info('Request module: '. $module);
Log::info('Request controller: '. $controller);
Log::info('Request method: ' . $method);
// 解析除了模块控制器方法以外的信息
if(count($pathArr) > 3){
    // 记录日志
    Log::log('Params On Url!');
    // 含有其他部分
    // 将其作为get表单数据
    $pathParam = array_slice($pathArr, 3, count($pathArr) - 3);
    // 默认以name/value/name/value形式解析为get参数
    $len = count($pathParam);
    $getArr = [];
    for($i = 1; $i < $len; $i += 2){
        $getArr = array_merge($getArr, [$pathParam[$i - 1] => $pathParam[$i]]);
    }
    // 合并到get数组中
    $_GET = array_merge($_GET, $getArr);
    // 将pathinfo后面的信息传递给request，可以供用户自己解析
    Request::$pathParamStr = '/' . implode('/', $pathParam);
    // 记录日志
    Log::info('Url Params: '. Request::$pathParamStr);
}

//定义相关常量
define("__MODULE_PATH__", __APP_PATH__ . $module);                  //模块目录
define("__CONTROLLER_PATH__", __MODULE_PATH__ . '/controller/');    //控制器目录
define("__MODEL__PATH_", __MODULE_PATH__ . '/model/');              //模型目录
define("__VIEW_PATH__", __MODULE_PATH__ . '/view/');                //模板目录
// 保存请求的模块、控制器、方法信息
Request::$rmodule = $module;
Request::$rcontroller = $controller;
Request::$rmethod = $method;
// 查找是否有模块额外配置文件并导入
if(\file_exists(__MODULE_PATH__. '/config.php')){
    LAZYConfig::load(require_once(__MODULE_PATH__. '/config.php'));
    Log::log('Import module config file: '. __MODULE_PATH__. '/config.php');
    $LAZYDebug = new AppDebug();
    $LAZYDebug->getHandler(LAZYConfig::get('app_debug'))
        ->errorRun(LAZYConfig::get('app_error_run'));
    ini_set('display_errors', LAZYConfig::get('app_debug'));
}
// 第一次保存内存中所有日志
Log::save();
//开始执行对应的方法并输出结果
print_r(Controller::callMethod($module, $controller, $method));
// 保存内存中所有日志
Log::save();