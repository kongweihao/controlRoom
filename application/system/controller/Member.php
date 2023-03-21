<?php
namespace app\system\controller;
use app\common\model\Member as MemberModel;
use think\Request;
use app\common\model\Role;
use app\common\model\AdminRole;
use think\Controller;
use app\common\utils\SystemConfig;
use think\Db;


/**
 * 人员类
 * @author kongweihao
 *
 */
// class Member extends Controller{ // 调试用的，没有做权限限制
class Member extends Auth{ // 上线用的
	/**
	 * 人员列表
	 */
	public function memberList(MemberModel $member){
		//人员列表
		$data = input('param.');//get post照单全收
		$condition = array();
		$condition['name'] = array('like','%'.$data['name'].'%');
		$condition['phone'] = array('like','%'.$data['phone'].'%');
		$condition['department'] = array('like','%'.$data['department'].'%');
		$condition['group'] = array('like','%'.$data['group'].'%');
		$condition['job_number'] = array('like','%'.$data['job_number'].'%');
		// return json($condition);
		$memberList = $member->getMemberList($condition);
		$this->assign([
			'memberList'=>$memberList,
			'memberListSearchData'=>$data,
		]);
		// return json($data);
		return $this->fetch();
	}
	
	/**
	 * 添加人员
	 */
	public function addMember(Request $request,MemberModel $member) {

        if ($request->isPost()) {//只是打开页面则不进入下面
            $data = input('param.');
			$name = trim($data['name']);
			
            if ($member->checkName($name)) {
                return redirect('@system/addMember', ['data' => $data])->with('errorTs', '姓名重复！');
			}
			$rs = $member->allowField(true)->save($data);
			//这种写法只适合于保存一条数据，
			//如果用循环语句保存多条数据，会导致后面的覆盖前面的，既更新了此时要解决此问题就在save之前加上->isUpdate(false)
			return $rs;
        }
		$monitorPostTypeList = Db::table('monitorpost_type')->select();
		$this->assign('monitorPostTypeList', $monitorPostTypeList);

        $this->assign('data', input('data/a'));
        return $this->fetch();
	}
	// /**
	//  * 以excle形式导入人员信息附件
	//  * @return json
	//  */
	// public function uploadMemberExcle(Request $request){
	// 	if($request->isPost()){
	// 		return json_encode($request->file('upload_excle'));

	// 		//获取表单上传文件
	// 		$file = $request->file('upload_excle');
	// 		//移动头像文件到框架应用根目录/public/uploads/ 目录下，最大为5M（1024*1024*5）
	// 		if($file){
	// 			$vilidates = [
	// 					'size' => 5242880,
	// 					'ext' => 'excle',
	// 			];
	// 			$info = $file->validate($vilidates)->rule(function($file){
	// 				return md5(time());
	// 			})->move(ROOT_PATH.'public'.DS.'uploads'.DS.'adminHead');
	// 			if($info){//移动文件文件成功
	// 				$saveImageName = DS.'public'.DS.'uploads'.DS.'adminHead'.DS.$info->getSaveName();
	// 				return json(['isok'=>true,'imgPath'=>$saveImageName]);
	// 			}else{//移动文件失败
	// 				$uploadError = $file->getError();
	// 				return json(['isok'=>false,'msg'=>$uploadError]);
	// 			}
	// 		}
	// 	}
	// }
	
	/**
	 * 导入附件形式批量添加人员
	 */
	public function addMoreMember(Request $request,MemberModel $member) {

		if (input('param/a')) {//只是打开页面则不进入下面
			$data = input('param/a');
			$dataLen = input('eachDataLen');
			// return json($data);
            // if ($member->checkName($name)) {
            //     return redirect('@system/addMoreMember', ['data' => $data])->with('errorTs', '姓名重复！');
			// }
			// $rs = Db::table('member')->inserAll($data);

			for($i = 0 ; $i<$dataLen ; $i++){
				if($data[$i]){
					$rs = Db::table('member')->insert($data[$i]);
				}
			}
			return $rs;// 0 失败 1 成功
			// if($rs){
			// 	return 0;//redirect('@system/memberList')->with('successTs', '创建成功！');
			// }else{
            //     return 1;//redirect('@system/addMoreMember', ['data' => $data])->with('errorTs', '新增失败，请重新尝试，如果多次失败，请尝试刷新');
			// }
        }

        $this->assign('data', input('data/a'));
        return $this->fetch();
    }
	/**
	 * 编辑人员
	 */
	public function editMember(Request $request,MemberModel $member) {

		$id = input('id');
		$rs = Db::table('member')
			->where('id',$id)
			->find();
		$monitorPostTypeList = Db::table('monitorpost_type')->select();
		$rs['monitorPost_type_id'] = explode(',',$rs['monitorPost_type_id']);
		for($i = 0; $i < sizeof($monitorPostTypeList); $i++){
			for($j = 0; $j < sizeof($rs['monitorPost_type_id']); $j++){
				if($monitorPostTypeList[$i]['monitorPost_type_id'] == $rs['monitorPost_type_id'][$j]){
					$monitorPostTypeList[$i]['is_select'] = 1;
				}
			}
		}
		// return json($monitorPostTypeList);
		$this->assign('monitorPostTypeList', $monitorPostTypeList);
		$this->assign('data', $rs);

		// return json($rs);
        return $this->fetch();
	}
	
