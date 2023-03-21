<?php
namespace app\common\model;
use think\Model;

/**
 * 角色模型类
 * @author kongweihao
 *
 */
class Role extends Model{
	//只读字段，写入后不允许修改
	protected $readonly = ['id','create_time'];
	
	/**
	 * 添加角色
	 * @param array $arr 角色数据
	 * @return mixed
	 */
	public function addRole($arr){
		$findRs = $this->where('role_name',$arr['role_name'])->find();
		if($findRs){
			$this->error = '角色已存在！';
			return -1;
		}else{
			$rs = $this->allowField(true)->insert($arr);
			return $rs;
		}
	}
	
	/**
	 * 角色列表（分页）
	 * @return array
	 */
	public function getRoleList(){
		$rs = $this->paginate();
		return $rs;
	}
	
	/**
	 * 全部角色列表
	 * @return array
	 */
	public function getAllRoleList(){
		$rs = $this->all();
		return $rs;
	}
	
	/**
	 * 更新角色信息
	 * @param array $arr 角色信息
	 * @return mixed
	 */
	public function updateRole($arr){
		$findRs = $this->where('id',$arr['id'])->find();
		if($findRs){
			$rs = $this->allowField(true)->where('id',$arr['id'])->update($arr);
			return $rs;
		}else{
			$this->error = '更新失败，角色信息不存在';
			return -1;
		}
	}
	
	/**
	 * 根据角色id删除角色
	 * @param int $roleId 角色id
	 * @return int
	 */
	public function deleteRoleById($roleId){
		$rs = $this->where('id',$roleId)->delete();
		return $rs;
	}
	
	/**
	 * 根据id获取角色信息
	 * @param int $roleId 角色id
	 * @return
	 */
	public function getRoleInfoById($roleId){
		$rs = $this->where('id',$roleId)->find();
		return $rs;
	}
	
	/**
	 * status获取器
	 */
	protected function getStatusDataAttr($value, $data){
		$statusData = [1=>'正常', 2=>'禁用'];
		return $statusData[$data['status']];
	}
}