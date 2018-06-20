<?php 
namespace app\index\controller;
use think\Controller;
use think\Db;
use think\Session;
use app\index\model\Fragment;
use app\index\model\Comment;
use app\index\model\Article;
use app\index\model\Daily;
use app\index\model\Focus;
use app\index\model\User;

class Index extends Base{
	public function index(){
		$tody = date('Y-m-d');
		//$daily = Daily::where("show_time",$tody)->limit(1)->value("content");
		$daily = Daily::limit(1)->value("content");
		// 碎片 喜欢数量最高的四个
		$hotMood = Fragment::limit(4)->where('remove',0)->order('like', 'desc')->select();
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

		// 文章 浏览量最多的三个
		$hotArticle = Article::limit(3)->where(['remove'=>0,'is_publish'=>2])->order('skip', 'desc')->select();
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

		// 粉丝最多作者 四个 后期做成相关度推荐
		$hotUser = Db::query("select be_followed_uid,count(1) as counts from think_focus group by be_followed_uid order by counts desc limit 4");
		$userArr = Array();
		foreach($hotUser as $key=>$res){
			$userArr[$key]=[
				"user_id" => $res["be_followed_uid"],
				"avatar"  => User::get($res["be_followed_uid"])->avatar, 
				"nickname"=> User::get($res["be_followed_uid"])->nickname,  
				"desc"    => User::get($res["be_followed_uid"])->desc
			];

		}

		$this->assign("empty",'<div class="no-more-data">-&nbsp;暂时没有数据&nbsp;-</div>');
		$this->assign([
				"moodArr"	=> $moodArr,
				"articleArr"=> $articleArr,
				"userArr"   => $userArr,
				"daily"     => $daily
			]);

		return $this->fetch("index");
	}
	
}