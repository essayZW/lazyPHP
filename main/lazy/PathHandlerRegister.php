<?php
namespace lazy;
use Exception;

class PathHandlerRegister {
    private $callbackTable = [];
    private $pathInfoToCallback = [];
    /**
     * 注册指定模块控制器回调函数
     * @param function $callback 回调函数
     * @param string $module 生效的模块范围
     * @param string $controller 生效的控制器范围
     */
    public function regist($callback, $module = '*', $controller = '*') {
        if(! is_callable($callback)) throw new Exception("callback must be a callable function");
        $this->callbackTable[] = $callback;
        $index = count($this->callbackTable) - 1;
        if(!is_string($module) || !is_string($controller)) {
            throw new Exception("Module or Controller must be a string");
        }
        $url = "/$module/$controller";
        if(!isset($this->pathInfoToCallback[$url])) {
            $this->pathInfoToCallback[$url] = array();
        }
        $this->pathInfoToCallback[$url] = array_merge($this->pathInfoToCallback[$url], array($index));
    }
    /**
     * 取消某个handler
     * @param string $module 生效的模块范围
     * @param string $controller 生效的控制器范围
     */
    public function unRegist($module = '.', $controller = '.') {
        if(!is_string($module) || !is_string($controller)) {
            throw new Exception("Module or Controller or Method must be a string");
        }
        $pattern = "/^\/$module+\/$controller+$/";
        $this->pathInfoToCallback = array_filter($this->pathInfoToCallback, function($key) use($pattern) {
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
    private function getReigistedHandlerIDByURL($url) {
        $res = [];
        if(isset($this->pathInfoToCallback[$url])) {
            $res = array_merge($res, $this->pathInfoToCallback[$url]);
        }
        return $res;
    }

    /**
     * 得到注册的回调函数列表
     * @param string $module
     * @param string $controller
     * @return array
     */
    public function getRegistedHandler($module, $controller) {
        $res = [];
        $url = "/$module/$controller";
        $res = array_merge($res, $this->getReigistedHandlerIDByURL($url));
        $url = "/*/*";
        $res = array_merge($res, $this->getReigistedHandlerIDByURL($url));
        $url = "/$module/*";
        $res = array_merge($res, $this->getReigistedHandlerIDByURL($url));
        // 去重, 防止重复回调
        $res = array_flip($res);
        $res = array_flip($res);
        // 由于存储的是回调函数编号，需要转化为具体的函数变量
        $callbackTable = [];
        for($index = 0; $index < count($res); $index ++) {
            $callbackTable[$index] = $this->callbackTable[$res[$index]];
        }
        return $callbackTable;
    }

}

