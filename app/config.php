<?php
/**
 * 应用设置
 */

return [
    //应用调试模式
    'app_debug'                     => true,
    //出现非致命错误后是否继续运行整个脚本
    'app_error_run'                 => false,
    //默认模块
    'default_module'                => 'index',
    //默认控制器
    'default_controller'            => 'index',
    //默认方法名
    'default_method'                => 'index',
    //是否使用路由
    'url_route_on'                  => true,
    //模板渲染时是否对值进行转义
    'fetch_specialchars'            => true,
    //模板中是否允许执行任意PHP代码
    //修改此项之后需要对模板中任意地方修改以使重新编译才可生效
    'fetch_allow_code'              => false,
];

