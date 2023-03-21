<?php
namespace app\test\controller;

use app\common\model\KnowledgePoint;
use app\common\utils\BarCodeUtil;
use app\common\utils\FileUtil;
use app\common\utils\HttpRequest;
use app\common\utils\UploadUtil;
use app\common\utils\Util;
use app\common\utils\WxApi;
use think\Cache;
use think\Db;

class Index {

    public function getExercises(){
        $result = array();
        $result['logo'] = "../../image/dell.png";
        $result['summary'] = "戴尔(Dell），是一家总部位于美国德克 萨斯州朗德罗客的世界五百强企业，由迈 克尔戴尔于1984年创立。戴尔以生产、 设计、销售家用以及办公室电脑而闻名";
        $result['totalAnswer'] = ["去", "我", "额", "人", "志", "有", "梁", "戴", "尔", "啦", "噜", "噜", "噜", "点", "噜", "噜", "噜", "脑", "噜", "是", "噜"];
        $result['name'] = "戴尔";

        $all = array();
        $all[] = $result;

        $result['logo'] = "../../image/yuan.png";
        $result['name'] = "戴尔电脑";

        $all[] = $result;

        $result['logo'] = "../../image/jindou.png";
        $result['name'] = "戴尔梁志";
        $all[] = $result;

//        $result = '{logo: "../../image/dell.png",summary: "戴尔(Dell），是一家总部位于美国德克 萨斯州朗德罗客的世界五百强企业，由迈 克尔戴尔于1984年创立。戴尔以生产、 设计、销售家用以及办公室电脑而闻名",totalAnswer:
//        ["去", "我", "额", "人", "他", "有", "啦", "戴", "尔", "啦", "噜", "噜", "噜", "噜", "噜", "噜", "噜", "噜", "噜", "是", "噜"],name: "戴尔"}';
        return json($all);
    }


    public function getTheCharts(){
        $mine = array();
        $result = array();
        $result['icon'] = '../../image/num1.png';
        $result['avatar'] = '../../image/touxiang.png';
        $result['nickname'] = '我很帅';
        $result['point'] = 93;
        $result['count'] = 34;

        $mine[] = $result;

        $result['icon'] = '../../image/num2.png';
        $result['avatar'] = '../../image/touxiang.png';
        $result['nickname'] = '我很美';
        $result['point'] = 95;
        $result['count'] = 34;

        $mine[] = $result;


        $all = array();
        $all['mine'] = $mine;

        $mine[] = $result;
        $mine[] = $result;

        $result['icon'] = '../../image/num1.png';
        $result['avatar'] = '../../image/touxiang.png';
        $result['nickname'] = '我很帅';
        $result['point'] = 93;
        $result['count'] = 34;

        $mine[] = $result;
        $mine[] = $result;
        $mine[] = $result;

        $all['world'] = $mine;

        $info = array();
        $info['count'] = 199;
        $info['ranking'] = 10;
        $info['world_ranking'] = 10000;
        $all['info'] = $info;
        return json($all);
    }


    public function getQuestion(){
        $question = 'F:问题';
        $answer = 'Q:这是答案';
        $result = array();
        $all = array();
        for ($i=0;$i<10;$i++){
            $result['F'] = $question.$i;
            $result['Q'] = $answer.$i;
            $all[] = $result;
        }
        return json($all);
    }

    public function index() {
        return '<style type="text/css">*{ padding: 0; margin: 0; } div{ padding: 4px 48px;} a{color:#2E5CD5;cursor: pointer;text-decoration: none} a:hover{text-decoration:underline; } body{ background: #fff; font-family: "Century Gothic","Microsoft yahei"; color: #333;font-size:18px;} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.6em; font-size: 42px }</style><div style="padding: 24px 48px;"> <h1>:)</h1><p> ThinkPHP V5<br/><span style="font-size:30px">十年磨一剑 - 为API开发设计的高性能框架</span></p><span style="font-size:22px;">[ V5.0 版本由 <a href="http://www.qiniu.com" target="qiniu">七牛云</a> 独家赞助发布 ]</span></div><script type="text/javascript" src="http://tajs.qq.com/stats?sId=9347272" charset="UTF-8"></script><script type="text/javascript" src="http://ad.topthink.com/Public/static/client.js"></script><thinkad id="ad_bd568ce7058a1091"></thinkad>';
    }


    public function testIf(){
        if (123){
            return 1;
        }
        return 2;
    }

    public function testBCode(){
        BarCodeUtil::barcode_create(123,'bcode','123.png');
    }

    public function testHttp(){
        return HttpRequest::curl_get_data('www.baidu.com');
    }


    public function testUpload(){
        $img = request()->file('img');
        UploadUtil::uploadImgs($img,'img',false);
    }

    public function testSevenTime(){
        return json(Util::getLastWeekTimestamp(time()));
        return json(WxApi::getDayData(date('Ymd',strtotime('-1 day'))));
    }

    public function testWeekData(){
        $week_start = Util::getLastWeekTimestamp(time(),'Y-m-d');
        $week_money = array();
        foreach ($week_start as $ws){
            $money = Db::table('recharge_history')
                                ->where(['create_time'=>array('>',$ws.' 00:00:00')])
                                ->where(['create_time'=>array('<',$ws.' 23:59:59')])
                                ->sum('rh_sum');
            $week_money[] = $money?$money:0;
        }
        return json($week_money);
        return json($last_week);
    }
    public function testMonthData(){

        $s = '2018-03-12';
        return ++$s;


        $last_month_start = date('Ym',strtotime('-1 month')).'00';
        $last_month_end  = date('Ymd',strtotime('-'.date('d').'day'));
        $user_data = array();
        $date_data = array();
        $number_data = array();
        for ($i = $last_month_start;$i<=$last_month_end;$i++){
            $day_data = WxApi::getDayData($i)['list'];
            $date_data[] = $i;
            if (empty($day_data)){
                $number_data[] = 0;
            }else{
                $number_data = $day_data['visit_uv_new'];
            }
        }
        $user_data['ref_date'] = $date_data;
        $user_data['number'] = $number_data;
        Cache::set('month_data',$user_data,7200);
        return json($user_data);
        return $last_month_end  = date('Ymd',strtotime('-'.date('d').'day'));
        return $last_month = date('Ym',strtotime('-1 month')).'00';;
    }

    public function testFileWrite(){
        FileUtil::writeFile(ROOT_PATH.DS.'public/uploads/files/test2.txt','就是用来测试的1','w');
        return 1;
    }

    public function testKC(){
        $kp = new KnowledgePoint();
        return json($kp->getKnowledgeClassListForAdmin2());
    }
}
