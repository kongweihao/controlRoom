<?php
namespace app\common\model;
use think\Db;
use think\Model;

/**
 * 人员模型类
 * @author kongweihao
 *
 */
class Member extends Model{
	//只读字段，写入以后就不允许被更新
	protected $readonly = ['id','create_time'];
	
	/**
	 * 获取全部人员（过滤敏感信息）
	 * @return array
	 */ 
	public function getMemberList($condition){
		if ($condition!=null){
			$rs = Db::table('member')
			->where($condition)
			->order(['id ASC'])//DESC降序
			->paginate(10);
        }else{
			$rs = Db::table('member')
			->order(['id ASC'])
			->paginate(10);
        }
		return $rs;
	}

	/**
	 * 检查人员姓名，是否已存在
	 * @return array
	 */
	public function checkName($name){
		$rs = Db::table('member')
			->where('name', $name)
			->select();
		return $rs;
	}

	
	/**
	 * 删除人员
	 * @return array
	 */
	public function deleteMemberById($memberId){
		$rs = Db::table('member')
			->where('id',$memberId)->delete();
		return $rs;
	}

}