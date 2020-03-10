## 1. 框架说明

本框架是仿写的think PHP框架。

文档也是仿照think PHP5.0看云的文档写的。

本框架作个人学习PHP使用。

目前版本：**1.0.0**

> **框架是通过`PATH_INFO`得到请求的模块控制器方法的，所以需要保证服务器支持`	$_SERVER['PATH_INFO']`变量，最好支持URL重写功能**
>
> 另外框架需求PHP版本在5.6及以上

## 2. 目录结构


```
project
│  .htaccess	URL重写文件
│  favicon.ico	默认站标
│  index.php	应用入口文件
│  
├─app	应用目录
│  │  common.php	用户公用函数文件
│  │  config.php	应用配置文件
│  │  database.php	应用数据库配置文件
│  │  router.php	应用路由配置文件
│  │  
│  └─index                              默认的index模块
│      ├─controller                     模块中的控制器目录
│      │      Index.php                 Index控制器文件
│      │      
│      ├─model                          index模块的模型目录
│      └─view                           index模块的模板目录
├─extend
├─main                                  框架核心文件夹
│  │  base.php                          应用基础环境加载文件
│  │  main.php                          控制路径的解析以及路由的转发
│  │  
│  └─load                               框架核心类
│          captcha.class.php	        验证码相关类
│          code.class.php	            扩展PHP反射相关类
│          common.php	                框架公用函数文件
│          controller.class.php	        控制器类
│          cookieAndSession.class.php	cookie以及session相关操作类
│          debug.class.php	            框架调试类
│          lazyconfig.class.php	        框架配置相关类
│          log.class.php                日志操作相关类
│          model.class.php              模型类
│          mysqlDB.class.php            框架MySQL数据库操作类
│          request.class.php            请求参数相关类
│          router.class.php             路由解析转发相关类
│          validate.class.php           验证器相关类
│          view.class.php               模板类
│          
├─runtime                               日志以及临时文件缓存存放目录
│  ├─log                                日志目录
│  └─temp                               临时文件存放目录
└─static                                静态资源存放目录
```

**默认除static以外的目录请求都会被重写以保护目录**

## 3. 框架流程

### 1. 入口文件

入口文件是`project/index.php`文件，其负责定义应用根目录常量，并尝试捕获`E_PARSE`和`E_ERROR`的错误处理

```php
define("__ROOT_PATH__", dirname(__FILE__)); //根目录

try {
    require_once("./main/main.php");
}catch (\Error $error) {
    if(!class_exists('\lazy\debug\AppDebug')){
        echo $error->getMessage() . ' at' . $error->getFile() . ' on line ' . $error->getLine();
        return;
    }
    $debug = new \lazy\debug\AppDebug();
    $debug->throwError($debug->setLevel(E_ERROR)
          ->setErrorEnv(get_defined_vars())
          ->setErrorFile($error->getFile())
          ->setErrorLine($error->getLine())
          ->setErrorMsg($error->getMessage())
          ->setErrorTrace($error->getTraceAsString())
          ->build());
}
```

### 2. 变量注册

```php
namespace lazy;         //顶级命名空间
define("__MAIN_PATH__", __ROOT_PATH__ . '/main/');          //核心文件目录
require_once(__MAIN_PATH__ . "/base.php");                  //引入基础变量加载，环境设置文件
```

处在顶级命名空间`lazy`之下，并加载`base.php`注册变量

```php
//全局变量定义
define("__APP_PATH__", __ROOT_PATH__ . '/app/');                //应用目录
define("__LOAD_PATH__", __MAIN_PATH__ . '/load/');              //应用加载核心文件的目录
define("__LAZY_CONFIG__", __APP_PATH__ . '/config.php');        //配置文件路径
define("__ROUTER__", __APP_PATH__ . '/router.php');             //路由文件目录
define("__DATABASE_CONFIG__", __APP_PATH__ . '/database.php');  //用户数据库配置文件
define("__USER_COMMON__", __APP_PATH__ . '/common.php');        //用户公用函数文件
define("__TEMP_PATH__", __ROOT_PATH__ . '/runtime/temp/');      //临时文件目录
define("__LOG_PATH__", __ROOT_PATH__ . '/runtime/log/');        //日志文件目录
define("__EXTEND_PATH__", __ROOT_PATH__ . '/extend/');          //扩展类库目录
// 定义静态文件目录，是相对路径
define("__STATIC_PATH__", '/' . lazy\getRelativelyPath(lazy\request\Request::wwwroot(), __ROOT_PATH__). '/static/');         										//静态资源目录
define("__CSS__", __STATIC_PATH__ . '/css/');                               //css目录
define("__JS__", __STATIC_PATH__ . '/js/');                                 //js目录
define("__IMAGE__", __STATIC_PATH__ . '/image/');                           //image目录
// 定义入口文件相对于网站根目录的相对目录
define("__RELATIVE_ROOT_PATH__", lazy\getRelativelyPath(lazy\request\Request::wwwroot(), __ROOT_PATH__));
```

### 3. 核心文件引入

根据依赖关系依次引入框架核心文件

```php
// 先加载通用方法文件
require_once(__LOAD_PATH__ . '/common.php');

//引入其他核心函数库、类文件
lazy\requireAllFileFromDir(__LOAD_PATH__, [
        'view.class.php'    => 'controller.class.php',      //controller依赖于view
        'mysqlDB.class.php' => 'model.class.php',           //model依赖于mysqlDB
        'validate.class.php'=> 'controller.class.php',      //controller依赖于validate
    ]
);
```

### 4. 配置加载

导入配置文件

```php
//导入配置文件
lazy\LAZYConfig::load();
```

### 5. 时区配置

   ```php
   date_default_timezone_set(lazy\LAZYConfig::get('default_timezone'));
   ```

### 6. 注册错误以及异常机制

通过`lazy\debug\AppDebug`注册错误处理，并根据配置文件配置处理机制，并设置错误日志存储

```php
//根据__APP_DEBUG__ 开启或者关闭应用调试模式
(new lazy\debug\AppDebug())->getHandler(lazy\LAZYConfig::get('app_debug'))
                           ->errorRun(lazy\LAZYConfig::get('app_error_run'));
// 设置报错日志存储
ini_set('log_errors', true);
ini_set('error_log', __LOG_PATH__ . '/error.log');
```

### 7. 加载路由列表

加载应用定义的路由列表，根据`PATH_INFO`匹配并解析新的URL

### 8. 解析URL

对请求的URL进行解析，得到请求的模块、控制器、方法，检测请求方法是否合法

```php
lazy\controller\Controller::callMethod($module, $controller, $method)
```

同时定义新的变量

```php
//定义相关常量
define("__MODULE_PATH__", __APP_PATH__ . $module);                  //模块目录
define("__CONTROLLER_PATH__", __MODULE_PATH__ . '/controller/');    //控制器目录
define("__MODEL__PATH_", __MODULE_PATH__ . '/model/');              //模型目录
define("__VIEW_PATH__", __MODULE_PATH__ . '/view/');                //模板目录
//保存本次请求中的模型，控制器，方法信息
request\Request::$module = $module;
request\Request::$controller = $controller;
request\Request::$method = $method;
```

### 9. 响应输出

控制器的方法返回的值将被`print_r`函数输出

### 10. 日志保存

    将过程中记录到内存中的日志写入文件。