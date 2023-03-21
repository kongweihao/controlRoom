<?php
namespace app\system\controller;
use app\common\model\Admin as AdminModel;
use think\Request;
use think\Db;
use app\common\model\Role;
use app\common\model\AdminRole;
use app\common\utils\SystemConfig;

/**
 * 管理员类
 * @author kongweihao
 *
 */
class Admin extends Auth{
	/**
	 * 管理员列表
	 */
	public function adminList(AdminModel $admin,AdminRole $adminRole){
		
		$data = input();

		if($data['adminList'] == 1){
			//管理员列表
			$adminList = $admin->getAdminList($data);
			$admin_monitor_list = Db::table('admin_member')->select();
			// 获取管理员关联用户信息
			for($j = 0 ; $j < sizeof($adminList) ; $j++){
				$adminList[$j]['member_id'] = '';
				$adminList[$j]['memberName'] = '';
				for($i = 0 ; $i < sizeof($admin_monitor_list) ; $i++){
					if($admin_monitor_list[$i]['admin_id'] == $adminList[$j]['id']){
						// 当请求某个角色用户列表时，直接对$adminList[$j]赋值无效，原因未知，因此采用中间变量$aa的方式赋值
						$aa = $adminList[$j];
						$aa['member_id'] = $admin_monitor_list[$i]['member_id'];
						$memberName = Db::table('member')->where('id',$admin_monitor_list[$i]['member_id'])->find()['name'];
						$aa['memberName'] =  $memberName;
						$adminList[$j] = $aa;
						continue;
					}
				}
			}
			$adminListListArr = $adminList->toArray();
			//全部管理员角色
			$adminRoleList = $adminRole->getAdminRoleList();

			$rs['code'] = 0;
			$rs['count'] = $adminListListArr['total'];
			$rs['msg'] = '加载成功';
			$rs['data'] = $adminListListArr['data'];
			$rs['adminRoleList'] = $adminRoleList;
			return json($rs);
		} else {
			// 角色列表
			$roleList_selected = $data['role_id'];
			$roleList = Db::table('role')->select();
			$this->assign('roleList',$roleList);
			$this->assign('roleList_selected',$roleList_selected);

			return $this->fetch();
		}
	}
	
	/**
	 * 添加管理员
	 */
	public function addAdmin(Request $request,AdminModel $admin,Role $role,AdminRole $adminRole){
		//角色列表
		$roleList = $role->getAllRoleList();

		// 获取监控组人员信息用于新增账号时管理人员信息
		$monitorList = Db::table('member')->where('is_monitor','1')->select();
		$admin_monitor_list = Db::table('admin_member')->select();

		// 剔除已被关联的监控人员
		for($i = 0 ; $i < sizeof($admin_monitor_list) ; $i++){
			for($j = 0 ; $j < sizeof($monitorList) ; $j++){
				if($admin_monitor_list[$i]['member_id'] == $monitorList[$j]['id']){
					array_splice($monitorList, $j, 1);
					continue;
				}
			}
		}
		
		$this->assign('roleList',$roleList);
		$this->assign('monitorList',$monitorList);
		// return json(input());
		if($request->isPost()){
			$data = input('post.');
			$member_id = input('member_id');//管理监控人员id
			$role_ids = input('post.role_ids/a');//所属角色id
			$two_region_ids = input('post.two_region_ids/a');//管理二级区域id
			
			//数据回填
			$this->assign('data',$data);
			$this->assign('role_ids',$role_ids);
			//验证数据
			if(trim($data['admin_password']) == '' || trim($data['admin_confirm_password']) == ''){
				$this->assign('errorTs','密码不能为空');
				return $this->fetch();
			}else{
				if(trim($data['admin_password']) != trim($data['admin_confirm_password'])){
					$this->assign('errorTs','两次密码不相同');
					return $this->fetch();
				}
			}


			//保存用户
			unset($data['admin_confirm_password']);
			unset($data['role_ids']);
			unset($data['upload_admin_head']);
			unset($data['member_id']);
			$insertId = $admin->addAdmin($data);
			if($insertId == -1){
				$this->assign('errorTs',$admin->getError());
				return $this->fetch();
			}
			
			//保存管理员角色
			$saveAdminRole = $adminRole->addAdminRole($insertId, $role_ids);
			
			// 关联账号与监控人员信息
			if($member_id){
				// 如果人员id存在
				$insertId_admin_member = Db::table('admin_member')->insert(['member_id'=>$member_id,'admin_id'=>$insertId]);
			}

			return redirect('@system/adminList')->with('successTs','添加管理员成功');
		}
	
		return $this->fetch();
	}
	
