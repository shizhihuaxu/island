<?php 

namespace app\index\controller;
use think\Controller;
use think\Request;
use think\Paginator;
use app\index\model\Classify;
use app\index\model\Article;
use app\index\model\User;
use app\index\model\Comment;
use app\index\model\Feed;

class Read extends Base{

	//获取分类信息
	private function getClassify(){
		$classifys=Classify::select();
	 	$classifyArr=[];
	 	
	 	foreach ($classifys as $key=>$val) {
	 		$classifyArr[$key]=[
	 			"classify_id"=>$val->classify_id,
	 			"name"  =>$val->name,
	 			"en_desc"=>$val->en_desc,
	 			"desc" =>$val->desc,
	 			"img"   =>$val->img
	 		];
		}
		return $classifyArr;
	}

	/**
     * [index   阅读首页]
    */
	public function index(){

		$article = new Article;
		$classifyArr = $this->getClassify();	//文章分类信息
		$sortCount=Array(); 					//每类文章数量
		$where["remove"] = 0;					//未删除
		$where["is_publish"] = 2;				//已发布

		$artCount=$article->where($where)->count();  	//文章总量以便做分页判断

		foreach ($classifyArr as $key=>$val) {

			$where["classify"]= $val["classify_id"];
			$sortCount[$key] = $article->where($where)->count();
		}
		
		$this->assign([
			"sortCount"   =>$sortCount,
			"artCount"	  => $artCount,
			"classifyArr" => $classifyArr
		]);

		return $this->fetch("index");
	}
	
