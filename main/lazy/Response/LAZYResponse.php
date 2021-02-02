<?php
namespace lazy\Response;

use Exception;

class LAZYResponse implements BaseResponse {
    protected $code = 200;
    protected $content = '';
    protected $type = 'text/html';
    protected $headers = [];
    public function __construct($content = '', $code = 200, $type = "text/html", $headers = []) {
        if(!is_array($headers)) throw new Exception("headers must be a key-value array");
        $this->headers = $headers;
        $this->setContent($content);
        $this->setCode($code);
        $this->setContentType($type);
    }
    public function setCode($code) {
        if(!is_numeric($code)) throw new Exception("http code must be a integer");
        $this->code = $code;
    }
    public function getCode() {
        return $this->code;
    }
    public function setContentType($value) {
        $this->type = $value;
        $this->setHeader("Content-Type", $value);
    }
    public function getContentType() {
        return $this->type;
    }
    public function setContent($content) {
        $this->content = $content;
    }
    public function getContent() {
        return $this->content;
    }
    public function setHeader($name, $value) {
        if(!is_string($name)) throw new Exception("header name must be a string");
        $this->headers[$name] = $value;
    }
    public function getHeaders() {
        return $this->headers;
    }

    /**
     * @return object
     */
    public static function BuildFromVariable($variable) {
        if(is_object($variable) && $variable instanceof LAZYResponse) return $variable;
        $resObject = new self($variable);
        return $resObject;
    }
}
