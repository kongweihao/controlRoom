<?php

namespace app\system\controller;

use app\common\utils\UploadUtil;
use think\Controller;
use think\Request;

class Base extends Controller {

    //获取文件路径名
    public function getFolderName($code){
        switch ($code){
            case 1:return 'member';
            case 2:return 'emoticon';
            case 3:return 'work';
            case 4:return 'business';
            case 5:return 'head';
            default:return 'default';
        }
    }
    //上传文件
    public function uploadFile(){
        $rs = [];
        //判断上传的文件是否出错,是的话，返回错误
        try{
            $_FILES["img"]["error"];
        }catch(Exception $e){
            return json($e);

        }

        if($_FILES["img"]["error"])
        {
            // echo $_FILES["file"]["error"];  
            return 0;
            // 文件错误，网络错误
        }
        else
        {
            //没有出错
            //加限制条件
            //判断上传文件类型为png或jpg且大小不超过1024000B
            $imgType = explode("/", $_FILES["img"]["type"])[1];
            $code = input('code');
            if($imgType=="png"||$imgType=="jpeg"||$imgType=="jpg"||$imgType=="gif")
            {
                    //防止文件名重复
                    $filename ="/public/uploads/imgs/".$this->getFolderName($code).'/'.time().'.'.$imgType;
                    //转码，把utf-8转成gb2312,返回转换后的字符串， 或者在失败时返回 FALSE。
                    $filename =iconv("UTF-8","gb2312",$filename);
                    //检查文件或目录是否存在
                    if(file_exists($filename))
                    {
                        echo"该文件已存在";
                    }
                    else
                    {  
                        //保存文件,   move_uploaded_file 将上传的文件移动到新位置  
                        move_uploaded_file($_FILES["img"]["tmp_name"],ROOT_PATH.$filename);//将临时地址移动到指定地址    
                    }    
                    $rs['url'] = $filename;
                    return json($rs);
            }else
            {
                return 2;
                // echo"文件类型不对";
            }
        }


        // 下面的方法使用了Image插件，该插件处理gif图时候会失真因此弃用，不过功能还是很强大的，如果后续需要用到其功能处理静态图片的话可以重新启用
        // $code = input('code');
        // $imgType = input('type');
        // $img = request()->file('img');
        // // return json(1);
        // return json(['url'=>UploadUtil::uploadImg($img,$this->getFolderName($code),false,$imgType)]);
    }


}
