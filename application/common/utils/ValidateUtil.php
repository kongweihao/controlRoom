<?php
/**
 * Created by PhpStorm.
 * User: Di
 * Date: 2017/12/16 0016
 * Time: 14:03
 */

namespace app\common\utils;


use think\Validate;

class ValidateUtil extends Validate {

    public function emailValidate($email){
        return $this::is($email,'email');
    }

    public function numberValidate($number){
        return $this::is($number,'number');
    }
    public function integerValidate($integer){
        return $this::is($integer,'integer');
    }

    public function timestampValidate($timestamp){
        return $this::is($timestamp,'Y-m-d H:i:s');
    }

}