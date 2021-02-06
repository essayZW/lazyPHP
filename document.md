# 一. 基础

## 1. 框架说明

本框架是仿写的think PHP框架。

连文档也是仿照think PHP5.0看云的文档写的。

目前版本：**1.0.0**

> **框架是通过`PATH_INFO`得到请求的模块控制器方法的，所以需要保证服务器支持`	$_SERVER['PATH_INFO']`变量，并且最好支持URL重写功能以隐藏入口文件和关键目录**
>
> 另外框架需求PHP版本在5.6及以上

## 2. 目录结构


```php
project/
├── app		# 应用目录
│   ├── index			# 默认的index模块
│   │   └── controller	# 模块中的控制器目录
│   │       └── Index.php	# 模块中的Index控制器文件
│   ├── common.php		# 用户扩展函数文件
│   ├── config.php		# 整个应用配置文件
│   ├── database.php	# 整个应用数据库配置文件
│   └── router.php		# 应用路由注册文件
├── extend				# 第三方扩展类库目录
├── main				# 框架核心文件目录
│   ├── lazy			# 框架核心类库目录
│   │   ├── DB				# DB类目录
│   │   │   └── MysqlDB.php	# MySQL类
│   │   ├── AppDebug.php	# 应用异常、错误捕获处理类
│   │   ├── Captcha.php		# 验证码类
│   │   ├── Controller.php	# 控制器类
│   │   ├── Cookie.php		# cookie类
│   │   ├── LAZYConfig.php	# 框架配置类
│   │   ├── Log.php			# 框架日志类
│   │   ├── Model.php		# 框架模型类
│   │   ├── Request.php		# 框架request类
│   │   ├── Router.php		# 框架路由类
│   │   ├── Session.php		# session类
│   │   ├── Validate.php	# 验证器类
│   │   ├── View.php		# 视图类
│   │   └── common.php		# 杂项类以及方法
│   ├── base.php		# 框架环境初始化文件
│   └── main.php			# 解析URL以及路由调用控制器等
├── runtime
│   ├── log		# 框架日志文件目录
│   └── temp	# 框架缓存以及临时文件目录
├── static		# 静态资源目录
├── index.php			# 入口文件
├── README.md
├── favicon.ico
├── .htaccess	# URL重写文件，保护app, extend, main, runtime等目录不被访问
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

同时定义新的变量

```php
//定义相关常量
define("__MODULE_PATH__", __APP_PATH__ . $module);                  //模块目录
define("__CONTROLLER_PATH__", __MODULE_PATH__ . '/controller/');    //控制器目录
define("__MODEL__PATH_", __MODULE_PATH__ . '/model/');              //模型目录
define("__VIEW_PATH__", __MODULE_PATH__ . '/view/');                //模板目录
// 保存请求的模块、控制器、方法信息
Request::$rModule = $module;
Request::$rController = $controller;
Request::$rMethod = $method;
```

### 8. 配置项二次加载

若存在模块配置文件，则加载模块配置文件并覆盖主配置文件中的相同项

### 9. 响应输出

控制器方法需要返回实现了`lazy\Response\BaseResponse`接口的类的实例，若返回其他对象将会抛出一个异常

若返回的值是一个非对象的变量，则会被包装为`lazy\Response\LAZYResponse`类的实例，该类中默认以`echo`输出值

### 10. 日志保存

将过程中记录到内存中的日志写入文件。

# 二. 配置

应用配置文件必须在`project\app\config.php`中以`return`形式返回，暂不支持在应用生命周期内动态更改配置项，可以自定义非框架配置项

>`project`在本文档中代表项目目录

## 1. 应用读取配置

使用`lazy\LAZYConfig::get($config_name)` 可以读取到`$config_name`的配置值

## 2. 动态设置配置

使用`lazy\LAZTConfig::set($name, $val)` 设置指定名称的配置项。

## 3.  数据库配置文件

数据库配置文件在`project/app/database.php`中，支持在应用中动态改变，详情在`模型`中会提到

## 4. 模块独立配置

模块独立配置文件位于`project/app/{模块名}/config.php`，该配置文件会覆盖应用配置文件中的同名配置，但是由于配置文件是在url解析之后引入的，所以对于路由、默认模块、控制器、方法等的配置不生效。其是在框架流程中的第8步执行的。

## 5. 数据库模块独立配置

每个模块可以有该模块独立的数据库配置文件，其位于`project/app/{模块名}/database.php`其会覆盖应用数据库配置文件中的同名项。

# 三. 路由

## 1. 路由模式

该框架的路由功能可以开启或者关闭

配置项名为`url_route_on`，默认为`true`，开启之后应用解析URL之前先在路由列表中匹配并将匹配结果作为新的请求URL， 当该值设为`false`之后直接进行URL解析。

倘若开启了`url_route_must`，则强制使用路由模式，因此需要为每一个页面定义路由，否则匹配不到的时候会抛出错误。该项默认为`false`。

## 2. 路由规则

路由规则定义在`/project/app/router.php`中，以键值数组形式定义，键为路由的匹配模式，值为其匹配结果。
路由以**正则表达式**形式进行匹配或者**字符串匹配**。因此，键名可以是一个**完整的正则表达式**，即必须以`/`包裹，并且特殊字符需要使用`\`转义，使用`preg_replace`函数进行匹配，因此可以在值中使用如同`$1 $2 ……`这样的获取到匹配的相关信息，比如

现有路由规则：`    '/^\/answer\/(\d+)/'=>'/index/index/answer/id/$1'`

当访问：

`http://serverName/answer/5`

会自动解析到：

`http://serverName/index/index/index/id/5`

> 字符串匹配就是直接判断pathinfo与匹配规则是否相等

URL多余的部分`/id/5`会被作为GET表单参数传入，该部分将在后面的**URL 解析**中具体说明

>  同时路由还支持请求类型的定义

当需要指定某URL的请求方法时，将路由定义中的对应规则的值改为一个数组，第一项时匹配结果串，后面几项是其支持的请求方法的字符串，不区分大小写，当使用错误的请求方法请求该URL时会触发一个`E_USER_ERROR`，比如

