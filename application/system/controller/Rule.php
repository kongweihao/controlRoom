<?php
namespace app\system\controller;
use think\Request;
use app\common\model\Rule as RuleModel;

/**
 * 权限规则类
 * @author kongweihao
 *
 */
class Rule extends Auth{
	/**
	 * 权限列表
	 */
	public function ruleList(RuleModel $rule, $oneLevelRuleId=0){
		//获取一级菜单（分页处理）
		$oneRuleList = $rule->getOneRuleForPaignate($oneLevelRuleId);
		$this->assign('oneRuleList',$oneRuleList);
		//全部权限列表
		$allRuleList = $rule->getAllRule();
		$this->assign('allRuleList',$allRuleList);
		// return json($allRuleList);
		return $this->fetch();
	}
	
	/**
	 * 添加权限
	 */
	public function addRule(Request $request, RuleModel $rule){
		//所属菜单（即一二级菜单）
		$menuList = $rule->getMenu();
		$this->assign('menuList',$menuList);
		
		if($request->isPost()){
			$data = input('post.');
			$addResult = $rule->addRule($data);
			if($addResult == -1){
				$this->assign('backData',$data);//数据回填
				$this->assign('errorTs',$rule->getError());
				return $this->fetch();
			}else{
				return redirect('@system/ruleList')->with('successTs','添加成功');
			}
		}
		
		return $this->fetch();
	}
	
	/**
	 * 修改权限
	 */
	public function editRule(Request $request, RuleModel $rule, $ruleId=null){
		//所属菜单（即一二级菜单）
		$menuList = $rule->getMenu();
		$this->assign('menuList',$menuList);
		//权限信息
		$ruleInfo = $rule->getRuleInfoById($ruleId);
		$this->assign('ruleInfo',$ruleInfo);
		
		if($request->isPost()){
			$data = input('post.');
			$page = isset($data['page']) ? $data['page'] : 1;
			unset($data['page']);
			$updateResult = $rule->updateRule($data);
			if($updateResult == -1){
				$this->assign('errorTs',$rule->getError());
				return $this->fetch();
			}else{
				return redirect('@system/ruleList',['page'=>$page])->with('successTs','修改成功');
			}
		}
		
		return $this->fetch();
	}
	
	/**
	 * 权限排序
	 */
	public function sortRule(Request $request,RuleModel $rule){
		if($request->isAjax()){
			$sortNum = input('get.sortNum');
			$ruleId = input('get.ruleId');
			$result = $rule->updateRuleSort($ruleId,$sortNum);
			if($result==-1){
				$returnRs = ['isok'=>false,'msg'=>$rule->getError()];
				return json($returnRs);
			}else{
				$returnRs = ['isok'=>true,'msg'=>'排序成功'];
				return json($returnRs);
			}
		}
	}
	
	/**
	 * 删除权限
	 */
	public function deleteRule(RuleModel $rule, $ruleId){
		$result = $rule->deleteRuleById($ruleId);
		return redirect(prevUrl())->with('successTs','删除成功');
	}
}