<?php
namespace app\index\model;
use lazy\model\Model;

class index extends Model{
    public function index(){
        // $res = $this->connect([
        //                 'database' => 'test',
        //                 'username' => 'root',
        //                 'password' => 'beihai'
        //             ])->getPrimaryKey('info');
        // $res = $this->connect([
        //     'database' => 'test',
        //     'username' => 'root',
        //     'password' => 'beihai'
        // ])->table('info')
        //          ->where('name', '=', '123')
        //          ->where('id', '>=', 0)
        //          ->field(['id', 'name', 'num'])
        //          ->order('id', 'DESC')
        //          ->limit(5)
        //          ->select();
        // $res = $this->connect([
        //     'database' => 'test',
        //     'username' => 'root',
        //     'password' => 'beihai'
        // ])->prepareAndExecute('SELECT * FROM info WHERE `name`=?', ['张三']);
        // $res = $this->connect([
        //     'database' => 'test',
        //     'username' => 'root',
        //     'password' => 'beihai'
        // ])->query('DELETE FROM info WHERE num=999999');
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