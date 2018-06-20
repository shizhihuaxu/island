<?php 

namespace app\index\controller;
use think\Request;
use think\Db;
use app\index\model\User;
use app\index\model\Focus;
use app\index\model\Comment;
use app\index\model\Fragment;
use app\index\model\Article;
use app\index\model\Feed;

class UserInfo extends Base{
	/**
     * [getMsg  根据消息类型获取消息]
        @param   int    $infoType 消息类型
       			2	   点赞
       			34     评论及回复
       			5      粉丝
       			6      私信 
       	@param   int   $isRead  是否已读 默认查询未读消息
       			 0		未读
       			 1		已读
    */
	private function getMsg($infoType,$isRead=0){
		$obj_user_id = session("user_id");		
		$whereStr = '';

		if($infoType==34){
			$whereStr.=$whereStr.'(action=3 or action=4)';
		}else{
			$whereStr.=$whereStr.'action='.$infoType;
		}

		$whereStr.=' and obj_user_id='.$obj_user_id.' and is_read='.$isRead;
		// 只能将带括号的or先拼装 否则它的括号会与where方法的括号匹配 从而报错

		$msgArr = Db::name('feed')
					->where($whereStr)
					->limit(10)
					->select();
    	
    	return $msgArr;
    } 

	/**
     * [user_info  个人信息首页]
       @param   String    $user_id    用户id 
       @param   int    $infoType 消息类型
       			1      显示发布模块  默认
       			2	   点赞
       			34     评论及回复
       			5      粉丝
       			6      私信 
    */
	public function user_info($user_id,$infoType = 1){

		$user= User::get($user_id);
		$user_info = $user->toArray();


		$following = Focus::where("fan_uid",$user_id)->count();			//我的关注量
		$follower  = Focus::where("be_followed_uid",$user_id)->count();  //我的粉丝量
		$article   = Article::where(["user_id"=>$user_id,"remove"=>0,"is_publish"=>2])->count();		//已发布文章数量
		$fragment  = Fragment::where(["user_id"=>$user_id,"remove"=>0])->count();		//碎片数量
		$comment   = Feed::where("obj_user_id",$user_id)
							->where(function ($query) {
				    			$query->where('action', 3)->whereor('action', 4);
							})
							->count();		//评论或回复我的总量
		$like	   = Feed::where(["action"=>2,"obj_user_id"=>$user_id])->count(); //点赞过我的
		$focusMsgCount=Feed::where(["action"=>5,"obj_user_id"=>$user_id])->count(); 	//关注我的消息

		// 判断当前登录者与此用户的关系 0 未关注 1 是此用户的粉丝 2 互相关注
		$focusType = 0;
		$isFollower = Focus::where(["fan_uid"=>session("user_id"),"be_followed_uid"=>$user_id])->find();
		if(!empty($isFollower)){
			$focusType = 1;
		}

		$isFollowing = Focus::where(["fan_uid"=>$user_id,"be_followed_uid"=>session("user_id")])->find();
		if(!empty($isFollowing)&&!empty($isFollower)){
			$focusType = 2;
		}

		// 消息通知内容 根据infoType查询
		$msgArr = array();
		if($infoType != 1){
			$msgArr = $this->getMsg($infoType);
		}


		$countArr = array(
			'following' => $following, 
			"follower"	=> $follower,
			"article"	=> $article,
			"fragment"	=> $fragment,
			"comment"   => $comment,
			"like"		=>$like,
			"focusMsgCount"=>$focusMsgCount
		);

		$this->assign([
			"user_info" => $user_info,  	//当前用户基本信息
			"countArr"  => $countArr,
			"focusType"	=> $focusType,
			"infoType"  => $infoType,
			"msgArr"    => $msgArr			//未读消息信息数组
		]);


		return $this->fetch("user_info");
	}

	/**
     * [visit  个人主页浏览量]
    */ 
    public function visit(Request $req){

    	if($req->isAjax()){
			$data 	  = $req->param();
			$user_id  = $data["user_id"];
			$user	  = User::get($user_id);
			$visitNum = $user->visit; 

			$user->visit = $visitNum + 1;		
			$user->save();
		}
	}

