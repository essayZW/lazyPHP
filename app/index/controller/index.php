<?php
namespace app\index\controller;
use lazy\controller\Controller;

class index extends Controller{
    public function index(){
        // $this->validate->rule('name', 'integer|max:20|min:10|between:13,19');
        // $this->validate->rule('xss', 'integer');
        // $this->validate->msg([
        //     'name.integer' => 'name不是一个整数',
        //     'name.max'     => '不能大于20',
        //     'name.min'     => '不能小于10',
        //     'name.between' => '必须在13和19之间',
        //     'integer'  => '必须是一个整数',
        // ]); 
        // var_dump($this->validate->batch()->check(['name' => (float)(\lazy\request\Request::get('get')), 'xss' => '1']));
        // var_dump($this->validate->getErrorMsg());
        return $this->fetch();
    }

}