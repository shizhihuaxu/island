<?php 
namespace app\admin\controller;
use think\Request;
use think\Image;
use think\File;
use think\Db;
use think\Controller;
use app\admin\model\User;
use app\admin\model\Classify;
use app\admin\model\Article;

class Articles extends Base{
	// 文章列表页
	public function index(){

        return $this->fetch('index');
    }
    // 停用不显示文章
    public function remove(Request $req){
    	$data=$req->param();
    	$article_id = $data['article_id'];

    	$article = new Article;
		$isUpdated = $article->save([
		    'remove'  => 1
		],['article_id' => $article_id]);

		if($isUpdated){
			return ["msg"=>"success"];
		}
    }
     // 启用 显示文章
    public function show(Request $req){
    	$data=$req->param();
    	$article_id = $data['article_id'];

    	$article = new Article;
		$isUpdated = $article->save([
		    'remove'  => 0
		],['article_id' => $article_id]);

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

		if(""!= $data["keyword"]){
			//错误
			$articleList = Db::query('SELECT t.*,classify.name FROM (SELECT art.article_id,u.nickname,u.user_id,art.classify,art.title,art.remove FROM think_user AS u LEFT JOIN (SELECT * FROM think_article where is_publish=2) AS art ON u.user_id = art.user_id) AS t  LEFT JOIN ( SELECT * FROM think_classify) AS classify  ON classify.classify_id = t.classify  WHERE t.title LIKE "%'.$data["keyword"].'%" OR t.nickname LIKE "%'.$data["keyword"].'%" OR classify.name LIKE "%'.$data["keyword"].'%" LIMIT 10 OFFSET '.$page.'');

			$countArr = Db::query('SELECT count(*) as count FROM (SELECT art.article_id,u.nickname,u.user_id,art.classify,art.title,art.remove FROM think_user AS u LEFT JOIN (SELECT * FROM think_article where is_publish=2) AS art ON u.user_id = art.user_id) AS t  LEFT JOIN ( SELECT * FROM think_classify) AS classify  ON classify.classify_id = t.classify  WHERE t.title LIKE "%'.$data["keyword"].'%" OR t.nickname LIKE "%'.$data["keyword"].'%" OR classify.name LIKE "%'.$data["keyword"].'%"');

			$count = $countArr[0]["count"];//数据条数
			
		}else{
			$articleList = Article::where("is_publish",2)->limit($page, 10)->select();
			$count = Article::where("is_publish",2)->count();
		}

		foreach($articleList as $key=>$res){
			$list[$key]=[
				"article_id" => $res["article_id"],
				"classify"  => Classify::where("classify_id",$res["classify"])->value("name"),
				"title"   => $res["title"], 
				"nickname"   => User::where("user_id",$res["user_id"])->value("nickname"), 
				"remove" => $res["remove"]
			];

		}
		
		empty($list) && $list = array();
		return json(array('list' => $list, 'count' => $count));
	}
}
?>