<?php 
namespace app\index\controller;
use think\Controller;
use think\Db;
use think\Request;
use app\index\model\Fragment;
use app\index\model\Comment;
use app\index\model\Article;
use app\index\model\Feed;
use app\index\model\User;

class Dynamics extends Base{
	/**
		index  动态首页
	*/ 
	public function index(){
		$feedArr = Array();

		// 查找所有当前用户所关注的人发布的文章和碎片
		$feeds = Db::table('think_feed')
				->where(['action' => 1,'is_read' => 0])
				->where(function ($query) {
				    $query->where('obj_type', 1)->whereor('obj_type', 2);
				})
				->where('user_id','IN',function($query){
				    $query->table('think_focus')->where('fan_uid','=',session("user_id"))->field('be_followed_uid');
				})
				->order("create_time desc")
				->limit(0,10)
				->select();

		foreach($feeds as $key=>$res){
			$user = User::get($res["user_id"]);

			// 发布的是碎片
			if($res["obj_type"] == 1){
				$mood = Fragment::get($res["obj_id"]);

				$feedArr[$key] = [
					"user_id"  => $res["user_id"], 
			   		"nickname" => $user->nickname,
			   		"avatar"   => $user->avatar,  
			   		"obj_id"   => $res["obj_id"],
			   		"obj_type" => $res["obj_type"],  
			   		"create_time" => $res["create_time"],
					"content" => $mood->content,
					"img"     => $mood->img,
					"like"    => $mood->like,
					"skip"    => $mood->skip,
					"comm_num"=> Comment::where([
						"article_mood_id" => $res["obj_id"],
						"type"			  => 0, 
						"remove"		  => 0
						])->count() 
				];
			}
			// 发布的是文章
			if($res["obj_type"] == 2){
				$article = Article::get($res["obj_id"]);

				$feedArr[$key] = [
					"user_id"  => $res["user_id"], 
			   		"nickname" => $user->nickname,
			   		"avatar"   => $user->avatar,  
			   		"obj_id"   => $res["obj_id"],
			   		"obj_type" => $res["obj_type"],  
			   		"create_time" => $res["create_time"],
					"title"   => $article->title,
					"excerpt" => $article->excerpt,
					"img"     => $article->img,
					"like"    => $article->like,
					"skip"    => $article->skip,
					"comm_num"=> Comment::where([
						"article_mood_id" => $res["obj_id"],
						"type"			  => 1, 
						"remove"		  => 0
						])->count() 
				];
			}

		}

		$this->assign([
			"feedArr" => $feedArr
		]);

		return $this->fetch("index");
	}


	/**
		getMore   查看更多动态
	*/
	public function get_more(Request $req){
		$feedArr = Array();
		$data    = $req->param();

		$page = $data["page"];

		// 查找所有当前用户所关注的人发布的文章和碎片
		$feeds = Db::table('think_feed')
				->where(['action' => 1,'is_read' => 0])
				->where(function ($query) {
				    $query->where('obj_type', 1)->whereor('obj_type', 2);
				})
				->where('user_id','IN',function($query){
				    $query->table('think_focus')->where('fan_uid','=',session("user_id"))->field('be_followed_uid');
				})
				->order("create_time desc")
				->limit($page,10)
				->select();

		foreach($feeds as $key=>$res){
			$user = User::get($res["user_id"]);

			// 发布的是碎片
			if($res["obj_type"] == 1){
				$mood = Fragment::get($res["obj_id"]);

				$feedArr[$key] = [
					"user_id"  => $res["user_id"], 
			   		"nickname" => $user->nickname,
			   		"avatar"   => $user->avatar,  
			   		"obj_id"   => $res["obj_id"],
			   		"obj_type" => $res["obj_type"],  
			   		"create_time" => redate($res["create_time"]),
					"content" => $mood->content,
					"img"     => $mood->img,
					"like"    => $mood->like,
					"skip"    => $mood->skip,
					"comm_num"=> Comment::where([
						"article_mood_id" => $res["obj_id"],
						"type"			  => 0, 
						"remove"		  => 0
						])->count() 
				];
			}
			// 发布的是文章
			if($res["obj_type"] == 2){
				$article = Article::get($res["obj_id"]);

				$feedArr[$key] = [
					"user_id"  => $res["user_id"], 
			   		"nickname" => $user->nickname,
			   		"avatar"   => $user->avatar,  
			   		"obj_id"   => $res["obj_id"],
			   		"obj_type" => $res["obj_type"],  
			   		"create_time" => redate($res["create_time"]),
					"title"   => $article->title,
					"excerpt" => $article->excerpt,
					"img"     => $article->img,
					"like"    => $article->like,
					"skip"    => $article->skip,
					"comm_num"=> Comment::where([
						"article_mood_id" => $res["obj_id"],
						"type"			  => 1, 
						"remove"		  => 0
						])->count() 
				];
			}

		}

		return ["feedArr" => $feedArr,"count" => count($feedArr)];		
	}
}