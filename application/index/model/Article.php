<?php 
namespace app\index\model;
use think\Model;

class Article extends Model{
	public function getCreateTimeAttr($time){
		// 时间戳到年月日的转化
		return date("Y-m-d",$time);
	}
	
}