<?php
/**
 * Created by PhpStorm.
 * User: kwd
 * Date: 2018/5/12 0012
 * Time: 10:30
 */

namespace app\common\validate;


use think\Validate;

class MessageValidate extends Validate {


    protected $rule = [
        ['m_username','require','用户名必须'],
        ['m_contact_type','number|in:1,2,3,4','联系方式必须'],
        ['m_contact_number','require','联系方式必须'],
        ['m_content','require','内容必须'],
    ];


}