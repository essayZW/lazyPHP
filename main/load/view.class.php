<?php

/**
 * 模板操作相关类
 * version:2.2
 * Update Info:
 *      1.新增条件结构渲染
 *      2.支持循环结构嵌套渲染
 *      3.新增缓存文件机制
 *      4.新增渲染添加函数支持
 *      5.新增模板引入功能
 *      6.修复一些bug
 */
namespace lazy\view;
use lazy\request\Request;
class View{
    private $list;                  //需要渲染的变量即对应值列表
    private $useCache = true;              //渲染时是否实用缓存
    public function __construct(){
        $this->list = [];   //初始化
        $this->useCache = true;
    }

    /**
     * 赋值单个变量
     * @param  string $key   变量名
     * @param  mixed  $value 变量值
     */
    private function assignSingle($key, $value){
        $this->list[$key] = $value;
    }

    /**
     * 赋值变量，可以多组
     * @return [type] [description]
     */
    protected function assign($key, $value = ''){
        if(gettype($key) == gettype('')){
            $this->assignSingle($key, $value);
        }
        if(gettype($key) == gettype([])){
            foreach ($key as $k => $v) {
                $this->assignSingle($k, $v);
            }
        }
    }

    /**
     * 导入预设的系统变量
     * @return [type] [description]
     */
    private function loadSystem(){
        $this->assign([
            '__CSS__'           => __CSS__,
            '__JS__'            => __JS__,
            '__IMAGE__'         => __IMAGE__,
            '__STATIC_PATH__'   => __STATIC_PATH__,
            'LazyRequest'       => [
                'get'       => \lazy\request\Request::get(),
                'post'      => \lazy\request\Request::post(),
                'files'     => \lazy\request\Request::files(),
                'url'       => \lazy\request\Request::url(),
                'host'      => \lazy\request\Request::host(),
                'referer'   => \lazy\request\Request::referer()
            ]
        ]);
    }

    /**
     * 渲染指定模板，赋值，并将渲染后的代码返回
     * @param  string $path 模板路径
     * @return [type]       [description]
     */
    protected function fetch($path = false){
        if($path == false && gettype($path) != gettype('')){
            //参数错误,采用默认参数
            $path = Request::controller();
        }
        //导入预设环境变量
        $this->loadSystem();
        $path .= '.html';
        //加载模板代码
        $code = $this->load($path);
        // 先渲染模板引入部分
        $code = $this->assignInclude($code);
        //得到模板代码MD5
        $key = md5($code);
        //查找是否有缓存文件
        if($this->searchTemp($path, $key) && $this->useCache){
            //存在模板文件
            $filename = $this->getTempFileName($path);
        }
        else{
            //渲染变量
            $code = $this->fetchCode($code);
            //编译
            $filename = $this->build($code, $path, $key);
        }
        $code = $this->runCode($filename);
        //渲染完成，初始化
        $this->__construct();
        return $code;
    }

    /**
     * 运行指定文件的PHP代码，并得到结果
     * @param  string $filename [description]
     * @return [type]           [description]
     */
    protected function runCode($filename){
        //引入变量
        extract($this->list);
        //开启输出缓存区
        ob_start();
        //开始引入文件
        require($filename);
        //得到结果
        $code = ob_get_clean();
        //如果不使用缓存，则删除缓存
        if(!$this->useCache){
            unlink($filename);
        }
        return $code;
    }
    /**
     * 渲染代码
     * @param  string $code 需要渲染的代码
     * @return [type] [description]
     */
    protected function fetchCode($code){
        //开始处理
        //根据配置决定是否允许模板运行任意PHP代码
        if(!\lazy\LAZYConfig::get('fetch_allow_code')){
            //不允许PHP代码
            $code = $this->assignCode($code);
        }
        //渲染模板注释
        $code = $this->assignCommet($code);
        //渲染单个变量
        $code = $this->assignValue($this->list, $code);
        //渲染循环标签
        $code = $this->assignVolist($code);
        //渲染条件判断标签
        $code = $this->assignCondition($code);
        return $code;
    }
    /**
     * 渲染模板引入的部分，将引入的代码加载进去
     *
     * @param string $code
     * @return void
     */
    private function assignInclude($code){
        $pattern = '/\{include +?file="(.+?) *?"\}/';
        $code = preg_replace_callback($pattern, function($matches){
            $includeCode = $this->load($matches[1]);
            // 添加引入注释信息
            $includeCode = "\r\n{--Include from:$matches[1];Include Start--}\r\n" . $includeCode . "\r\n{--Include End!--}\r\n";
            return $includeCode;
        }, $code);
        return $code;
    }
    /**
     * 过滤模板中所有的PHP代码
     * @param  string $code     需要处理的代码
     * @return string           处理之后的代码
     */
    private function assignCode($code){
        $pattern = '/<\?php(.*?)\?>/s';
        $code = preg_replace($pattern, '', $code);
        return $code;
    }
    /**
     * 加载一个模板的源代码
     * @param  string $filename 模板文件路径
     * @return string           模板源代码
     */
    private function load($filename){
        if(!\file_exists(__VIEW_PATH__ . $filename)){
            \trigger_error('View ' . __VIEW_PATH__ . $filename . ' Not Exists!', E_USER_ERROR);
            return;
        }
        return file_get_contents(__VIEW_PATH__ . $filename);
    }

