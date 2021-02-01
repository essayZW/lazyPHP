<?php
namespace lazy\Exception;

use Throwable;

interface BaseException extends Throwable {
    /**
     * 返回发生异常时的环境中的变量信息,数组形式
     * @return array
     */
    public function getEnvInfo();
    /**
     * 返回自定义异常页面代码
     * 若返回null则使用默认异常页
     * @param boolean 系统是否开启DEBUG
     */
    public function getErrorPage($debug);
    /**
     * 错误页面的Content-Type
     * @return string
     */
    public function getResponseType();
}
