<?php
namespace lazy;
use Exception;

ob_start();
define("__MAIN_PATH__", __ROOT_PATH__ . '/main/');          //核心文件目录
require_once(__MAIN_PATH__ . "/base.php");                  //引入基础变量加载，环境设置文件
Log::init(LAZYConfig::get('log_file_path'), LAZYConfig::get('log_file_autoclear'), LAZYConfig::get('log_max_time'));
Log::line();
Log::log("[". date('Y年m月d日H时i分s秒') ."] App Start!");
Log::info('User IP: '. Request::ip());
Log::info('Request Host: '. Request::host());
Log::info('Request Url: ' . Request::url());
Log::info('Query String: '. Request::query());
Log::info('Request Method: '. Request::method());
Log::info('Referer: '. (Request::referer() ? Request::referer() : 'None'));
//解析url
$pathinfo = Request::path();
Log::info('PathInfo: '. $pathinfo);
//默认支持所有请求
$errorPath = __APP_PATH__.  '/'.LAZYConfig::get('error_default_module').'/controller/'. LAZYConfig::get('error_default_controller'). '.php';
//加载路由列表
if(LAZYConfig::get('url_route_on')){
    Log::info('Router On');
    $routerList = require_once(__ROUTER__);
    Router::importFromArray($routerList);
    $rule = Router::getRule($pathinfo);
    $url = $rule['url'];
    $acceptedMethod = $rule['method'];
    Log::info('Matched Router: '. ($url ? $url : 'None'));
    if($url != null) {
        $pathinfo = $url;
    }
    else if(LAZYConfig::get('url_route_must')){
        if(file_exists($errorPath)){
            $pathinfo = '/'.LAZYConfig::get('error_default_module').'/'. LAZYConfig::get('error_default_controller');
        }
        else{
            // 因为该错误不可逆，因此应该抛出后停止运行
            LAZYConfig::set('app_error_run', false);
            throw new Exception('Route not found');
        }
    }
    //检查请求方法是否允许
    if(!Router::isExists(Request::method(), $acceptedMethod)){
        Log::error('Request method '. Request::method(). ' not allowed');
        if(file_exists($errorPath)){
            $pathinfo = '/'.LAZYConfig::get('error_default_module').'/'. LAZYConfig::get('error_default_controller');
        }
        else{
            LAZYConfig::set('app_error_run', false);
            throw new Exception("Forbidden!");
        }
    }
}
//解析URL
$pathArr = Request::parsePathInfo($pathinfo);
$module = $pathArr['module'];
$controller = $pathArr['controller'];
$method = $pathArr['method'];
Log::info('Request module: '. $module);
Log::info('Request controller: '. $controller);
Log::info('Request method: ' . $method);
// 解析除了模块、控制器、方法以外的信息
if(count($pathArr) > 3){
    Log::log('Params On Url!');
    // 含有其他部分
    // 将其作为get表单数据
    $pathParam = array_slice($pathArr, 3, count($pathArr) - 3);
    // 默认以/name/value/name/value形式解析为get参数
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
// 保留用户请求的模块、控制器和操作名
Request::$rModule = $module;
Request::$rController = $controller;
Request::$rMethod = $method;
// 查找是否有模块额外配置文件并导入
$path = changeFilePath(__MODULE_PATH__. '/config.php');
if(\file_exists($path)){
    LAZYConfig::load(require_once($path));
    LAZYConfig::init();
}
// 第一次保存日志，防止之后运行中出现崩溃日志丢失
Log::save();
// 调用对应的控制器方法并将结果输出
// 尝试访问对应的模块的类的方法
$module_path = __APP_PATH__ . $module;
$controller_path = $module_path . '/controller/';
if(!file_exists($module_path)){
    $blankModule = \lazy\LAZYConfig::get('error_default_module');
    if(!file_exists(__APP_PATH__ . $blankModule . '/controller/')){
        //模块不存在
        throw new Exception("Module $module Not Exists!");
    }
    $module = $blankModule;
    $controller_path = __APP_PATH__ . $module . '/controller/';
}

$controllerPath = $controller_path . $controller . '.php';
// 空控制器
$blankController = \lazy\LAZYConfig::get('error_default_controller');
if(!file_exists($controllerPath)){
    if(!file_exists($controller_path . $blankController . '.php')){
        throw new Exception("Controller $controller Not Exists!");
    }else{
        $controllerPath = $controller_path . $blankController . '.php';
    }
}

//引入控制器文件
$controllerName = 'app\\' . $module . '\controller\\' . $controller;
if(!class_exists($controllerName)){
    if(!class_exists('app\\' . $module . '\controller\\' . $blankController)){
        throw new Exception("Controller $controllerName Not Exists!");
    }
    else{
        $controllerName = 'app\\' . $module . '\controller\\' . $blankController;
    }
}

//实例化一个控制器
$appController = new $controllerName;
if(!method_exists($appController, $method)){
    // 当请求方法不存在时，尝试调用默认方法
    $blankMethod = \lazy\LAZYConfig::get('error_default_method');
    if(!method_exists($appController, $blankMethod)){
        throw new Exception("Method $method Not Exists!");
    }
    else{
        $method = $blankMethod;
    }
}
\lazy\Log::info('Use module: '. $module);
\lazy\Log::info('Use controller: '. $controller);
\lazy\Log::info('Use method: '. $method);
Request::$module = $module;
Request::$controller = $controller;
Request::$method = $method;
// 将表单参数作为方法参数传进去，需要获取调用方法的参数列表
$LAZYCode = new \lazy\PHPCodeMethod($appController, $method);
$response = $LAZYCode->callMethod(\lazy\Request::params(), $appController);
if(!is_object($response)) {
    $response = Response\LAZYResponse::BuildFromVariable($response);
}
if(! $response instanceof Response\LAZYResponse) {
    throw new Exception("Response object must is a instance of lazy\\Response\\LAZYResponse");
}
$callbackTable = Response\BeforeResponse::getRegistedHandler(Request::$module, Request::$controller, Request::$method);
foreach($callbackTable as $value) {
    $response = call_user_func($value, $response);
    if(! $response instanceof Response\BaseResponse) {
        throw new Exception("before response handler must return a object that is a instance of lazy\\Response\\LAZYResponse");
    }
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
