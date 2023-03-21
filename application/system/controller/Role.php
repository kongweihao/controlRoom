<?php
namespace app\system\controller;
use app\common\model\Rule;
use app\common\model\Role as RoleModel;
use think\Request;

/**
 * 角色类
 * @author kongweihao
 *
 */
class Role extends Auth{
	/**
	 * 角色列表
	 */
	public function roleList(RoleModel $role){
		$roleList = $role->getRoleList();
		$this->assign('roleList',$roleList);
		return $this->fetch('roleList');
	}
	
	/**
	 * 添加角色
	 */
	public function addRole(Request $request,Rule $rule,RoleModel $role){
		//权限列表
		$ruleList = $rule->getRuleListByStatus(1);
		$this->assign('ruleList',$ruleList);

		//提交添加信息
		if($request->isPost()){
			$data = input('post.');
			$rule_ids = input('post.rule_ids/a');
				
			//处理权限集合为逗号分割的字符串
			$rule_ids_str;
			if($rule_ids != []){
				foreach ($rule_ids as $k => $v){
					if($k==0){
						$rule_ids_str = $v;
						continue;
					}
					$rule_ids_str = $rule_ids_str.','.$v;
				}
			}
			$data['rule_ids'] = $rule_ids_str;
				
			$this->assign('data',$data);//数据回填
			if(!trim($data['role_name'])){
				$this->assign('errorTs','角色名称不能为空');
				return $this->fetch();
			}
				
			$result = $role->addRole($data);
			if($result == -1){
				$this->assign('errorTs',$role->getError());
				return $this->fetch();
			}else{
				return redirect('@system/roleList')->with('successTs','添加角色成功');
			}
		}
		
		return $this->fetch();
	}
	
	/**
	 * 修改角色
	 */
	public function editRole(Request $request,Rule $rule,RoleModel $role, $roleId=null){
		//权限列表
		$ruleList = $rule->getRuleListByStatus(1);
		$this->assign('ruleList',$ruleList);
		//角色信息
		$roleInfo = $role->getRoleInfoById($roleId);
		$this->assign('data',$roleInfo);
		
		//提交编辑信息
		if($request->isPost()){
			$data = input('post.');
			$page = $data['page'] ? $data['page'] : 1;
			unset($data['page']);
			$rule_ids = input('post.rule_ids/a');
		
			//处理权限集合为逗号分割的字符串
			$rule_ids_str;
			if($rule_ids != []){
				foreach ($rule_ids as $k => $v){
					if($k==0){
						$rule_ids_str = $v;
						continue;
					}
					$rule_ids_str = $rule_ids_str.','.$v;
				}
			}
			$data['rule_ids'] = $rule_ids_str;
		
			$this->assign('data',$data);//数据回填
			if(!trim($data['role_name'])){
				$this->assign('errorTs','角色名称不能为空');
				return $this->fetch();
			}
				
			$result = $role->updateRole($data);
			if($result == -1){
				$this->assign('errorTS',$role->getError());
				return $this->fetch();
			}else{
				return redirect('@system/roleList',['page'=>$page])->with('successTs','修改成功');
			}
		}
		
		return $this->fetch();
	}
	
	/**
	 * 删除角色
	 */
	public function deleteRole(RoleModel $role, $roleId){
		$result = $role->deleteRoleById($roleId);
		if($result){
			return redirect('@system/roleList')->with('successTs','删除成功');
		}else{
			return redirect('@system/roleList')->with('errorTs','删除失败');
		}
	}
}