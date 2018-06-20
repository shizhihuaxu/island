<?php
namespace app\admin\controller;
use think\Request;
use think\Controller;
use app\admin\model\User;
use app\admin\model\Article;
use app\admin\model\Fragment;

class Analyse extends Base{
	// 用户登录时间分布
    public function user ()
    {	
    	$data = [];
    	$data = array_pad($data, 7, 0);
    	$times = [];
    	$userList = User::select();

    	foreach($userList as $key=>$res){
			$times[$key]= ceil(($res->leave_time - $res->last_login_time)/60);
		}
    	for($i = 0;$i < count($times); $i++){
			if($times["$i"] <=10 ){
				$data[0]++;
			}
			if($times["$i"] >10 && $times["$i"]<=20){
				$data[1]++;
			}
			if($times["$i"] >20 && $times["$i"]<=30){
				$data[2]++;
			}
			if($times["$i"] >30 && $times["$i"]<=40){
				$data[3]++;
			}
			if($times["$i"] >40 && $times["$i"]<=50){
				$data[4]++;
			}
			if($times["$i"] >50 && $times["$i"]<=60){
				$data[5]++;
			}
			if($times["$i"] >60){
				$data[6]++;
			}
		}
		$this->assign("online",json_encode($data));
        return $this->fetch('user');
    }
  //ajax动态统计    
    //public function online ()
  //   {	
  //   	$data = [];
    //$data = array_pad($data, 7, 0);
  //   	$times = [];
  //   	$userList = User::select();

  //   	foreach($userList as $key=>$res){
		// 	$times[$key]= ceil(($res->leave_time - $res->last_login_time)/60);
		// }
  //   	for($i = 0;$i < count($times); $i++){
		// 	if($times["$i"] <=10 ){
		// 		$data[0]++;
		// 	}
		// 	if($times["$i"] >10 && $times["$i"]<=20){
		// 		$data[1]++;
		// 	}
		// 	if($times["$i"] >20 && $times["$i"]<=30){
		// 		$data[2]++;
		// 	}
		// 	if($times["$i"] >30 && $times["$i"]<=40){
		// 		$data[3]++;
		// 	}
		// 	if($times["$i"] >40 && $times["$i"]<=50){
		// 		$data[4]++;
		// 	}
		// 	if($times["$i"] >50 && $times["$i"]<=60){
		// 		$data[5]++;
		// 	}
		// 	if($times["$i"] >60){
		// 		$data[6]++;
		// 	}
		// }

  //       return ["data"=>$data];
  //   }
    // 发布内容的统计
     public function publish ()
    {	
    	$mood = [];
    	$moodData = [];
    	$moodData = array_pad($moodData, 7, 0);
    	$article = [];
    	$articleData = [];
    	$articleData = array_pad($articleData, 7, 0);

    	$moodList = Fragment::whereTime('create_time','between',['-7day','-0day'])
    						 ->order("create_time","desc")
    						 ->field('fragment_id, FROM_UNIXTIME(create_time,"%Y-%m-%d") as day,count(fragment_id) as num,create_time')
    						 ->group("day")
    						 ->select();
    	foreach ($moodList as $key => $res) {
    		$mood[$key]=[
    			"interval"=>ceil((time()-strtotime($res->day))/60/60/24),//转化为天
    			"day" => $res->day,
    			"num" => $res->num
    		];
    	}
    	foreach ($mood as $key => $res) {   		
    		$moodData[$mood[$key]["interval"]-1]=$mood[$key]["num"];
    	}
    
    	$articleList = Article::whereTime('update_time','between',['-7day','-0day'])
    						  ->order("update_time","desc")
    						  ->field('article_id, update_time,FROM_UNIXTIME(update_time,"%Y-%m-%d") as day,count(article_id) as num')
    						  ->group("day")
    						  ->select();
    
    	foreach ($articleList as $key => $res) {
    		$article[$key]=[
    			"interval"=>ceil((time()-strtotime($res->day))/60/60/24),//转化为天
    			"day" => $res->day,
    			"num" => $res->num
    		];
    	}

    	foreach ($article as $key => $res) {   		
    		$articleData[$article[$key]["interval"]-1]=$article[$key]["num"];
    	}
    	
    	$this->assign(["mood"=>json_encode($moodData),"article"=>json_encode($articleData)]);

        return $this->fetch('publish');
    }
}