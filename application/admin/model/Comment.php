<?php 
 
namespace app\admin\model;
use think\Model;

class Comment extends Model{
	// 去掉评论内容首尾的空格 安全过滤在配置文件中配置了
	public function setContentAttr($val)
    {
        return trim($val);
    }
    public function getCommTimeAttr($time){
		// 时间戳到年月日的转化
		return date("Y-m-d H:m",$time);
	}
}

