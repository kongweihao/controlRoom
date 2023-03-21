<?php
namespace app\customer\controller;
use think\Request;
use think\Db;
// use think\Controller;




            // 转成JSON格式，用于微信当中
        // echo json_encode($data);

        // $DB = new Db;
        // $data = Db::table('rota')
        // ->where('time_stamp','<=',20200531)
        // ->where('time_stamp','>=',20200501) 
        // ->where('monitorPost_type','=','一干传输')
        // ->order('time_stamp') ->select();
    

class WxUser 
{
    public function index()
    {
        $data = '我是前台User控制器中的index方法1101';
        dump($data);
        // return "我是前台User控制器中的index方法";
    
    }

    

    public function changedata(){

        $arr = Request::instance()->post();
        //传入的是rota表中的id字段
        $id = $arr['id'];    
        $userName = $arr['userName'];   //replacement   operation_guy
        $beforeName = $arr['beforeName'];   //guy_be_replaced
        $monitor_post_name = Db::table('rota')->where('id', $id)->value('monitor_post_name');
        $equiment = $arr['equiment'];
        $operation_account =  $arr['operation_account'];
        $rota_time_stamp = Db::table('rota')->where('id', $id)->value('time_stamp');

        $list = [
            'rota_time_stamp'=>$rota_time_stamp,
            'replacement'=>$userName,
            'guy_be_replaced'=>$beforeName,
            'monitor_post_name'=>$monitor_post_name,
            'equipment'=>$equiment,
            'operation_guy'=> $userName,
            'operation_account'=>$operation_account,
        ];
        
        // dump($list);
        // dump (array($monitor_post_name,$equiment);

        // 冲突检测
        $backName = Db::table('rota')->where('id',$arr['id']) ->select();
        $backName = $backName[0]['member_name'];
        $monitor_post_name2 = Db::table('rota')
        ->where('time_stamp', $rota_time_stamp)
        ->where('member_name', $beforeName)
        ->where('mp_alias', '值班长')
        ->value('monitor_post_name');
        




        // dump (array($backName,$beforeName));

        //如果冲突检测过关，修改数据库
        if($backName == $beforeName){

            //插入换班记录
            $inserData = Db::table('shift_record')->insert($list);
            //插入值班长换班记录
            if($monitor_post_name2){
                $list2 = [
                    'rota_time_stamp'=>$rota_time_stamp,
                    'replacement'=>$userName,
                    'guy_be_replaced'=>$beforeName,
                    'monitor_post_name'=>$monitor_post_name2,
                    'equipment'=>$equiment,
                    'operation_guy'=> $userName,
                    'operation_account'=>$operation_account,
                ];
                $inserData = Db::table('shift_record')->insert($list2);
            }

            if($inserData == 1){

                //根据输入的id，修改数据库
                $data = Db::table('rota')->where('id', $id)->update(['member_name' => $userName]);
                $data_2 = Db::table('rota')
                ->where('time_stamp', $rota_time_stamp)
                ->where('member_name', $beforeName)
                ->where('mp_alias', '值班长')
                ->update(['member_name' => $userName]);
                return 'ok';

            }
            else{
                //插入换班记录有问题
                return 'dbnotok';
            };
            
            
            
            
        }
        //冲突检测不过关
        else{
            return 'notok';
        };


        



        // $nArr = Db::table('rota')->where('id',$arr['id']);
        // dump( $nArr);
        #查询数据方法一，使用tp方法
        // $DB = new Db;
        // $data = $DB::table('rota') ->select();
        // dump($data);

        #方法2：使用sql语句
        // $DB = new Db;
        // $data = $DB::query('select * from rota');
        // dump($data);

    }

