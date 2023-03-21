<?php
namespace app\system\controller;
use think\Request;
use think\Db;
use think\Session;

/**
 * 我要反馈类
 * @author kongweihao
 *
 */
class ForDemo extends Auth{
	/**
	 * 打开周报管理页面
	 */
	public function getZbglList(){
		return $this->fetch();
	}
	/**
	 * 打开培训管理页面
	 */
	public function getPxglList(){
		return $this->fetch();
	}
}