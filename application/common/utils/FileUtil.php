<?php
/**
 * Created by PhpStorm.
 * User: kwd
 * Date: 2018/3/6 0006
 * Time: 9:12
 */

namespace app\common\utils;


use think\File;

class FileUtil {

    /**
     * 获取文件类型 参数格式为：$_FILES['file']
     * @param $file
     * @return bool|null|string
     */
    public static function getFileType($file){
        $type = $file['type'];
        $result = substr(strchr($type,'/'),1);
        if ($result){
            return $result;
        }else{
            return null;
        }
    }

    /**
     * 写入一个文件
     * @param $fileName
     * @param $content
     * @param string $mode
     */
    public static function writeFile($fileName,$content,$mode='w'){
        $file = fopen($fileName,$mode);
        fwrite($file,$content);
        fclose($file);
        return;
    }


    /**
     * 传入文件url数组，合并为压缩包
     * @param String $zipPath  包括文件名(如：'/public/uploads/zipFile/test.zip')
     * @param $fileList
     * @return bool
     */
    public static function zipFile($zipPath,$fileList){
        $zipName = ROOT_PATH.$zipPath;
        $zip = new \ZipArchive();
        if ($zip->open($zipName,\ZipArchive::OVERWRITE|\ZipArchive::CREATE==true)){
            if (is_array($fileList)){
                foreach ($fileList as $file){
                    if (file_exists($file)){
                        $zip->addFile($file,basename($file));
                    }
                }
            }else{
                $zip->addFile($fileList,basename($fileList));
            }
            $zip->close();
        }
        if (!file_exists($zipName)){
            return false;
        }
        return $zipPath;
    }

}