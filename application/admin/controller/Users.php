<?php 
namespace app\admin\controller;
use think\Request;
use think\Image;
use think\File;
use think\Controller;
use app\admin\model\User;

class Users extends Base{
	// 用户列表页
	public function index(){

        return $this->fetch('index');
    }
    // 启用用户
    public function open(Request $req){
    	$data=$req->param();
    	$user_id = $data['user_id'];

    	$user = new User;
		$isUpdated = $user->save([
		    'status'  => 1
		],['user_id' => $user_id]);

		if($isUpdated){
			return ["msg"=>"success"];
		}
    }
     // 停用用户
    public function stop(Request $req){
    	$data=$req->param();
    	$user_id = $data['user_id'];

    	$user = new User;
		$isUpdated = $user->save([
		    'status'  => 0
		],['user_id' => $user_id]);

		if($isUpdated){
			return ["msg"=>"success"];
		}
    }
   /**
	 * 异步获取更多
	 */
	public function getList(Request $req) {
		$data = $req->param();
		$list = Array();

		if ($data['page'] != '') {
			$page = $data['page'];
			$page = ($page - 1) * 10;
		} else {
			$page = 0;
		}

		if("" != $data["keyword"]){
			$where["nickname|last_login_ip"] = ['like', "%" . $data["keyword"] . "%"];
			$userList = User::where($where)->limit($page, 10)->select();
			$count = User::where($where)->count();
		}else{
			$userList = User::limit($page, 10)->select();
			$count = User::count();
		}
		
		foreach($userList as $key=>$res){
			$list[$key]=[
				"user_id" => $res->user_id,
				"nickname"   => $res->nickname, 
				"last_login_ip"   => $res->last_login_ip, 
				"last_login_time"   => date("Y-m-d",intval($res->last_login_time)),
				"status" => $res->status
			];

		}
		
		empty($list) && $list = array();
		return json(array('list' => $list, 'count' => $count));
	}
}
?>