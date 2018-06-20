<?php
namespace app\admin\controller;
use think\Request;
use think\Controller;
use app\admin\model\Daily;

class Day extends Base{
     /**
        [index 首页 ]
    */
    public function index ()
    {
        return $this->fetch('index');
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
        $dailyList = Daily::limit($page, 10)->select();
        foreach($dailyList as $key=>$res){
            $list[$key]=[
                "id" => $res->id,
                "content"   => $res->content, 
                "show_time"   => $res->show_time
            ];

        }

        $count = Daily::count();
        
        empty($list) && $list = array();
        return json(array('list' => $list, 'count' => $count));
    }
    /**
        [add 新增每日一句页面 ]
    */
    public function add ()
    {
        return $this->fetch('add');
    }
    /**
    	[add_one 添加一条] 
    */
    public function add_one (Request $req)
    {
        $msg = "error";
        $data = $req->param();

        $daily = new Daily();
	    $daily->data($data);
	    $res=$daily->save(); 

	    if($res){
	    	 $msg = "success";
	    }

	    return ["msg"=>$msg];

    }
     /**
    	[batch 批量添加] 
    */
    public function batch(Request $req)
    {
       //
    }
     /**
        [edit 编辑] 
    */
     public function edit($id)
    {
       $daily_info=Daily::get($id);

        $this->assign("daily_info",$daily_info);
        return $this->fetch('edit');
    }
     public function update(Request $req)
    {
        $data=$req->param();
        $daily = new Daily;
        $res=$daily->allowField(['content','show_time'])
                ->update($data, ['id' => $data['id']]); 

        if($res){
            $msg="success";
        }else{
            $msg="更新失败";
        }

        return ["msg" => $msg];
    }
      public function delete(Request $req)
    {   
        $data=$req->param();
        $id = $data["id"];
        $daily = Daily::get($id);
        $res = $daily->delete();

        if($res){
            $msg="success";
        }else{
            $msg="删除失败";
        }

        return ["msg" => $msg];
    }
}