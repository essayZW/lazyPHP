<?php
namespace lazy\Response;

interface BaseResponse {
    /**
     * 返回HTTP 状态码
     * @return integer
     */
    public function getCode();
    /**
     * 设置HTTP 状态码
     */
    public function setCode($code);
    /**
     * 返回内容响应类型
     * @return string
     */
    public function getContentType();
    /**
     * 设置内容相应类型
     */
    public function setContentType($type);
    /**
     * 返回响应内容
     * @return string
     */
    public function getContent();
    /**
     * 设置相应内容
     */
    public function setContent($content);
    /**
     * 返回设置的响应头
     * @return array
     */
    public function getHeaders();
    /**
     * 设置响应头
     */
    public function setHeader($name, $value);

    /**
     * 常见响应头中Content-type值常量定义
     */
    const HTML_TYPE = "text/html";
    const JSON_TYPE = "application/json";
    const XML_TYPE = "text/xml";
    const PLAIN_TYPE = "text/plain";
    const GIF_IMAGE_TYPE = "image/gif";
    const JPG_IMAGE_TYPE = "image/jpeg";
    const PNG_IMAGE_TYPE = "image/png";
    const PDF_TYPE = "application/pdf";
    const MSWORD_TYPE = "application/msword";
    const OCTET_TYPE = "application/octet-stream";
    const WAV_AUDIO_TYPE = "audio/x-wav";
    const MP3_TYPE = "audio/mp3";
    const MP4_TYPE = "video/mpeg4";
    const AVI_TYPE = "video/avi";
}
