## 1. 框架说明

**框架是通过`PATH_INFO`得到请求的模块控制器方法的，所以需要保证服务器支持`	$_SERVER['PATH_INFO']`变量，并且最好支持URL重写功能以隐藏入口文件和关键目录**

另外框架需求PHP版本在5.6及以上

## 2. 目录结构


```php
project/
├── app # 应用目录
│   ├── index # 默认index模块目录
│   │   └── controller # index模块控制器目录
│   │       └── Index.php # index模块默认Index控制器
│   ├── database.php # 应用数据库配置文件
│   ├── common.php # 用户扩展函数文件
│   ├── config.php # 应用配置文件
│   ├── router.php # 应用路由规则定义文件
├── main # 框架核心文件目录
│   ├── base.php # 框架初始化、环境加载、全局变量定义文件
│   ├── main.php # 框架主流程文件
│   └── lazy # 框架核心类目录
│       ├── Cookie.php # Cookie类
│       ├── DB # DB类目录
│       │   └── MysqlDB.php # MysqlDB类文件
│       ├── LAZYConfig.php # LAZYConfig类文件，提供配置文件管理功能
│       ├── Log.php # Log类文件，提供日志记录
│       ├── Session.php # Session类文件
│       ├── Validate.php # 验证器类文件
│       ├── AppDebug.php # AppDebug类文件，提供对于未捕获异常的默认处理方法
│       ├── View.php # 模板渲染类文件
│       ├── Router.php # 路由管理类文件
│       ├── Controller.php # 控制器基类文件
│       ├── Request.php # 请求类文件，提供某次请求的基本信息
│       ├── Response # 响应相关类目录
│       │   ├── BaseResponse.php # 一个标准的响应应该实现的接口文件
│       │   ├── FILEResponse.php # 文件类型响应类
│       │   ├── HTMLResponse.php # HTML文件类型响应类
│       │   ├── JSONResponse.php # JSON类型数据响应类
│       │   ├── XMLResponse.php # XML类型相应类
│       │   ├── LAZYResponse.php # 默认数据响应类
│       │   └── BeforeResponse.php # 注册响应前回调方法
│       ├── PathHandlerRegister.php # 工具类，对某个路由注册回调函数
│       ├── HookHandler.php # 对指定的模块、控制器注册回调函数
│       ├── Request # 请求类目录
│       │   └── AfterRequest.php # 注册请求后回调
│       ├── Captcha.php # 验证码类
│       ├── Exception # 异常处理类目录
│       │   ├── LAZYException.php # 默认异常处理类
│       │   └── BaseException.php # 框架异常接口
│       ├── common.php # 提供框架运行中的一些工具函数
│       └── Model.php # 模型类
├── extend # 扩展目录
├── runtime # 存放运行时文件目录
│   ├── log # 日志文件存放目录
│   └── temp # 缓存、临时文件存放目录
├── static # 静态资源目录
├── favicon.ico # 默认站标
├── index.php # 框架入口文件
├── README.md
└── document.md
```

**默认除static以外的目录请求都会被重写以保护目录**

## 3. 框架流程

### 1. 入口文件

入口文件是`project/index.php`文件，其负责定义应用根目录常量。

```php
define("__ROOT_PATH__", dirname(__FILE__). '/'); //根目录

require_once("./main/main.php");
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
define("__LOAD_PATH__", __MAIN_PATH__ . '/lazy/');              //应用加载核心文件的目录
define("__LAZY_CONFIG__", __APP_PATH__ . '/config.php');        //配置文件
define("__ROUTER__", __APP_PATH__ . '/router.php');             //路由文件
define("__DATABASE_CONFIG__", __APP_PATH__ . '/database.php');  //用户数据库配置文件
define("__USER_COMMON__", __APP_PATH__ . '/common.php');        //用户公用函数文件
define("__TEMP_PATH__", __ROOT_PATH__ . '/runtime/temp/');      //临时文件目录
define("__LOG_PATH__", __ROOT_PATH__ . '/runtime/log/');        //日志文件目录
define("__EXTEND_PATH__", __ROOT_PATH__ . '/extend/');          //扩展类库目录
// 定义入口文件相对于网站根目录的相对目录
define("__RELATIVE_ROOT_PATH__", '/' . lazy\getRelativelyPath(lazy\Request::wwwroot(), __ROOT_PATH__) . '/');
// 定义静态文件目录，是相对路径
define("__STATIC_PATH__", __RELATIVE_ROOT_PATH__ . 'static/');         //静态资源目录
define("__CSS__", __STATIC_PATH__ . '/css/');                               //css目录
define("__JS__", __STATIC_PATH__ . '/js/');                                 //js目录
define("__IMAGE__", __STATIC_PATH__ . '/image/');                           //image目录
```

