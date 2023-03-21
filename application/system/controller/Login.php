<?php
namespace app\system\controller;
use think\Controller;
use think\Request;
use app\common\model\Admin;
use think\Session;
use think\Db;
use app\common\utils\Util;
use app\common\model\Rule;
use app\common\utils\SystemConfig;
use app\common\model\Setting;

class Login extends Controller{
	/**
	 * 登录页面（无权限限制）
	 */
	public function login(Request $request, Admin $admin, Setting $setting){
		//网站设置
		$settingInfo = $setting->getSetting();
		$this->assign('settingInfo',$settingInfo);
		//版本号--用于css js强制刷新
		$this->assign('VERSION',SystemConfig::$systemVersion);
		
		if($request->isPost()){
			$data = input('post.');
			$this->assign('data',$data);//数据回填
//
//			if(!captcha_check($data['captcha'])){
//				$this->assign('ts','验证码不正确');
//				return $this->fetch();
//			}
			
			$result = $admin->login($data['admin_account'], $data['admin_password']);
			if($result == -1){
				$this->assign('ts',$admin->getError());
				return $this->fetch();
			}else{
				Session::set('adminId', $result->id);
				Session::set('adminAccount', $result->admin_account);
				Session::set('adminFullname', $result->admin_fullname);
				Session::set('adminSessionStartTime', time()); // 用户判断操作是否超时
				$adminTicket = Util::getRandStr(32);
				Session::set('adminTicket', $adminTicket);
				$admin->updateAdminInfo($result->id, ['admin_ticket'=>$adminTicket,'last_login_time'=>time(),'last_login_ip'=>$request->ip()]); // 更新登录信息
				
				//登录成功后跳转
				$loginRedirectUrl = $this->lgionRedirect();
				if(empty($loginRedirectUrl)){
					$this->assign('ts','无权限访问后台系统');
					return $this->fetch();
				}
				$this->redirect('@'.$loginRedirectUrl);
			}
		}
		
		return $this->fetch();
	}
	
	/**
	 * 查询用户的权限，用于成功登录后，重定向到第一个权限页面
	 * 两种情况：1、白名单管理员，直接重定向到第一个页面
	 * 2、非白名单管理员，只能重定向到有权限的第一个页面
	 */
	private function lgionRedirect(){
		$redirectUrl = null;//跳转url
		if(in_array(Session::get('adminAccount'), SystemConfig::$passPrivilege)){
			$rule = new Rule();
			$firstPageRule = $rule->getFirstPageRule();
			return $firstPageRule->rule;
		}else{
			//获取当前管理员的全部权限集合(字符串)
			$adminRuleIds = Db::view('admin_role a',['role_id'])
			->view('role r',['rule_ids'],'a.role_id = r.id')
			->where('admin_id',Session::get('adminId'))
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
			$rule = new Rule();
			$currAdminHasRules = $rule->getRulesByIds($ruleIdsArr);

			//返回第一个权限页面
			foreach ($currAdminHasRules as $v){
				if($v['parent_id'] == 0){
					foreach ($currAdminHasRules as $sv){
						if($sv['parent_id'] == $v['id'] && $sv['parent_id'] != 0 && $sv['is_menu'] == 1){
							return $sv['rule'];
						}
					}
				}
			}

			return $redirectUrl;
		}
	}
	
	/**
	 * 退出登录
	 */
	public function logout(){
		Session::delete('adminId');
		Session::delete('adminAccount');
		Session::delete('adminSessionStartTime');
		Session::delete('adminTicket');
		$this->redirect('@system/login');
	}
	
}