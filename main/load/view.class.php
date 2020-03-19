<?php

/**
 * 模板操作相关类
 */
namespace lazy\view;
class View{
    private $list;                  //需要渲染的变量即对应值列表
    protected $useCache = true;              //渲染时是否实用缓存
    // 是否允许模板中PHP代码的运行
    private $allowCode = false;
    // 系统变量渲染列表
    protected $systemVar = [];
    // 是否对变量输出转义
    private $specialChar = true;
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
    public function assign($key, $value = ''){
        if(gettype($key) == gettype('')){
            $this->assignSingle($key, $value);
        }
        if(gettype($key) == gettype([])){
            foreach ($key as $k => $v) {
                $this->assignSingle($k, $v);
            }
        }
    }

    private $filepath;
    /**
     * 渲染指定模板，赋值，并将渲染后的代码返回
     * @param  string $path 模板路径
     * @return [type]       [description]
     */
    public function fetch($path){
        $this->filepath = $path;
        //导入预设环境变量
        $this->assign($this->systemVar);
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
        $this->list = [];   //初始化
        $this->useCache = true;
        return $code;
    }

    /**
     * @param $code string 需要渲染的代码片段
     * @return string 渲染运行之后的结果
     */
    public function fetchPart($code){
        $fileName = md5(time()) . '.php';
        $code = $this->fetchCode($code);
        file_put_contents($fileName, $code);
        return $this->noCache()->runCode($fileName);
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
            $this->useCache = true;
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
        if(!$this->allowCode){
            //不允许PHP代码
            $code = $this->assignCode($code);
        }
        // 渲染literal
        $code = $this->assignLiteral($code);
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
        $code = preg_replace_callback($pattern, function($matches) use($pattern){
            $includeCode = $this->load($matches[1]);
            // 添加引入注释信息
            $includeCode = "\r\n{--Include from:$matches[1];Include Start--}\r\n" . $includeCode . "\r\n{--Include End!--}\r\n";
            if(preg_match($pattern, $includeCode)){
                $includeCode = $this->assignInclude($includeCode);
            }
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
    protected function load($filename){
        if(!\file_exists($filename)){
            \trigger_error('View ' . $filename . ' Not Exists!', E_USER_ERROR);
            return;
        }
        return file_get_contents($filename);
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
            $funName = '';
            $param = '';
            if(isset($matches[2])){
                $matches[2] = \preg_replace('/^\|(.+?)/', '$1', $matches[2]);
                if(\preg_match('/^(\w+?):(.+?)$/', $matches[2])){
                    $funName = \preg_replace_callback('/^(\w+?):(.+?)$/', function ($matches) use (&$param, $value){
                        $param = $matches[2];
                        if(\strstr($param, '###')){
                            $param = \str_replace('###', $value, $param);
                        }
                        else{
                            $param = $value. ','. $param;
                        }
                        return $matches[1];
                    }, $matches[2]);
                }
                else{
                    $funName = $matches[2];
                    $param = $value;
                }
                $value = $funName . '(' . $param . ')';
            }
            if($this->specialChar){
                $value = 'htmlspecialchars(' . $value . ')';
            }
            return '<?php echo ' . $value . ';?>';
        }, $code);
        return $code;
    }

    /**
     * 渲染literal标签
     *
     * @param [type] $code
     * @return void
     */
    private function assignLiteral($code){
        $pattern = '/{literal}(.*?){\/literal}/s';
        $code = preg_replace_callback($pattern, function($matches){
            $content = \base64_encode($matches[1]);
            return '<?php echo base64_decode("'. $content. '"); ?>';
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
    protected function build($code, $path, $key){
        //生成文件名
        $filename = md5($this->filepath) . '.php';
        //生成头文件信息
        $code = "<?php /*@MD5:" . $key . "@*/ ?>\r\n" . $code;
        file_put_contents($filename, $code);
        return $filename;
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
    protected function getTempFileName($path){
        return md5($this->filepath) . '.php';
    }


    /**
     * 下次渲染模板时不使用缓存也不生成缓存
     * @return [type] [description]
     */
    protected function noCache(){
        $this->useCache = false;
        return $this;
    }
    /**
     * 切换是否对输出转义
     *
     * @param boolean $flag
     * @return void
     */
    public function specialChar($flag = true){
        $this->specialChar = $flag;
    }
    /**
     * 设置是否支持模板中的PHP代码
     *
     * @param boolean $flag
     * @return void
     */
    public function allowCode($flag = false){
        $this->allowCode = $flag;
    }
}