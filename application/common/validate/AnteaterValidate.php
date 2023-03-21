<?php
namespace app\common\validate;
use think\Validate;
/**
 * Created by PhpStorm.
 * User: kwd
 * Date: 2018/5/10 0010
 * Time: 19:57
 */

class AnteaterValidate extends Validate {


    protected $rule = [
        ['ae_name','require','公司名必须'],
        ['ae_corporate','require','公司法人必须'],
        ['ae_logo','require','公司logo必须'],
        ['ae_show_img','require','公司展示图片必须'],
        ['ae_introduction','require','公司简介必须'],
        ['ae_phone','require','公司联系方式格式错误'],
        ['ae_email','require','公司email格式错误'],
        ['ae_address','require','公司地址必须'],
        ['ae_shop_name','require','食议兽应用商店必须'],
        ['ae_shop_qrcode','require','食议兽应用商店小程序码必须'],
    ];


    protected $scene = [
        'anteaterUpdate' => [
            'ae_name',
            'ae_corporate',
            'ae_logo',
            'ae_show_img',
            'ae_introduction',
            'ae_phone',
            'ae_email',
            'ae_address',
            'ae_shop_name',
            'ae_shop_qrcode'
        ],
    ];



}