<?php 
 
namespace app\admin\model;
use think\Model;

class User extends Model{

	/*
	public function getSexAttr($val){

		$sex=[0=>"男",1=>"女"];
		return $sex[$val];
	}
	*/
	public function getStatusAttr($val){

		$status=[0=>"停用",1=>"启用"];
		return $status[$val];
	}
  	
}