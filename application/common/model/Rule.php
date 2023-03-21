<?php
namespace app\common\model;
use think\Model;

/**
 * 权限模型类
 * @author kongweihao
 *
 */
class Rule extends Model{
	//只读字段，写入以后就不允许被更新
	protected $readonly = ['id','create_time'];
	
	/**
	 * 添加权限
	 * @param array $data 权限数据
	 * @return integer
	 */
	public function addRule($data){
		$findRs = $this->where('rule',$data['rule'])->find();
		if($findRs){
			$this->error = '该权限已存在';
			return -1;
		}else{
			$rs = $this->allowField(true)->insert($data);
			return $rs;
		}
	}
	
	/**
	 * 更新权限
	 * @param array $data 权限数据
	 * @return integer
	 */
	public function updateRule($data){
		$findRs = $this->where('id',$data['id'])->find();
		if($findRs){
			if(!isset($data['is_menu'])){
				$data['is_menu'] = 2;
			}
			if(!isset($data['status'])){
				$data['status'] = 2;
			}
			$rs = $this->allowField(true)->where('id',$data['id'])->update($data);
			return $rs;
		}else{
			$this->error = '该权限不存在';
			return -1;
		}
	}
	
	/**
	 * 权限排序
	 * @param int $ruleId 权限id
	 * @param int $sortNum 排序数字
	 * @return mixed
	 */
	public function updateRuleSort($ruleId,$sortNum){
		$findRs = $this->where('id',$ruleId)->find();
		if($findRs){
			$rs = $this->where('id',$ruleId)->update(['sort'=>$sortNum]);
			return $rs;
		}else{
			$this->error = '权限不存在';
			return -1;
		}
	}
	
	/**
	 * 根据id删除权限（父权限被删除，子权限也应当被删除）
	 * @param int $ruleId 权限id
	 * @return integer
	 */
	public function deleteRuleById($ruleId){
		$rs = $this->where('id',$ruleId)->delete();
		$srs = $this->where('parent_id',$ruleId)->delete();
		return $rs;
	}
	
	/**
	 * 根据权限获取权限信息
	 * @param string $rule 权限规则
	 * @return array
	 */
	public function getRuleInfo($rule){
		$rs = $this->where('rule',$rule)->find();
		return $rs;
	}
	
	/**
	 * 根据权限ID获取权限信息
	 * @param integer $ruleId 权限ID
	 * @return array
	 */
	public function getRuleInfoById($ruleId){
		$rs = $this->where('id',$ruleId)->find();
		return $rs;
	}
	
	/**
	 * 根据权限id集合获取权限集合(状态为1可用)
	 * @param array $ids 权限id集合
	 * @return array 返回状态正常的权限id集合
	 */
	public function getRuleIdsByIds($ids){
		$rs = $this->where('status',1)->where('id','in',$ids)->distinct(true)->column('id');
		return $rs;
	}
	
	/**
	 * 根据权限id集合获取权限集合(状态为1可用)
	 * @param array $ids 权限id集合
	 * @return array 返回状态正常的权限集合
	 */
	public function getRulesByIds($ids){
		$rs = $this->where('status',1)->where('id','in',$ids)->distinct(true)->order('sort asc')->select();
		return $rs;
	}
	
	/**
	 * 获取一级菜单列表
	 * @return array
	 */
	public function getOneLevelMenu(){
		$rs = $this->where('parent_id',0)->select();
		return $rs;
	}
	
	/**
	 * 获取一级权限列表（分页处理）
	 * @param integer $oneLevelRuleId 一级菜单id
	 * @return array
	 */
	public function getOneRuleForPaignate($oneLevelRuleId){
		if($oneLevelRuleId != 0){
			$where['id'] = $oneLevelRuleId;
		}else{
			$where['parent_id'] = 0;
		}
		$rs = $this->where($where)->order(['sort asc'])->paginate(1);
		return $rs;
	}
	
	/**
	 * 获取所有权限列表
	 * @return array
	 */
	public function getAllRule(){
		$rs = $this->order('sort asc')->select();
		return $rs;
	}
	
	/**
	 * 获取菜单（状态正常）
	 * @return array
	 */
	public function getMenu(){
		$rs = $this->where('is_menu',1)->where('status',1)->order('sort asc')->select();
		return $rs;
	}
	
	/**
	 * 获取权限中第一个页面
	 * @return array
	 */
	public function getFirstPageRule(){
		$rs = $this->where('is_menu',1)->where('status',1)->where('parent_id','<>',0)->order('sort asc')
		->find();
		return $rs;
	}
	
	/**
	 * 根据权限id获取顶级权限
	 * @param integer $ruleId 权限id
	 * @return array
	 */
	public function getTopRuleById($ruleId){
		$frs = $this->where('id',$ruleId)->find();
		if($frs->parent_id == 0){//证明已经是顶级
			return $frs;
		}else{
			$srs = $this->where('id',$frs->parent_id)->find();
			return $srs;
		}
	}
	
	/**
	 * 面包屑导航
	 * @param string $currRule 当前导航
	 * @return array
	 */
	public function navPath($currRule){
		$currRuleRs = $this->where('rule',$currRule)->find();
		$threeRuleRs = $this->where('id',$currRuleRs->parent_id)->find();
		$twoRuleRs = $this->where('id',$threeRuleRs->parent_id)->find();
		$rs = [
				'twoRuleInfo' => $twoRuleRs,
				'threeRuleInfo' => $threeRuleRs,
				'fourRuleInfo' => $currRuleRs
		];
		return $rs;
	}
	
	/**
	 * 根据权限状态获取权限集合
	 * @param integer $status 状态  1正常   2禁用 
	 * @return array
	 */
	public function getRuleListByStatus($status){
		$rs = $this->where('status',$status)->order('sort asc')->select();
		return $rs;
	}
	
	/**
	 * is_menu获取器
	 */
	protected function getIsMenuDataAttr($value, $data){
		$isMenuData = [1=>'是', 2=>'否'];
		return $isMenuData[$data['is_menu']];
	}
	
	/**
	 * status获取器
	 */
	protected function getStatusDataAttr($value, $data){
		$statusData = [1=>'正常', 2=>'禁用'];
		return $statusData[$data['status']];
	}
}