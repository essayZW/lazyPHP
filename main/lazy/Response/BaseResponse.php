<?php
namespace lazy\Response;

interface BaseResponse {
    /**
     * 返回HTTP 状态码
     * @return integer
     */
    public function getCode();
    /**
     * 返回内容响应类型
     * @return string
     */
    public function getType();
    /**
     * 返回响应内容
     * @return string
     */
    public function getContent();
    /**
     * 输出响应页面
     */
    public function showPage();
    /**
     * 设置相应头
     */
    public function setHeader($name, $value);
}
