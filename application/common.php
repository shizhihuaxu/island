<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
/**
 *  内容发布时间格式化
 *  小于60分钟的显示多少分钟前，小于24小时的显示多少小时前，其余的显示为年月日的形式
 *  @author xiaoma
 *  @access public
 *  @param   int    $time 时间
 *  @return  String $text 格式化后的时间
 */
function redate($time){
    $text='';
    $t=time()-$time;
    switch($t){
		case $t < 0:
            $text = '';
            break;
		case $t >= 0 && $t < 60:
            $text = '刚刚';
            break;
        case $t >= 60 && $t <= 60*60:
        $text = floor((time()-intval($time))/60).' minutes ago';				//分钟
            break;
        case $t > 60*60 && $t < 60*60*3:
            $text = floor((time()-intval($time))/3600).' hours ago';		    //小时
            break;
        case $t>=60*60*3 && $t >= 60*60*24:
            $text = date("Y-m-d",intval($time));					//年月日
            break;
        default:
            $text = date("Y-m-d",intval($time));
            break;
    }
    return $text;
}


/**
 *  生成唯一id 时间戳+user_id 
 *  @author  xiaoma
 *  @access  public
 *  @param   int    $uid     用户id
 *  @return  String $id      唯一id,32个字符串长度
 */
function get_unique_id($uid){

    $id = uniqid($uid);
    
    return $id;

}

function  num_format($num){
    if($num >=1000){
        return substr($num/1000,0,3).'k';
    }else{
        return $num;
    }
}