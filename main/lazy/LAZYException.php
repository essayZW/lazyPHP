<?php

namespace lazy;

use Error;
use Exception;

class LAZYException extends Exception implements BaseException {
    protected $envInfo = [];
    public function __construct($message, $code = 500, $envInfo = [], Exception $previous = null) {
        $this->envInfo = $envInfo;
        parent::__construct($message, $code, $previous);
    }
    public function getEnvInfo() {
        return array_merge($this->envInfo, get_defined_vars());
    }

    public function getErrorPage($debug) {
        return null;
    }

    public function getResponseType() {
        return "text/html";
    }

    /**
     * 从PHP 自带Exception类生成框架异常类
     * 生成类为当前调用者类
     */
    public static function BuildFromPHPException($exception) {
        if($exception instanceof self) return $exception;
        if($exception instanceof Error) return self::BuildFromPHPError($exception);
        $reflectionClass = new \ReflectionClass($exception);
        $className = get_called_class();
        $resObject = new $className($exception->getMessage(), $exception->getCode());
        $resObject->file = $exception->file;
        $resObject->line = $exception->line;
        $prop = $reflectionClass->getProperty("trace");
        $prop->setAccessible(true);
        $trace = $prop->getValue($exception);
        $prop->setValue($resObject, $trace);
        $prop = $reflectionClass->getProperty("previous");
        $prop->setAccessible(true);
        $previous = $prop->getValue($exception);
        $prop->setValue($resObject, $previous);
        return $resObject;
    }
    /**
     * 从PHP 自带Error类生成框架异常类
     * 生成类为当前调用者类
     */
    public static function BuildFromPHPError($error) {
        if($error instanceof self) return $error;
        if($error instanceof Exception) return self::BuildFromPHPException($error);
        $className = get_called_class();
        $resObject = new $className($error->getMessage(), $error->getCode());
        $reflectionClass = new \ReflectionClass($error);
        $prop = $reflectionClass->getProperty("line");
        $prop->setAccessible(true);
        $line = $prop->getValue($error);
        $prop = $reflectionClass->getProperty("file");
        $prop->setAccessible(true);
        $file = $prop->getValue($error);
        $reflectionClass = new \ReflectionClass($resObject);
        $prop = $reflectionClass->getProperty("line");
        $prop->setAccessible(true);
        $prop->setValue($resObject, $line);
        $prop = $reflectionClass->getProperty("file");
        $prop->setAccessible(true);
        $prop->setValue($resObject, $file);
        return $resObject;
    }

}
