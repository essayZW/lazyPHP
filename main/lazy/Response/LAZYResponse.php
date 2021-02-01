<?php
namespace lazy\Response;

use Exception;
use lazy\LAZYConfig;

class LAZYResponse implements BaseResponse {
    protected $code = 200;
    protected $content = '';
    protected $type = 'text/html';
    public function __construct($content = '', $code = 200, $type = "text/html") {
        $this->content = $content;
        if(!is_numeric($code)) throw new Exception("http code must be a integer");
        $this->code = $code;
        $this->type = $type;
    }
    public function getCode() {
        return $this->code;
    }
    public function getType() {
        return $this->type;
    }
    public function getContent() {
        return $this->content;
    }

    public function setHeader($name, $value) {
        header($name . ':' . $value);
    }
    public function setContentType($value) {
        $this->setHeader("Content-Type", $value);
    }
    public function showPage() {
        $this->setContentType($this->type);
        http_response_code($this->code);
        $returnPrintMethod = LAZYConfig::get('method_return_print');
        if(!function_exists($returnPrintMethod)) {
            $returnPrintMethod = 'print_r';
        }
        call_user_func($returnPrintMethod, $this->content);
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
