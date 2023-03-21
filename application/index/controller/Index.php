<?php
namespace app\index\controller;
use app\common\utils\HttpRequest;
use think\Controller;
use think\Session;
use app\common\model\User;
use app\common\utils\WxApi;
use think\Request;
use app\common\model\WxMenu;
use app\common\model\WxWelcome;
define('TOKEN', 'weixin');

/**
 * 首页
 * @author kongweihao
 *
 */
class Index extends Controller{
	/**
     * 微信服务器推送xml的url
     */
	public function wx_index(){
    	if(!isset($_GET["echostr"])){
    		$this->responseMsg();//回复微信服务器
    	}else{
    		$this->valid();//验证微信服务器
    	}
    }
    
    /**
     * 接收并回复微信服务器
     */
    public function responseMsg(){
    	//获取微信服务器post过来的数据
    	$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
    	libxml_disable_entity_loader(true);
    	$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);//转化为对象
    	$wxData = json_decode(json_encode($postObj),true);//转化为数组
		    	
    	//关注事件处理
    	if($postObj->MsgType=="event" && $postObj->Event=="subscribe"){
    		$welcome = new WxWelcome();
    		$welcomeInfo = $welcome->getWelcomeInfo();
    		if($welcomeInfo->status == 1){
    			if($welcomeInfo->res_type == 'text'){//回复文本
    				$resMsg = WxApi::responseText($postObj->FromUserName, $postObj->ToUserName, $welcomeInfo->content);
    				echo $resMsg;
    			}else{//回复图文或图片
    				$this->responseNewsOrImage($wxData,$welcomeInfo);
    			}
    		}
    	}
    	
    	//点击自定义菜单事件
    	if($postObj->MsgType=="event" && $postObj->Event=="CLICK"){
    		$this->responseClickEvent($wxData);//回复点击事件
    	}
    	
    }
    
    /**
     * 回复公众号关注（图文或图片）
     * @desc 使用客服接口回复
     * @param array $wxData 微信传过来的信息
     * @param array $welcomeInfo 回复的信息
     */
    private function responseNewsOrImage($wxData,$welcomeInfo){
    	if($welcomeInfo->res_type == 'news'){//回复图文
    		$resData = [
    				'touser' => $wxData['FromUserName'],
    				'msgtype' => 'mpnews',
    				'mpnews' => [
    						'media_id' => $welcomeInfo->media_id
    				]
    		];
    	}else if($welcomeInfo->res_type == 'image'){//回复图片
    		$resData = [
    				'touser' => $wxData['FromUserName'],
    				'msgtype' => 'image',
    				'image' => [
    						'media_id' => $welcomeInfo->media_id
    				]
    		];
    	}
    	$url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.WxApi::get_access_token();
    	HttpRequest::curl_post($url, json_encode($resData));
    }
    
    /**
     * 回复点击事件
     * @desc 使用客服接口回复
     * @param array $wxData 微信传过来的数据
     */
    private function responseClickEvent($wxData){
    	$menu = new WxMenu();
    	$wxMenuInfo = $menu->getWxMenuByKey($wxData['EventKey']);
    	if($wxMenuInfo){
    		if($wxMenuInfo->res_type == 'news'){//回复图文
    			$resData = [
    					'touser' => $wxData['FromUserName'],
    					'msgtype' => 'mpnews',
    					'mpnews' => [
    							'media_id' => $wxMenuInfo->media_id
    					]
    			];
    		}else if($wxMenuInfo->res_type == 'image'){//回复图片
    			$resData = [
    					'touser' => $wxData['FromUserName'],
    					'msgtype' => 'image',
    					'image' => [
    							'media_id' => $wxMenuInfo->media_id
    					]
    			];
    		}
    		$url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.WxApi::get_access_token();
    		HttpRequest::curl_post($url, json_encode($resData));
    	}else{
    		$resMsg = WxApi::responseText($wxData['FromUserName'], $wxData['ToUserName'], '没有找到回复内容');
    		echo $resMsg;
    	}
    }
    
    
    /*****************************以下方法为公众号入口*************************************/
    /**
     * 检查用户状态
     */
    public function dingshui($toPage=null){
    	if(!Session::has('userOpenId') || !Session::has('userId')){
    		//1、静默授权获取code
    		$redirect_uri = urlencode(config('DOMAIN').'/getUserOpenId.html?toPage='.$toPage);
    		$url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid='.config("APP_ID").'&redirect_uri='.$redirect_uri.'&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect';
    		$this->redirect($url);
    	}else{
    		$this->redirect('@'.$toPage);
    	}
    }
    
    /**
     * 2、通过code换取网页授权openid
     * 获取用户openid
     * 判断用户是否注册，未注册则直接跳到注册页面
     */
    public function getUserOpenId(){
    	$code = input('get.code');
    	$url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.config("APP_ID").'&secret='.config("APP_SECRET").'&code='.$code.'&grant_type=authorization_code';
    	$result = HttpRequest::curl_get($url);
    	$openid = $result['openid'];
    	Session::set('userOpenId',$openid);//用户openid存session

    	$this->ckUser(input('get.toPage'));
    }
    
    /**
     * 检查用户
     * @param $toPage 重定向页面
     */
    protected function ckUser($toPage){
    	//用户信息
    	$user = new User();
    	$ckUser = $user->getUserByOpenId(Session::get('userOpenId'));
    	 
    	if(!$ckUser){//未注册
    		$this->redirect('@addUser');
    	}else{//已注
    		if(empty($ckUser['region_one_id']) || empty($ckUser['region_two_id']) || empty($ckUser['region_three_id']) || empty($ckUser['address']) || empty($ckUser['phone'])){//用户信息不全，则需要补全
    			$this->redirect('@editUser');
    		}else{
    			Session::set('userId',$ckUser['id']);//设置用户id
    			$this->redirect('@'.$toPage);
    		}
    	}
    }
    
    /************************************************************************/
    /**
     * 微信自定义菜单
     */
    public function definedMenu(Request $request){
    	$url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".WxApi::get_access_token();
    	
    	$menuArr = array(
    			'button' => array(
    					array(
    							'name' => urlencode('立即订水'),
    							'type' => 'view',
    							'url' => $request->domain()."/dingshui.html?toPage=addOrder",
    					),
    					array(
    							'name' => urlencode('个人中心'),
    							'type' => 'view',
    							'url' => $request->domain()."/dingshui.html?toPage=usercenter",
    					)
    			),
    	);
    	$postRs = HttpRequest::curl_post($url, urldecode(json_encode($menuArr)));
    	var_dump($postRs);
    }
	
    /*******************************************************************************/
    /**
     * 验证微信服务器
     */
    private function valid(){
    	$echoStr = $_GET["echostr"];
    
    	//valid signature , option
    	if($this->checkSignature()){
    		echo $echoStr;
    		exit;
    	}
    }
    private function checkSignature(){
    	// you must define TOKEN by yourself
    	if (!defined("TOKEN")) {
    		throw new Exception('TOKEN is not defined!');
    	}
    
    	$signature = $_GET["signature"];
    	$timestamp = $_GET["timestamp"];
    	$nonce = $_GET["nonce"];
    
    	$token = TOKEN;
    	$tmpArr = array($token, $timestamp, $nonce);
    	// use SORT_STRING rule
    	sort($tmpArr, SORT_STRING);
    	$tmpStr = implode( $tmpArr );
    	$tmpStr = sha1( $tmpStr );
    
    	if( $tmpStr == $signature ){
    		return true;
    	}else{
    		return false;
    	}
    }
}