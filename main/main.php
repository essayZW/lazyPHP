<?php
namespace lazy;
use Exception;
use FFI\Exception as FFIException;

define("__MAIN_PATH__", __ROOT_PATH__ . '/main/');          //核心文件目录
require_once(__MAIN_PATH__ . "/base.php");                  //引入基础变量加载，环境设置文件
Log::init(LAZYConfig::get('log_file_path'), LAZYConfig::get('log_file_autoclear'), LAZYConfig::get('log_max_time'));
Log::line();
Log::log("[". date('Y年m月d日H时i分s秒') ."] App Start!");
Log::info('User IP: '. Request::ip());
Log::info('Request Host: '. Request::host());
Log::info('Request Url: ' . Request::url());
Log::info('Query String: '. Request::query());
Log::info('Request Method: '. Request::getMethod());
Log::info('Referer: '. (Request::referer() ? Request::referer() : 'None'));
//解析url
$pathinfo = Request::path();
if($pathinfo{0} != '/') {
    $pathinfo = '/' . $pathinfo;
}
Log::info('PathInfo: '. $pathinfo);
//默认支持所有请求
$accpetMethod = 'ALL';
$errorPath = __APP_PATH__.  '/'.LAZYConfig::get('error_default_module').'/controller/'. LAZYConfig::get('error_default_controller'). '.php';
//加载路由列表
if(LAZYConfig::get('url_route_on')){
    Log::info('Router On');
    $routerList = require_once(__ROUTER__);
    Router::importFromArray($routerList);
    $rule = Router::getRule($pathinfo);
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
            throw new Exception('Route not found');
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
        Log::warn('Request method '. Request::getMethod(). ' not allowed');
        $pathinfo = '/'.LAZYConfig::get('error_default_module').'/'. LAZYConfig::get('error_default_controller');
    }
    else{
        throw new Exception("Forbidden!");
        exit();
    }
}
//解析URL
$pathArr = array_filter(explode('/', $pathinfo));
$module = strtolower(array_key_exists(1, $pathArr) ? $pathArr[1] : LAZYConfig::get('default_module'));
$controller = ucwords(strtolower(array_key_exists(2, $pathArr) ? $pathArr[2] : LAZYConfig::get('default_controller')));
$method = strtolower(array_key_exists(3, $pathArr) ? $pathArr[3] : LAZYConfig::get('default_method'));
Log::info('Request module: '. $module);
Log::info('Request controller: '. $controller);
Log::info('Request method: ' . $method);
// 解析除了模块、控制器、方法以外的信息
if(count($pathArr) > 3){
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
    $_GET = array_merge($_GET, $getArr);
    // 将pathinfo后面的信息传递给request，可以供用户自己解析
    Request::$pathParamStr = '/' . implode('/', $pathParam);
    Log::info('Url Params: '. Request::$pathParamStr);
}

define("__MODULE_PATH__", __APP_PATH__ . $module);                  //模块目录
define("__CONTROLLER_PATH__", __MODULE_PATH__ . '/controller/');    //控制器目录
define("__MODEL__PATH_", __MODULE_PATH__ . '/model/');              //模型目录
define("__VIEW_PATH__", __MODULE_PATH__ . '/view/');                //模板目录
// 跨模块调用时可能会覆盖当前的模块控制器方法信息，需要保留最初始的信息
Request::$rmodule = $module;
Request::$rcontroller = $controller;
Request::$rmethod = $method;
// 查找是否有模块额外配置文件并导入
$path = changeFilePath(__MODULE_PATH__. '/config.php');
if(\file_exists($path)){
    LAZYConfig::load(require_once($path));
    LAZYConfig::init();
}
// 第一次保存日志，防止之后运行中出现崩溃日志丢失
Log::save();
ob_start();
// 调用对应的控制器方法并将结果输出
$response = Controller::callMethod($module, $controller, $method);
if(!is_object($response)) {
    $response = Response\LAZYResponse::BuildFromVariable($response);
}
if(! $response instanceof Response\LAZYResponse) {
    throw new \Exception("Response content must is a instance of lazy\\Response\\LAZYResponse");
}
// 防止在此之前有输出，将输出缓冲区内容取出并清空
$beforeConetent = ob_get_contents();
ob_end_clean();
$contentType = $response->getContentType();
$response->setHeader("Content-Type", $contentType);
$headers = $response->getHeaders();
foreach($headers as $key => $value) {
    header($key . ':' . $value);
}
http_response_code($response->getCode());
echo $beforeConetent;
echo $response->getContent();
Log::save();
