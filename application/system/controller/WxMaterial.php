<?php
namespace app\system\controller;
use think\Request;
use app\common\utils\WxApi;
use app\common\utils\HttpRequest;

/**
 * 素材管理类
 * @author kongweihao
 *
 */
class WxMaterial extends Auth{
	/**
	 * 获取永久图文素材列表
	 */
	public function getNewsList(Request $request){
		if($request->isAjax()){		
			$page = input('get.page');
			$url = 'https://api.weixin.qq.com/cgi-bin/material/batchget_material?access_token='.WxApi::get_access_token();
			$data = [
					'type' => 'news',
					'offset' => ($page - 1) * 20,
					'count' => 20
			];
			$newsList = HttpRequest::curl_post($url, json_encode($data));
			return json(['isok'=>true,'rs'=>$newsList]);
		}
	}
	
	/**
	 * 获取永久素材图片列表
	 */
	public function getImageList(Request $request){
		if($request->isAjax()){	
			$page = input('get.page');
			$url = 'https://api.weixin.qq.com/cgi-bin/material/batchget_material?access_token='.WxApi::get_access_token();
			$data = [
					'type' => 'image',
					'offset' => ($page - 1) * 20,
					'count' => 20
			];
			$newsList = HttpRequest::curl_post($url, json_encode($data));
			return json(['isok'=>true,'rs'=>$newsList]);
		}
	}
	
	/**
	 * 输出图片文件流
	 * @return string
	 */
	public function get_contents(){
		$imageUrl = input('get.imageUrl');
		return file_get_contents($imageUrl);
	}
}