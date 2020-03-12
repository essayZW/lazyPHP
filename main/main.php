<?php
namespace lazy;         //顶级命名空间
define("__MAIN_PATH__", __ROOT_PATH__ . '/main/');          //核心文件目录
require_once(__MAIN_PATH__ . "/base.php");                  //引入基础变量加载，环境设置文件
// 日志记录
// 初始化日志类
log\Log::init(LAZYConfig::get('log_file_path'), LAZYConfig::get('log_file_autoclear'), LAZYConfig::get('log_max_time'));
// 写入日志开头
log\Log::line();
log\Log::log("[". date('Y年m月d日H时i分s秒') ."] App Start!");
// 写入请求信息
log\Log::info('User IP: '. request\Request::ip());
log\Log::info('Request Host: '. request\Request::host());
log\Log::info('Request Url: ' . request\Request::url());
log\Log::info('Query String: '. request\Request::query());
log\Log::info('Request Method: '. request\Request::getMethod());
log\Log::info('Referer: '. (request\Request::referer() ? request\Request::referer() : 'None'));
//解析url
$pathinfo = request\Request::path();
// 记录pathinfo日志
log\Log::info('PathInfo: '. $pathinfo);
$accpetMethod = 'ALL';                                  //默认支持所有请求
//加载路由列表
if(LAZYConfig::get('url_route_on')){
    //开启了路由
    // 记录日志
    log\Log::info('Router On');
    $routerList = require_once(__ROUTER__);
    router\Router::importFromArray($routerList);          //将已经有配置文件中的路由列表导入
    $rule = router\Router::getRule($pathinfo);            //得到对应的记录
    // 记录日志
    log\Log::info('Matched Router: '. ($rule ? $rule : 'None'));
    $accpetMethod = router\Router::getMethod($pathinfo);
    if($rule != false) {
        $pathinfo = $rule;
    }
    else if(LAZYConfig::get('url_route_must')){
        // 没有找到对应的路由
        trigger_error('Route not found', E_USER_ERROR);
        exit();
    }
    if($accpetMethod == false) {
        //若路由中不存在则支持所有方法
        $accpetMethod = 'ALL';
    }
}
//解析URL
$pathArr = array_filter(explode('/', $pathinfo));
$module = strtolower(array_key_exists(1, $pathArr) ? $pathArr[1] : LAZYConfig::get('default_module'));
$controller = ucwords(strtolower(array_key_exists(2, $pathArr) ? $pathArr[2] : LAZYConfig::get('default_controller')));
$method = strtolower(array_key_exists(3, $pathArr) ? $pathArr[3] : LAZYConfig::get('default_method'));
// 记录日志
log\Log::info('Request module: '. $module);
log\Log::info('Request controller: '. $controller);
log\Log::info('Request method: ' . $method);
// 解析除了模块控制器方法以外的信息
if(count($pathArr) > 3){
    // 记录日志
    log\Log::log('Params On Url!');
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
    request\Request::$pathParamStr = '/' . implode('/', $pathParam);
    // 记录日志
    log\Log::info('Url Params: '. request\Request::$pathParamStr);
}

//检查请求方法是否符合
if(!request\Request::isExists(request\Request::getMethod(), $accpetMethod)){
    //请求方法不符合
    trigger_error("Forbidden!", E_USER_ERROR);
    exit();
}
//定义相关常量
define("__MODULE_PATH__", __APP_PATH__ . $module);                  //模块目录
define("__CONTROLLER_PATH__", __MODULE_PATH__ . '/controller/');    //控制器目录
define("__MODEL__PATH_", __MODULE_PATH__ . '/model/');              //模型目录
define("__VIEW_PATH__", __MODULE_PATH__ . '/view/');                //模板目录
// 保存请求的模块、控制器、方法信息
request\Request::$rmodule = $module;
request\Request::$rcontroller = $controller;
request\Request::$rmethod = $method;
// 第一次保存内存中所有日志
log\Log::save();
//开始执行对应的方法并输出结果
print_r(controller\Controller::callMethod($module, $controller, $method));
// 保存内存中所有日志
log\Log::save();