<?php
namespace app\system\controller;
use think\Controller;
use think\Session;
use think\Request;
use think\Db;
use app\common\model\Admin;
use app\common\model\Rule;
use app\common\utils\SystemConfig;
use app\common\model\Setting;

/**
 * 系统基类、权限拦截
 * @author kongweihao
 *
 */
class Auth extends Controller{
	//管理员ID
	public $adminId;
	//管理员帐号
	public $adminAccount;
	
	/**
	 * 构造函数
	 */
	protected function _initialize(){
		$this->checkIsLogin();//检查是否登录
		$this->checkPrivilege();//检查操作权限
		$this->leftMenu();//左侧菜单
		$this->initTemplate();//初始化模板（初始化模板应在检查权限之后）
		$this->websiteSetting();//网站设置
	}
	
	/**
	 * 检查是否登录
	 * @return 1.已登录 ，跳转到后台首页    2.未登录，跳转到登录页面    3.登录超时，跳转到登录页面     4.被抢登，跳转到登录页面
	 */
	protected function checkIsLogin(){
		if(Session::has('adminId')){
			if(time() - Session::get('adminSessionStartTime') > 7*86400){ // 超过7天不操作，则操作超时
				$this->deleteAdminSession();
				Session::flash('ts','操作超时，请重新登录');
				$this->redirect('@system/login');
			}else{
				$admin = new Admin();
				$adminInfo = $admin->getAdminInfo(Session::get('adminId'));
				if(Session::get('adminTicket') != $adminInfo->admin_ticket){ // 抢登，被逼下线
					$this->deleteAdminSession();
					Session::flash('ts','您的帐号已在其他地方登录');
					$this->redirect('@system/login');
				}else if($adminInfo->status == 2){//管理员被锁定
					$this->deleteAdminSession();
					Session::flash('ts','您的帐号已被锁定，请联系上级管理员解锁');
					$this->redirect('@system/login');
				}else{
					$this->assign('adminInfo', $adminInfo);
					$this->adminId = Session::get('adminId');
					$this->adminAccount = Session::get('adminAccount');
					Session::set('adminSessionStartTime', time());//更新操作时间
				}
			}
		}else{
			$this->redirect('@system/login');
		}
	}
	
	/**
	 * 删除管理员相关的session
	 */
	private function deleteAdminSession(){
		Session::delete('adminId');
		Session::delete('adminAccount');
		Session::delete('adminSessionStartTime');
		Session::delete('adminTicket');
	}
	
	/**
	 * 操作权限拦截
	 */
	protected function checkPrivilege(){
		//当前请求信息
		$request = Request::instance();
		$currRule = $request->path();//当前请求权限
		$rule = new Rule();
		$currRuleRs = $rule->getRuleInfo($currRule);//当前请求数据库中对应的权限信息
		$currRuleId = $currRuleRs->id;//获取当前请求权限对应数据库中的权限标识id
		
		//当前请求的顶级父节点
		$currRequestTopRule = $rule->getTopRuleById($currRuleRs->parent_id);
		$this->assign('currRequestTopParentId',$currRequestTopRule->id);//当前请求的父节点，用于控制菜单状态
	
		//获取当前管理员的全部权限集合(字符串)
		$adminRuleIds = Db::view('admin_role a',['role_id'])
		->view('role r',['rule_ids'],'a.role_id = r.id')
		->where('admin_id',$this->adminId)
		->where('status',1)
		->column('rule_ids');
	
		//角色含有的权限之和(转换成一维数组)
		$ruleIdsArr = array();
		foreach ($adminRuleIds as $v){
			$subArr = explode(',', $v);
			if(is_array($subArr)){
				foreach ($subArr as $subValue){
					array_push($ruleIdsArr, $subValue);
				}
			}
		}
	
		//查出权限表中状态为正常的权限集合（防止禁用权限被通过）
		$currAdminHasRules = $rule->getRuleIdsByIds($ruleIdsArr);
		$this->assign('currAdminHasRules',$currAdminHasRules);//赋值到前端，用于左侧菜单判断
	
		//判断当前请求是否有权限
		if(!in_array($this->adminAccount, SystemConfig::$passPrivilege)){//放行白名单管理员
			if(!in_array($currRuleId, $currAdminHasRules)){//无权限
				if($request->isAjax()){//ajax请求
					$this->error('无权限操作');
				}else{//模板渲染
					Session::flash('errorTs','无权限操作');//下次请求之前有效
					$this->redirect(prevUrl());
				}
			}
		}
		
		//面包屑导航
		$navPath = $rule->navPath($currRule);
		$this->assign('navPath',$navPath);
		
	}
	
	/**
	 * 控制台左侧菜单
	 */
	protected function leftMenu(){
		$rule = new Rule();
		$menuList = $rule->getMenu();
		$this->assign('menuList',$menuList);
	}
	
	/**
	 * 初始化模板
	 */
	protected function initTemplate(){
		//版本号--用于css js强制刷新
		$this->assign('VERSION',SystemConfig::$systemVersion);
		//系统管理员(开发者)
		$this->assign('systemAdmin',SystemConfig::$systemAccount);
		//权限放行管理员白名单
		$this->assign('passAdmins',SystemConfig::$passPrivilege);
	}
	
	/**
	 * 网站配置
	 */
	protected function websiteSetting(){
		$setting = new Setting();
		$settingInfo = $setting->getSetting();
		$this->assign('settingInfo',$settingInfo);
	}
}