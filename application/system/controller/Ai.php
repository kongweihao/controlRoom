<?php
namespace app\system\controller;
use think\Request;
use app\common\model\Role;
use app\common\model\AdminRole;
use think\Controller;
use app\common\utils\SystemConfig;
use think\Db;
use Psr\Log\Logger;
use think\Session;

/**
 * 值班表类
 * @author kongweihao
 *
 */
// class Ai extends Controller{ // 调试用的，没有做权限限制
class Ai extends Auth{ // 上线用的
	/**
	 * 图像生成·管理员端口8860
	 */
	public function sd0(Request $request) {
		return $this->fetch();
	}
	/**
	 * 图像生成·设备1
	 */
	public function sd1(Request $request) {
		return $this->fetch();
	}
	/**
	 * 图像生成·设备2
	 */
	public function sd2(Request $request) {
		return $this->fetch();
	}
	/**
	 * 图像生成·设备3
	 */
	public function sd3(Request $request) {
		return $this->fetch();
	}
	/**
	 * 图像生成·设备4
	 */
	public function sd4(Request $request) {
		return $this->fetch();
	}
	/**
	 * 图像生成·设备5
	 */
	public function sd5(Request $request) {
		return $this->fetch();
	}
	/**
	 * 图像生成·设备6
	 */
	public function sd6(Request $request) {
		return $this->fetch();
	}
	/**
	 * 图像生成·设备7
	 */
	public function sd7(Request $request) {
		return $this->fetch();
	}
}