在上面的路由规则这样修改

`    '/^\/answer\/(\d+)/'=>['/index/index/answer/id/$1', 'POST']`

表明`http://serverName/answer/5`这样的URL只支持POST方法

**默认情况的路由规则是支持所有的请求方法的**

同时路由也可以调用函数进行绑定，如:`lazy\Router::bind('/^\/answer\/(\d+)/','/index/index/answer/id/$1');`可以绑定一个一个路由规则。

路由类提供了以下表所示的绑定函数

**但是这必须在解析路由之前定义,可以定义在`router.php`文件中返回数组的上册，也可以在后续版本提供的插件接口中定义**

| 方法名 |        说明        |
| :----: | :----------------: |
|  bind  | 支持任何方法的路由 |
|  get   |   只支持GET请求    |
|  post  |   只支持POST请求   |
| delete |  只支持delete方法  |
|  put   |   只支持put方法    |

## 3. URL解析规则

通过`$_SERVER['PATH_INFO']`获取到请求的具体URL，之后通过`/`分割URL，按顺序依次作为模块，控制器，方法名，[参数1，值1，……]

> 倘若服务器不支持`$_SERVER['PATH_INFO']`则会使用`$_REQUEST['PATH_INFO']`参数，否则默认为`/`

比如：`http://serverName/index/demo/test/name/essay`

会被解析为：模块:index，控制器：demo，方法：test，GET参数：name=essay

但是由于具体的项目需求，将后面的参数部分会存储起来，可以通过`lazy\request\Request::$pathParamStr`获取到进行自定义的解析。

> 通过URL获得的参数优先级较高，会覆盖`$_GET `中已经有的同名参数值

> 另外，框架不会对`PATH_INFO`信息进行大小写转化处理

框架默认的模块名，控制器名，方法名都为`index`

```php
	//默认模块
    'default_module'                => 'index',
    //默认控制器
    'default_controller'            => 'index',
    //默认方法名
    'default_method'                => 'index',
    // 当请求方法不存在时候的执行方法名
    'error_default_method'          => '_Error',
```

可以在`project\app\config.php`修改，`error_default_method`在后面的**控制器\空操作**部分详细说明 

# 四. 控制器

## 1. 控制器定义

控制器必须放在`project\app\模块名\cotroller\`下，并且控制器文件名必须与文件中的类名保持一致，控制器文件名为控制器名。

**按规定，控制器名最好是首字母大写**

一个典型的控制器类定义如下:

```php
namespace app\index\controller;

class index{
    public function index(){
        return 'Index!';
    }
}
```

其中，`app\index\controller`是命名空间，`index`是模块名，该命名空间的申明是**必须**的，控制器可以不继承任何类，当然也可以继承`lazy\controller\Controller`类，这样就可以使用`Controller`类提供的一些API。控制器方法中返回的返回值会被框架输出

2. 控制器初始化

由于控制器实际上是一个类，于是可以通过PHP的`__construct`魔术方法初始化

## 3. 跳转与重定向

`lazy\controller\Controller`中内置了两个跳转方法`success`和`error`，用于页面跳转提示，使用此方法需要控制器继承`lazy\controller\Controller`

比如：

```php
namespace app\index\controller;

use lazy\Captcha;
use lazy\Controller;
class index extends Controller{

    public function check($code = ''){
        $captcha = new Captcha();
        if($captcha->check($code)){
            $this->success('验证码正确');
        }
        else{
            $this->error('验证码错误');
        }
    }
}
```

该例子中验证了验证码，并进行了跳转，默认跳转到`$_SERVER['HTTP_REFERER']`倘若不存在，则跳转到域名根目录下，默认的等待时间是**3秒**。

函数原型是：

```php
protected function success($info = '', $url = false, $time = 3){};
protected function error($info = '', $url = false, $time = 3){};
```

可以指定跳转到的地址，以及等待时间

跳转页面的模板代码在`lazy\Controller::$pageCode`中存储，可以渲染`$info, $url, $time`三个变量

## 4. 跨模块调用

`lazy\Controller`中有着一个方法`callMethod`，框架正是通过该方法执行指定的模块、控制器、方法的，该函数可以调用一个指定的模块中的控制器中的方法。

函数原型如下：

`public static function callMethod($module, $controller, $method);`

##  5. 空模块

当请求的模块不存在的时候，可以自定义执行一个默认的模块，其在配置文件中配置默认的模块名。

```php
// 当模块不存在的时候的模块
'error_default_module'          => 'error'
```

通过空控制器、空操作的结合可以配置全APP的模块、控制器、方法找不到的时候的错误处理。

现有空模块`error`,其目录控制器文件`project/app/error/controller/Error.php`

```php
<?php
namespace app\error\controller;
class Error{
    public function _Error(){
        return 'error 404!'
    }
}
```

## 6. 空控制器

当请求的控制器不存在的时候，可以自定义执行一个默认的控制器，其在配置文件中配置默认控制器名

```php
// 当请求控制器不存在的时候的控制器
'error_default_controller'      => 'Error'
```

这样可以优化控制器找不到时候的错误页面。

一个典型的空控制器定义如下，其中`_Error`方法是空操作。

```php
<?php
namespace app\index\controller;
class Error{
    public function _Error(){
        return '404';
    }
}
```



## 7. 空操作

当请求的方法不存在的时候，可以自定义执行一个默认的方法，其在配置文件中配置默认方法名

```php
	// 当请求方法不存在时候的执行方法名
    'error_default_method'          => '_Error',