	/**
     * [lazyload  加载更多文章]
    */
    public function lazyload(Request $req){
 
    	$data = $req->param();
    	$order = $data["orderBy"];
    	$pageSize = $data["pageSize"];
    	$pageNum = $data["pageNum"];

    	$offset = ($pageNum - 1)*$pageSize;

    	$where["remove"]=0;
    	$where["is_publish"]=2;


    	$articles = Article::where($where)->limit($offset,$pageSize)->order($order)->select();
		$articleArr = Array();
		
		foreach($articles as $key=>$res){
			$articleArr[$key]=[
				"article_id" => $res->article_id,
				"title"	     => $res->title,
		   		"excerpt"	 => $res->excerpt,
		   		"img"		 => $res->img,
		   		"skip"	     => $res->skip,
		   		"like"	     => $res->like,
		   		"user_id"	 => $res->user_id, 
		   		"user"		 => User::where('user_id',$res->user_id)->value("nickname"),
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
     * [edit  		编辑文章页面展示]
    */
    public function edit($id=""){
    	$article = new Article;
    	$classifyArr=$this->getClassify();
    	$infoArr = [];
    	$draft_list=[];


    	if($id!=""){
    		$article_info = Article::get($id);
			$infoArr      = $article_info->toArray();
			$infoArr["classify_name"] = Classify::where("classify_id",$infoArr["classify"])->value('name');
    	}

    	//获取草稿列表 5个
    	$articles = Article::where(['user_id'=>session("user_id"),'remove'=>0,'is_publish'=>1])->limit(5)->order("create_time desc")->select();
		$draft_list = Array();
		
		foreach($articles as $key=>$res){
			$draft_list[$key]=[
				"article_id" => $res->article_id,
				"title"	     => $res->title
			];
		}

		$this->assign([
			'infoArr' 	=> $infoArr,
			'draft_list'=> $draft_list,
			'classifyArr'	=> $classifyArr,		//文章分类信息
			'update_article_id' => $id  //如果编辑的是草稿 草稿的id
		]);

    	return $this->fetch("edit");
    }
    /**
     * [preview  预览文章]
    */
    public function preview(Request $req){
    	//可能是编辑草稿以后预览 也可能是发布文章预览
    	$data = $req->param();
    	$infoArr=$data;

    	$infoArr["create_time"] = time();
    	$infoArr["user_id"] = session("user_id");
    	$infoArr["nickname"] = session("nickname");
    	$infoArr["avatar"] = session("avatar");
   
    	$this->assign('infoArr',$infoArr);
    	return $this->fetch("preview");

    }

    /**
     * [publish  发布文章功能]
     * 文章类型分为发布草稿 
    */
    public function publish(Request $req){
  
    	$article = new Article;
    	$feed = new Feed;
    	$user_info ="";
    	$infoArr =[];
    	$data = $req->param();
    	$id=$data["update_article_id"];

    	$data["update_time"] = time();
    	$data["is_publish"] = 2;
    		
    	if(""!=$id){
    		//修改内容 带id条件
    		$article=Article::get($id);
    		$article->data($data,true);					//触发修改器
    		$isSaved = $article->allowField(true)->save();
    	}else{
    		//发布内容
    		$data["create_time"] = time();
    		$article->data($data,true);					//触发修改器
    		$isSaved = $article->allowField(true)->save();

    		if($isSaved){
	    		$id = $article->article_id;

	    		// 添加到feed表中
	    		$feed->data([
					"feed_id" => get_unique_id(session("user_id")),  
					"user_id" => session("user_id"), 
					"action"  => 1, 
					"obj_id"  => $id,  
					"obj_type"=> 2,  
					"obj_user_id" => session("user_id"),  
					"content" => "",  
					"create_time" => time(),  
					"is_read" => 0
				]); 
				$feed->save();

  			}
    	}  	
		
    	$this->redirect("read/article_detail",["id"=>$id]);
    }
	
	/**
     * [article_sort    分类文章页]
    */
	public function article_sort($classify){
		$article = new Article;
		$where["classify"]=$classify;
		$where["remove"]=0;
		$where["is_publish"]=2;
		$classifyArr = $this->getClassify();  //获取文章分类配置

		$count = $article->where($where)->count();		//此分类下文章总量以便做分页判断

		$this->assign([
			"classify_id"  => $classify,
			"classify_info"=> $classifyArr[$classify-1],  //因为数组下标从0开始，所以要减一
			"count"        => $count
		]);

		return $this->fetch("article_sort");
	}

	/**
     * [sort_type  分类文章最新最热数据]
    */
	public function sort_type(Request $req){

		if($req->isAjax()){
			
			$data  = $req->param();
			$type  = $data["type"];				//判断是获取最新还是最热数据
			$classify = $data["classify"];		//文章分类
			$order = "";						//时间/浏览量
			$pageSize = $data["pageSize"];		//分几页
    		$pageNum = $data["pageNum"];		//当前页是第几页
    		
    		$offset = ($pageNum - 1)*$pageSize;

			if($type=="new"){
				$order = "create_time desc";
			}
			if($type=="hot"){
				$order = "skip desc";
			}

			$sortArticle = Article::where([
										"classify"=>$classify,
										"remove"  =>0,
										"is_publish" => 2
									])->limit($offset,$pageSize)->order($order)->select();

			$artArr = Array();
			foreach($sortArticle as $key=>$res){
				$artArr[$key]=[
					"article_id" => $res->article_id,
					"title"	     => $res->title,
			   		"excerpt"	 => $res->excerpt,
			   		"img"	     => $res->img,
			   		"skip"	     => $res->skip,
			   		"like"	     => $res->like,
			   		"user_id"	 => $res->user_id, 
			   		"user"		 => User::where('user_id',$res->user_id)->value("nickname"),
			   		"comment_count"=>Comment::where([
						"article_mood_id" => $res->article_id,
						"type"			  => 1,
						"remove"		  => 0
						])->count()  
				];

			}

			return $artArr;
		}
	}

	/**
     * [article_detail  文章详情页]
     @param  $id  文章id 
    */
	public function article_detail($id){
		
		$article_info  = Article::get($id);
		$user_info     = User::get($article_info->user_id);
		$infoArr       = $article_info->toArray();

		//判断当前用户是否已对此文章点赞
		$is_like = Feed::get(["user_id"=>session("user_id"),"action"=>2,"obj_id"=>$id,"obj_type"=>2]);
		if(""==$is_like){
			$infoArr["is_like"] = "no";  //未点赞
		}else{
			$infoArr["is_like"] = "yes";
		}

		// 获取评论数据
		$comment= new Comment();
		$commArr =Array();
		$total=$comment->where([
						"article_mood_id" => $id,
						"type"			  => 1,
						"remove"		  => 0
						])->count();		//评论条数
		$comm_count = $total;

		$list = $comment->where([
						"article_mood_id" => $id,
						"type"			  => 1,
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
						"article_mood_id" => $id,
						"type"			  => 1, //1表示是文章 0是碎片
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
		$this->assign([
			"user_info" => $user_info,			//当前用户信息
			"infoArr"   => $infoArr				//信息
			]);

		return $this->fetch("article_detail");
	}

	 /**
     * [article_like 对文章点赞]
     */
	public function article_like(Request $req){

		$feed= new Feed;

		if($req->isAjax()){
			$data 		 = $req->param();
			$isAdd		 = $data["isAdd"];
			$article_id  = $data["article_id"];
			$obj_user_id = $data["obj_user_id"];
			$like 		 = Article::get($article_id);
			$likeNum 	 = $like->like; 

			if((int)($isAdd)===1){
				$like->like = $likeNum + 1;

				//插入feed表
				$feed->data([
					"feed_id" => get_unique_id(session("user_id")),  
					"user_id" => session("user_id"), 
					"action"  => 2, 
					"obj_id"  => $article_id,  
					"obj_type"=> 2,  
					"obj_user_id" => $obj_user_id,  
					"content" => "",  
					"create_time" => time(),  
					"is_read" => 0
				]); 

				$feed->save();
			}else{
				$like->like = $likeNum - 1;

				Feed::destroy(['user_id' => session("user_id"),"action"=>2,"obj_id"=>$article_id,"obj_type"=>2]);
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
     * [article_skip  浏览量]
     */
     public function article_skip(Request $req){

		if($req->isAjax()){
			$data 		 = $req->param();
			$article_id  = $data["article_id"];
			$skip 		 = Article::get($article_id);
			$skipNum 	 = $skip->skip; 

			$skip->skip  = $skipNum + 1;		
			$isSaved     = $skip->save();
		}

     }
     /**
     * [article_del  删除已发布文章]
     */
     public function article_del(Request $req){
     
		if($req->isAjax()){
			$data 		 = $req->param();
			$article_id  = $data["article_id"];
			$item 		 = Article::get($article_id);
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
     * [article_comment 提交评论]
     */
	public function article_comment(Request $req){

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
					"obj_type"=> 2,  
					"obj_user_id" => $data["obj_user_id"],  
					"content" => $data["content"],  
					"create_time" => time(),  
					"is_read" => 0
				]); 

				$feed->save();

				$comm_count=$comment->where([
					"article_mood_id"=>$data["article_mood_id"],
					"type"   => 1,
					"remove" =>0
				])->count();
				return ["msg"=>"success","commInfo"=>$commInfo,"comm_count"=>$comm_count];
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
			$reply->type = 1;
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
					"type"   => 1,
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
				"type"   =>1,
				"remove" =>0
			])->count();					//更新当前评论数 根据 文章或心情id 未被移除

			if($isParent!=0){				//不是顶层元素
				$hasChild = false;
			}
			return ["msg"=>"success","hasChild"=>$hasChild,"count"=>$count];			
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

			if($isSaved){

				$likeNum = $praise->like;//获取更新后的值
				return ["msg"=>"success","likeNum"=>$likeNum];
			}else{

				return ["msg"=>"error"];
			}

		}
     }
     /**
     * [draft_saved  保存草稿]
    */
    public function draft_saved(Request $req){
    	$status = 0;//1 表示为更新操作 2表示为新增操作
    	$id = "";//如果新增，新增后的id
    	$isSaved = false;//是否成功  
  
    	$article = new Article;
    	$data = $req->param();

    	$update_article_id = $data["update_article_id"];
    	$data["update_time"] = time();

    	if("" != $update_article_id){
    		$status = 1;//更新操作，发布过的文章或草稿

    		$article=Article::get($update_article_id);
    		$article->data($data,true);					//触发修改器
    		$isSaved = $article->allowField(true)->save();

    	}else{
    		$status = 2;//新增草稿操作

    		$data["create_time"] = time();
    		$data["is_publish"] = 1; //1草稿 2为发布
    		$data["remove"] = 0;

    		$article->data($data,true);					
    		$isSaved = $article->allowField(true)->isUpdate(false)->save();
    		$id = $article->article_id;
    	}

    	if($isSaved){
    		return ["msg"=>"success","article_id"=>$id,"status"=>$status];
    	}else{
    		return ["msg"=>"error"];
    	}
		
    }
    /**
     * [draft_edit  编辑草稿]
    */
    public function draft_edit(Request $req){
    	$data 		 = $req->param();
		$article_id  = $data["article_id"];

    	$draft_info = Article::get($article_id);
		$infoArr      = $draft_info->toArray();
		$infoArr["classify_name"] = Classify::where("classify_id",$infoArr["classify"])->value('name');
 		if(!empty($infoArr)){
			return ["msg"=>"success","infoArr"=>$infoArr];
		}else{
			return ["msg"=>"error"];
		}
    }
     /**
     * [draft_del]   删除草稿
     */
      public function draft_del(Request $req){
      	if($req->isAjax()){
			$data 		 = $req->param();
			$article_id  = $data["article_id"];
			$item 		 = Article::get($article_id);
			$item->remove= 1;		
			$isSaved     = $item->isUpdate(true)->save();
		}

		if($isSaved){
			return ["msg"=>"success"];
		}else{
			return ["msg"=>"error"];
		}
      }
}