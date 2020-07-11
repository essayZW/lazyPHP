<?php
/**
 * 日志相关操作类
 */
namespace lazy;
class Log{
    private static $logPath;
    private static $log = [];

    private static $autoClearFlag;
    private static $maxMounth;
    private static $clearFlag = false;
    use \lazy\fileOperation;

    /**
     * 删除month个月之前的所有日志
     *
     * @param integer $month
     * @return void
     */
    public static function autoClear($month = -1){
        $dirhandler = opendir(self::$logPath);
        $now = (int)date('Ym');
        while(($filename = readdir($dirhandler)) !== false){
            if($filename == '.' || $filename == '..' || !is_dir(self::$logPath . '/' . $filename)) continue;
            $nm = $now % 100;
            $ny = ($now - $nm) / 100;
            $m = (int)$filename % 100;
            $y = ((int)$filename - $m) / 100;
            $num = ($ny - $y) * 12 + ($nm - $m);
            if($num >= self::$maxMounth){
                self::deldir(self::$logPath . '/' . $filename);
            }
        }
    }
    /**
     * 初始化日志存放路径
     *
     * @param string $path
     * @return void
     */
    public static function init($path, $autoClearFlag = false, $maxMounth = 3){
        self::$logPath = $path;
        self::$autoClearFlag = $autoClearFlag;
        self::$maxMounth = $maxMounth;
    }

    /**
     * 将日志写在内存中
     *
     * @param string $info
     * @param string $type
     * @return void
     */
    public static function record($info, $type = 'log'){
        $arr = [[
            'info' => $info,
            'type' => $type
        ]];
        self::$log = array_merge(self::$log, $arr);
    }

    /**
     * 写入一条日志
     *
     * @param [type] $info
     * @param string $type
     * @return void
     */
    public static function write($info, $type = 'log'){
        $content = "[ $type ] $info\r\n";
        self::writeFile($content);
    }

    /**
     * 写入文件
     */
    private static function writeFile($content){
        $dirname = date('Ym');
        $filename = date('d') . '.log';
        $path = self::$logPath . '/' . $dirname . '/' . $filename;
        if(!file_exists(self::$logPath . '/' . $dirname)){
            mkdir(self::$logPath . '/' . $dirname);
        }
        file_put_contents($path, $content, FILE_APPEND);
        if(self::$autoClearFlag && !self::$clearFlag){
            // 开启了自动清理
            self::$clearFlag = true;
            self::autoClear();
        }
    }

    /**
     * 写入空行
     */
    public static function line(){
        self::writeFile("\r\n");
    }
    /**
     * 将内存中的所有日志保存
     *
     * @return void
     */
    public static function save(){
        $content = '';
        foreach (self::$log as $key => $value) {
            $content .= "[ ". $value['type']. " ] ". $value['info']. "\r\n";
            unset(self::$log[$key]);
        }
        self::writeFile($content);
    }

    /**
     * 写入错误日志
     *
     * @param [type] $info
     * @return void
     */
    public static function error($info){
        self::record($info, 'error');
    }

    /**
     * 写入普通日志
     *
     * @param [type] $info
     * @return void
     */
    public static function info($info){
        self::record($info, 'info');
    }
    /**
     * 写入警告
     *
     * @param [type] $info
     * @return void
     */
    public static function notice($info){
        self::record($info, 'notice');
    }

    /**
     * 写入普通日志
     *
     * @return void
     */
    public static function log($info){
        self::record($info);
    }
    /**
     * 写入debug日志
     *
     * @param [type] $info
     * @return void
     */
    public static function debug($info){
        self::record($info, 'debug');
    }

    /**
     * 写入数据库日志
     *
     * @return void
     */
    public static function sql($info){
        self::record($info, 'sql');
    }

    /**
     * 写入警告日志
     *
     * @return void
     */
    public static function warn($info){
        self::record($info, 'warn');
    }

    /**
     * 写入带有时间的日志
     *
     * @param [type] $info
     * @return void
     */
    public static function time($info){
        self::record($info, date('Y-m-d-h-i-s'));
    }
}