<?php
namespace app\system\controller;
use app\common\model\WxWelcome as WxWelcomeModel;
use app\common\utils\WxApi;
use think\Request;
use app\common\utils\HttpRequest;

/**
 * 微信关注类
 * @author kongweihao
 *
 */
class WxWelcome extends Auth{
	/**
	 * 关注回复设置
	 */
	public function welcomeSetting(Request $request, WxWelcomeModel $welcome){
		//回复内容
		$welcomeInfo = $welcome->getWelcomeInfo();
		$this->assign('welcomeInfo',$welcomeInfo);
		//素材内容
		if($welcomeInfo->media_id){
			if($welcomeInfo->res_type == 'news'){//图文
				$url = 'https://api.weixin.qq.com/cgi-bin/material/get_material?access_token='.WxApi::get_access_token();
				$mediaData = ['media_id'=>$welcomeInfo->media_id];
				$mediaInfo = HttpRequest::curl_post($url, json_encode($mediaData));
				$this->assign('mediaInfo',$mediaInfo);
			}
		}
		
		if($request->isPost()){
			$data = input('post.');
			$result = $welcome->updateWelcomeInfo($data);
			return redirect('@system/welcomeSetting')->with('successTs','修改成功');
		}
		
		return $this->fetch();
	}
}