```

这样可以优化方法名找不到时候的错误页面显示。

## 8. 控制器方法返回值

控制器方法返回值会被框架输出，规定返回值必须是实现了`lazy\Response\BaseResponse`接口的对象或者是一个非对象的变量

对于对象，具体的输出行为由对象实现的`lazy\Response\BaseResponse::showPage`方法实现

对于普通变量，其会被包装为`lazyResponse\LAZYResponse`对象

>框架推荐所有的输出通过控制其返回值形式进行输出

# 五. 请求

如果要获取本次请求的相关请求信息，如请求路径，请求参数等，可以使用`lazy\request\Request`类，该类的所有方法支持静态调用。

例如：

```php
$request = new \lazy\Request();
// 获得本次的请求方法
echo 'Request method: '. $request->method(). '<br>';
// 得到名为test的GET表单值
echo 'Get param test value: ' . $request->get('test') . '<br>';
// 得到请求的URL
echo 'Request url : '. $request->url() . '<br>';
// 得到请求的域名
echo 'Request host: ' . $request->host() . '<br>';
// 获得请求的pathinfo信息，这里的值是兼容了$_REQUEST['PATH_INFO']的，即使两者都不存在值也是 '/'
echo 'Path info: '. $request->path() . '<br>';
// 得到请求的路径参数
echo 'Param in path: '. $request->pathParam() . '<br>';
// 得到USER_AGENT头
echo 'HTTP User Agent : ' . $request->getRequestHead('user-agent') . '<br>';
// 得到请求信息中的referer信息
echo 'HTTP Referer: ' . $request->referer() . '<br>';
// 得到请求着的IP地址
echo 'Requester ip address: '. $request->ip() . '<br>';
// 得到当前所运行的模块名
echo 'Request Module Name: '. $request->$module . '<br>';
// 得到当前所运行的控制器名
echo 'Request Controller Name : ' . $request->$controller . '<br>';
// 得到当前所运行的控制器方法名
echo 'Request Method Name: '. $request->$method . '<br>';

```

因为可能用户实际请求的模块、控制器、方法不存在，框架会转到设置好的空模块、空控制器、空操作中进行处理

此时`Request::$module`、`Request::$controller`、`Request::$method` 存储的是当前访问的模块控制器操作信息

因此需要使用`Request::$rModule`、`Request::$rController`、`Request::$rMethod`获取最初请求的模块控制器信息

> 注意：所保存的最初的请求信息是在路由匹配之后的请求信息
>
> 若需要最原始的请求信息
>
> ```php
> $pathInfo = Request::path();
> $res = Request::parsePathInfo($pathInfo);
> ```
>
> 

使用`Postman`访问`http://serverName/index/index/index/test/123`得到以下输出

```
Request method: GET
Get param test value: 123
Request url : /index/index/index/test/123
Request host: serverName
Path info: /index/index/index/test/123
Param in path: /test/123
HTTP User Agent : PostmanRuntime/7.22.0
HTTP Referer: 127.0.0.1
Requester ip address: 127.0.0.1
Request Module Name: index
Request Controller Name : index
Request Method Name: index
```

## 1. 请求后钩子函数注册

可以使用`lazy\Request\AfterRequest::regist`方法注册函数

该函数原型如下

```php
    /**
     * 注册响应发出前的钩子函数
     * @param function $callback 回调函数，函数参数为控制器方法返回的对象,并将新的对象返回
     * @param string $module 生效的模块范围
     * @param string $controller 生效的控制器范围
     */
    public static function regist($callback, $module = '*', $controller = '*');
```

其中`callback`参数可以是一个函数的名称，也可以是一个匿名函数

示例:

```php
AfterRequest::regist(function() {
    // some code
}, 'debug', 'Index');
```

其为debug模块下的Index控制器的所有方法注册了该请求后回调

该回调函数在实例化控制器调用操作前调用，无参数

若返回`null`则正常实例化控制器，若为其他，则作为响应直接截断请求的进行

可以使用`lazy\Response\AfterRequest::unRegist`函数注销对应的回调

其函数原型：

```php
    /**
     * 取消某个handler
     * @param string $module 生效的模块范围
     * @param string $controller 生效的控制器范围
     */
    public static function unRegist($module = '.', $controller = '.', $method = '.')；
```

# 六. 响应

框架中的任何输出到浏览器的内容都由`lazy\Response\LAZYResponse`类及其子类控制，该类实现了`lazy\Response\BaseResponse`接口

框架通过`getContentType`接口获取响应类型，之后通过`setHeader`设置到响应头中，通过`getHeaders`统一设置响应头

之后通过`getCode`获取HTTP状态码并设置

通过`getContent`获取相应内容并用`echo`输出

```php
$contentType = $response->getContentType();
$response->setHeader("Content-Type", $contentType);
$headers = $response->getHeaders();
foreach($headers as $key => $value) {
    header($key . ':' . $value);
}
http_response_code($response->getCode());
echo $response->getContent();
```

## 1. HTML页面响应

返回`lazy\Response\HTMLResponse`类的实例即可

构造函数: `public function __construct($content = '', $code = 200, $headers = [])`

示例:

```php
use function lazy\Response\HTMLResponseBuilder;
public function index(){
    $captcha = new Captcha(80, 30);
    $img = $captcha->set($captcha->str(5));
    $this->assign('imageSrc', $img);
    return HTMLResponseBuilder($this->fetch()); // 或者 return new HTMLResponse($this->fetch());
}
```

其中`HTMLResponseBuilder`是一个助手函数，用来快速创建一个`HTMLResponse`类的实例

## 2. JSON数据响应

返回`lazy\Response\JSONResponse`类的实例即可

构造函数: `public function __construct($content = '', $code = 200, $headers = [])`

示例：

```php
use function lazy\Response\JSONResponseBuilder;
public function json() {
    $content = array(
        "name" => 'essay',
        "age" => 23
    );
    return JSONResponseBuilder($content); // 或者 return new JSONResponse($content);
}
```

其中`JSONResponseBuilder`是一个助手函数，用来快速创建一个`JSONResponse`类的实例

## 3. XML数据响应

返回`lazy\Response\XMLResponse`类的实例即可

构造函数: `public function __construct($content = '', $code = 200, $headers = [])`

示例:

```php
use function lazy\Response\XMLResponseBuilder;
public function xml() {
    $content = <<<EOD
<?xml version="1.0" encoding="ISO-8859-1"?>
<note>
<to>Tove</to>
<from>Jani</from>
<heading>Reminder</heading>
<body>Don't forget me this weekend!</body>
</note>
EOD;
    return XMLResponseBuilder($content);	// 或者 return new XMLResponse($content);
}
```

