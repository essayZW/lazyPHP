<?php
namespace app\index\controller;
use lazy\controller\Controller;

class index extends Controller{
    public function index(){
        return $this->fetch();
    }

}