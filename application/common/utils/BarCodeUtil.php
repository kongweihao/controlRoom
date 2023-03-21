<?php
namespace app\common\utils;


use think\Loader;

class BarCodeUtil{


    public static function barcode_create($number, $savePath, $fileName){
        $content = $number;
        $savePath = 'public'.DS.'uploads'.DS.$savePath.DS.$fileName;
        // 引用barcode文件夹对应的类
        Loader::import('BCode.BCGFontFile',EXTEND_PATH);
        //Loader::import('BCode.BCGColor',EXTEND_PATH);
        Loader::import('BCode.BCGDrawing',EXTEND_PATH);
        // 条形码的编码格式
        Loader::import('BCode.BCGcode39',EXTEND_PATH,'.barcode.php');
        // $code = '';
        // 加载字体大小
        //$font = new BCGFontFile('./class/font/Arial.ttf', 18);
        //颜色条形码
        $color_black = new \BCGColor(0, 0, 0);
        $color_white = new \BCGColor(255, 255, 255);
        $drawException = null;
        try {
            $code = new \BCGcode39();
            $code->setScale(2);
            $code->setThickness(30); // 条形码的厚度
            $code->setForegroundColor($color_black); // 条形码颜色
            $code->setBackgroundColor($color_white); // 空白间隙颜色
            // $code->setFont($font); //
            $code->parse($content); // 条形码需要的数据内容
        } catch(\Exception $exception) {
            $drawException = $exception;
        }
        //根据以上条件绘制条形码
        $drawing = new \BCGDrawing($savePath, $color_white);
        if($drawException) {
            $drawing->drawException($drawException);
        }else{
            $drawing->setBarcode($code);
            $drawing->draw();
        }
        // 生成PNG格式的图片
        header('Content-Type: image/png');
        header('Content-Disposition:attachment',true); //自动下载
        $drawing->finish(\BCGDrawing::IMG_FORMAT_PNG);
        return $savePath;
    }
}