<?php
namespace app\system\controller;
use think\Request;
use think\Db;
use think\Session;

/**
 * 广运自主开发类 更名为 广运总览
 * @author kongweihao
 *
 */
class SelfResearch extends Auth{
	// 自研成果总览
	public function selfResearchList(){
		return $this->fetch();
	}
	// 常用网址汇总
	public function commonURLList(){
		return $this->fetch();
	}
	// 小工具库
	public function widgetsList(){
		return $this->fetch();
	}
	// 自主开发展示
	public function selfResearchDetails(){
		return $this->fetch();
	}
}