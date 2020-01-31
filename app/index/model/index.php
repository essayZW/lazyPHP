<?php
namespace app\index\model;
use lazy\model\Model;

class index extends Model{
    public function index(){
        $res = $this->getPrimaryKey('info');
        // $res = $this->table('info')
        //          ->where('name', '=', '123')
        //          ->where('id', '>=', 0)
        //          ->field(['id', 'name', 'num'])
        //          ->order('id', 'DESC')
        //          ->limit(5)
        //          ->select();
        // $res = $this->prepareAndExecute('SELECT * FROM info WHERE `name`=?', ['张三']);
        // $res = $this->query('DELETE FROM info WHERE num=999999');
        // echo $this->affectedRows();
        // $res = $this->table('info')
        //          ->insert([
        //                  // 'id'     => 1,
        //                  'name'  => 'test',
        //                  'num'   => 999999
        //              ]);
        // $res = $this->table('info')
        //          ->where('num', '=', 999999)
        //          ->delete();
        // $res = $this->table('info')
        //          ->where('num', '=', 123)
        //          ->where('id', '=', 29)
        //          ->update([
        //              'name' => 'test',
        //              'num'  => 123456
        //          ]);
        // $res = $this->table('info')
        //          ->field('`*`')
        //          ->select();
        // return $res;
        // $this->getAllProtype();
    }
}