其中`XMLResponseBuilder`是一个助手函数，用来快速创建一个`XMLResponse`类的实例

## 4. 文件下载响应

返回`lazy\Response\XMLResponse`类的实例即可

构造函数: `public function __construct($filename, $content, $headers = [])`

示例:

```php
use function lazy\Response\FILEResponseBuilder;
public function file() {
    $content = "hello world";
    $name = "test.txt";
    return FILEResponseBuilder($name, $content); // 或者 return new FILEResponse($name, $content);
}
```

其中`FILEResponseBuilder`是一个助手函数，用来快速创建一个`FILEResponse`类的实例

这样访问该控制器方法浏览器则会把这当作一个文件进行下载

## 5. 自定义响应类

自定义响应类可以直接继承已有的`lazy\Response\LAZYResponse`类

或者可以继承其子类``lazy\Response\HTMLResponse``,``lazy\Response\XMLResponse``,``lazy\Response\JSONResponse``,``lazy\Response\FILEResponse``

但是无论如何都必须实现`lazy\Response\BaseResponse`接口

## 6. 响应前函数钩子注册

可以使用`lazy\Response\BeforeResponse::regist`方法注册函数

该函数原型如下

```php
    /**
     * 注册响应发出前的钩子函数
     * @param function $callback 回调函数，函数参数为控制器方法返回的对象,并将新的对象返回
     * @param string $module 生效的模块范围
     * @param string $controller 生效的控制器范围
     */
    public static function regist($callback, $module = '*', $controller = '*');
```

其中`callback`参数可以是一个函数的名称，也可以是一个匿名函数

示例:

```php
BeforeResponse::regist(function($rep) {
    $content = $rep->getContent();
    $rep->setContent("123" . $content);
    return $rep;
}, 'debug', 'Index');
```

其为debug模块下的Index控制器的所有方法注册了该响应前回调

该回调函数在控制器方法返回后调用，参数为控制器方法的返回值(即继承了`BaseResponse`接口的对象)，返回值必须也是同类型的对象

>若控制器返回值为普通的变量比如字符串，数组等，会被先包装为`LAZYResponse`类的实例，之后再执行回调函数

可以使用`lazy\Response\BeforeResponse::unRegist`函数注销对应的回调

其函数原型：

```php
    /**
     * 取消某个handler
     * @param string $module 生效的模块范围
     * @param string $controller 生效的控制器范围
     */
    public static function unRegist($module = '.', $controller = '.', $method = '.')；
```

# 七. 数据库

框架内置了`lazy\DB\MysqlDB`类，提供了简单的对MySQL数据库的增删改查操作，支持用户自定义语句、模板语句执行，内置的增删改查主要通过预处理模板方式，以防止SQL注入。

## 1. 数据库连接

数据库默认有一个配置文件在`project\app\database.php`中，文件内容如下。

```php
return [
    // 服务器地址
    'hostname'          => '127.0.0.1',
    // 数据库名
    'database'          => '',
    // 用户名
    'username'          => '',
    // 密码
    'password'          => '',
    // 端口
    'hostport'          => 3306
];
```

可以配置数据库地址、端口、用户名、密码、项目数据库信息。

在数据库操作函数中会自动根据配置连接数据库然后关闭。

> 注意：该配置文件只有在用户模型继承了框架中`lazy\model\Model`类自动生效，自己实例化`lazy\DB\MysqlDB`类需要手动导入配置

所以可以使用`lazy\DB\MysqlDB->load()`函数主动加载配置信息，参数是一个键值数组，这样之后的所有数据库操作都将会使用该配置。

另外，还可以使用`lazy\DB\MysqlDB->connect()`主动连接数据库，并通过参数自定义本次连接的配置,如：

```php
$db = new \lazy\DB\MysqlDB();
$db->load($configArr);		//  在这里$configArr是上文中那个配置文件中的数组
$db->connect([
    'username' : 'test',
    'password' : 'pass'
]);
```

  这样就可以使用新的用户名和密码**临时**连接数据库，并且将在一次数据库操作之后使用原来的配置。

## 2. 查询

该类提供了`lazy\DB\MysqlDB->select()` 方法进行数据库查询的操作。

同时需要其他方法对查询范围进行约束。

一个简单的查询写法如下：

```php
// $DB是一个已经实例化的类，下文中的也是如此
$DB->table('demo')->where('id', '=', '1')->select();
```

> `select`  方法查询不到信息的时候返回空数组

这样执行的语句相当于:

```sql
SELECT * FROM `demo` WHERE `id`=1
```

> 还支持`find`方法，其只返回一条数据，而`select`返回所有的数据,若find查找不到数据返回`false`

* 可以使用`field`函数限定查询的字段范围

```php
$DB->table('demo')->field('name')->where('id', '=', '1')->select();
```

其SQL语句相当于:

```sql
SELECT `name` FROM `demo` WHERE `id`=1
```

可以使用数组指定多个字段或者多次调用

```php
field(['name', 'num']);
```

与 

```PHP
field('name')->field('num');
```

效果是一样的。

* 同时可以使用`order` 函数对查询结果进行排序

```php
$DB->table('demo')->order('num', 'DESC')->select();	// 通过num字段降序排序
```

默认使用升序排序。

* 可以使用`limit`函数限定查询条数

```php
$DB->table('demo')->limit(5, 2)->select();
```

上例代表从第**3**条开始选取5条数据

## 3. 添加

 使用`lazy\DB\MysqlDB->insert()`方法插入新的数据。返回为布尔值代表操作是否成功

```php
$DB->table('demo')->insert([
						'name' => '张三',
						'num'  => '2019'
 					]);
```

其sql语句为:

``` sql
INSERT INTO `demo`  (`name`, `num`)  VALUES  ('张三', '2019');
```

## 4.  删除

使用`lazy\DB\MysqlDB->delete()` 更新数据。返回值为布尔值。

同样使用`where`函数限定范围。

```php
$DB->table('demo')->where('id', '>', 3)->delete();
```

其SQL语句为：

