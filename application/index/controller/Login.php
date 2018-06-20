<?php 

namespace app\index\controller;
use think\Request;
use think\Controller;
use app\index\model\User;
use think\Session;

class Login extends Base{
    /**
     * [sign_in 登录页]
    */
    public function sign_in(){

        return $this->fetch('sign_in');
    }
    /**
     * [sign_up 注册页]
    */
    public function sign_up(){

        return $this->fetch('sign_up');
    }

    //登录操作 设置session
	public function login(Request $req){

        $user=new User;

        $data=$req->param();
        if($req->isAjax()){
            if(captcha_check($data["captcha"])){
                return ["msg" =>"success"];
            }else{
                return ["msg" =>"error"];
            }
        }

        $where['tel'] = $data['tel'];
        $where['password'] = md5($data['password']);
        $where['status']=1;

        $res=$user->where($where)->find();

        if($res){
            // 注册用户信息到session
            session('user_id',$res['user_id']);
            session('nickname',$res['nickname']);
            session('password',$res['password']);
            session('avatar',$res['avatar']);
            session('tel',$res['tel']);
            session('sex',$res['sex']);
            session('desc',$res['desc']);

            $_SESSION['IsAuthorized']=true; //授权当前用户上传文件权限

            // 更新最后登录的ip地址  时间稍后增加
            $ip = $req->ip();
            $res->where($where)->update([
                'last_login_ip'  => $ip,  
                'last_login_time'=>time()
            ]);

            //跳转到首页 
            return $this->redirect('index/index'); 
        }else{
            return $this->error('登录失败','index/login/sign_in');
        }
    }

    /**
     * [logout 退出]
    */
    public function logout(){
        $user_id = session("user_id");

        $user = new User;
        $user->save([
            'leave_time'  => time()
        ],['user_id' => $user_id]);

       
        session(null);
        $_SESSION['IsAuthorized']=false; //取消当前用户上传文件权限

        return $this->redirect('index/login/sign_in');      
    }

    /**
     * [register 注册]
    */
    public function register(Request $req){

        $user = new User;
        $data = $req->param();

        $nickname = $data['nickname'];
        $tel = $data['tel'];
        $password = $data['password'];
        $ip = $req->ip();

        if($nickname!=''&&$tel!=''){
            $user->data([
                'nickname'  =>  $nickname,
                'tel'       =>  $tel,
                'password'  =>  md5($password),  
                'last_login_ip' => $ip,  
                'last_login_time'=> time()
            ]);

            $hasUser = User::get(['tel' => $tel]);
            if($hasUser){
                return $this->success("该手机号码已注册,请直接登录",'login/sign_in');
            }

            $res = $user->isUpdate(false)->save();

            if($res){
                return $this->success("注册成功，请登录",'index/login/sign_in');
            }else{
                return $this->error("注册失败",'index/login/sign_up');
            }
        }
    }
}