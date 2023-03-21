<?php
namespace app\system\controller;
use think\Controller;

class Error extends Controller{
	/**
	 * 空控制器
	 */
	public function _empty(){
		return $this->fetch('inc/404');
	}
	
	/**
	 * 404未找到页面
	 */
	public function notFound(){
		return $this->fetch('inc/404');
	}
	
}