```sql
DELETE FROM `demo` WHERE `id`>3
```

## 5. 更新

使用`lazy\DB\MysqlDB->update()`函数对数据进行更新，返回布尔值。

```php
$DB->table('demo')->where('id', '=', '2')->update([
    										'name' => 'essay',
    										'num'  => '123'
										]);
```

其SQL语句为：

```sql
UPDATE `demo` SET `name`='essay', `num`='123'  WHERE `id`=2
```

## 6. 自定义语句

由于类中提供的功能比较基础、薄弱，所以提供方法供用户执行自定义的SQL语句或者模板。

使用`lazy\DB\MysqlDB->query()` 执行一句SQL语句，并将结果返回，若是查询语句返回结果数组，其他语句返回布尔值代表语句是否执行成功。

使用`lazy\DB\MysqlDB->prepareAndExecute()`  执行一个SQL模板。

```php
// 执行一句SQL语句
$DB->query('SELECT * FOMR demo');
// 执行模板语句，保证第二个参数中的数组要与模板中的占位符相对应
$DB->prepareAndExecute('SELECT * FROM demo WHERE id=?', ['id' => 1]);
```

## 8. 受影响行数

使用`lazy\DB\MysqlDB->affectedRows()`获得上次SQL操作的受影响行数。

## 9. 主键

使用`lazy\DB\MysqlDB->getPrimaryKey()` 获得指定表的主键。

# 八. 模型

## 1. 模型定义

模型文件必须在`project\app\模块名\model\`目录下面，并且模型中的类必须与文件名保持一致。模型文件名为模型名。

**按规定，模型名最好是首字母大写**

模型可以继承`lazy\model\Model`类，这样就可以直接调用`lazy\DB\MysqlDB`类中的方法。

> 默认模型名和数据库表名对应，于是在该模型中的数据库操作会默认以模型名为表名。（前提是继承了`Model`类）

一个简单的模型定义如下，该模型是`index`模块下，对应数据库中的user表。

```php
namespace app\index\model;
class user{
    
}
```

## 2. 控制器中使用模型

```php
namespace app\index\controller;

use lazy\Controller;
class index extends Controller{

    public function index(){
        $model = $this->model();
    }
}
```

需要控制器类继承`lazy\Controller`类，调用类中的`model`方法实例化一个模型，默认实例化一个与controller同名的模型。

> 这里模型名字区分大小写

或者直接通过命名空间导入

```php
$model = new \app\index\model\Index();
```

> 在模型实例化的时候会自动加载数据库配置文件，以及模块独立配置文件

# 九. 视图

视图功能由`lazy\Controller`重载的`lazy\View`类提供，提供了对HTML 模板的渲染功能。模板文件必须在`project\app\模块名\view`下。模板的具体语法在下一节说明。

## 1. 使用

需要控制器继承`lazy\controller\Controller`类，通过`$this`调用相关方法。**且以下的文档都是在这种情况下所叙述的**

|  方法名   |              说明              |
| :-------: | :----------------------------: |
|   fetch   |      渲染模板，并得到代码      |
|  assign   |           对模板赋值           |
|  noCache  | 下次渲染不使用也不生成缓存文件 |
| fetchPart |          渲染一段代码          |

**注意: `noCache`函数只对下一次的渲染起作用**

## 2. 模板赋值

通过`lazy\View->assign()`方法赋值。可以通过数组形式传参数赋多组值。

## 3. 模板渲染

通过`lazy\View->fetch()`函数渲染，默认渲染与控制器同名的模板文件。返回的是模板渲染之后的结果。

若要指定模板文件，则需要传入其相对该模块的view目录的路径。

模板渲染的原理是通过正则表达式替换为PHP代码，用`extract`函数导入变量，通过`require`引入代码执行，使用`ob_get_clean`获得执行结果。

>  **注意这里模板文件名区分大小写，且不要添加.html后缀**

## 4. 变量转义

框架默认配置对输出变量使用`htmlspecialchars`函数进行转义，可以通过修改配置文件中的`fetch_specialchars `进行调整。或者使用`specialChar`方法调整。

## 5. 系统变量

模板在渲染的时候自动

添加一些框架变量，列表如下：

|        变量名        |                           值或说明                           |
| :------------------: | :----------------------------------------------------------: |
|     \_\_CSS\_\_      |                     `project\static\css`                     |
|      \_\_JS\_\_      |                     `project\static\js`                      |
|    \_\_IMAGE\_\_     |                    `project\static\image`                    |
| \_\_STATIC\_PATH\_\_ |                       `project\static`                       |
|  \_\_ROOT\_PATH\_\_  |                          `project`                           |
|     LazyRequest      | 一个数组，包含了`GET`、`POST`、`FILES`、`URL`、`HOST`、`REFERER`信息 |

> 对LazyRequest的说明

```php
$LazyRequest = [
    'get'       => \lazy\Request::get(),
    'post'      => \lazy\Request::post(),
    'files'     => \lazy\Request::files(),
    'url'       => \lazy\Request::url(),
    'host'      => \lazy\Request::host(),
    'referer'   => \lazy\Request::referer()
]
```

## 6. 视图中PHP代码

框架默认不允许模板中使用PHP代码，可以通过修改配置文件中的`fetch_allow_code`调整，或者使用`allCode`方法调整。

> **修改此项需要删除所有的缓存文件才可生效。**

# 十. 模板

模板功能由`lazy\View`类提供，其与上面的视图的区别是上面的模板类时被`Controller`  重载了模板定位等函数以适配框架中视图功能。以下模板部分文档是上面的一个功能补充。

## 1. 模板定位

对`fetch`函数传入相对于当前目录的模板文件名即可，或者是其绝对目录，且不要加`.html`后缀。

> 若是在Controller中，则是相对于同模块下的view目录。

## 2. 变量输出

在模板中，通过`{$变量名}`的形式对变量名输出。**注意区分大小写**，变量名两端可以允许空格的出现。

有模板文件内容如下：

```
{$name}
```

渲染：

```php
$this->assign('name', 123);
echo $this->fetch();
```

这样变得到了`123` 的输出。

其模板编译为PHP文件如下:

```php
<?php echo htmlspecialchars($name);?>
```

**注意**：这里用了`htmlspecialchars`函数对输出进行处理，可以在配置文件中修改`fetch_specialchars `为`false`禁用输出处理。

> 或者通过`specialChar`函数设置，参数为bool值，为true则进行转义，否则不转义

## 3. 使用函数

可以在变量输出的同时使用函数对变量处理。

语法： `{$变量名|函数名}` 

注意：`|`两边允许出现空格。

例如：

```php
{$name | md5}
```

依旧通过上面的方法渲染，得到结果：

`202cb962ac59075b964b07152d234b70`

> 不支持函数嵌套。

函数传入参数

有以下模板

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    {$time}
    <br>
    {$time|md5}
    <br>
    {$time|date:'Y时m月d日H时i分s秒',###}
</body>
</html>
```

