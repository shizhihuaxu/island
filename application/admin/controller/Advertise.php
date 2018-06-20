<?php
namespace app\admin\controller;
use think\Request;
use think\Controller;
use app\admin\model\Daily;

class Advertise extends Base{
    public function index ()
    {
        return $this->fetch('index');
    }
    /**
    	[add添加一条] 
    */
    public function add(Request $req)
    {
       
    }
     /**
    	[delete 删除] 
    */
    public function delete(Request $req)
    {
       //
    }
}