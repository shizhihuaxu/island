<?php 
namespace app\admin\controller;
use think\Request;
use think\Image;
use think\File;
use think\Controller;
use app\admin\model\Tag;
use app\admin\model\Classify;

class Label extends Base{
	public function mood(){
		$tagList=Array();

		$list=Tag::select();

		foreach($list as $key=>$res){
			$tagList[$key]=[
				"tag_id" => $res->tag_id,
				"name"   => $res->name, 
		   		"img"	 => $res->img,
			];

		}
		$this->assign("tagList",$tagList);

        return $this->fetch('mood_label');
    }
    public function read(){
    	$classifyList=Array();

		$list=Classify::select();

		foreach($list as $key=>$res){
			$classifyList[$key]=[
				"classify_id" => $res->classify_id,
				"name"   => $res->name, 
				"en_desc"   => $res->en_desc, 
				"desc"   => $res->desc, 
		   		"img"	 => $res->img,
			];

		}
		$this->assign("classifyList",$classifyList);

        return $this->fetch('read_label');
    }
    /**
    	[tag_edit 碎片标签信息编辑修改 ]
    	@param $tag_id 被编辑的标签id
    */
    public function tag_edit($tag_id){

    	$tag_info=Tag::get($tag_id);

    	$this->assign("tag_info",$tag_info);
    	return $this->fetch('tag_edit');
    }
    /**
    	[classify_edit 文章分类信息编辑修改 ]
    	@param $classify_id 被编辑的分类id
    */
    public function classify_edit($classify_id){

    	$classify_info=Classify::get($classify_id);

    	$this->assign("classify_info",$classify_info);
    	return $this->fetch('classify_edit');
    }
    /**
    	[new_tag 添加碎片标签页面 ]
    */
    public function new_tag(){
    	 return $this->fetch('new_tag');
    }
    /**
    	[new_classify 添加文章分类页面 ]
    */
    public function new_classify(){
    	 return $this->fetch('new_classify');
    }
     /**
    	[add_tag 添加碎片标签功能 ]
    */
    public function add_tag(Request $req){

    	$data=$req->param();
    	$img = request()->file('img');
    	$img_name="";
    	$msg 	= "未知错误";

		if($img){
	        $tag_img = $img->validate(['ext'=>'jpg,png'])
	        			  ->move(APP_PATH. DS .'uploads'. DS .'tagImg');
	        if($tag_img){
	        	
	        	$filename = $tag_img->getSaveName(); 
	        	$img_name = str_replace("\\", "/", $filename);  

	        }else{
	        	$msg=$tag_img->getError();
	           return ["msg"=>$msg];	
	        }
	    }else{
	    	return ["msg"=>"请上传图片"];
	    }
	    
	    $data["img"]=$img_name;

	    // 更新其它信息
	    $tag = new Tag();
	    $tag->data($data);
	    $res=$tag->allowField(['name','img'])
	    		->save(); 

	    if($res){
	    	$msg="success";
	    }else{
	    	$msg="添加失败";
	    }

    	return ["msg" => $msg];
    	 
    }
    /**
    	[add_classify 添加文章分类功能 ]
    */
    public function add_classify(Request $req){
    	$data=$req->param();
    	$img = request()->file('img');
    	$img_name="";
    	$msg 	= "未知错误";

		if($img){
	        $classify_img = $img->validate(['ext'=>'jpg,png'])
	        			  ->move(APP_PATH. DS .'uploads'. DS .'classifyImg');
	        if($classify_img){
	        	
	        	$filename = $classify_img->getSaveName(); 
	        	$img_name = str_replace("\\", "/", $filename);  

	        }else{
	        	$msg=$classify_img->getError();
	           return ["msg"=>$msg];	
	        }
	    }else{
	    	return ["msg"=>"请上传图片"];
	    }

	    $data["img"]=$img_name;

	    // 更新其它信息
	    $classify = new Classify();
	    $classify->data($data);
	    $res=$classify->allowField(['name','en_desc','desc','img'])
	    		->save(); 

	    if($res){
	    	$msg="success";
	    }else{
	    	$msg="添加失败";
	    }

    	return ["msg" => $msg];
    	 
    }

     /**
    	[update_tag 更新碎片标签 ]
    */
    public function update_tag(Request $req){
    	$data=$req->param();
    	$img = request()->file('img');
    	$msg 	= "未知错误";

		if($img){
	        $tag_img = $img->validate(['ext'=>'jpg,png'])
	        			  ->move(APP_PATH. DS .'uploads'. DS .'tagImg');
	        if($tag_img){
	        	
	        	$filename = $tag_img->getSaveName(); 
	        	$img_name = str_replace("\\", "/", $filename);  

	        	$flag=Tag::update(['tag_id' => $data['tag_id'], 'img' => $img_name]);  //移动成功更新数据库图片地址

	        	if(!$flag){
	        		return ["msg"=>"图片上传失败"];
	        	}
	        }else{
	        	$msg=$tag_img->getError();
	           return ["msg"=>$msg];	
	        }
	    }

	    // 更新其它信息
	    $tag = new Tag();
	    $res=$tag->allowField(['name'])
	    		->update($data, ['tag_id' => $data['tag_id']]); 

	    if($res){
	    	$msg="success";
	    }else{
	    	$msg="更新失败";
	    }

    	return ["msg" => $msg];

    }
    /**
    	[update_classify 更新文章分类 ]
    */
    public function update_classify(Request $req){
    	$data=$req->param();
    	$img = request()->file('img');
    	$msg 	= "未知错误";

		if($img){
	        $classify_img = $img->validate(['ext'=>'jpg,png'])
	        			  ->move(APP_PATH. DS .'uploads'. DS .'classifyImg');
	        if($classify_img){
	        	
	        	$filename = $classify_img->getSaveName(); 
	        	$img_name = str_replace("\\", "/", $filename);  

	        	$flag=Classify::update(['classify_id' => $data['classify_id'], 'img' => $img_name]);  //移动成功更新数据库图片地址
	        	if(!$flag){
	        		return ["msg"=>"图片上传失败"];
	        	}
	        }else{
	        	$msg=$classify_img->getError();
	           return ["msg"=>$msg];	
	        }
	    }

	    // 更新其它信息
	    $classify = new Classify();
	    $res=$classify->allowField(['name','en_desc','desc'])
	    		->update($data, ['classify_id' => $data['classify_id']]); 

	    if($res){
	    	$msg="success";
	    }else{
	    	$msg="更新失败";
	    }

    	return ["msg" => $msg];
    }

}
?>