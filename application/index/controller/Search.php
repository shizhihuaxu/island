<?php 
namespace app\index\controller;
use think\Controller;
use think\Db;
use think\Request;
use think\Session;
use app\index\model\Fragment;
use app\index\model\Comment;
use app\index\model\Article;
use app\index\model\Focus;
use app\index\model\User;

class Search extends Base{
	public function index(Request $req){
		$data = $req->param();
		$keyword = $data["keyword"];

		// 碎片 喜欢数量最高的四个
		$hotMood = Fragment::where('remove',0)
							->where('content','like',"%".$keyword."%")
							->order('create_time', 'desc')
							->select();
		$moodArr = Array();

		foreach($hotMood as $key=>$res){
			$moodArr[$key]=[
				"fragment_id" => $res->fragment_id,
		   		"img"		  => $res->img,
		   		"content"     => $res->content,
		   		"user_id"	  => $res->user_id, 
		   		"user"		  => User::where('user_id',$res->user_id)->value("nickname") 
			];

		}

		// 文章 
		$hotArticle = Article::where(['remove'=>0,'is_publish'=>2])
							->where('content','like',"%".$keyword."%")
							->order('create_time', 'desc')->select();
		$articleArr = Array();
		foreach($hotArticle as $key=>$res){
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

		// 作者
		$findUser = User::where('status',1)
							->where('nickname','like',"%".$keyword."%")
							->select();
		$userArr = Array();
		foreach($findUser as $key=>$res){
			$userArr[$key]=[
				"user_id" => $res->user_id,
				"avatar"  => $res->avatar, 
				"nickname"=> $res->nickname,  
				"desc"    => $res->desc
			];

		}

		$total = count($moodArr) + count($articleArr) + count($userArr);

		$this->assign("empty",'<div class="no-more-data">-&nbsp;暂时没有数据&nbsp;-</div>');
		$this->assign([
				"moodArr"	=> $moodArr,
				"articleArr"=> $articleArr,
				"userArr"   => $userArr,
				"total"     => $total
			]);


		return $this->fetch("index");
	}
	
}