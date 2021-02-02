<?php
namespace lazy\Response;

use \Exception;
class BeforeResponse {
    private static $callbackTable = [];
    private static $pathInfoToCallback = [];
    /**
     * 注册响应发出前的钩子函数
     * @param function $callback 回调函数，函数参数为控制器方法返回的对象,并将新的对象返回
     * @param string $module 生效的模块范围
     * @param string $controller 生效的控制器范围
     * @param string $method 生效的控制器操作范围
     */
    public static function regist($callback, $module = '*', $controller = '*', $method = '*') {
        if(! is_callable($callback)) throw new Exception("callback must be a callable function");
        self::$callbackTable[] = $callback;
        $index = count(self::$callbackTable) - 1;
        if(!is_string($module) || !is_string($controller) || !is_string($method)) {
            throw new Exception("Module or Controller or Method must be a string");
        }
        $url = "/$module/$controller/$method";
        if(!isset(self::$pathInfoToCallback[$url])) {
            self::$pathInfoToCallback[$url] = array();
        }
        self::$pathInfoToCallback[$url] = array_merge(self::$pathInfoToCallback[$url], array($index));
    }
    /**
     * 取消某个handler
     * @param string $module 生效的模块范围
     * @param string $controller 生效的控制器范围
     * @param string $method 生效的控制器操作范围
     */
    public static function unRegist($module = '.', $controller = '.', $method = '.') {
        if(!is_string($module) || !is_string($controller) || !is_string($method)) {
            throw new Exception("Module or Controller or Method must be a string");
        }
        $pattern = "/^\/$module+\/$controller+\/$method+$/";
        self::$pathInfoToCallback = array_filter(self::$pathInfoToCallback, function($key) use($pattern) {
            if(preg_match($pattern, $key)) {
                return false;
            }
            return true;
        }, ARRAY_FILTER_USE_KEY);
    }
    /**
     * 通过URL得到回调函数编号列表
     * @param string $url
     * @return array
     */
    public static function getReigistedHandlerIDByURL($url) {
        $res = [];
        if(isset(self::$pathInfoToCallback[$url])) {
            $res = array_merge($res, self::$pathInfoToCallback[$url]);
        }
        return $res;
    }

    /**
     * 得到注册的回调函数列表
     * @param string $module
     * @param string $controller
     * @param string $method
     * @return array
     */
    public static function getRegistedHandler($module, $controller, $method) {
        $res = [];
        $url = "/$module/$controller/$method";
        $res = array_merge($res, self::getReigistedHandlerIDByURL($url));
        $url = "/*/*/*";
        $res = array_merge($res, self::getReigistedHandlerIDByURL($url));
        $url = "/$module/*/*";
        $res = array_merge($res, self::getReigistedHandlerIDByURL($url));
        $url = "/$module/$controller/*";
        $res = array_merge($res, self::getReigistedHandlerIDByURL($url));
        // 去重, 防止重复回调
        $res = array_flip($res);
        $res = array_flip($res);
        // 由于存储的是回调函数编号，需要转化为具体的函数变量
        $callbackTable = [];
        for($index = 0; $index < count($res); $index ++) {
            $callbackTable[$index] = self::$callbackTable[$res[$index]];
        }
        return $callbackTable;
    }
}