	/**
	 * 上传管理员头像
	 * @return json
	 */
	public function uploadAdminHead(Request $request){
		if($request->isPost()){
			//获取表单上传文件
			$file = $request->file('upload_admin_head');
			//移动头像文件到框架应用根目录/public/uploads/ 目录下，最大为5M（1024*1024*5）
			if($file){
				$vilidates = [
						'size' => 5242880,
						'ext' => 'jpg,png,gif',
						'type' => 'image/gif,image/jpeg,image/jpg,image/pjpeg,image/x-png,image/png'
				];
				$info = $file->validate($vilidates)->rule(function($file){
					return md5(time());
				})->move(ROOT_PATH.'public'.DS.'uploads'.DS.'adminHead');
				if($info){//移动文件文件成功
					$saveImageName = DS.'public'.DS.'uploads'.DS.'adminHead'.DS.$info->getSaveName();
					return json(['isok'=>true,'imgPath'=>$saveImageName]);
				}else{//移动文件失败
					$uploadError = $file->getError();
					return json(['isok'=>false,'msg'=>$uploadError]);
				}
			}
		}
	}
	
	/**
	 * 编辑管理员
	 */
	public function editAdmin(Request $request,AdminModel $admin,Role $role,AdminRole $adminRole,$id=null){
		//系统管理员只允许被自己修改
		$adminId = $id;
		$toEditAdmin = $admin->getAdminInfo($adminId);
		if(in_array($toEditAdmin->admin_account, SystemConfig::$passPrivilege)){
			if($this->adminId != $toEditAdmin->id){
				return redirect('@system/adminList')->with('errorTs','无权限修改系统管理员');
			}
		}
		$this->assign('data',$toEditAdmin);
		//角色列表
		$roleList = $role->getAllRoleList();
		$this->assign('roleList',$roleList);
		//该管理员角色
		$adminRoleList = $adminRole->getAdminRoleById($adminId);
		$this->assign('adminRoleList',$adminRoleList);

		$data = input();

		//该管理员关联的监控人员
		$adminMember = Db::table('member')
						->alias(['admin_member'=>'am','admin'=>'a','member'=>'m'])
						->where('am.admin_id',$adminId)
						->join('admin_member am','m.id=am.member_id')
						->find();
		$this->assign('adminMember',$adminMember);
		// 获取监控组人员信息用于新增账号时管理人员信息
		$monitorList = Db::table('member')->where('is_monitor','1')->select();
		$admin_monitor_list = Db::table('admin_member')->select();
		// 剔除已被关联的监控人员
		for($i = 0 ; $i < sizeof($admin_monitor_list) ; $i++){
			for($j = 0 ; $j < sizeof($monitorList) ; $j++){
				if($admin_monitor_list[$i]['member_id'] === $monitorList[$j]['id']){
					array_splice($monitorList, $j, 1);
					// unset($monitorList[$j]);//unset 只删除了元素，但是没有重新排列键值
					continue;
				}
			}
		}
		$this->assign('monitorList',$monitorList);


		//接收修改信息
		if($request->isPost() || $data['change_status'] == 1){
			$change_status = 0;
			if( $data['change_status'] == 1){
				$change_status = 1;
			}
			unset($data['change_status']);

			$role_ids = input('post.role_ids/a');//所属角色id
			$two_region_ids = input('post.two_region_ids/a');//管理二级区域id
			$member_id = input('member_id');//获取关联人员id
			
			//数据回填
			$this->assign('data',$data);
			$this->assign('role_ids',$role_ids);
			
			//密码留空表示不修改密码
			if(trim($data['admin_password']) == ''){
				unset($data['admin_password']);
			}else{//密码不为空则验证
				if(trim($data['admin_password']) != trim($data['admin_confirm_password'])){
					$this->assign('errorTs','两次密码不相同');
					return $this->fetch();
				}
			}

			unset($data['admin_confirm_password']);
			unset($data['role_ids']);
			unset($data['upload_admin_head']);
			unset($data['member_id']);

			//更新管理员关联人员信息
			$memberIdRs;
			if($member_id == -1){
				// 断开人员关联关系
				$memberIdRs = Db::table('admin_member')->where('admin_id',$data['id'])->delete();
				if(!$memberIdRs){
					$this->assign('errorTs','修改关联人员信息时发生错误');
				}
			}else if($member_id){
				// 检查该id是否已存在关联人员
				$is_admin_member_link = Db::table('admin_member')->where('admin_id',$data['id'])->find();
				if($is_admin_member_link){
					$memberIdRs = Db::table('admin_member')->where('admin_id',$data['id'])->update(['member_id'=>$member_id]);
					if(!$memberIdRs){
						$this->assign('errorTs','修改关联人员信息时发生错误');
					}
				}else{
					$memberIdRs = Db::table('admin_member')->insert(['member_id'=>$member_id,'admin_id'=>$data['id']]);
					if(!$memberIdRs){
						$this->assign('errorTs','修改关联人员信息时发生错误');
					}
				}
			}

			//更新用户
			$result = $admin->updateAdmin($data);
			if($result == -1){
				$this->assign('errorTs',$admin->getError());
				return $this->fetch();
			}
			//更新管理员角色
			$saveAdminRole = $adminRole->addAdminRole($data['id'], $role_ids);

			
			if($change_status == 1){
				return $result;
			}else{
				return redirect('@system/adminList')->with('successTs','修改管理员成功');
			}
		}
		// 修改年假资格状态
		if ($data['change_editHolidaysStatus'] == 1) {
			unset($data['change_editHolidaysStatus']);
			$result =Db::table('admin')->update($data);
			if($result == -1){
				$this->assign('errorTs',$admin->getError());
				return $this->fetch();
			} else {
				return 1;
			}
		}
		// 修改年假资格状态
		if ($data['change_editVacationsStatus'] == 1) {
			unset($data['change_editVacationsStatus']);
			$result =Db::table('admin')->update($data);
			if($result == -1){
				$this->assign('errorTs',$admin->getError());
				return $this->fetch();
			} else {
				return 1;
			}
		}
		return $this->fetch();
	}
	
