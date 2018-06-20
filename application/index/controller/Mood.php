<?php 

namespace app\index\controller;
use think\Controller;
use think\Request;
use think\Image;
use think\Validate;
use think\File;
use app\index\model\Fragment;
use app\index\model\Tag;
use app\index\model\User;
use app\index\model\Comment;
use app\index\model\Feed;

class Mood extends Base{

	//获取分类信息
	 private function getTag(){
	 	$tags=Tag::select();
	 	$tagArr=[];
	 	
	 	foreach ($tags as $key=>$val) {
	 		$tagArr[$key]=[
	 			"tag_id"=>$val->tag_id,
	 			"name"  =>$val->name,
	 			"img"   =>$val->img
	 		];
		}
	 	
	 	return $tagArr;
	 }
	
    /**
     * [index 获取全部碎片数据列表]
     */
	public function index(){
		// 获取日期
		$timeArr = ["","January","February","March","April","May","June","July","August","September","October","November","December"];
		$month   = $timeArr[date('n')];
		$day 	 = date('d');
		$tagArr = $this->getTag();	//心情分类信息
		$sortCount=Array(); 		//每个标签心情数量
		$where["remove"] = 0;
	

		// 碎片分类数量
		foreach ($tagArr as $key=>$val) {

			$where["tag"]= $val["tag_id"];
			$where["remove"]= 0;
			$sortCount[$key] = Fragment::where($where)->count();
		}

		$moodNum=Fragment::where('remove', 0)->count();

		$this->assign([
			'month' 	=> $month,
			'day'   	=> $day,
			'tagArr'	=> $tagArr,		//碎片分类信息
			'sortCount' => $sortCount,	//分类碎片的数量
			'moodNum'	=> $moodNum 	//碎片总量
		]);
		return $this->fetch("index");
	}
	 /**
     * [lazy_load 获取全部碎片数据列表]
     */
     public function lazy_load(Request $req){
 
    	$data = $req->param();
    	$order ="create_time desc";
    	$tag = $data["tag"];				//标签
    	$pageSize = $data["pageSize"];		//分几页
    	$pageNum = $data["pageNum"];		//当前是第几页
    	$moodArr=Array();					//碎片数据信息数组

    	$offset = ($pageNum - 1)*$pageSize;
    	$where["remove"] =0;

    	if($tag!=""){
    		$where["tag"] = $tag;
    	}

    	$moodList=Fragment::where($where)->limit($offset,$pageSize)->order($order)->select();

		foreach($moodList as $key=>$res){
			$moodArr[$key]=[
				"fragment_id" => $res->fragment_id,
		   		"img"		  => $res->img,
		   		"content"     => $res->content,
		   		"user_id"	  => $res->user_id, 
		   		"user_avatar" => User::where('user_id',$res->user_id)->value("avatar"), 
		   		"nickname"    => User::where('user_id',$res->user_id)->value("nickname")
			];
			$is_like = Feed::get(["user_id"=>session("user_id"),"action"=>2,"obj_id"=>$res->fragment_id,"obj_type"=>1]);

			if(""==$is_like){
				$moodArr[$key]["is_like"] = "no";
			}else{
				$moodArr[$key]["is_like"] = "yes";
			}
		}

		return $moodArr;
     }

	/**
     * [mood_publish  发布碎片]
     */
	public function mood_publish(Request $req){

		$msg  = "发送失败";
		$img  = request()->file('moodImg');
		$data = $req->param();
		$fragment = new Fragment;
		$feed     = new Feed;

		// 如果有图片上传图片
		if($img){

	        $info = $img->validate(['ext'=>'jpg,png'])
	        			->rule('md5')
	        			->move(APP_PATH. DS .'uploads'. DS .'mood');

	        if($info){
	        	// 将 \转化为/
	        	$filename = $info->getSaveName();
	            $data["img"] = str_replace("\\", "/", $filename); 
	        }else{
	           $msg = $img->getError();
	           return ["msg" => $msg];
	        }
	    }else{
	    	$data["img"]="";
	    }

		$data["create_time"] = time();
		$data["like"] = 0;
		$data["skip"] = 0;
		$data["remove"]=0;  //未删除

		// 新增碎片
		$fragment->data($data,true);		//触发修改器
		$fragment->isUpdate(false)->save();
		$id = $fragment->fragment_id;

		if(""!=$id){
			//插入feed表
			$feed->data([
				"feed_id" => get_unique_id(session("user_id")),  
				"user_id" => session("user_id"), 
				"action"  => 1, 
				"obj_id"  => $id,  
				"obj_type"=> 1,  
				"obj_user_id" => session("user_id"),  
				"content" => "",  
				"create_time" => time(),  
				"is_read" => 0
			]); 
			$feed->save();

			$msg = "success";
		}
		return ["msg"=>$msg];
	}
	
