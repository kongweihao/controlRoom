<?php
namespace app\system\controller;
use think\Request;
use app\common\model\Role;
use app\common\model\AdminRole;
use think\Controller;
use app\common\utils\SystemConfig;
use think\Db;
use think\Session;

/**
 * 生产信息管理系统类
 * @author kongweihao
 *
 */
// class controlRoomPrivate extends Controller{ // 调试用的，没有做权限限制
class controlRoomPrivate extends Auth{ // 上线用的
	/**
	 * 公共测试接口
	 */
	public function test(Request $request){
		
	}
	
	/**
	 * 生产信息管理系统·首页
	 */
	public function cpHomePage(Request $request){
		return $this->fetch();
	}
}