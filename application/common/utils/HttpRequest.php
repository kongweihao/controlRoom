<?php
namespace app\common\utils;

/**
 * http请求类
 * @author kongweihao
 *
 */
class HttpRequest{
    /**
     * curl post请求
     * @param string $url 请求url
     * @param array $data 请求数据  （微信接口需将参数数组转化为字符串：json_encode()）
     * @return array|error 成功返回微信服务器响应数组，失败返回错误信息
     */
    public static function curl_post($url, $data){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        $response = curl_exec($curl);
        $result = json_decode($response,true);
        $error = curl_error($curl);
        return $error ? $error : $result;
    }

    /**
     * curl get请求
     * @param string $url 请求url
     * @return array|error 成功返回微信服务器响应数组，失败返回错误信息
     */
    public static function curl_get($url){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, 0);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        $response = curl_exec($curl);
        $result = json_decode($response,true);
        $error = curl_error($curl);
        return $error ? $error : $result;
    }

    /**
     * get数据不处理直接返回
     * @param $url
     * @return mixed|string
     */
    public static function curl_get_data($url){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, 0);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        $response = curl_exec($curl);
        $error = curl_error($curl);
        return $error ? $error : $response;
    }

    /**
     * 下载远程文件
     * @param $url 远程地址
     * @param $savePath 保存路径（包括文件名）
     * @return 成功返回路径，失败返回false
     */
    public static function put_file($url, $savePath) {
        set_time_limit(0);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $file = curl_exec($curl);
        curl_close($curl);

        $write = @fopen($savePath,"w");
        if($write == false){
            return false;
        }
        if(fwrite($write,$file) == false){
            return false;
        }
        if(fclose($write) == false){
            return false;
        }

        return $savePath;
    }
}