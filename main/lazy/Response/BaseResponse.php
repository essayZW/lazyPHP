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
}
