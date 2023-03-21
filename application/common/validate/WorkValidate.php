<?php
/**
 * Created by PhpStorm.
 * User: kwd
 * Date: 2018/5/12 0012
 * Time: 10:30
 */

namespace app\common\validate;


use think\Validate;

class WorkValidate extends Validate {


    protected $rule = [
        ['wc_id','number','案例分类必须为正整数'],
        ['w_name','require','案例名'],
        ['w_introduction','require','案例介绍必须'],
//        ['w_content','require','案例展示图片必须'],
//        ['w_qrcode','require','案例二维码必须'],
        ['w_sort','number','案例排序必须为正整数'],
        ['w_status','number|in:0,1','案例状态未知'],
    ];


}