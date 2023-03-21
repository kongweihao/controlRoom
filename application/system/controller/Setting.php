<?php
namespace app\system\controller;
use think\Request;
use app\common\model\Setting as SettingModel;

/**
 * 网站设置类
 * @author kongweihao
 *
 */
class Setting extends Auth{
	/**
	 * 网站设置页面
	 */
	public function setting(SettingModel $setting){
		//配置信息
		$settingInfo = $setting->getSetting();
		$this->assign('settingInfo',$settingInfo);
		return $this->fetch();
	}
	
	/**
	 * 修改网站设置
	 */
	public function editSetting(Request $request, SettingModel $setting){
		if($request->isPost()){
			$data = input('post.');
			$result = $setting->updateSetting($data);
			if($result){
				return redirect('@system/setting')->with('successTs','修改成功');
			}else{
				return redirect('@system/setting')->with('errorTs','修改失败');
			}
		}
	}
}