    //获取数据库数据，并整理成合适的数据输出组合输出给前端页面
    public function total($date){
        // dump('能进到这里total');

        $date1 = (int)substr($date,0,8);
        $date2 = (int)substr($date,8,15);
        // dump(is_int($date1));
        // <br>;
        $DB = new Db;

        #条件查询数据   ？为占位符    
        // $data = DB::query('select * from rota where time_stamp>=? and time_stamp<=?',[20200501,20200531]);
        $len = $date2 - $date1; 
        $dataList = array();

        for($i=0;$i<=$len;$i++)
        {
            $tempList = array();
            $n = 0;
            // $tempdate = (string)($date1+$i);
            $tempList['time'] = (string)($date1+$i);
            $tempList['date'] = $i+1;
            

            // 获取一干传输
            // ITMC白
            $data = Db::table('rota')
                ->where('time_stamp','=',$date1+$i) 
                // ->where('monitorPost_type','=','一干传输')
                ->where('monitor_post_name','=','ITMC白班')
                ->select();

            // dump($data);                  
            $tempList['id_'.$n]=$data[0]['id'];
            $tempList['name_'.$n]=$data[0]['member_name'];
            $tempList['post_'.$n]=$data[0]['monitorPost_type'];
            $tempList['day_'.$n]=$data[0]['is_day'];
            $tempList['night_'.$n]=$data[0]['is_night'];
            $tempList['week']=$data[0]['week'];
            // $tempList['id_?'.$n]=$data[$j]['id'];
            $n++;

            // 专网白班
            $data = Db::table('rota')
                ->where('time_stamp','=',$date1+$i) 
                // ->where('monitorPost_type','=','一干传输')
                ->where('monitor_post_name','=','专网白班')
                ->select();

            // dump($data);                  
            $tempList['id_'.$n]=$data[0]['id'];
            $tempList['name_'.$n]=$data[0]['member_name'];
            $tempList['post_'.$n]=$data[0]['monitorPost_type'];
            $tempList['day_'.$n]=$data[0]['is_day'];
            $tempList['night_'.$n]=$data[0]['is_night'];
            $tempList['week']=$data[0]['week'];
            // $tempList['id_?'.$n]=$data[$j]['id'];
            $n++;

            // ITMC夜班
            $data = Db::table('rota')
                ->where('time_stamp','=',$date1+$i) 
                // ->where('monitorPost_type','=','一干传输')
                ->where('monitor_post_name','=','ITMC夜班')
                ->select();

            // dump($data);                  
            $tempList['id_'.$n]=$data[0]['id'];
            $tempList['name_'.$n]=$data[0]['member_name'];
            $tempList['post_'.$n]=$data[0]['monitorPost_type'];
            $tempList['day_'.$n]=$data[0]['is_day'];
            $tempList['night_'.$n]=$data[0]['is_night'];
            $tempList['week']=$data[0]['week'];
            // $tempList['id_?'.$n]=$data[$j]['id'];
            $n++;

            // 专网夜班
            $data = Db::table('rota')
                ->where('time_stamp','=',$date1+$i) 
                // ->where('monitorPost_type','=','一干传输')
                ->where('monitor_post_name','=','专网夜班')
                ->select();

            // dump($data);                  
            $tempList['id_'.$n]=$data[0]['id'];
            $tempList['name_'.$n]=$data[0]['member_name'];
            $tempList['post_'.$n]=$data[0]['monitorPost_type'];
            $tempList['day_'.$n]=$data[0]['is_day'];
            $tempList['night_'.$n]=$data[0]['is_night'];
            $tempList['week']=$data[0]['week'];
            // $tempList['id_?'.$n]=$data[$j]['id'];
            $n++;


            // // 获取一干传输
            // $data = Db::table('rota')
            //     ->where('time_stamp','=',$date1+$i) 
            //     ->where('monitorPost_type','=','一干传输')
            //     ->order('time_stamp') ->select();

            // // dump($data);  

            // for($j=0;$j<count($data);$j++)
            // {
                
            //     $tempList['id_'.$n]=$data[$j]['id'];
            //     $tempList['name_'.$n]=$data[$j]['member_name'];
            //     $tempList['post_'.$n]=$data[$j]['monitorPost_type'];
            //     $tempList['day_'.$n]=$data[$j]['is_day'];
            //     $tempList['night_'.$n]=$data[$j]['is_night'];
            //     $tempList['week']=$data[$j]['week'];
            //     // $tempList['id_?'.$n]=$data[$j]['id'];
            //     $n++;

            // };

            // 获取一干投诉
            $data = Db::table('rota')
            ->where('time_stamp','=',$date1+$i) 
            ->where('monitorPost_type','=','一干投诉处理')
            ->order('time_stamp') ->select();
            for($j=0;$j<count($data);$j++)
            {

                $tempList['id_'.$n]=$data[$j]['id'];
                $tempList['name_'.$n]=$data[$j]['member_name'];
                $tempList['post_'.$n]=$data[$j]['monitorPost_type'];
                $tempList['day_'.$n]=$data[$j]['is_day'];
                $tempList['night_'.$n]=$data[$j]['is_night'];
                $tempList['week']=$data[$j]['week'];
                // $tempList['id_?'.$n]=$data[$j]['id'];
                $n++;

            };

//          获取传输支撑
            $data = Db::table('rota')
            ->where('time_stamp','=',$date1+$i) 
            ->where('monitorPost_type','=','传输支撑')
            ->order('time_stamp') ->select();
            for($j=0;$j<count($data);$j++)
            {

                $tempList['id_'.$n]=$data[$j]['id'];
                $tempList['name_'.$n]=$data[$j]['member_name'];
                $tempList['post_'.$n]=$data[$j]['monitorPost_type'];
                $tempList['day_'.$n]=$data[$j]['is_day'];
                $tempList['night_'.$n]=$data[$j]['is_night'];
                $tempList['week']=$data[$j]['week'];
                // $tempList['id_?'.$n]=$data[$j]['id'];
                $n++;

            };

            $dataList[$i]=$tempList;
        }


        return json_encode($dataList);
        // dump($dataList);

    }




