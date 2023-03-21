<?php
/**
 * Created by PhpStorm.
 * User: kwd
 * Date: 2018/2/7 0007
 * Time: 14:54
 */
namespace app\common\utils;


use think\Cache;
use think\Config;
use think\Session;

class WCmpApi {

    /**
     * 获取accessToken
     * @return \think\response\Json
     */
    public function getAccessToken(){
        $urlForAccessToken =
            'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.Config::get('APP_ID').'&secret='.Config::get('APP_SECRET');
        $response = HttpRequest::curl_get($urlForAccessToken);
        $access_token = $response['access_token'];
        if ($access_token!=null){
            return json([
                'access_token' => $access_token,
                'errorCode' => 0,
            ]);
        }else{
            return json([
                'errmsg' => $response['errmsg'],
                'errorCode' => 1010,
            ]);
        }
    }

    /**
     * 获取用户openid
     * @param $code
     * @return \think\response\Json
     */
    public function getOpenid($code){
        $urlForOpenid =
            'https://api.weixin.qq.com/sns/jscode2session?appid='.Config::get('APP_ID').'&secret='.Config::get('APP_SECRET').'&grant_type=authorization_code&js_code=';
        $response = HttpRequest::curl_get($urlForOpenid.$code);
        $openid = $response['openid'];
        if ($openid!=null){
            return json(['openid'=>$openid,'errorCode'=>0]);
        }else{
            return json(['errmsg'=>$response['errmsg'],'errorCode'=>1010]);
        }
    }

    /**
     * 获取SessionKey
     * @param $code
     * @return \think\response\Json
     */
    public function getSessionKey($code){
        $urlForOpenid =
            'https://api.weixin.qq.com/sns/jscode2session?appid='.Config::get('APP_ID').'&secret='.Config::get('APP_SECRET').'&grant_type=authorization_code&js_code=';
        $response = HttpRequest::curl_get($urlForOpenid.$code);
        $session_key = $response['session_key'];
        if ($session_key!=null){
            return json(['session_key'=>$session_key,'errorCode'=>0]);
        }else{
            return json(['errmsg'=>$response['errmsg'],'errorCode'=>1010]);
        }
    }




}