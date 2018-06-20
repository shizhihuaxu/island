<?php 
namespace app\admin\controller;
use think\Request;
use think\Controller;
use app\admin\model\User;
use think\Session;

class Base extends Controller{
	public function __construct() {
		parent::__construct();
	}
}

?>