	/**
	 * 删除管理员
	 */
	public function deleteAdmin(AdminModel $admin,AdminRole $adminRole,$adminId){
		//系统管理员不允许删除
		$toDeleteAdmin = $admin->getAdminInfo($adminId);
		if(in_array($toDeleteAdmin->admin_account, SystemConfig::$passPrivilege)){
			$rs['code'] = 0;
			$rs['msg'] = '系统管理员不允许删除';
			return $rs;
		}
		// 删除管理员与监控组人员的关联信息
		$is_admin_member_link = Db::table('admin_member')->where('admin_id',$adminId)->find();
		if($is_admin_member_link){//admin与member存在管理才进入
			$deleteAdminMs = Db::table('admin_member')->where('admin_id',$adminId)->delete();
			if(!$deleteAdminMs){
				$rs['code'] = 0;
				$rs['msg'] = '删除管理员与监控人员管理信息时发生错误';
				return $rs;
			}
		}
		// 删除管理员与角色的关联信息
		$deleteAdminRs = $admin->deleteAdminById($adminId);
		if($deleteAdminRs){
			$adminRole->deleteAdminRoleById($adminId);//删除管理员角色
			$rs['code'] = 1;
			$rs['msg'] = '删除成功';
			return $rs;
		}else{
			$rs['code'] = 0;
			$rs['msg'] = '删除管理员角色时发生';
			return $rs;
		}

	}
}