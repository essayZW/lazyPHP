<?php
namespace lazy\Response;

class HTMLResponse extends LAZYResponse {
    public function __construct($content = '', $code = 200) {
        parent::__construct($content, $code, 'text/html');
    }
    public function showPage() {
        $this->setContentType($this->type);
        http_response_code($this->code);
        echo $this->content;
    }
}
