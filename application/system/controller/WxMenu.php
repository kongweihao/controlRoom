<?php
namespace app\system\controller;
use app\common\model\WxMenu as MenuModel;
use think\Request;
use app\common\utils\WxApi;
use app\common\utils\HttpRequest;

/**
 * 微信自定义菜单
 * @author liuwnewei
 *
 */
class WxMenu extends Auth{
	/**
	 * 自定义菜单列表
	 */
	public function menuList(MenuModel $menu){
		//菜单列表
		$wxMenuList = $menu->getMenuList();
		$this->assign('wxMenuList',$wxMenuList);
		
		return $this->fetch();
	}
	
	/**
	 * 添加自定义菜单
	 */
	public function addMenu(Request $request, MenuModel $menu){
		//一级菜单
		$oneLevelMenuList = $menu->getOneLevelMenu();
		$this->assign('oneLevelMenuList',$oneLevelMenuList);
		
		if($request->isAjax()){
			$data = input('post.');
			$result = $menu->addMenu($data);
			if($result == -1){
				return json(['isok'=>false,'msg'=>$menu->getError()]);
			}else{
				return json(['isok'=>true,'msg'=>'添加微信菜单成功']);
			}
		}
		
		return $this->fetch();
	}
	
	/**
	 * 修改自定义菜单
	 */
	public function editMenu(Request $request, MenuModel $menu, $menuId){
		//菜单信息
		$menuInfo = $menu->getMenuInfoById($menuId);
		$this->assign('menuInfo',$menuInfo);
		//一级菜单
		$oneLevelMenuList = $menu->getOneLevelMenu();
		$this->assign('oneLevelMenuList',$oneLevelMenuList);
		//一级菜单是否有子级
		$hasChildren = $menu->getHasChildrenMenu($menuId);
		$this->assign('hasChildren',$hasChildren);
		//素材内容
		if($menuInfo->media_id){
			if($menuInfo->res_type == 'news'){//图文
				$url = 'https://api.weixin.qq.com/cgi-bin/material/get_material?access_token='.WxApi::get_access_token();
				$mediaData = ['media_id'=>$menuInfo->media_id];
				$mediaInfo = HttpRequest::curl_post($url, json_encode($mediaData));
				$this->assign('mediaInfo',$mediaInfo);
			}
		}
		
		if($request->isAjax()){
			$data = input('post.');
			$result = $menu->updateMenu($data);
			if($result == -1){
				return json(['isok'=>false,'msg'=>$menu->getError()]);
			}else{
				return json(['isok'=>true,'msg'=>'修改微信菜单成功']);
			}
		}
		
		return $this->fetch();
	}
	
	/**
	 * 菜单排序
	 */
	public function sortMenu(Request $request,MenuModel $menu){
		if($request->isAjax()){
			$sortNum = input('get.sortNum');
			$menuId = input('get.menuId');
			$result = $menu->updateMenuSort($menuId,$sortNum);
			if($result==-1){
				return json(['isok'=>false,'msg'=>$menu->getError()]);
			}else{
				return json(['isok'=>true,'msg'=>'排序成功']);
			}
		}
	}
	
	/**
	 * 删除自定义菜单
	 */
	public function deleteMenu(MenuModel $menu, $menuId){
		$result = $menu->deleteMenuById($menuId);
		if($result == -1){
			return redirect('@system/menuList')->with('errorTs',$menu->getError());
		}else{
			return redirect('@system/menuList')->with('successTs','删除菜单成功');
		}
	}
	
	/**
	 * 推送菜单至微信服务器
	 */
	public function pushWxMenu(MenuModel $menu){
		//全部菜单
		$wxMenuList = $menu->getMenuList();
		
		//构造自定义菜单json数据
		$arr = array();
		foreach ($wxMenuList as $v){
			$tempArr = array();
			if($v['parent_id'] == 0){
				if(empty($v['type'])){//有子菜单
					$subTempArr = array();
					foreach ($wxMenuList as $sv){
						if($sv['parent_id'] != 0 && $sv['parent_id'] == $v['id']){
							if($sv['type'] == 'view'){
								$sub = [
										'type' => $sv['type'],
										'name' => urlencode($sv['wx_name']),
										'url' => $sv['url']
								];
							}else if($sv['type'] == 'click'){
								$sub= [
										'type' => $sv['type'],
										'name' => urlencode($sv['wx_name']),
										'key' => $sv['key']
								];
							}else if($sv['type'] == 'miniprogram'){
								$sub= [
										'type' => $sv['type'],
										'name' => urlencode($sv['wx_name']),
										'url' => $sv['url'],
										'appid' => $sv['appid'],
										'pagepath' => $sv['pagepath']
								];
							}
							array_push($subTempArr, $sub);
						}
					}
					$tempArr = [
							'name' => urlencode($v['wx_name']),
							'sub_button' => $subTempArr
					];
				}else{//无子菜单
					if($v['type'] == 'view'){
						$tempArr = [
								'type' => $v['type'],
								'name' => urlencode($v['wx_name']),
								'url' => $v['url']
						];
					}else if($v['type'] == 'click'){
						$tempArr = [
								'type' => $v['type'],
								'name' => urlencode($v['wx_name']),
								'key' => $v['key']
						];
					}else if($v['type'] == 'miniprogram'){
						$tempArr = [
								'type' => $v['type'],
								'name' => urlencode($v['wx_name']),
								'url' => $v['url'],
								'appid' => $v['appid'],
								'pagepath' => $v['pagepath']
						];
					}
				}
				array_push($arr, $tempArr);
			}
		}
		$allArr = [
				'button' => $arr
		];
		
		//推送
		$url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".WxApi::get_access_token();
		$result = HttpRequest::curl_post($url, urldecode(json_encode($allArr)));
		if($result['errcode'] == 0 && $result['errmsg'] == 'ok'){
			return redirect('@system/menuList')->with('successTs','推送成功');
		}else{
			return redirect('@system/menuList')->with('errorTs','推送失败，错误码：'.$result['errmsg']);
		}
	}
	
}