函数传参形式用冒号`:`声明，例如`{$time|date:'Y时m月d日H时i分s秒',###}`，其中`###`被替换为变量，倘若没有`###`，则默认第一个参数为变量。

## 4. 选择结构

语法：

```
{if condition="PHP条件表达式1"}
	//内容	
{elseif conditon="PHP条件表达式2"/}
	//内容
{elseif conditon="PHP条件表达式3"/}
……
{else/}
	//内容
{endif/}
```

例如：

```html
{if condition="$name == 1"}
	<h1>first: {$name}</h1>
{elseif condition="$name >= 2 && $name <= 10"/}
	<h2>second: {$name}</h2>
{else/}
	<h3>third: {$name}</h3>
{endif/}
```

渲染HTML得到的代码：

```php
        <?php if($name == 1){ ?>
        	<h1>first: <?php echo htmlspecialchars($name);?></h1>
        <?php }else if($name >= 2 && $name <= 10){ ?>
        	<h2>second: <?php echo htmlspecialchars($name);?></h2>
        <?php }else{ ?>
        	<h3>third: <?php echo htmlspecialchars($name);?></h3>
        <?php };?>
```

## 4. 循环输出结构

语法:

```
{volist name="循环变量" id="循环每一步的值"}
	// 内容
{/volist}
```

注意：循环输出结构暂时只支持循环中得到值，而得不到索引。

例子：

```html
{volist name="name" id="item"}
	<div>{$item}</div>
{/volist}
```

渲染，`$name = [1, 2, 3, 4]`

结果：

```html
<div>1</div>
<div>2</div>
<div>3</div>
<div>4</div>
```

渲染得到的PHP代码：

```php
<?php foreach($name as $item){ ?>
	<div><?php echo htmlspecialchars($item);?></div>
<?php };?>
```

## 5. 模板注释

语法：

```
{--内容--}
```

 支持多行注释

> 与HTML注释的区别在于，模板注释不会出现在渲染之后的内容中，而是作为PHP注释写入渲染之后的缓存PHP文件

## 6. 模板引入

支持在一个模板中引入另外一个模板文件，提高模板复用功能。

语法：

```
{include file="路径"}
```

**注意： Controller中调用时，路径是相对于该模块下的`view`目录的相对路径，不是相对当前模板的相对路径，而自己实例化`View`类调用时，都是相对于当前目录**

例子：

现有模板`project\app\index\view\a.html`

```html
<!DOCTYPE html>
<html>
<head>
    <title></title>
</head>
<body>
    <form action="./index/index/check" method="POST">
        <input type="text" name="code" required="required">
        {include file="b.html"}
    </form>
</body>
</html>
```

又有模板文件`project\app\index\view\b.html`

```html
<img src="{$imageSrc}" alt="" onclick="window.location.href = '/'" style="cursor:pointer;">
<input type="submit" value="提交">
```

这样就将模板引入了进来，渲染之后的PHP代码为:

```php
<?php /*@MD5:7c07a412f331c629593040bb5b21c866@*/ ?>
<!DOCTYPE html>
<html>
<head>
    <title></title>
</head>
<body>
    <form action="./index/index/check" method="POST">
        <input type="text" name="code" required="required">
        
<?php /*Include from:code.html;Include Start*/ ?>
<img src="<?php echo htmlspecialchars($imageSrc);?>" alt="" onclick="window.location.href = '/'" style="cursor:pointer;">
<input type="submit" value="提交">
<?php /*Include End!*/ ?>

    </form>
</body>
</html>
```

## 7. 模板中使用PHP代码

倘若设置了配置文件中的`fetch_allow_code`值为`true`，则可以在模板中任意地方穿插PHP代码，默认该项是关闭的。

> 或者通过使用`allCode`方法设定其是否允许。

## 8. 渲染代码片段

使用`fetchPart`即可。参数是需要渲染的代码，返回渲染执行之后的结果，还是使用`assign`对变量赋值。

## 9. 模板缓存

在模板渲染之前可以使用`noCache`声明下次渲染不使用缓存，也不生成缓存。且默认情况下是根据缓存文件中存储的模板文件的MD5值与当前模板MD5对比而决定是否重新编译模板。当在`Controller`中渲染时，由于其重载了`getTempFileName` 函数以及`build`函数，其缓存会生成在特定目录下，然而`View`类本身是默认生成在当前目录下的。

## 10. 原样输出

可以使用`literal`标签放置模板标签被解析，其原理是将标签内的内容转化为`base64`编码， 之后用`php`输出。

比如:

```text
{literal}
	test:{$test}
{/literal}
```

这样`{$test}`就不会被渲染。

# 十一. 日志

框架提供自带的日志记录类，每次框架运行的时候会自动记录日志，日志默认保存3个月。

## 1. 配置

在配置文件中有着关于日志的相关配置

```php
    'log_file_path'                 => __LOG_PATH__,
    // 日志文件是否自动清理
    'log_file_autoclear'            => true,
    // 日志文件最长保留时间,单位：月,只有开启自动清理该项才有效
    'log_max_time'                  => 3
```

其中`__LOG_PATH__`是框架定义的环境常量，默认为`project/runtime/log/`目录。

一次访问的日志信息如下：

