<?php
namespace app\admin\controller;
use think\Request;
use think\Controller;
use app\admin\model\Admin;

class Admins extends Base{
    /**
        [index 修改密码页面 ]
    */
    public function index ()
    {
        return $this->fetch('index');
    }
    /**
        [change_pass 修改密码 ]
    */
     public function change_pass (Request $req)
    {
       $data = $req->param();
       $msg = "";
       $old_pass = $data["old_pass"];
       $new_pass = $data["new_pass"];

       $admin = Admin::get(1);//admin_id
       $pass = $admin->password;

       if(md5($old_pass)==$pass){
	       	$admin->password = md5($new_pass);
	       	$res = $admin->save();
	       	if($res){
	       		$msg = "success";
	       	}
       }else{
       		$msg = "原密码错误";
       }
       return ["msg"=>$msg];
    }
}