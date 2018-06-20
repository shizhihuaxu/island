<?php 
namespace app\admin\controller;
use think\Request;
use think\Image;
use think\File;
use think\Db;
use think\Controller;
use app\admin\model\User;
use app\admin\model\Fragment;
use app\admin\model\Tag;

class Moods extends Base{
	// 碎片列表页
	public function index(){

        return $this->fetch('index');
    }
    // 停用不显示碎片
    public function remove(Request $req){
    	$data=$req->param();
    	$id = $data['fragment_id'];

    	$mood = new Fragment;
		$isUpdated = $mood->save([
		    'remove'  => 1
		],['fragment_id' => $id]);

		if($isUpdated){
			return ["msg"=>"success"];
		}
    }
     // 启用 显示碎片
    public function show(Request $req){
    	$data=$req->param();
    	$id = $data['fragment_id'];

    	$mood = new Fragment;
		$isUpdated = $mood->save([
		    'remove'  => 0
		],['fragment_id' => $id]);

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
			$moodList = Db::query('SELECT t.*,tag.name FROM (SELECT frag.fragment_id,u.nickname,u.user_id,frag.tag,frag.content,frag.remove FROM think_user AS u LEFT JOIN (SELECT * FROM think_fragment) AS frag ON u.user_id = frag.user_id) AS t  LEFT JOIN ( SELECT * FROM think_tag) AS tag  ON tag.tag_id = t.tag  WHERE t.content LIKE "%'.$data["keyword"].'%" OR t.nickname LIKE "%'.$data["keyword"].'%" OR tag.name LIKE "%'.$data["keyword"].'%" LIMIT 10 OFFSET '.$page.'');

			$countArr = Db::query('SELECT count(*) as count FROM (SELECT frag.fragment_id,u.nickname,u.user_id,frag.tag,frag.content,frag.remove FROM think_user AS u LEFT JOIN (SELECT * FROM think_fragment) AS frag ON u.user_id = frag.user_id) AS t  LEFT JOIN ( SELECT * FROM think_tag) AS tag  ON tag.tag_id = t.tag  WHERE t.content LIKE "%'.$data["keyword"].'%" OR t.nickname LIKE "%'.$data["keyword"].'%" OR tag.name LIKE "%'.$data["keyword"].'%"');

			$count = $countArr[0]["count"];//数据条数
			
		}else{
			$moodList = Fragment::limit($page, 10)->select();
			$count = Fragment::count();
		}
		
		foreach($moodList as $key=>$res){
			$list[$key]=[
				"fragment_id" => $res["fragment_id"],
				"tag"   => Tag::where('tag_id',$res["tag"])->value("name"), 
				"content"   => $res["content"], 
				"nickname"   => User::where('user_id',$res["user_id"])->value("nickname"), 
				"remove" => $res["remove"]
			];
		}
		
		empty($list) && $list = array();
		return json(array('list' => $list, 'count' => $count));
	}
}
?>