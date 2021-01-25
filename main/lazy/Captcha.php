<?php
/**
 * 提供验证码相关功能的类
 */
namespace lazy;
class Captcha{
    // 验证码图片的宽，单位:px
    private $imgWidth;
    // 验证码图片的高，单位:px
    private $imgHeight;
    // 验证码内容的长度
    private $strLen = 5;
    // 保存验证码字符串的session名
    private $sessionName = 'captcha';
    // 验证码验证的时候是否区分大小写,默认不区分
    private $changeFlag = false;

    public function __construct($width = 80, $height = 20){
        $this->imgHeight = $height;
        $this->imgWidth = $width;
    }
    /**
     * 设置验证码的宽度
     *
     * @param integer $value
     * @return void
     */
    public function setWidth($value){
        $this->imgWidth = $value;
        return $this;
    }
    /**
     * 设置验证码图片的高度
     *
     * @param integer $value
     * @return void
     */
    public function setHeight($value){
        $this->imgHeight = $value;
        return $this;
    }
    // 是否有干扰线
    private $isInterferenceLine = true;
    // 是否有干扰点
    private $isInterferencePoint = true;
    /**
     * 设置是否有干扰线
     *
     * @param boolean $value
     * @return object
     */
    public function isLine($value){
        $this->isInterferenceLine = $value;
        return $this;
    }
    
    /**
     * 设置是否有干扰点
     *
     * @param boolean $value
     * @return object
     */
    public function isPoint($value){
        $this->isInterferencePoint = $value;
        return $this;
    }
    private $outputFlag = false;
    /**
     * 设置是否直接输出图片
     *
     * @return void
     */
    public function outputImage($value = true){
        $this->outputFlag = $value;
        return $this;
    }

    private $filename = '';                  // 保存验证码图片的文件名
    private $saveInFile = false;        // 是否保存文件，优先级最高
    /**
     * 设置保存到某个目录
     *
     * @return void
     */
    public function saveAsFile($path, $name = ''){
        if($name == ''){
            $name = md5(time()) . '.png';
        }
        $this->saveInFile = true;
        $this->filename = $path . '/' . $name;
        return $this;
    }

    /**
     * 设置验证码内容的长度
     *
     * @param [type] $value
     * @return void
     */
    public function setLength($value = true){
        $this->strLen = $value;
        return $this;
    }
    /**
     * 生成一个验证码字符串
     *
     * @return void
     */
    public function str($len = -1){
        if($len == -1) $len = $this->strLen;
        $str = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $res = '';
        for($i = 0; $i < $len; $i ++){
            $res .= $str{rand(0, 61)};
        }
        return $res;
    }
    /**
     * 设置保存验证码的session name
     *
     * @param [type] $str
     * @return void
     */
    public function setSessionName($str){
        $this->sessionName = $str;
        return $this;
    }
    /**
     * 设置一个验证码
     *
     * @param string $str
     * @return void
     */
    public function set($str = ''){
        if($str == '') $str = $this->str();
        // 开启session
        if(!isset($_SESSION)){
            session_start();
        }
        $_SESSION[$this->sessionName] = $str;
        // 生成验证码
        return $this->create($str);
    }

    /**
     * 设置检查的时候是否区分大小写
     */
    public function isLower($value = true){
        $this->changeFlag = $value;
        return $this;
    }
    /**
     * 检测验证码对不对
     *
     * @param [type] $str
     * @return void
     */
    public function check($str){
        if(!isset($_SESSION)){
            session_start();
        }
        if(!isset($_SESSION[$this->sessionName])){
            return false;
        }
        if(!$this->changeFlag){
            $str = strtolower($str);
            return $str === strtolower($_SESSION[$this->sessionName]);
        }
        else{
            return $str === $_SESSION[$this->sessionName];
        }

    }
    /**
     * 创建一个指定字符串的验证码图片
     *
     * @param [type] $str
     * @return void
     */
    private function create($str){
        // 创建一个空白图片
        $imgHandle = imagecreate($this->imgWidth, $this->imgHeight);
        // 设置背景颜色
        $backColor = imagecolorallocate($imgHandle, rand(0, 255), rand(0, 255), rand(0, 255));
        // 设置文字颜色
        $txtColor = imagecolorallocate($imgHandle, rand(0, 255), rand(0, 255), rand(0, 255));

        if($this->isInterferenceLine){
            // 需要设置干扰线
            // 随机产生几条干扰线
            $end = rand(8, 18);
            for($i = 0; $i < $end; $i ++){
                $line = imagecolorallocate($imgHandle, rand(0, 255), rand(0, 255), rand(0, 255));
                imageline($imgHandle, rand(0, $this->imgWidth), rand(0, $this->imgWidth), rand(0, $this->imgHeight), rand(0, $this->imgHeight), $line);
            }
        }

        if($this->isInterferencePoint){
            // 需要设置干扰点
            // 随机生成数目的干扰点
            $end = rand(400, 800);
            for($i = 0; $i < $end; $i ++){
                $point = imagecolorallocate($imgHandle, rand(0, 255), rand(0, 255), rand(0, 255));
                imagesetpixel($imgHandle, rand(0, $this->imgWidth), rand(0, $this->imgHeight), $point);
            }
        }

        // 填充背景颜色
        imagefill($imgHandle, 0, 0, $backColor);
        // 填充字符串
        $last = 0;
        $len = strlen($str);
        for($i = 0; $i < $len; $i ++){
            $last = rand($last + 6, $last + (int)($this->imgWidth / $len));
            imagestring($imgHandle, 28, $last, rand(0, (int)($this->imgHeight / 2) - 3), $str{$i}, $txtColor);
        }
        if($this->saveInFile){
            ob_start();
            imagepng($imgHandle, $this->filename);
            ob_get_clean();
            return $this->filename;
        }
        if($this->outputFlag){
            header("Content-type: image/png"); //生成验证码图片 
            imagepng($imgHandle);
        }
        else{
            ob_start();
            imagepng($imgHandle);
            $res = ob_get_clean();
            return 'data:image/png;base64,' . base64_encode($res);
        } 
    }
}
