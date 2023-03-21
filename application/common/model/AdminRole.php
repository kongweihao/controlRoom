<?php
namespace app\common\model;
use think\Model;
use think\Db;

/**
 * 管理员角色模型
 * @author kongweihao
 *
 */
class AdminRole extends Model{
	//只读字段，写入以后就不允许被更新
	protected $readonly = ['id','create_time'];
	
	/**
	 * 添加管理员角色
	 * @param int $adminId 管理员id
	 * @param array $role_ids 角色id集合
	 * @return mixed
	 */
	public function addAdminRole($adminId,$role_ids=null){
		if(is_array($role_ids)){
			$arr = array();
			foreach ($role_ids as $v){
				$data = ['admin_id'=>$adminId,'role_id'=>$v];
				array_push($arr, $data);
			}
			$this->where('admin_id',$adminId)->delete();
			$rs = $this->insertAll($arr);
		}else{
			$this->where('admin_id',$adminId)->delete();
		}
		
		return $rs;
	}
	
	/**
	 * 【视图查询】
	 * 获取全部管理员角色
	 * @param int $adminId 管理员id
	 * @return array
	 */
	public function getAdminRoleList(){
		$rs = Db::view('admin_role a',['admin_id'])
		->view('role r',['role_name'],'a.role_id = r.id','RIGHT')
		->distinct(true)
		->select();
		return $rs;
	}
	
	/**
	 * 根据管理员id获取管理员角色
	 * @param int $adminId
	 * @return array
	 */
	public function getAdminRoleById($adminId){
		$rs = $this->where('admin_id',$adminId)->column('role_id');
		return $rs;
	}
	
	/**
	 * 根据管理员id删除管理员角色
	 * @param int $adminId 管理员id
	 * @return integer
	 */
	public function deleteAdminRoleById($adminId){
		$rs = $this->where('admin_id',$adminId)->delete();
		return $rs;
	}
	
}