```
[ log ] [2020年02月25日19时17分39秒] App Start!
[ info ] User IP: 172.17.0.1
[ info ] Request Host: 127.0.0.1:8080
[ info ] Request Url: /lazy/index.php
[ info ] Query String: 
[ info ] Request Method: GET
[ info ] Referer: http://127.0.0.1:8080/lazy/
[ info ] PathInfo: /
[ info ] Router On
[ info ] Matched Router: None
[ info ] Module: index
[ info ] Controller: index
[ info ] Method: index
```

## 2. 使用

日志类依靠`lazy\Log::init()`函数初始化，初始化的时候配置了日志的存放路径、自动清理等信息。

由于框架已经自己初始化了该类，所以可以略过该步。

|    方法    |                描述                |
| :--------: | :--------------------------------: |
| `record()` |   记录指定类型的日志信息在内存中   |
| `write()`  |       立刻写入一条信息到文件       |
|  `save()`  | 将内存中的所有日志信息全部写入文件 |

日志分类：

- log 普通的日志信息
- error 程序错误的日志信息
- warn 程序运行警告的日志信息
- info 程序的输出信息
- notice 程序运行中的警告信息
- debug 程序的调试日志信息
- sql 程序数据库操作日志
- time 包含日志记录时间的日志，时间格式：Y-m-d-h-i-s

于是可以在调用`record`、`write`函数的时候可以传入第二个参数指定日志类型。

如:存入一条错误信息日志：`Log::record('error info', 'error')`

也可以使用专门的函数快速记录不同类别的日志

`Log::info('info')`记录info类型日志

`Log::warn('warning')` 记录warn类型日志，其他类型同理

> 另外可以使用`Log::line()`方法插入一条空行

## 3. 程序异常处理日志

在`project\main\load\common.php`中有日志接口`lazy\logMethod`，其中的`errorLog`提供了`lazy\debug\AppDebug`类的日志记录接口。

## 4. SQL 日志

框架提供的`lazy\DB\MysqlDB`类中使用`project\main\load\common.php`中的`lazy\logMethod`提供的日志接口`sqlLog`记录日志。默认记录通过`MysqlDB`类执行的所有SQL语句。

# 十二．扩展

## 1. 用户扩展函数

默认用户扩展函数文件在`project\app\common.php`中定义，可以通过修改日志文件中的`extra_file_list`项，添加用户扩展函数文件。

```php
// 扩展函数文件，已经有app/common.php，如要继续添加，需要在下面配置
    'extra_file_list'               => [],
```

用户扩展函数文件会在框架载入过程中被引入。

## 2. 第三方扩展类

