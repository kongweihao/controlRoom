<?php
namespace app\common\utils;

/**
 * 工具类
 * @author kongweihao
 *
 */
class Util{


    /**
     * 获取设定时间的上周日期（自然周起止时间：星期一~星期日）
     * @param string $time_format
     * @param $label    1-只获取七天数据，其他-获取其余辅助数据
     * @return array
     */
    public static function getLastWeekTimestamp($date_base,$time_format='Ymd',$label=1,$suffix=''){
        $date=date($time_format,$date_base);  //当前日期
        $first=1; //$first =1 表示每周星期一为开始日期 0表示每周日为开始日期
        $w=date('w',strtotime($date));  //获取当前周的第几天 周日是 0 周一到周六是 1 - 6
        $now_start=date($time_format,strtotime("$date -".($w ? $w - $first : 6).' days')); //获取本周开始日期，如果$w是0，则表示周日，减去 6 天
        $now_end=date($time_format,strtotime("$now_start +6 days"));  //本周结束日期
        $last_start=date($time_format,strtotime("$now_start - 7 days"));  //上周开始日期（上个星期一）
        $Tuesday = date($time_format,strtotime("$now_start - 6 days"));    //上个星期二
        $Wednesday = date($time_format,strtotime("$now_start - 5 days"));    //上个星期三
        $Thursday = date($time_format,strtotime("$now_start - 4 days"));    //上个星期四
        $Friday = date($time_format,strtotime("$now_start - 3 days"));    //上个星期五
        $Saturday = date($time_format,strtotime("$now_start - 2 days"));    //上个星期六
        $last_end=date($time_format,strtotime("$now_start - 1 days"));  //上周结束日期

        $week_data = array();
//        $week_data = [
//            'Monday' => $last_start,
//            'Tuesday' => $Tuesday,
//            'Wednesday' => $Wednesday,
//            'Thursday' => $Thursday,
//            'Friday' => $Friday,
//            'Saturday' => $Saturday,
//            'Sunday' => $last_end,
//        ];
        $week_data[] = $last_start.$suffix;
        $week_data[] = $Tuesday.$suffix;
        $week_data[] = $Wednesday.$suffix;
        $week_data[] = $Thursday.$suffix;
        $week_data[] = $Friday.$suffix;
        $week_data[] = $Saturday.$suffix;
        $week_data[] = $last_end.$suffix;

        if ($label==1){
            return $week_data;
        }
        $week_data['last_week_start'] = $last_start.$suffix;
        $week_data['last_week_end'] = $last_end.$suffix;
        $week_data['now_week_start'] = $now_start.$suffix;
        $week_data['now_end_start'] = $now_end.$suffix;

        return $week_data;
    }

    /**
     * 获取设置时间上周的开始以及结束日期（自然周起止时间：星期一~星期日）
     * @param $date_base
     * @param string $time_format
     * @return array
     */
    public function getLastWeekDate($date_base,$time_format='Ymd'){
        $date=date($time_format,$date_base);  //当前日期
        $first=1; //$first =1 表示每周星期一为开始日期 0表示每周日为开始日期
        $w=date('w',strtotime($date));  //获取当前周的第几天 周日是 0 周一到周六是 1 - 6
        $now_start=date($time_format,strtotime("$date -".($w ? $w - $first : 6).' days')); //获取本周开始日期，如果$w是0，则表示周日，减去 6 天
//        $now_end=date($time_format,strtotime("$now_start +6 days"));  //本周结束日期
        $last_start=date($time_format,strtotime("$now_start - 7 days"));  //上周开始日期（上个星期一）
        $last_end=date($time_format,strtotime("$now_start - 1 days"));  //上周结束日期
        return array(['last_start'=>$last_start,'last_end'=>$last_end]);
    }



    /**
     * 获取客户端ip
     * @return mixed
     */
    public static function getClientIP(){
        $user_IP = ($_SERVER["HTTP_VIA"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : $_SERVER["REMOTE_ADDR"];
        $user_IP = ($user_IP) ? $user_IP : $_SERVER["REMOTE_ADDR"];
        return $user_IP;
    }
    /**
     * 获取随机字符串
     * @return string
     */
    public static function guid(){
        if (function_exists('com_create_guid')){
            return com_create_guid();
        }else{
            mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
            $charid = strtoupper(md5(uniqid(rand(), true)));
            $hyphen = chr(45);// "-"
            $uuid = chr(123)// "{"
                .substr($charid, 0, 8).$hyphen
                .substr($charid, 8, 4).$hyphen
                .substr($charid,12, 4).$hyphen
                .substr($charid,16, 4).$hyphen
                .substr($charid,20,12)
                .chr(125);// "}"
            return $uuid;
        }
    }
	/**
	 * 生成随机字符串
	 * @param integer $length 生成字符串位数
	 * @return string
	 */
	public static function getRandStr($length){
		$char = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
		$str = '';
		for($i=0; $i < $length; $i++){
			$str = $str.$char[mt_rand(0,strlen($char)-1)];
		}
		return $str;
	}
	
	/**
	 * 生成随机数字
	 * @param integer $length 生成数字的位数
	 * @return string
	 */
	public static function getRandNum($length){
		$str = '';
		for($i=0; $i < $length; $i++){
			$str = $str.mt_rand(0, 9);
		}
		return $str;
	}
	
	/**
	 * 判断是否是微信内置浏览器
	 * @return boolean
	 */
	public static function isWeixin(){
		if (strpos($_SERVER['HTTP_USER_AGENT'],'MicroMessenger') !== false ) {
			return true;
		}
		return false;
	}
	
	/**
	 * 过滤emoji表情
	 * @param string $str 待过滤字符串
	 * @return string
	 */
	public static function filterEmoji($str){
		$str = preg_replace_callback('/./u',function(array $match){
			return strlen($match[0]) >= 4 ? '' : $match[0];
		},$str);
		
		return $str;
	}
}