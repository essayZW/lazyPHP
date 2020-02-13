<?php
namespace app\index\controller;

use lazy\captcha\Captcha;
use lazy\controller\Controller;
use lazy\view\View;
class index extends Controller{

    public function index($name){
        $captcha = new Captcha(100, 30);
        $image = $captcha->set($captcha->str(5));
        $view = new View(); 
        $view->assign('imageSrc', $image);
        return $view->fetch();
    }

    public function check($code = ''){
        $captcha = new Captcha();
        if($captcha->check($code)){
            $this->success('验证码正确');
        }
        else{
            $this->error('验证码错误');
        }
    }

    public function answer(){
        return \lazy\request\Request::get('id');
    }

    public function extend(){
        $a = \lazy\Vendor('demo.demo', 'demo');
        return $a->say_hello();
    }

    public function _Error(){
        return 'Error!';
    }
}