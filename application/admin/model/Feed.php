<?php 
namespace app\admin\model;
use think\Model;

class Feed extends Model{
	public function getCreateTimeAttr($time){
		// 时间戳到年月日的转化
		return date("Y-m-d",$time);
	}
}