<?php 

namespace app\index\controller;
use think\Controller;
use think\Request;
use think\Image;
use think\Validate;
use think\File;
use app\index\model\User;


class UserSet extends Base{
	/**
     * [user_set 显示用户信息设置页]
 	*/
	public function user_set(){
		return $this->fetch("user_set");
	}
	/**
     * [user_update 修改用户信息]
 	*/
	public function user_update(Request $req){
		$avatar = request()->file('avatar');		//获取上传头像
		$data	= $req->param();					//其它用户信息
		$msg 	= "未知错误";
		$isRight=1;
		$user   = new User();

		// 上传头像 移动application/uploads目录下
	    if($avatar){
	        $img = $avatar->validate(['ext'=>'jpg,png'])
	        			  ->rule('md5')
	        			  ->move(APP_PATH. DS .'uploads'. DS .'head');
	        if($img){
	        	
	        	$filename = $img->getSaveName(); 
	        	$avatar_name = str_replace("\\", "/", $filename);  

	        	$flag=$user->update(['user_id' => $data['user_id'], 'avatar' => $avatar_name]);  //移动成功更新数据库图片地址
	        	if(!$flag){
	        		return ["msg"=>"头像上传失败"];
	        	}
	        }else{
	        	$msg=$avatar->getError();
	           return ["msg"=>$msg];	
	        }
	    }

	    // 数据转化与验证
	    $rules=[
			  'tel'    => 'require|number|length:11',
	          'password' => 'min:6'
	        ];
	    $validate = new Validate($rules);

		if($data["password"]==""){
			$data["password"]=session("password");//如果未修改密码 则使用原密码
		}else{

	        $isRight = $validate->check([
	           'tel'  	  => $data['tel'],
	           'password' => $data['password']
	        ]);

	        $data["password"]=md5($data["password"]);
		}


       //更新数据库
        if($isRight){
			$res=$user->allowField(['nickname','password','tel','sex','desc'])
				  	  ->update($data, ['user_id' => $data['user_id']]); 
 
        }else{
        	$msg=$validate->getError();
        	return ["msg"=>$msg];

        }
        // 更新session
		if($res!=""){
			//更新session
			$info=$user->where('user_id',$data['user_id'])->find();
            session('nickname',$info['nickname']);
            session('password',$info['password']);
            session('avatar',$info['avatar']);
            session('tel',$info['tel']);
            session('sex',$info['sex']);
            session('desc',$info['desc']);

            $msg = "success";
		}else{
			$msg = "更新失败";
		}
		return ["msg"=>$msg];
	}
}