	/**
     * [mood_sort 获取碎片分类]
     */
	public function mood_sort(){
		$req     = Request::instance();
		$moodArr = Array();
		$fragment= new Fragment();

		if($req->isAjax()){
	 		$data = $req->param();
	 		$tag  = (int)($data['tag']);

	 		$list = $fragment->where('tag','=',$tag)->order('create_time', 'desc')->select();

	 		foreach($list as $key=>$res){
				$moodArr[$key]=[
					"fragment_id" => $res->fragment_id,
			   		"img"		  => $res->img,
			   		"content"     => $res->content,
			   		"user_id"	  => $res->user_id, 
			   		"user_avatar" => User::where('user_id',$res->user_id)->value("avatar"),  
			   		"nickname"    => User::where('user_id',$res->user_id)->value("nickname") 
				];
			}

			return ["msg"=>"success","moodArr"=>$moodArr];
	 		
	 	}
	}
	 /**
     * [mood_detail 获取碎片详情]
     */
	public function mood_detail($fragment_id){

		$fragment_info = Fragment::get($fragment_id);
		$user_info     = User::get($fragment_info->user_id);
		$infoArr       = $fragment_info->toArray();

		$infoArr["content"] = str_replace("\n","<br>",$infoArr["content"]);		//保留换行
		$infoArr["content"] = str_replace(" ","&nbsp;",$infoArr["content"]);    //保留空格

		//判断当前用户是否已对此碎片点赞
		$is_like = Feed::get(["user_id"=>session("user_id"),"action"=>2,"obj_id"=>$fragment_id,"obj_type"=>1]);
		if(""==$is_like){
			$infoArr["is_like"] = "no";  //未点赞
		}else{
			$infoArr["is_like"] = "yes";
		}


		// 获取评论数据
		$comment= new Comment();
		$commArr =Array();
		$total=$comment->where([
						"article_mood_id" => $fragment_id,
						"type"			  => 0,
						"remove"		  => 0
						])->count();		//评论条数
		$comm_count = $total;

		$list = $comment->where([
						"article_mood_id" => $fragment_id,
						"type"			  => 0,
						"parent_comm_id"  => 0,
						"remove"		  => 0		//表示是顶层评论
						])->order('comm_time', 'desc')->select();

 		foreach($list as $key=>$res){
			$commArr[$key]=[
				"comm_id" 	  => $res->comm_id,
		   		"user_id"	  => $res->user_id, 
		   		"user_avatar" => User::where('user_id',$res->user_id)->value("avatar"),  
		   		"nickname"    => User::where('user_id',$res->user_id)->value("nickname"),
		   		"like"		  => $res->like,
		   		"comm_time"	  => $res->comm_time,
		   		"content"     => $res->content
			];

			// 判断当前用户是否对父评论点赞过
			$is_like = Feed::get(["user_id"=>session("user_id"),"action"=>2,"obj_id"=>$res->comm_id,"obj_type"=>3]);
			if(""==$is_like){
				$commArr[$key]["is_like"] = "no";  //未点赞
			}else{
				$commArr[$key]["is_like"] = "yes";
			}

			$subList = $comment->where([
						"article_mood_id" => $fragment_id,
						"type"			  => 0,
						"parent_comm_id"  =>  $res->comm_id,
						"remove"		  => 0	//0表示未删除
						])->order('comm_time', 'desc')->select();
			$subArr = Array();
			foreach($subList as $k=>$v){
				$subArr[$k]=[
					"comm_id" 	  => $v->comm_id,
			   		"user_id"	  => $v->user_id, 
			   		"user_avatar" => User::where('user_id',$v->user_id)->value("avatar"),  
			   		"nickname"    => User::where('user_id',$v->user_id)->value("nickname"),
			   		"like"		  => $v->like,
			   		"comm_time"	  => $v->comm_time,
			   		"content"     => $v->content
				];
			}
			$commArr[$key]["subArr"]=$subArr;	//追加子评论数据
		}
		
		$this->assign('empty','<div class="no-comment">暂时没有评论，快和小伙伴互动吧</div>');
		$this->assign(["commArr"=>$commArr,"comm_count"=>$comm_count]); //评论信息
		$this->assign("user_info",$user_info);//当前用户信息
		$this->assign("infoArr",$infoArr);	//碎片详情

		return $this->fetch("mood_detail");
	}
	 /**
     * [mood_skip 浏览量]
     */
     public function mood_skip(Request $req){
     	if($req->isAjax()){
			$data 		 = $req->param();
			$fragment_id  = $data["fragment_id"];
			$mood 		 = Fragment::get($fragment_id);
			$skipNum 	 = $mood->skip; 

			$mood->skip  = $skipNum + 1;		
			$isSaved     = $mood->save();
		}
     }
      /**
     * [mood_del 删除自己发布的碎片]
     */
    public function mood_del(Request $req){
     	if($req->isAjax()){
			$data 		 = $req->param();
			$fragment_id  = $data["fragment_id"];
			$item 		 = Fragment::get($fragment_id);
			$item->remove= 1;		
			$isSaved     = $item->isUpdate(true)->save();
		}

		if($isSaved){
			return ["msg"=>"success"];
		}else{
			return ["msg"=>"error"];
		}
    }
	