    //获取数据库数据，并整理成合适的数据输出组合输出给前端页面  ----》过去版本
    public function total_fomer($date){
        // dump('能进到这里total');

        $date1 = (int)substr($date,0,8);
        $date2 = (int)substr($date,8,15);
        // dump(is_int($date1));
        // <br>;
        $DB = new Db;

        #条件查询数据   ？为占位符    
        // $data = DB::query('select * from rota where time_stamp>=? and time_stamp<=?',[20200501,20200531]);
        
        $data = Db::table('rota')
                ->where('time_stamp','<=',$date2)
                ->where('time_stamp','>=',$date1) 
                ->where('monitorPost_type','IN',['一干传输','一干投诉处理','传输支撑','值班长'])
                ->order('time_stamp') ->select();

        $len = $date2 - $date1; 
        $dataList = array();

        for($i=0;$i<=$len;$i++)
        {
            $tempList = array();
            $n = 0;
            $tempdate = (string)($date1+$i);


            $tempList['time'] = $tempdate;
            $tempList['date'] = $i+1;
            for($j=0;$j<count($data);$j++)
            {
                if($data[$j]['time_stamp']==$tempdate){
                    $tempList['id_'.$n]=$data[$j]['id'];
                    $tempList['name_'.$n]=$data[$j]['member_name'];
                    $tempList['post_'.$n]=$data[$j]['monitorPost_type'];
                    $tempList['day_'.$n]=$data[$j]['is_day'];
                    $tempList['night_'.$n]=$data[$j]['is_night'];
                    $tempList['week']=$data[$j]['week'];
                    // $tempList['id_?'.$n]=$data[$j]['id'];
                    $n++;
                };
            };

            $dataList[$i]=$tempList;
        };

        // return($dataList);
        
        // 方便网页调试查看
        // dump(count($data));
        
        // 转成JSON格式，用于微信当中
        return json_encode($dataList);

        // 增加数据
        // $data = DB:: execute('insert into rota value('','','')); #按表格字段数字填入
        // $data = DB:: execute('insert into rota value('',?,?),['','']); #按表格字段数字填入,这里用了占位符
        // $data = DB:: execute('insert into rota value('',:name,:pass),['name'=>','pass'=>'']); #按表格字段数字填入,这里用了占位符
        // dump($data);
        // 增加成功后，返回值是影响行数（即被修改的行数）

        //删除数据
        // $data DB::execute('delete from rota where id = 10');
        // $data DB::execute('delete from rota where id = ?',[15]);
        // $data DB::execute('delete from rota where id > :id',['id'=>15]);
        // dump($data);
        // 删除成功后，返回值是影响行数（即被修改的行数）

        // 修改
        // $data DB::execute('update  rota set age='20' where id = 10');
        // dump($data);
        // 修改成功后，返回值是影响行数（即被修改的行数）
    }

    // public function wxlogin(Request $request){

    //     if($request->isPost()){

    //     $data = input("post.");
    //     $pass = MD5($data['password']);
    //     $getName = Db::table('admin')
    //     ->where('admin_account','=',$data['userName'])
    //     ->where('admin_password','=',$pass) 
    //     ->value('admin_fullname');

    //     if(!$getName)
    //     {
    //         return 404;
    //     }
    //     else{
    //         return $getName;
    //     }
    //     };
    // }

    public function wxlogin(Request $request){
        // dump('能进到这里wxlogin');
        // $data = '我是前台User控制器中的wxlogin方法1101';
        // dump($data);

        if($request->isPost()){

        $data = input("post.");
        $pass = MD5($data['password']);
        
        $getId = Db::table('admin')
        ->where('admin_account','=',$data['userName'])
        ->where('admin_password','=',$pass) 
        ->value('id');

        if(!$getId)
        {
            return 404;
        }
        else{
            
        $getMemberId = Db::table('admin_member')
        ->where('admin_id','=',$getId)
        ->value('member_id');

        $getName = Db::table('member')
        ->where('id','=',$getMemberId)
        ->value('name');

        // $getName = substr($getName , 0 , 6);
        return ($getName);
        // return ($getId);

        }
        
        };
    }

    
}