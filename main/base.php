<?php

// 以下变量都是绝对路径
define("__APP_PATH__", __ROOT_PATH__ . '/app/');                //应用目录
define("__LOAD_PATH__", __MAIN_PATH__ . '/lazy/');              //应用加载核心文件的目录
define("__LAZY_CONFIG__", __APP_PATH__ . '/config.php');        //配置文件
define("__ROUTER__", __APP_PATH__ . '/router.php');             //路由文件
define("__DATABASE_CONFIG__", __APP_PATH__ . '/database.php');  //用户数据库配置文件
define("__USER_COMMON__", __APP_PATH__ . '/common.php');        //用户公用函数文件
define("__TEMP_PATH__", __ROOT_PATH__ . '/runtime/temp/');      //临时文件目录
define("__LOG_PATH__", __ROOT_PATH__ . '/runtime/log/');        //日志文件目录
define("__EXTEND_PATH__", __ROOT_PATH__ . '/extend/');          //扩展类库目录

require_once(__LOAD_PATH__ . '/common.php');

// 采用自动加载方式
spl_autoload_register(function($className) {
    // 核心文件自动加载
    $path = lazy\changeFilePath(__MAIN_PATH__ . $className . '.php');
    if(file_exists($path)) {
        require_once($path);
        return true;
    }
    // 普通文件自动加载
    $path = lazy\changeFilePath(__ROOT_PATH__ . $className . '.php');
    if(file_exists($path)) {
        require_once($path);
        return true;
    }
    // 扩展文件自动加载
    $path = lazy\changeFilePath(__EXTEND_PATH__ . $className . '.php');
    if(file_exists($path)) {
        require_once($path);
        return true;
    }
    return false;
});

// 入口文件相对于网站根目录的相对目录
define("__RELATIVE_ROOT_PATH__", '/' . lazy\getRelativelyPath(lazy\Request::wwwroot(), __ROOT_PATH__) . '/');
// 静态文件目录，是相对路径
define("__STATIC_PATH__", __RELATIVE_ROOT_PATH__ . 'static/');         //静态资源目录
define("__CSS__", __STATIC_PATH__ . '/css/');                               //css目录
define("__JS__", __STATIC_PATH__ . '/js/');                                 //js目录
define("__IMAGE__", __STATIC_PATH__ . '/image/');                           //image目录
ini_set('log_errors', true);
ini_set('error_log', __LOG_PATH__ . '/error.log');
lazy\LAZYConfig::load(require_once(__LAZY_CONFIG__));
lazy\LAZYConfig::init();
