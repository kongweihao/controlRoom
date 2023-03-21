<?php
namespace app\customer\controller;

use think\Controller;
use think\Db;
use think\Request;
use app\common\model\Rota as RotaModel;

/**
 * 值班信息类
 * @author kongweihao
 *
 */
class RotaSituation extends Controller{ // 调试用的，没有做权限限制
	/**
	 * 公共测试接口
	 */
	public function test(Request $request){
		
	}
	/**
	 * 获取值班信息某个三级表的字段
	 * $params['ctrr_rsrm_parent_id']
	 * 
	 */
	public function getRsrmLevelTree(Request $request){
		$params= \input();
		if($params != []){
			$rs = Db::table('rotasituationrecordmanagement')->where(['ctrr_rsrm_parent_id'=>$params['ctrr_rsrm_parent_id'],'ctrr_rsrm_level'=>3,'ctrr_rsrm_type'=>4])->select();
		}
		return \json($rs);
	}
	/**
	 * 获取字段类型为计算时长的管理时间范围数据
	 * $params = {ctrr_rsrm_id:'ctrr_rsrm_id'}
	 * 
	 */
	public function getDateRangByDateLength(Request $request){
		$params= \input();
		if($params != []){
			$rs = Db::table('rotasituationrecordmanagement')->where($params)->find();
		}
		return \json($rs);
	}
	/**
	 * 记录大值班历史数据
	 */
	public function addDutySummary(Request $request){
		$params = \input();
		$rs = Db::table('duty_summary_management_history')->insert(['dsmH_d'=> $params['dutySummary']]);
		return $rs;
	}
}