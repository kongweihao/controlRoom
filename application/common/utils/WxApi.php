<?php
namespace app\common\utils;
use think\Cache;

/**
 * 微信常用api
 * @author kongweihao
 * 
 */
class WxApi{
	/**
	 * 获取access_token
	 */
	public static function get_access_token(){
		//优先从缓存中读取access_token
		if(Cache::has('access_token')){
			return Cache::get('access_token');
		}
		
		$url="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".config("APP_ID")."&secret=".config("APP_SECRET");
		$ch=curl_init($url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		$output = curl_exec($ch);
		curl_close($ch);
		
		if($output){
			$result = json_decode($output,true);
			Cache::set('access_token', $result['access_token'],7000);//缓存access_token
			return $result['access_token'];
		}
	}


    /**
     * 获取access_token和openid（网页授权时使用）
     * @param $code
     * @return \app\common\utils\error|array
     */
    public static function getAccessTokenAndOpenid($code){
        $APPID = config("APP_ID");
        $APPSECRET = config("APP_SECRET");
        $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$APPID.'&secret='.$APPSECRET.'&code='.$code.'&grant_type=authorization_code';
        $response = HttpRequest::curl_get($url);
        return $response;
    }

    /**
     * 申请二维码
     * @param $action_info
     * @param $savePath 包含文件名
     * @return bool
     */
    public static function getQRcodeTemp($qrCodeType,$action_info,$savePath){
        //获取ticket
        $urlForTicket = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token='.self::get_access_token();
        $urlForQrCode = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=';  //ticket要urlEncode
        //写入参数
        if ($qrCodeType==0){
            //0-临时二维码
            $param['expire_seconds'] = 2592000;
            $param['action_name'] = "QR_SCENE"; //设置为临时的字符串参数值，整型为：QR_SCENE
        }elseif ($qrCodeType==1){
            //1-永久二维码
            $param['action_name'] = 'QR_LIMIT_STR_SCENE';   //设置为永久的字符串参数值，整型为：QR_LIMIT_SCENE
        }
        //场景id
        $scene = ['scene_str' => md5(time().mt_rand(1,1000000))];
        $param['action_info']['scene'] = $scene;
        $param['action_info']['param'] = $action_info;
        $rs = HttpRequest::curl_post($urlForTicket,json_encode($param));
        dump($rs);
        //保存二维码
        $ticket = urlencode($rs['ticket']);
        if ($ticket!=null){
            file_put_contents($savePath,HttpRequest::curl_get_data($urlForQrCode.$ticket));
            return true;
        }else{
            return false;
        }

    }


    /**
	 * 回复Text格式xml
	 * @param string $from 要发送的用户名
	 * @param string $to 来自用户
	 * @param string $contentStr 回复的文本内容 
	 */
	public static function responseText($from, $to, $contentStr){
		$textTpl = "<xml>
					<ToUserName><![CDATA[%s]]></ToUserName>
					<FromUserName><![CDATA[%s]]></FromUserName>
					<CreateTime>%s</CreateTime>
					<MsgType><![CDATA[text]]></MsgType>
					<Content><![CDATA[%s]]></Content>
					<FuncFlag>0</FuncFlag>
					</xml>";
		$time = time();
		$resultStr = sprintf($textTpl, $from, $to, $time, $contentStr);
		return $resultStr;
	}






	/*********************/
	//   数据分析接口
	/*********************/

    /**
     * 获取周数据
     * @param $date_time    格式为：'Ymd'
     * @return error|array
     */
	public static function getDayData($date_time){
	    //访问日趋势接口
	    $url = 'https://api.weixin.qq.com/datacube/getweanalysisappiddailyvisittrend?access_token='.self::get_access_token();
        $param  = [
            'begin_date'=>$date_time,
            'end_date'=>$date_time,
        ];
	    return HttpRequest::curl_post($url,json_encode($param));
    }

    /**
     * 获取周数据
     * @param $begin_date   格式为：'Ymd'
     * @param $end_date 格式为：'Ymd'
     * @return error|array
     */
    public static function getWeekData($begin_date,$end_date){
        //访问周趋势接口
        $url = 'https://api.weixin.qq.com/datacube/getweanalysisappidweeklyvisittrend?access_token='.self::get_access_token();
        $param  = [
            'begin_date'=>$begin_date,
            'end_date'=>$end_date,
        ];
        return HttpRequest::curl_post($url,json_encode($param));
    }










	
}