	 /**
     * [mood_like 点赞]
     */
	public function mood_like(Request $req){

		$feed= new Feed;

		if($req->isAjax()){
			$data 		 = $req->param();
			$isAdd		 = $data["isAdd"];
			$fragment_id = $data["fragment_id"];
			$obj_user_id = $data["obj_user_id"];
			$like 		 = Fragment::get($fragment_id);
			$likeNum 	 = $like->like; 

			if((int)($isAdd)===1){
				$like->like = $likeNum + 1;
				//插入feed表
				$feed->data([
					"feed_id" => get_unique_id(session("user_id")),  
					"user_id" => session("user_id"), 
					"action"  => 2, 
					"obj_id"  => $fragment_id,  
					"obj_type"=> 1,  
					"obj_user_id" => $obj_user_id,  
					"content" => "",  
					"create_time" => time(),  
					"is_read" => 0
				]); 

				$feed->save();

			}else{
				$like->like = $likeNum - 1;

				Feed::destroy(['user_id' => session("user_id"),"action"=>2,"obj_id"=>$fragment_id,"obj_type"=>1]);
			}

			$isSaved = $like->save();

			$likeNum = $like->like;//获取更新后的值

			if($isSaved){
				return ["msg"=>"success","likeNum"=>$likeNum];
			}else{
				return ["msg"=>"error"];
			}

		}
	}

