<?php

namespace app\common\model;

use think\Db;
use think\Model;

class User extends Model {


    /**
     * 获取用户信息
     * @param $u_openid
     * @return $this
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserInfo($u_openid){
        return $this->find($u_openid);
    }


    /***********************管理端后台接口****************************/

    public static function getUserListForAdmin($condition=null){
        if ($condition!=null){
            $rs = Db::table('user')
                ->where($condition)
                ->order('create_time','DESC')
                ->paginate(10);
        }else{
            $rs = Db::table('user')
                ->order('create_time','DESC')
                ->paginate(10);
        }
        return $rs;
    }
}
