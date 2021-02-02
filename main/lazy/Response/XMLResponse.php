<?php
namespace lazy\Response;

class XMLResponse extends LAZYResponse {
    public function __construct($content = '', $code = 200, $headers = []) {
        parent::__construct($content, $code, "text/xml", $headers);
    }
}
