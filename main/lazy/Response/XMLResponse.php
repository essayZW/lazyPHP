<?php
namespace lazy\Response;

class XMLResponse extends LAZYResponse {
    public function __construct($content = '', $code = 200) {
        parent::__construct($content, $code, "text/xml");
    }
    public function showPage() {
        $this->setContentType($this->type);
        http_response_code($this->code);
        echo $this->content;
    }
}