	/**
	 * 更新人员信息
	 */
	public function updateMember(Request $request,MemberModel $member) {
		$data = input('param.');
		$id = $data['id'];
		unset($data['id']);//删除关联数组中的指定元素;
		// return json($data);
		$rs = Db::table('member')->where('id',$id)->update($data);
		return $rs;//1成功 0失败
	}
	
	/**
	 * 删除人员
	 */
	public function deleteMember(MemberModel $member){
		$memberId = input('id');

		$rs = $member->deleteMemberById($memberId);
		if($rs){
			return redirect('@system/memberList')->with('successTs','删除人员成功');
		}else{
			return redirect('@system/memberList')->with('errorTs','删除人员失败');
		}
	}
// 	/**
// 	 * 上传人员头像
// 	 * @return json
// 	 */
// 	public function uploadAdminHead(Request $request){
// 		if($request->isPost()){
// 			//获取表单上传文件
// 			$file = $request->file('upload_admin_head');
// 			//移动头像文件到框架应用根目录/public/uploads/ 目录下，最大为5M（1024*1024*5）
// 			if($file){
// 				$vilidates = [
// 						'size' => 5242880,
// 						'ext' => 'jpg,png,gif',
// 						'type' => 'image/gif,image/jpeg,image/jpg,image/pjpeg,image/x-png,image/png'
// 				];
// 				$info = $file->validate($vilidates)->rule(function($file){
// 					return md5(time());
// 				})->move(ROOT_PATH.'public'.DS.'uploads'.DS.'adminHead');
// 				if($info){//移动文件文件成功
// 					$saveImageName = DS.'public'.DS.'uploads'.DS.'adminHead'.DS.$info->getSaveName();
// 					return json(['isok'=>true,'imgPath'=>$saveImageName]);
// 				}else{//移动文件失败
// 					$uploadError = $file->getError();
// 					return json(['isok'=>false,'msg'=>$uploadError]);
// 				}
// 			}
// 		}
// 	}
	
// 	/**
// 	 * 编辑人员
// 	 */
// 	public function editAdmin(Request $request,AdminModel $admin,Role $role,AdminRole $adminRole,$adminId=null){
// 		//系统人员只允许被自己修改
// 		$toEditAdmin = $admin->getAdminInfo($adminId);
// 		if(in_array($toEditAdmin->admin_account, SystemConfig::$passPrivilege)){
// 			if($this->adminId != $toEditAdmin->id){
// 				return redirect('@system/adminList')->with('errorTs','无权限修改系统人员');
// 			}
// 		}
		
// 		$this->assign('data',$toEditAdmin);
// 		//角色列表
// 		$roleList = $role->getAllRoleList();
// 		$this->assign('roleList',$roleList);
// 		//该人员角色
// 		$adminRoleList = $adminRole->getAdminRoleById($adminId);
// 		$this->assign('adminRoleList',$adminRoleList);
		
// 		//接收修改信息
// 		if($request->isPost()){
// 			$data = input('post.');
// 			$role_ids = input('post.role_ids/a');//所属角色id
// 			$two_region_ids = input('post.two_region_ids/a');//管理二级区域id
			
// 			//数据回填
// 			$this->assign('data',$data);
// 			$this->assign('role_ids',$role_ids);
			
// 			//密码留空表示不修改密码
// 			if(trim($data['admin_password']) == ''){
// 				unset($data['admin_password']);
// 			}else{//密码不为空则验证
// 				if(trim($data['admin_password']) != trim($data['admin_confirm_password'])){
// 					$this->assign('errorTs','两次密码不相同');
// 					return $this->fetch();
// 				}
// 			}

// 			unset($data['admin_confirm_password']);
// 			unset($data['role_ids']);
// 			unset($data['upload_admin_head']);

// 			//更新用户
// 			$result = $admin->updateAdmin($data);
// 			if($result == -1){
// 				$this->assign('errorTs',$admin->getError());
// 				return $this->fetch();
// 			}
// 			//更新人员角色
// 			$saveAdminRole = $adminRole->addAdminRole($data['id'], $role_ids);
			
	
// 			return redirect('@system/adminList')->with('successTs','修改人员成功');
// 		}
	
// 		return $this->fetch();
// 	}
	
	/**
	 * 值班人员列表
	 */
	public function monitorGuyList(MemberModel $member){
		//人员列表
		$data = input('param.');//get post照单全收
		$condition = array();
		$condition['name'] = array('like','%'.$data['name'].'%');
		if($data['name'] == ''){//如果name为空则只显示监控人员
			$condition['is_monitor'] = array('=',1);
		}
		$condition['phone'] = array('like','%'.$data['phone'].'%');
		// return json($condition);
		$memberList = $member->getMemberList($condition);
		$this->assign([
			'memberList'=>$memberList,
			'memberListSearchData'=>$data,
		]);
		// return json($data);
		return $this->fetch();
	}
}