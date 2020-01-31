<?php
/**
 * 应用全局变量注册
 * 一些环境的注册
 */

//全局变量定义
define("__APP_PATH__", __ROOT_PATH__ . '/app/');                //应用目录
define("__STATIC_PATH__", './static/');         //静态资源目录
define("__CSS__", __STATIC_PATH__ . '/css/');                   //css目录
define("__JS__", __STATIC_PATH__ . '/js/');                     //js目录
define("__IMAGE__", __STATIC_PATH__ . '/image/');               //image目录
define("__LOAD_PATH__", __MAIN_PATH__ . '/load/');              //应用加载核心文件的目录
define("__DEFAULT_PATH_INFO__", '/index/index/index');          //默认的模块，控制器，方法
define("__LAZY_CONFIG__", __APP_PATH__ . '/config.php');        //配置文件路径
define("__ROUTER__", __APP_PATH__ . '/router.php');             //路由文件目录
define("__DATABASE_CONFIG__", __APP_PATH__ . '/database.php');  //用户数据库配置文件
define("__USER_COMMON__", __APP_PATH__ . '/common.php');        //用户公用函数文件
define("__TEMP_PATH__", __ROOT_PATH__ . '/runtime/temp/');      //临时文件目录
define("__LOG_PATH__", __ROOT_PATH__ . '/runtime/log/');        //日志文件目录
//加载核心函数库，以及类
// 先加载通用方法文件

require_once(__LOAD_PATH__ . '/common.php');

//引入其他核心函数库、类文件
lazy\requireAllFileFromDir(__LOAD_PATH__, [
        'view.class.php'    => 'controller.class.php',      //controller依赖于view
        'mysqlDB.class.php' => 'model.class.php',           //model依赖于mysqlDB
    ]
);
//导入配置文件
lazy\LAZYConfig::load();

//根据__APP_DEBUG__ 开启或者关闭应用调试模式
(new lazy\debug\AppDebug())->getHandler(lazy\LAZYConfig::get('app_debug'))
                           ->errorRun(lazy\LAZYConfig::get('app_error_run'));

ini_set('log_errors', true);
ini_set('error_log', __LOG_PATH__ . '/error.log');

//引入用户自定义函数文件
require_once(__USER_COMMON__);