    /**
     * 为模板代码单个变量赋值
     * @param  string $name  变量名
     * @param  string $code  模板代码
     * @return string        新的代码
     */
    private function assignSingleValue($name, $code){
        $pattern = '/\{ *?\$' . $name . '(\[.+?\])? *?(\|.+?)?\}/';
        $code = preg_replace_callback($pattern, function($matches) use ($name){
            if(!array_key_exists(1, $matches)){
                $matches[1] = '';
            }
            $value = '$' . $name . $matches[1];
            if(isset($matches[2])){
                $matches[2] = str_replace('|', '', $matches[2]);
                $value = $matches[2] . '(' . $value . ')';
            }
            if(\lazy\LAZYConfig::get('fetch_specialchars')){
                $value = 'htmlspecialchars(' . $value . ')';
            }
            return '<?php echo ' . $value . ';?>';
        }, $code);
        return $code;
    }

    /**
     * 渲染多组值
     * @param  array  $arr  [description]
     * @param  string $code [description]
     * @return [type]       [description]
     */
    private function assignValue($arr, $code){
        foreach ($arr as $k => $v) {
            $code = $this->assignSingleValue($k, $code);
        }
        return $code;
    }

    /**
     * 渲染循环列表标签
     * @param  string $code 需要渲染的代码
     * @return string       渲染之后的代码
     */
    private function assignVolist($code){
        $pattern = '/\{volist +?name="(.+?)" +?id="(\w+) *"\}/';
        $arr = [];
        $code = preg_replace_callback($pattern, function($matches) use(&$arr){
            $arr[$matches[2]] = $matches[1];
            return '<?php foreach($'. $matches[1]. ' as $'. $matches[2]. '){ ?>';
        }, $code);
        $code = str_replace('{/volist}', '<?php };?>', $code);
        $code = $this->assignValue($arr, $code);
        return $code;
    }

    /**
     * 渲染模板注释
     * @param  string $code 需要渲染的代码
     * @return string       渲染之后的代码
     */
    private function assignCommet($code){
        //模板注释
        //形如{--commet--}
        $pattern = '/\{\-\-(.+?)\-\-\}/s';
        $code = preg_replace_callback($pattern, function($matches){
            return '<?php /*' . $matches[1] . '*/ ?>';
        }, $code);
        return $code;
    }
    /**
     * 渲染条件语句
     * @param  string $code 需要渲染的代码
     * @return string       渲染之后的代码
     */
    private function assignCondition($code){
        //先匹配if语句
        $pattern = '/\{if +?condition="(.*?)" *?\}/s';
        $code = preg_replace_callback($pattern, function($matches){
            return '<?php if(' . $matches[1] . '){ ?>';
        }, $code);
        // 匹配else if语句
        $pattern = '/\{elseif +?condition="(.*?)" *?\/\}/';
        $code = preg_replace_callback($pattern, function($matches){
            return '<?php }else if(' . $matches[1] . '){ ?>';
        }, $code);
        // 匹配if结束语句
        $code = str_replace('{else/}', '<?php }else{ ?>', $code);
        $code = str_replace('{endif/}', '<?php };?>', $code);
        return $code;
    }


    /**
     * 编译代码为PHP文件，并返回
     * @param  string $code [description]
     * @param  string $path 模板文件名称
     * @param  string $key  模板文件MD5
     * @return [type]       [description]
     */
    private function build($code, $path, $key){
        //生成文件名
        $filename = md5(Request::module() . Request::controller(). $path) . '.php';
        //生成头文件信息
        $code = "<?php /*@MD5:" . $key . "@*/ ?>\r\n" . $code;
        file_put_contents(__TEMP_PATH__ . $filename, $code);
        return __TEMP_PATH__ . $filename;
    }


    /**
     * 查找对应模板文件是否有编译好的缓存文件
     * @param  string $path 模板文件名
     * @param  string $key  模板文件MD5
     * @return [type]       [description]
     */
    private function searchTemp($path, $key){
        $filename = $this->getTempFileName($path);
        if(file_exists($filename)){
            $info = file($filename)[0];
            $pattern = '/<\?php \/\*@MD5:(\w+?)@\*\/ \?>/';
            $res = '';
            preg_replace_callback($pattern, function($matches) use(&$res){
                $res = $matches[1];
            }, $info);
            return $res == $key;
        }
        return false;
    }

    /**
     * 得到模板文件编译后的缓存文件名
     * @param  string $path [description]
     * @return [type]       [description]
     */
    private function getTempFileName($path){
        return __TEMP_PATH__ . md5(Request::module() . Request::controller(). $path) . '.php';
    }


    /**
     * 下次渲染模板时不使用缓存也不生成缓存
     * @return [type] [description]
     */
    protected function noCache(){
        $this->useCache = false;
        return $this;
    }
}