	/**
     * [focus  关注与取消关注某一用户]
    */
	public function focus(Request $req){

		if($req->isAjax()){
			$data 	  = $req->param();
			$cur_uid = $data["be_followed_uid"];
			$fan_uid = $data["fan_uid"];
			$res = "";
			$focus = new Focus;
			$feed  = new Feed;

			if($data["isToFocus"]=="true"){
				$data["create_time"] = time();
				$res = $focus->allowField(true)->data($data)->save();

				// 加入feed表
				$feed->data([
					"feed_id" => get_unique_id($fan_uid),  
					"user_id" => $fan_uid, 
					"action"  => 5, 
					"obj_id"  => $cur_uid,  
					"obj_type"=> 4,  
					"obj_user_id" => $cur_uid,  
					"content" => "",  
					"create_time" => time(),  
					"is_read" => 0
				]); 
				$feed->save();

			}else{
				$res = Focus::destroy(["fan_uid"=>$fan_uid,"be_followed_uid"=>$cur_uid]);
				// 从feed表中删除
				Feed::destroy(['user_id' => $fan_uid,"action"=>5,"obj_id"=>$cur_uid,"obj_type"=>4]);
			}
			// 重新判断两人的关系
			$focusType = 0;
			$isFollower = Focus::where(["fan_uid"=>$fan_uid,"be_followed_uid"=>$cur_uid])->find();
			if(!empty($isFollower)){
				$focusType = 1;
			}

			$isFollowing = Focus::where(["fan_uid"=>$cur_uid,"be_followed_uid"=>$fan_uid])->find();
			if(!empty($isFollowing)&&!empty($isFollower)){
				$focusType = 2;
			}

			if($res){
				return ["msg"=>"success","type"=>$focusType];
			}else{
				return ["msg"=>"error"];
			}
		}
	}
	/**
     * [getUserFocus 获取粉丝与关注的用户信息]
    */
	public function getUserFocus(Request $req){
		if($req->isAjax()){

			$data = $req->param();
			$type = $data["type"];
			$pageSize = $data["pageSize"];
			$pageNum  = $data["pageNum"];
			$user_id  = $data["user_id"];
			$where = Array();		//查询条件数组
			$focusArr = Array(); 	//查询到的用户数组
			$getColumn = "";		//表示字段名的字符串

			if($type == "getFollower"){		//获取粉丝列表
				$where["be_followed_uid"] = $user_id;
				$getColumn = "fan_uid";
			}

			if($type == "getFollowing"){	//获取关注的人列表
				$where["fan_uid"] = $user_id;
				$getColumn = "be_followed_uid";
			}

			$offset = ($pageNum - 1)*$pageSize;
			$userList = Focus::where($where)->limit($offset,$pageSize)->order("create_time desc")->select();

			// 获取查询到的用户信息
			foreach ($userList as $key => $res) {
				// 判断登录用户与查询到的用户的关系 0 没关系 1 是登录用户的粉丝 2 是登录用户关注的人
				$focusType = 0;
				$isFollower = Focus::where(["fan_uid"=>session("user_id"),"be_followed_uid"=>$res->$getColumn])->find();
				if(!empty($isFollower)){
					$focusType = 1;
				}

				$isFollowing = Focus::where(["fan_uid"=>$res->$getColumn,"be_followed_uid"=>session("user_id")])->find();
				if(!empty($isFollowing)&&!empty($isFollower)){
					$focusType = 2;
				}

				$focusArr[$key]=[
					"user_info"=>User::get($res->$getColumn)->toArray(), 
					"type"     => $focusType
				];
			}

			return $focusArr;			
		}
	}
	/**
     * [getMyArticle 获取当前用户发布的文章]
    */
	public function getMyArticle(Request $req){
		$data = $req->param();
    	$pageSize = $data["pageSize"];
    	$pageNum = $data["pageNum"];
    	$cur_uid = $data["user_id"];

    	$offset = ($pageNum - 1)*$pageSize;


    	$articles = Article::where(['user_id'=>$cur_uid,'remove'=>0,'is_publish'=>2])->limit($offset,$pageSize)->order("create_time desc")->select();
		$articleArr = Array();
		
		foreach($articles as $key=>$res){
			$articleArr[$key]=[
				"article_id" => $res->article_id,
				"title"	     => $res->title,
		   		"excerpt"	 => $res->excerpt,
		   		"img"		 => $res->img,
		   		"skip"	     => $res->skip,
		   		"like"	     => $res->like,
		   		"comment_count"=>Comment::where([
						"article_mood_id" => $res->article_id,
						"type"			  => 1,
						"remove"		  => 0
						])->count() 
			];
		}

		return $articleArr;
	}

	/**
     * [getMyMood 获取当前用户发布的碎片]
    */ 
	public function getMyMood(Request $req){
		$data = $req->param();
    	$pageSize = $data["pageSize"];
    	$pageNum = $data["pageNum"];
    	$cur_uid = $data["user_id"];

    	$offset = ($pageNum - 1)*$pageSize;


    	$moodList = Fragment::where(['remove'=>0,'user_id'=>$cur_uid])->limit($offset,$pageSize)->order("create_time desc")->select();
		$articleArr = Array();
		
		foreach($moodList as $key=>$res){
			$moodArr[$key]=[
				"fragment_id" => $res->fragment_id,
		   		"img"		  => $res->img,
		   		"content"     => $res->content,
		   		"skip"	  	  => $res->skip, 
		   		"like" 		  => $res->like, 
		   		"comment_count"=>Comment::where([
						"article_mood_id" => $res->fragment_id,
						"type"			  => 0,
						"remove"		  => 0
						])->count() 
			];
		}

		return $moodArr;
	}

