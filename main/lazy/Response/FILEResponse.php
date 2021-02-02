<?php
namespace lazy\Response;

class FILEResponse extends LAZYResponse {
    protected $filename;
    public function __construct($filename, $content, $headers = []) {
        parent::__construct($content, 200, "application/octet-stream", $headers);
        $this->filename = $filename;
        $this->setHeader("Content-Disposition", "attachment; filename=$filename");
    }
}
