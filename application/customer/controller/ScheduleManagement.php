<?php
namespace app\customer\controller;

use think\Controller;
use think\Db;
use think\Request;
use app\common\model\Rota as RotaModel;
use think\Session;

/**
 * 日程管理类
 * @author kongweihao
 *
 */
class ScheduleManagement extends Controller{ // 调试用的，没有做权限限制
		/**
		 * 公共测试接口
		 */
		public function test(Request $request){
			
		}
		/**
		 * 获取我的年假余额·查
		 */
		public function getMyAnnualLeaveBalance(){
			$alm_num_of_holidays_Available = Db::table('admin')->where('admin_account', Session::get('adminAccount'))->find()['alm_num_of_holidays_Available'];
			return $alm_num_of_holidays_Available;
		}
		/**
		 * 获取我的调休余额·查
		 */
		public function getMyVacationBalance(){

			$rs = [];
			$rs['code'] = 1;
			$rs['msg'] = '操作成功';
			$rs['data'] = [];

			// 获取调休过期日期
			$rvs_time_liness = Db::table('rota_vacation_setting')->find()['rvs_time_liness'];
			$ths_earliest_day =  $yesterday = date('Ymd',strtotime("-".$rvs_time_liness." month"));
			// 获取我所有的有效调休
			$days_off_balance_list = Db::table('days_off_list')
									->where('admin_account', Session::get('adminAccount'))
									->where(['dol_timestamp'=>['>=',$ths_earliest_day]])
									->order(['dol_timestamp ASC'])//DESC降序
									->alias(['rota'=>'r','days_off_list'=>'dol','admin'=>'a'])
									->join('admin a','dol.admin_id=a.id')
									->select();
			// 计算调休余额
			$dol_days_off_balance_sum = 0;
			for ($i = 0; $i < \sizeof($days_off_balance_list); $i++) {
				$dol_days_off_balance_sum += $days_off_balance_list[$i]['dol_days_off_balance'];
			}
			$rs['data']['dol_days_off_balance_sum'] = $dol_days_off_balance_sum;
			$rs['data']['days_off_balance_list'] = $days_off_balance_list;
			return $rs;
		}	
	}