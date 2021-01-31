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
    /**
     * @return object
     */
    public function setEnvInfo($info) {
        $this->envInfo = $info;
        return $this;
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

    public static function BuildFromPHPException($exception) {
        if($exception instanceof Error) return self::BuildFromPHPError($exception);
        $reflectionClass = new \ReflectionClass($exception);
        $resObject = new self($exception->getMessage(), $exception->getCode());
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
    public static function BuildFromPHPError($error) {
        if($error instanceof Exception) return self::BuildFromPHPException($error);
        $resObject = new self($error->getMessage(), $error->getCode());
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
