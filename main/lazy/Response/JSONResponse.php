<?php
namespace lazy\Response;
class JSONResponse extends LAZYResponse {
    public function __construct($content = '', $code = 200, $headers = []) {
        $content = json_encode($content);
        parent::__construct($content, $code, self::JSON_TYPE, $headers);
    }
}
