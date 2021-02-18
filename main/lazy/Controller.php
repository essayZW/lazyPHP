<?php

namespace lazy;

use Exception;

class Controller extends View{
    protected $validate;
    private $pageCode = '<!DOCTYPE html><html><head><title>{$title}</title><meta charset="utf-8"><script type="text/javascript">window.onload=function(){let endTime={$time};let now=0;let url="{$url}";let block=document.querySelector("#time");let jump=function(){now++;show(endTime-now,block);if(now<endTime){setTimeout(jump,1000)}else{window.location.href=url}};show(endTime,block);setTimeout(jump,1000);function show(num,position){position.innerHTML="还有"+num+"s跳转到:   "+url}};</script><style type="text/css">*{margin:0px;padding:0px}a,a:hover{text-decoration:none}html,body{width:100%;height:100%}h1{padding-left:20px;font-family:simhei,宋体;margin-top:50px;margin-bottom:10px}#time,#click,pre{display:block;padding-left:20px;margin-bottom:10px}#click{width:200px}pre{width:90%;min-height:200px;margin-top:10px;max-width:300px;font-size:110%;word-break:break-word;white-space:pre-wrap}</style></head><body><h1>{$word}</h1><div id="time"></div><a href="{$url}"id="click">点击立即跳转!</a>{if condition="$info != """}<pre>{$info}</pre>{endif/}</body></html>';
    public function __construct(){
        parent::__construct();
        $this->validate = new Validate();
        $this->allowCode(\lazy\LAZYConfig::get('fetch_allow_code'));
        $this->systemVar = [
            // 因这些常量最后一个是 / 因此，需要去掉方便前端模板使用
            '__CSS__'           => substr(\str_replace('//', '/', __CSS__), 0, -1),
            '__JS__'            => substr(\str_replace('//', '/', __JS__), 0, -1),
            '__IMAGE__'         => substr(\str_replace('//', '/', __IMAGE__), 0, -1),
            '__STATIC_PATH__'   => substr(\str_replace('//', '/', __STATIC_PATH__), 0, -1),
            '__ROOT_PATH__'     => substr(\str_replace('//', '/', __RELATIVE_ROOT_PATH__), 0, -1),
            'LazyRequest'       => [
                'get'       => \lazy\Request::get(),
                'post'      => \lazy\Request::post(),
                'files'     => \lazy\Request::files(),
                'url'       => \lazy\Request::url(),
                'host'      => \lazy\Request::host(),
                'referer'   => \lazy\Request::referer()
            ]
        ];
        $this->specialChar(\lazy\LAZYConfig::get('fetch_specialchars'));
    }

    /**
     * 显示一个操作成功的页面
     * @param  string  $info 需要显示的详细信息
     * @param  string  $url  完成后跳转到的URL
     * @param  integer $time 页面停留时间
     */
    protected function success($info = '', $url = false, $time = 3){
        if($url == false){
            $url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $_SERVER['HTTP_HOST'];
        }
        $this->assign([
            'title'=> 'Success',
            'info' => $info,
            'url'  => $url,
            'time' => $time,
            'word' => '操作成功！'
        ]);
        die($this->fetchPart($this->pageCode));
    }

    /**
     * 显示一个操作失败的页面
     * @param  string  $info 需要显示的详细信息
     * @param  string  $url  完成后跳转到的URL
     * @param  integer $time 页面停留时间
     */
    protected function error($info = '', $url = false, $time = 3){
        if($url === false){
            $url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'http://' . $_SERVER['HTTP_HOST'];
        }
        $this->assign([
            'title'=> 'Error',
            'info' => $info,
            'url'  => $url,
            'time' => $time,
            'word' => '操作失败！'
        ]);
        die($this->fetchPart($this->pageCode));
    }

    /**
     * 实例化一个model类，并返回
     * @param  string $name model名称
     * @return [type]       [description]
     */
    protected function model($name = ''){
        if(!$name){
            $name = \lazy\Request::$controller;
        }
        if(!file_exists(__MODEL__PATH_ . $name . '.php')){
            //模型不存在
            throw new Exception("Model $name Not Exists!");
        }
        \lazy\Log::log('Use model: '. __MODEL__PATH_ . $name . '.php');
        $model = 'app\\' . \lazy\Request::$module . '\model\\' . $name;
        $res = new $model;
        return $res;
    }

    // 关于view中的方法
    /**
     * 得到模板文件编译后的缓存文件名
     * @param  string $path [description]
     * @return [type]       [description]
     */
    protected function getTempFileName($path){
        return __TEMP_PATH__ . md5(Request::$module . Request::$controller. $path) . '.php';
    }

    /**
     * 加载一个模板的源代码
     * @param  string $filename 模板文件路径
     * @return string           模板源代码
     */
    protected function load($filename){
        $path = \lazy\changeFilePath( __APP_PATH__ . '/' .\lazy\Request::$module . '/view/' . $filename);
        return parent::load($path);
    }

    public function fetch($path = false){
        if($path == false && gettype($path) != gettype('')){
            //参数错误,采用默认参数
            $path = Request::$controller;
        }
        return parent::fetch($path);
    }
    public function fetchPart($code){
        $fileName = __TEMP_PATH__ . md5(time()) . '.php';
        $code = $this->fetchCode($code);
        file_put_contents($fileName, $code);
        return $this->noCache()->runCode($fileName);
    }
    /**
     * 编译代码为PHP文件，并返回
     * @param  string $code [description]
     * @param  string $path 模板文件名称
     * @param  string $key  模板文件MD5
     * @return [type]       [description]
     */
    protected function build($code, $path, $key){
        //生成文件名
        $filename = md5(Request::$module . Request::$controller. $path) . '.php';
        //生成头文件信息
        $code = "<?php /*@MD5:" . $key . "@*/ ?>\r\n" . $code;
        file_put_contents(__TEMP_PATH__ . $filename, $code);
        return __TEMP_PATH__ . $filename;
    }
}
