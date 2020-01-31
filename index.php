<?php

/**
 * 入口文件
 * 提供用户自定义的全局变量
 */

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
