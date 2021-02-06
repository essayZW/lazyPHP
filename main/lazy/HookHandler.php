<?php
namespace lazy;
/**
 * 钩子函数注册以及取消注册获取等
 */
trait HookHandler {
    private static $register = null;

    /**
     * 注册指定模块控制器回调函数
     * @param function $callback 回调函数
     * @param string $module 生效的模块范围
     * @param string $controller 生效的控制器范围
     */
    public static function regist($callback, $module = '*', $controller = '*') {
        if(self::$register == null) self::$register = new PathHandlerRegister();
        self::$register->regist($callback, $module, $controller);
    }
    /**
     * 取消某个handler
     * @param string $module 生效的模块范围
     * @param string $controller 生效的控制器范围
     */
    public static function unRegist($module = '.', $controller = '.') {
        if(self::$register == null) self::$register = new PathHandlerRegister();
        self::$register->unRegist($module, $controller);
    }
    /**
     * 得到注册的回调函数列表
     * @param string $module
     * @param string $controller
     * @return array
     */
    public static function getRegistedHandler($module, $controller) {
        if(self::$register == null) self::$register = new PathHandlerRegister();
        return self::$register->getRegistedHandler($module, $controller);
    }
}
