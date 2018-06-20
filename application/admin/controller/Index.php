<?php
namespace app\admin\controller;
use think\Request;
use think\Controller;
use app\admin\model\User;

class Index extends Base
{
    public static function _redate($time){

		return date('G',$time);//格式化为没有前导0的24小时格式 0-23
	}

    public function index ()
    {	$res_arr = Array();//返回值数组

    	$login_time_arr = User::where('status', '1')->column('last_login_time');
		$login_hour_arr = array_map(array($this, '_redate'), $login_time_arr);//在类中使用array_map或者使用回调的形式
		$count_arr = array_count_values($login_hour_arr);

		for($i = 0; $i<24; $i++){
			if(array_key_exists($i,$count_arr)){
				$res_arr[$i]=$count_arr[$i];
			}else{
				$res_arr[$i]=0;
			}
		}		

		$this->assign("times",json_encode($res_arr));
        return $this->fetch('index');
    }
}