可以将第三方扩展类库放入`project\extend\`  目录下面。框架已经定义了类库的自动加载方式。

```php
spl_autoload_register(function($className) {
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

例如：在`project\extend\`目录下有一个`demo`类库。其目录结构为：

```
extend
└── demo
    └── demo.php
```

`demo.php`中的内容如下：

```php
<?php
namespace demo;
class demo{
    public function say_hello(){
        return 'Hello World';
    }
}
```

使用该类库

```php
$demo = new \demo\demo(1, 2);
echo $demo->say_hello();
```

# 十三. 其他内置功能

## 1. 验证器

框架内置验证器功能，可以方便的进行数据的验证。

### (1) 使用

在控制器中，如果继承了`lazy\Controller` 类，则可以通过`$this->validate`使用验证器类，且该方式有个好处是可以方便的扩展以及重写验证方法，具体在后面会说明。如果没有继承`Controller`类，则可以实例化`lazy\Validate`类。

### (2) 验证规则

可以在验证器实例化的时候传入验证规则，直接使用`$this->validate`时可以通过`$this->validate->rule`方法添加验证规则。

```php
$rule = [
    'name' => 'isrequire|lenmax:25|lenmin:6',
    'age'  => 'integer|between:1,120'
];
$this->validate->rule($rule);
```

对于一个变量可以有多个验证规则，用`|`分隔。倘若单个验证规则中含有`|`字符，可以使用数组。

```php
$rule = [
            'name' => 'isrequire|lenmax:25|lenmin:6',
            'age'  => ['integer', 'between:1,120']
        ];
```

对于验证规则参数中存在`:`字符的时候，可以用数组

```php
$rule = [
    'name' => [['equal', '123:123'], ['lenmax', 25]]
];
```

这样的二维数组，其中每个子数组的第一个是方法名，后面是参数列表。

其中验证规则名是一个函数，函数的第一个参数是需要验证的信息，验证规则后面的`:`是函数其余需要的参数。

比如：对于`between:1,120`来说，其实相当于`between($age, 1,120)`

### (3) 验证

使用`$this->validate->check()`验证指定的数据。

比如对于上面的验证规则来说

```php
$this->validate->check([
            'name' => $name,
            'age'  => $age
]);
```

可以检验`$name`与`$age`变量的值是否符合规则，并且在检验到第一个错误的时候，就会停止。返回布尔值。

可以使用`batch()`方法忽略错误继续验证所有的数据。

```php
$this->validate->batch()->check([
            'name' => $name,
            'age'  => $age
        ])
```

### (4) 错误信息

可以自定义指定的验证规则验证错误之后的错误信息。

```php
$this->validate->msg([
            'isrequire'       => '该项必须!',			  // 对require
            'lenmax'        => '长度超过限制!',	   	 // 对lenmax
            'name.lenmin'   => '名字长度最小为6',		// 对name变量的lenmin规则
            'integer'       => '必须是整数',			// 对integer规则
            'age.between'   => '年龄范围必须是1~120'  // 对age变量的between规则
]);
```

这样可以使用`getErrorMsg`函数获得错误信息，由于错误信息可能不止一条，所以可以使用`getErrorMsg(true)`得到错误信息数组。

```php
$name = '123';
$age = 0;
// 验证规则如上，错误信息设置如上。
// 验证
var_dump($this->validate->batch()->check([
            'name' => $name,
            'age'  => $age
        ]));
// 打印错误信息
var_dump($this->validate->getErrorMsg(true));
```

得到结果:

```php
bool(false)
array(2) {
  ["name.lenmin"]=>
  string(22) "名字长度最小为6"
  ["age.between"]=>
  string(26) "年龄范围必须是1~120"
}

```

### (5) 内置规则

内置规则有：

* integer

  > 判断是不是整数，使用`filter_var`判断

* float

  > 判断是不是浮点数，使用`filter_val`判断

* boolean

  > 判断是不是布尔值，使用`filter_val判断`

* email

  > 判断是不是一个合法的电子邮件格式，使用`filter_val`判断

* isarray

  > 判断是不是一个数组

* accept

  > 判断是不是数字1或者字符串的`yes`,`on`不区分大小写

* date

  > 判断是不是一个有效的日期，使用`strtotime`判断

* alpha

  > 判断是不是仅有字母组成

* alphaNum

  > 判断是不是仅有字母与数字组成

* url

  > 判断是不是一个有效的URL地址，使用`filter_val`判断

* ip

  > 判断是不是一个合法的IP地址，使用`filter_val`判断

* max

  > 判断数字是不是小于等于一个值

* min

  > 判断数字数不是大于等于一个值

* chs

  > 判断是不是只含有中文

* isChs

  > 判断是不是含有中文

* between

  > 判断数字是不是在某个闭区间内

* equal

  > 判断是否与指定值相等

* lenBetween

  > 判断一个字符串的长度是否在某个闭区间范围内

* substr

  > 判断是不是某字符串的子串

* nowBetween

  > 判断某个数字不在某个区间

* isrequire

  > 判断某个值是否为空，即其是否为true

* lenmax

  > 判断字符串的长度是不是小于等于一个值

* lenmin

  > 判断字符串的长度是不是大于等于一个值

* len

  > 判断字符串的长度是不是等于一个值

* different 

  > 判断是不是不等于某个值

* egt

  > 判断是不是大于等于某个值

* gt

  > 判断是不是大于某个值

* elt

  > 判断是不是小于等于某个值

* lt

  > 判断是不是小于某个值

* regmatch

  > 判断某个字符串是否匹配某正则表达式

### (6) 规则扩展

由于内置规则的局限性，所以允许扩展验证规则。

使用`extend`函数既可以扩展规则 。

```php
$this->validata->extend('test', function($value){
    // some code;
});
```

这样就可以再验证规则中使用新的规则，且同名扩展规则会覆盖原来的规则。

## 2. 验证码

框架内置了验证码的生成以及基本的验证功能，使用`session`存储验证码值，生成普通的字母数字图片，使用点与线干扰。

验证码功能由`lazy\captcha\Captcha`类提供。

### (1) 生成验证码

实例化一个`lazy\captca\Captcha`类，实例化的时候可以传入参数定义生成的验证码图片的尺寸。

```php
$captcha = new Captcha(80, 30);		// 80px宽，30px高
$str = $captcha->str();				// 得到一个验证码值
$img = $captcha->set($str);			// 根据str生成验证码图片，返回的是图片的base64数据
```

也可以后续自定义验证码的宽高:

```php
$captcha->setWidth(100);			// 设置宽
$captcha->setHeight(40);			// 设置高
```



倘若要指定验证码值的长度:

```php
$str = $captcah->str(6);			// 验证码值长度为6，默认5位
// 更改默认长度值
$captcha->setLength(6);				// 更改默认长度为6
```

默认生成的验证码有点和线的干扰，可以设置关闭。

```php
$captcha->isIine(false);			// 取消线干扰
$captcha->isPoint(false);			// 取消点干扰
```

可以更改生成的图片方式为文件，或者以图片形式输出

以图片形式输出:

```php
$captcha->outputImage();			// 这样就会直接输出图片
```

以文件形式保存:

```php
$captcha->saveAsFile($path, $name);	// 文件名若未设置则随机文件名
$captcha->set($str);				// 这样返回图片路径
```

验证码值默认保存在名为`captcha` 的`session`中，可以设置该名字。

```php
$captcha->setSessionName('code');	// 设置名字为code
```

### (2) 验证验证码

```php
$captcha = new Captcha();
$captcha->check($value);		//检查，返回布尔值
```

默认检查的时候不区分大小写，可以设置是否区分大小写

```php
$captcha->isLower();			// 设置区分大小写
```

## 3. cookie

框架内置了简单操作cookie的类：`lazy\Cookie`

cookie使用之前需要初始化该类，默认会读取配置文件中的cookie设置

```php
    // *******************************************************
    // cookie相关设置
    // *******************************************************
    'cookie'                        => [
        // cookie 名称前缀
        'prefix'        => '',
        // cookie 保存时间
        'expire'        => 0,
        // cookie 有效域名
        'domain'        => '',
        // cookie 保存路径
        'path'          => __RELATIVE_ROOT_PATH__,
        // 是否启用安全传输
        'secure'        => false,
        // 是否开启httponly
        'httponly'      => false,
    ]
```

也可以使用`Cookie::init()` 方法初始化。

设置一个cookie

```php
// 设置一个cookie名为test,值为val,一小时后过期
lazy\Cookie::set('test', 'val', 3600);
// 设置cookie使用独立设置
lazy\Cookie::set('test', 'val', [
    'prefix' => 'lazy_'
]);
```

得到指定cookie的值

```php
echo lazy\Cookie::get('test');
```

判断是否存在某个cookie

```php
lazy\Cookie::has('test');
```

删除一个指定的cookie

```php
lazy\Cookie::delete('test');
```

清空所有的cookie

```php
lazy\Cookie::clear();
```

## 4. session

框架内置了简单的操作session的类: `lazy\Session`

开启session回话:

```php
lazy\Session::start();
```

设置一个session：

```php
lazy\Session::set('test', 'val');
```

得到一个session值

```php
echo lazy\Session::get('test');
```

判断是否有某个session

```php
var_dump(lazy\Session::has('test'));
```

删除某个session:

```php
lazy\Session::delete('test');
```

清空所有的session:

```php
lazy\Session::clear();
```

关闭session回话：

```php
lazy\Session::close();
```

