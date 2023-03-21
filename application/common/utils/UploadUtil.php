<?php
/**
 * Created by PhpStorm.
 * User: kwd
 * Date: 2018/2/7 0007
 * Time: 13:34
 */

namespace app\common\utils;


use think\File;
use think\Image;

class UploadUtil {

    /***************************所有图片均以相对地址操作***************************************/


    /**
     * 上传单张图片不含host
     * @param File $img
     * @param $savePath
     * @param $is_md5   文件名是否md5加密，true-MD5加密
     * @return bool|string
     */
    public static function uploadImg(File $img, $savePath, $is_md5=false,$imgType){
        // $imgType其实可以用image方法获取，没必要传，后续用到这个插件的时候记得处理
        $savePath = '/public/uploads/imgs/'.$savePath.'/';

        if (!file_exists(substr($savePath,1,strlen($savePath)-1))){
            mkdir(substr($savePath,1,strlen($savePath)-1),0777,true);
        }

//        if (!is_readable($savePath)){
//            is_file($savePath) or mkdir($savePath,0700);
//        }
        
        //设置文件上传限制，允许上传的最大文件大小：10M，类型为jpg、png或gif
        $img->validate(['size' => 10485760, 'ext' => 'jpg,png,gif']);
        $imgComp = Image::open($img);
        $fileName =time().'.'.$imgType;
        if ($is_md5==true){
            $fileName = md5($fileName).'.'.$imgType;
        }
        $imgUploadResult = $imgComp->save(ROOT_PATH.$savePath.DS.$fileName,$imgType);
        // thumb(720, 720)虽然可以设置图片缩略图，但是png图的透明背景会变成白色背景
        // $imgUploadResult = $imgComp->thumb(720, 720)->save(ROOT_PATH.$savePath.DS.$fileName,$imgType);
        if ($imgUploadResult) {
            return $savePath . $fileName;
        }else{
            return false;
        }
    }

    /**
     * 上传多张图片
     * @param $imgs
     * @param $savePath
     * @return array
     */
    public static function uploadImgs($imgs,$savePath,$is_md5){
        $i = 1;
        $savePath = '/public/uploads/imgs/'.$savePath.'/';
        $imgSet = array();
        foreach ($imgs as $img) {
            //设置文件上传限制，允许上传的最大文件大小：10M，类型为jpg或png
            $img->validate(['size' => 10485760, 'ext' => 'jpg,png']);
            $imgComp = Image::open($img);
            $fileName = time().'_'.$i .'.png';
            if ($is_md5==true){
                $fileName = md5($fileName).'.png';
            }
            $imgUploadResult = $imgComp->thumb(360, 360)
                ->save(ROOT_PATH.$savePath.DS.$fileName,'png');
            if ($imgUploadResult) {
                $imgSet[] =  $savePath . $fileName;
            }
            $i++;
        }
        return $imgSet;
    }

    /**
     * 删除单张图片
     * @param $img
     */
    public static function deleteImg($img) {
        unlink(ROOT_PATH . DS . $img);
    }


    /**
     * 删除图片
     * @param array $imgs
     * @return bool
     */
    public static function deleteImgs(array $imgs){
        for ($i = 0; $i <= count($imgs); $i++) {
            unlink(ROOT_PATH . DS . $imgs[$i]);
        }
        return true;
    }

}