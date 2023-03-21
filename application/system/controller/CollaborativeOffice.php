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
 * 广运网盘类
 * @author kongweihao
 *
 */
// class CollaborativeOffice extends Controller{ // 调试用的，没有做权限限制
class CollaborativeOffice extends Auth{ // 上线用的
	/**
	 * 公共测试接口
	 */
	public function test(Request $request){
		
	}
	
	/**
	 * 广运网盘·首页
	 */
	public function coHomePage(Request $request){
		return $this->fetch();
	}
}