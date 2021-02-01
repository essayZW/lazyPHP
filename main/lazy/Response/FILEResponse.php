<?php
namespace lazy\Response;

class FILEResponse extends LAZYResponse {
    protected $filename;
    public function __construct($filename, $content) {
        $this->filename = $filename;
        parent::__construct($content, 200, "application/octet-stream");
    }
    public function showPage() {
        $this->setContentType($this->type);
        $name = $this->filename;
        $this->setHeader("Content-Disposition", "attachment; filename=$name");
        http_response_code($this->code);
        ob_clean();
        echo $this->content;
    }
}
