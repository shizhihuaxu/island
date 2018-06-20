<?php 
namespace app\admin\controller;
use think\Request;
use think\Controller;
use app\admin\model\Admin;
use think\Session;

class Login extends Base{
	public function sign_in(){

        return $this->fetch('sign_in');
    }
    /**
     * [login 登录]
    */
    public function login(Request $req){

        $data=$req->param();

        $where['username'] = $data['username'];
        $where['password'] = md5($data['password']);

        $res=Admin::where($where)->find();

        if($res){
            // 注册用户信息到session
            session('admin_id',$res['admin_id']);
            session('username',$res['username']);
            session('password',$res['password']);

            $_SESSION['IsAuthorized']=true; //授权当前用户上传文件权限

            //跳转到后台管理首页 
            return $this->redirect('admin/index/index'); 
        }else{
            return $this->error('登录失败','admin/login/sign_in');
        }
    }
    /**
     * [logout 退出]
    */
    public function logout(){
        $req = request();
        session(null);
        $_SESSION['IsAuthorized']=false; //取消当前用户上传文件权限

        return $this->redirect($req->root(true).'/admin/login/sign_in');      
    }
}
?>