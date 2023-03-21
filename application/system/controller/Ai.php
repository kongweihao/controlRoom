<?php
namespace app\system\controller;
use think\Request;
use app\common\utils\HttpRequest;
use app\common\model\Role;
use app\common\model\AdminRole;
use think\Controller;
use app\common\utils\SystemConfig;
use think\Db;
use Psr\Log\Logger;


/**
 * 值班表类
 * @author kongweihao
 *
 */
// class Ai extends Controller{ // 调试用的，没有做权限限制
class Ai extends Auth{ // 上线用的
	/**
	 * 公共测试接口
	 */
	public function test(Request $request){
		return json(input());
	}
	/**
	 * 监控岗位列表
	 */
	public function faceDetect(Request $request){
		$data = input();
		if($data['detect'] == 1){
			// 开始人脸检测
			unset($data['detect']);
			// 获取AccessToken
			$getAccessToken = 'https://aip.baidubce.com/rest/2.0/face/v3/detect';
			//写入参数
			$rs = HttpRequest::curl_post($getAccessToken,$data);
			return json($rs);
		}else{
			// 获取AccessToken
			$getAccessToken = 'http://aip.baidubce.com/oauth/2.0/token';
			//写入参数
			$param['grant_type'] = 'client_credentials';
			$param['client_id'] = 'EmG9tUExovCtSfY8gzeEGnYr';
			$param['client_secret'] = '31cCNKFiQF5NY41KBnWknMZQaXyoUWsf';
			$rs = HttpRequest::curl_post($getAccessToken,$param);
			$this->assign('AccessToken', $rs['access_token']);
			return $this->fetch();
		}
	}
}