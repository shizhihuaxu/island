<?php
namespace app\index\controller;
use think\Controller;
use think\Request;
use think\Session;
use think\Image;
use think\Validate;
use think\File;
use app\index\model\Daily;
use app\index\model\User;
use app\index\model\Focus;
use app\index\model\Comment;
use app\index\model\Fragment;
use app\index\model\Article;
use app\index\model\Feed;

class Base extends Controller{

	public function __construct() {
		parent::__construct();

		$this->notify();
	}

	/**
	 *	notify   消息通知
	 */
	public function notify(){
		
		$where['obj_user_id'] = session('user_id');
		$where['is_read'] = 0;//未读消息

		$comm_msg = Feed::where($where)
							->where(function ($query) {
				    			$query->where('action', 3)->whereor('action', 4);
							})
							->count();	
		$like_msg = Feed::where($where)->where("action",2)->count();	
		$focus_msg   = Feed::where($where)->where("action",5)->count();	
		$private_msg = Feed::where($where)->where("action",6)->count();	

		$all_msg = $comm_msg + $like_msg + $focus_msg + $private_msg;

		$this->assign([
			"comm_msg"  => $comm_msg,		//评论及回复
			"like_msg"  => $like_msg,		//点赞
			"focus_msg" => $focus_msg,		//粉丝
			"private_msg" => $private_msg, 	//私信,
			"all_msg" => $all_msg
		]);
		
	}
}