	/**
     * [getComment 获取与我相关的评论及回复全部消息]
    */
	public function getComment(Request $req){
		$data = $req->param();
    	$pageSize = $data["pageSize"];
    	$pageNum = $data["pageNum"];
    	$cur_uid = $data["user_id"];

    	$offset = ($pageNum - 1)*$pageSize;


    	$comments = Feed::where("obj_user_id",$cur_uid)
							->where(function ($query) {
				    			$query->where('action', 3)->whereor('action', 4);
							})
    						->limit($offset,$pageSize)->order("create_time desc")->select();
		$commArr = Array();
		
		foreach($comments as $key=>$res){
			$commArr[$key]=[
				"user_id" => $res->user_id,
				"nickname"=>User::where('user_id',$res->user_id)->value("nickname"),
				"avatar"=>User::where('user_id',$res->user_id)->value("avatar"),
				"create_time"=> $res->create_time,
		   		"action"	=> $res->action,
		   		"obj_id"	=> $res->obj_id,
		   		"obj_type"	=> $res->obj_type,
		   		"content"	=> $res->content,
		   		"is_read"	=> $res->is_read
			];
			//这里有点问题 不同人或者同一个人对我的评论进行回复的话 parent_comm_id和obj_user_id都是一样的 这样怎么取出不同
			//所以不能只靠parent_comm_id去定位内容
			if(4==$res->action){
				$commArr[$key]["obj_type"]=Comment::where(["parent_comm_id"=>$res->obj_id])->value("type")+1;
				$commArr[$key]["obj_id"]=Comment::where(["parent_comm_id"=>$res->obj_id])->value("article_mood_id");
			}
			if(2==$commArr[$key]["obj_type"]){
				$commArr[$key]["title"]=Article::where("article_id",$commArr[$key]["obj_id"])->value("title");
			}else{
				$commArr[$key]["title"]="碎片";
			}
		}

		return $commArr;
		
	}

	/**
     * [getLikeMsg 获取点赞我的全部消息]
    */
	public function getLikeMsg(Request $req){
		$data = $req->param();
    	$pageSize = $data["pageSize"];
    	$pageNum = $data["pageNum"];
    	$cur_uid = $data["user_id"];

    	$offset = ($pageNum - 1)*$pageSize;


    	$likes = Feed::where(["obj_user_id"=>$cur_uid,"action"=>2])
    						->limit($offset,$pageSize)->order("create_time desc")->select();
		$likeArr = Array();
		
		foreach($likes as $key=>$res){
			$likeArr[$key]=[
				"user_id" => $res->user_id,
				"nickname"=>User::where('user_id',$res->user_id)->value("nickname"),
				"avatar"=>User::where('user_id',$res->user_id)->value("avatar"),
				"create_time"=> $res->create_time,
		   		"obj_id"	=> $res->obj_id,
		   		"obj_type"	=> $res->obj_type,
		   		"is_read"	=> $res->is_read
			];

			if(1==$res->obj_type){
				$likeArr[$key]["title"]="碎片";
			}
			if(2==$res->obj_type){
				$likeArr[$key]["title"]=Article::where("article_id",$res->obj_id)->value("title");
			}
			if(3==$res->obj_type){
				$likeArr[$key]["title"]=Comment::where(["comm_id"=>$res->obj_id])->value("content");
			}
		}

		return $likeArr;
		
	}

	/**
     * [getFocusMsg 获取关注我的全部消息]
    */
	public function getFocusMsg(Request $req){
		$data = $req->param();
    	$pageSize = $data["pageSize"];
    	$pageNum = $data["pageNum"];
    	$cur_uid = $data["user_id"];

    	$offset = ($pageNum - 1)*$pageSize;


    	$focus = Feed::where(["obj_user_id"=>$cur_uid,"action"=>5])
    						->limit($offset,$pageSize)->order("create_time desc")->select();
		$focusArr = Array();
		
		foreach($focus as $key=>$res){
			$focusArr[$key]=[
				"user_id" => $res->user_id,
				"nickname"=>User::where('user_id',$res->user_id)->value("nickname"),
				"avatar"=>User::where('user_id',$res->user_id)->value("avatar"),
				"create_time"=> $res->create_time,
		   		"is_read"	=> $res->is_read
			];

			// 判断当前登录者与此用户的关系 0 未关注 1 是此用户的粉丝 2 互相关注
			$focusType = 0;
			$isFollower = Focus::where(["fan_uid"=>$cur_uid,"be_followed_uid"=>$res->user_id])->find();
			if(!empty($isFollower)){
				$focusType = 1;
			}

			$isFollowing = Focus::where(["fan_uid"=>$res->user_id,"be_followed_uid"=>$cur_uid])->find();
			if(!empty($isFollowing)&&!empty($isFollower)){
				$focusType = 2;
			}

			$focusArr[$key]["focusType"]=$focusType;
		}

		return $focusArr;
		
	}

	/*
	*	[updateMsg 更新消息数量]
	*/
	public function updateMsg(Request $req){
		$data = $req->param();

		$action=$data["action"];
		$obj_user_id=$data["obj_user_id"];
		$whereStr="";

		if($action==34){
			$whereStr.=$whereStr.'(action=3 or action=4)';
		}else{
			$whereStr.=$whereStr.'action='.$action;
		}

		$whereStr.=' and obj_user_id='.$obj_user_id;

		//执行更新操作
		$num=Feed::where($whereStr)->update(['is_read' => 1]);
		
		return ["msg"=>"success","num"=>$num];
	}
}