### 3. 设置文件自动加载

分别按照

1. 核心文件目录
2. 应用根目录下
3. 扩展目录下

的顺序自动加载文件。

```php
// 采用自动加载方式
spl_autoload_register(function($className) {
    // 核心文件自动加载
    $path = __MAIN_PATH__ . $className . '.php';
    $path = str_replace('\\', '/', $path);
    $path = str_replace('//', '/', $path);
    if(file_exists($path)) {
        require_once($path);
        return true;
    }
    // 普通文件自动加载
    $path = __ROOT_PATH__ . $className . '.php';
    $path = str_replace('\\', '/', $path);
    $path = str_replace('//', '/', $path);
    if(file_exists($path)) {
        require_once($path);
        return true;
    }
    // 扩展文件自动加载
    $path = __EXTEND_PATH__ . $className . '.php';
    $path = str_replace('\\', '/', $path);
    $path = str_replace('//', '/', $path);
    if(file_exists($path)) {
        require_once($path);
        return true;
    }
    return false;
});
```

### 4. 配置加载

导入默认主配置文件

```php
lazy\LAZYConfig::load(require_once(__LAZY_CONFIG__));
```

根据配置文件中的项目初始化应用

```php
$LAZYDebug = new AppDebug();
$LAZYDebug->getHandler(LAZYConfig::get('app_debug'))
    ->errorRun(LAZYConfig::get('app_error_run'));
ini_set('display_errors', LAZYConfig::get('app_debug'));
foreach (LAZYConfig::get('extra_file_list') as $value) {
    require_once($value);
}
Cookie::init(LAZYConfig::get('cookie'));
```

### 5. 注册错误以及异常机制

通过`lazy\debug\AppDebug`注册错误处理，并根据配置文件配置处理机制，并设置错误日志存储

```php
//根据__APP_DEBUG__ 开启或者关闭应用调试模式
$LAZYDebug = new lazy\AppDebug();
$LAZYDebug->getHandler(lazy\LAZYConfig::get('app_debug'))
          ->errorRun(lazy\LAZYConfig::get('app_error_run'));
// 设置报错日志存储
ini_set('log_errors', true);
ini_set('error_log', __LOG_PATH__ . '/error.log');
ini_set('display_errors', lazy\LAZYConfig::get('app_debug'));
```

### 6. 加载路由列表

加载应用定义的路由列表，根据`PATH_INFO`匹配并解析新的URL

### 7. 解析URL

对请求的URL进行解析，得到请求的模块、控制器、方法，检测请求方法是否合法，并输出结果

```php
Controller::callMethod($module, $controller, $method);
```

同时定义新的变量

```php
//定义相关常量
define("__MODULE_PATH__", __APP_PATH__ . $module);                  //模块目录
define("__CONTROLLER_PATH__", __MODULE_PATH__ . '/controller/');    //控制器目录
define("__MODEL__PATH_", __MODULE_PATH__ . '/model/');              //模型目录
define("__VIEW_PATH__", __MODULE_PATH__ . '/view/');                //模板目录
// 保存请求的模块、控制器、方法信息
Request::$rmodule = $module;
Request::$rcontroller = $controller;
Request::$rmethod = $method;
```

### 8. 配置项二次加载

若存在模块配置文件，则加载模块配置文件并覆盖主配置文件中的相同项

### 9. 响应输出

控制器方法需要返回实现了`lazy\Response\BaseResponse`接口的类的实例，若返回其他对象将会抛出一个异常

若返回的值是一个非对象的变量，则会被包装为`lazy\Response\LAZYResponse`类的实例，该类中默认以`echo`输出值

### 10. 日志保存

将过程中记录到内存中的日志写入文件。