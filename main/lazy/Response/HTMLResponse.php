<?php
namespace lazy\Response;

class HTMLResponse extends LAZYResponse {
    public function __construct($content = '', $code = 200, $headers = []) {
        parent::__construct($content, $code, 'text/html', $headers);
    }
}
