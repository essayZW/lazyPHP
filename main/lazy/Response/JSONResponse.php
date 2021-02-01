<?php
namespace lazy\Response;
class JSONResponse extends LAZYResponse {
    public function __construct($content = '', $code = 200) {
        parent::__construct($content, $code, "application/json");
    }
    public function showPage() {
        $this->setContentType($this->type);
        http_response_code($this->code);
        if(is_array($this->content) || is_object($this->content)) {
            $this->content = json_encode($this->content);
        }
        echo $this->content;
    }
}
