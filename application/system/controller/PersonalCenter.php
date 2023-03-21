<?php
namespace app\system\controller;
namespace app\system\controller;
use app\common\model\Admin as AdminModel;
use think\Request;
use think\Db;
use app\common\model\Role;
use app\common\model\AdminRole;
use app\common\utils\SystemConfig;
use think\Session;

/**
 * 个人中心类
 * @author kongweihao
 *
 */
class PersonalCenter extends Auth{
	/**
	 * 个人中心页面
	 */
	public function personalInformation(Request $request,AdminModel $admin,Role $role,AdminRole $adminRole,$id=null){
		$adminId = Session::get('adminId');
		$data = input();
		if($data['editPassword'] == 1){
			$admin_password_old = md5($data['admin_password_old']);
			$admin_password = md5($data['admin_password']);
			// 判断旧密码是否正确
			$oldPassWord = Db::table('admin')
					->where('id',$adminId)
					->find()['admin_password'];
			if($admin_password_old == $oldPassWord) {
				$rs = Db::table('admin')
						->where('id',$adminId)
						->update(['admin_password'=>$admin_password]);
				if($rs){
					$this->assign('successTs','密码修改成功');
				}else{
					$this->assign('errorTs','新旧密码不能相同');
				}
			} else {
				return '旧密码错误，请返回确认';
			}

			// return \json($data);
		}else{
			$adminMessage = Db::table('admin')
							->where('id',$adminId)
							->find();
			// return \json($adminMessage);
			$this->assign('adminMessage',$adminMessage);
		}
		return $this->fetch();
	}
}