	 /**
     * [mood_comment 提交评论]
     */
	public function mood_comment(){

		$req = Request::instance();

		if($req->isAjax()){

			$data 	 = $req->param();
			$comment = new Comment;
			$user 	 = new User;
			$feed    = new Feed;
			// 新增评论数据
			$data["article_mood_id"]= $data["content_id"];		//字段名不一样 需要处理一下
			$comment->data($data);
			$comment->parent_comm_id =0;
			$comment->comm_time = time();
			$isSaved = $comment->isUpdate(false)->allowField(true)->save();		//过滤上传数组中的非数据表字段数据 返回插入的记录数

			if($isSaved){
				$id = $comment->comm_id;
				$commInfo = Comment::get($id);

				//插入feed表
				$feed->data([
					"feed_id" => get_unique_id(session("user_id")),  
					"user_id" => session("user_id"), 
					"action"  => 3, 
					"obj_id"  => $data["content_id"],  
					"obj_type"=> 1,  
					"obj_user_id" => $data["obj_user_id"],  
					"content" => $data["content"],  
					"create_time" => time(),  
					"is_read" => 0
				]); 

				$feed->save();

				$comm_count=$comment->where([
					"article_mood_id"=>$data["article_mood_id"],
					"type"   => 0,
					"remove" =>0
				])->count();
				return ["msg"=>"success","commInfo"=>$commInfo,"comm_count"=>$comm_count];
			}else{
				return ["msg"=>"error"];
			}

		}
	}
	 /**
     * [comm_like]   对于评论的点赞
     */
     public function comm_like(Request $req){

		if($req->isAjax()){
			$data 	 = $req->param();
			$feed = new Feed;
			$isAdd	 = $data["isAdd"];
			$comm_id = $data["comm_id"];
			$obj_user_id=$data["obj_user_id"];
			$praise	 = Comment::get($comm_id);
			$likeNum = $praise->like; 

			if((int)($isAdd)===1){
				$praise->like = $likeNum + 1;

				//插入feed表
				$feed->data([
					"feed_id" => get_unique_id(session("user_id")),  
					"user_id" => session("user_id"), 
					"action"  => 2, 
					"obj_id"  => $comm_id,  
					"obj_type"=> 3,  
					"obj_user_id" => $obj_user_id,  
					"content" => "",  
					"create_time" => time(),  
					"is_read" => 0
				]); 

				$feed->save();

			}else{
				$praise->like = $likeNum - 1;

				Feed::destroy(['user_id' => session("user_id"),"action"=>2,"obj_id"=>$comm_id,"obj_type"=>3]);
			}

			$isSaved = $praise->save();

			$likeNum = $praise->like;//获取更新后的值

			if($isSaved){
				return ["msg"=>"success","likeNum"=>$likeNum];
			}else{
				return ["msg"=>"error"];
			}

		}
     }
     /**
     * [comm_reply]   对于评论的回复
     */
     public function comm_reply(Request $req){

		if($req->isAjax()){
			$data  = $req->param();
			$reply = new Comment;
			$user  = new User;
			$feed  = new Feed;
			
			$reply->data($data);
			$reply->comm_time = time();
			$reply->type = 0;
			$isSaved = $reply->isUpdate(false)->save();		//返回插入的记录数

			if($isSaved){
				$id = $reply->comm_id;

				//插入feed表
				$feed->data([
					"feed_id" => get_unique_id(session("user_id")),  
					"user_id" => session("user_id"), 
					"action"  => 4, 
					"obj_id"  => $data["parent_comm_id"],  
					"obj_type"=> 3,  
					"obj_user_id" => $data["user_id"],  
					"content" => $data["content"],  
					"create_time" => time(),  
					"is_read" => 0
				]); 
				$feed->save();

				$replyInfo = Comment::get($id);
				$replyInfo["nickname"] =User::where('user_id',$replyInfo->user_id)->value("nickname");
				$replyInfo["comm_count"]=$reply->where([
					"article_mood_id"=>$data["article_mood_id"],
					"type"   => 0,
					"remove" =>0
				])->count();		
				return ["msg"=>"success","replyInfo"=>$replyInfo];
			}else{
				return ["msg"=>"error"];
			}
		}
     	
     }
      /**
     * [comm_del]   对于评论的删除
     */
     public function comm_del(Request $req){

		if($req->isAjax()){
			$data = $req->param();
			$reply = new Comment();
			$hasChild=true;		//当前评论是否是顶层评论 true表示是

			// 通过查询当前元素的parent_comm_id是否为0判断当前元素是否是顶层元素 如果有子评论 删除子评论	
			$isParent=$reply->where("comm_id",$data["comm_id"])->value("parent_comm_id");

			if($isParent==0){	//是顶层元素
				Comment::where("parent_comm_id",$data["comm_id"])->update(["remove"=>1]);
			}

			Comment::where("comm_id",$data["comm_id"])->update(["remove"=>1]);				//软删除 1表示删除

			$count=$reply->where([
				"article_mood_id"=>Comment::where("comm_id",$data["comm_id"])->value("article_mood_id"),
				"type"   =>0,
				"remove" =>0
			])->count();					//更新当前评论数 根据 文章或心情id 未被移除

			if($isParent!=0){				//不是顶层元素
				$hasChild = false;
			}
			return ["msg"=>"success","hasChild"=>$hasChild,"count"=>$count];
			
		}
     	
     }

}