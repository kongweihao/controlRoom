<?php
namespace app\system\controller;
use think\Request;
use app\common\model\Role;
use app\common\model\AdminRole;
use think\Controller;
use app\common\utils\SystemConfig;
use think\Db;
use Psr\Log\Logger;
use think\Session;

/**
 * 日程管理类
 * @author kongweihao
 *
 */
// class ScheduleManagement extends Controller{ // 调试用的，没有做权限限制
class ScheduleManagement extends Auth{ // 上线用的
	/**
	 * 公共测试接口
	 */
	public function test(Request $request){
		
	}
	
	/**
	 * 查看日历
	 */
	public function calendar_list(){
		$params = input();
		if( $params['isSearch'] != 1 ){
			// 首次打开页面
			$calendar_list = Db::table('calendar')
				->order('create_time','DESC') // ASC 升序
				->paginate(10)->toArray();
			$this->assign([
				'calendar_list'=>$calendar_list
			]);
			return $this->fetch();
		} else {
			// 搜索功能
			$pagination = $params['pagination'];
			$searchForm = $params['searchForm'];
			$condition = [];
			$condition['date'] = ['like','%'.$searchForm['date'].'%'];
			$condition['weekDay'] = ['like','%'.$searchForm['weekDay'].'%'];
			$condition['yearTips'] = ['like','%'.$searchForm['yearTips'].'%'];
			$condition['type'] = ['like','%'.$searchForm['type'].'%'];
			$condition['typeDes'] = ['like','%'.$searchForm['typeDes'].'%'];
			$condition['chineseZodiac'] = ['like','%'.$searchForm['chineseZodiac'].'%'];
			$condition['solarTerms'] = ['like','%'.$searchForm['solarTerms'].'%'];
			$condition['lunarCalendar'] = ['like','%'.$searchForm['lunarCalendar'].'%'];
			$condition['suit'] = ['like','%'.$searchForm['suit'].'%'];
			$condition['avoid'] = ['like','%'.$searchForm['avoid'].'%'];
			$condition['dayOfYear'] = ['like','%'.$searchForm['dayOfYear'].'%'];
			$condition['weekOfYear'] = ['like','%'.$searchForm['weekOfYear'].'%'];
			$condition['constellation'] = ['like','%'.$searchForm['constellation'].'%'];
			$condition['indexWorkDayOfMonth'] = ['like','%'.$searchForm['indexWorkDayOfMonth'].'%'];
			$calendar_list = Db::table('calendar')
				->where($condition)
				->order('create_time','DESC') // ASC 升序
				->paginate($pagination['per_page'],false,[
					'page' => $pagination['current_page'],
					'list_rows' => $pagination['per_page'],
				]);
			return \json($calendar_list);
		}
	}
	/**
	 * 创建本地日历
	 * 接口只有2002-至今的返回数据
	 */
	public function create_calendar()
	{	
		$arrContextOptions=array( // ；临时跳过ssl认证
		    "ssl"=>array(
		        "verify_peer"=>false,
		        "verify_peer_name"=>false,
		        "allow_self_signed"=>true,
		    ),
		);
		$start_month = input()['start_month'];
		$end_month = input()['end_month'];

		if (!is_numeric($start_month) || !is_numeric($end_month)) {
			return '参数需为yyyymm格式的六位数字';
		}

		if ($start_month < 200200) {
			return '开始时间不能小于200201';
		}

		if ($start_month >= $end_month) {
			return '开始时间不能大于等于结束时间';
		}
		$is_time_range_exit = Db::table('calendar')->where('date', substr( $start_month, 0, 4) . '-'.substr( $start_month, 4, 2).'-01')->find();
		
		if ($is_time_range_exit) {
			return '开始月份已存在';
		}
		$ym = $start_month; // 开始时间
		// 取消30s的时间限制 -- 不过有时候也会失败，因为roll接口有访问总数限制
		set_time_limit(0);
		while ($ym <= $end_month) { // 结束时间 循环获取2002年-2024年的数据，每年更新当年日历，未来的数据要实时获取
			if (substr($ym, 4, 2) == 13) {
				$ym = $ym - 12 + 100;
			}
			$rollHolidayAPI = 'https://www.mxnzp.com/api/holiday/list/month/' . $ym . '?ignoreHoliday=false&app_id=n0suksemrqafttpd&app_secret=T1d6Z0wraTdYeTRjZktnc2VoeDUrQT09';
			$rollHolidayRS = json_decode(file_get_contents($rollHolidayAPI, false, stream_context_create($arrContextOptions)), true);
			if ($rollHolidayRS['coda'] != 1) {
				return $rollHolidayRS['msg'];
			}
			$holidayData = $rollHolidayRS['data'];
			// 检测字段完整性 insertAll方法的缺陷，字段必须一一对应
			for ($i = 0; $i < sizeof($holidayData); $i ++) {
				if (!array_key_exists('date', $holidayData[$i])) {
					$holidayData[$i]['date'] = '';
				}
				if (!array_key_exists('weekDay', $holidayData[$i])) {
					$holidayData[$i]['weekDay'] = '';
				}
				if (!array_key_exists('yearTips', $holidayData[$i])) {
					$holidayData[$i]['yearTips'] = '';
				}
				if (!array_key_exists('type', $holidayData[$i])) {
					$holidayData[$i]['type'] = '';
				}
				if (!array_key_exists('typeDes', $holidayData[$i])) {
					$holidayData[$i]['typeDes'] = '';
				}
				if (!array_key_exists('chineseZodiac', $holidayData[$i])) {
					$holidayData[$i]['chineseZodiac'] = '';
				}
				if (!array_key_exists('solarTerms', $holidayData[$i])) {
					$holidayData[$i]['solarTerms'] = '';
				}
				if (!array_key_exists('lunarCalendar', $holidayData[$i])) {
					$holidayData[$i]['lunarCalendar'] = '';
				}
				if (!array_key_exists('suit', $holidayData[$i])) {
					$holidayData[$i]['suit'] = '';
				}
				if (!array_key_exists('avoid', $holidayData[$i])) {
					$holidayData[$i]['avoid'] = '';
				}
				if (!array_key_exists('dayOfYear', $holidayData[$i])) {
					$holidayData[$i]['dayOfYear'] = '';
				}
				if (!array_key_exists('weekOfYear', $holidayData[$i])) {
					$holidayData[$i]['weekOfYear'] = '';
				}
				if (!array_key_exists('constellation', $holidayData[$i])) {
					$holidayData[$i]['constellation'] = '';
				}
				if (!array_key_exists('indexWorkDayOfMonth', $holidayData[$i])) {
					$holidayData[$i]['indexWorkDayOfMonth'] = '';
				}
			}
			$rs = Db::table('calendar')->insertAll($holidayData);
			$ym++;
			print($rs);
			sleep(1); // 接口qps不允许连续获取数据，此处每次延迟一秒保存一次数据
		}
		
		return $ym;
	}
	/**
	 * 规则设置·查
	 */
	public function rotaVacationSettingManagementList(){
		$rotaVacationSettingManagementList = Db::table('rota_vacation_setting')->select();
		$this->assign([
			'rotaVacationSettingManagementList'=>$rotaVacationSettingManagementList
		]);
		return $this->fetch();
	}
	/**
	 *规则设置·改
	 */
	public function editrotaVacationSettingManagementList(){
		$rs = [];
		$rs['code'] = 1;
		$rs['msg'] = '操作成功';
		$params = \input();
		$params['update_time'] = date('Y-m-d H:i:s');
		// return \json($params);
		$editrotaVacationSettingManagementListRs = Db::table('rota_vacation_setting')
													->where('rvs_id', $params['rvs_id'])
													->update([
														$params['key']=>$params[$params['key']], 
														'update_time'=>$params['update_time']
													]);
		if (!$editrotaVacationSettingManagementListRs) {
			$rs['code'] = 0;
			$rs['msg'] = '操作失败，请联系管理员';
		}
		return \json($rs);
	}
	/**
	 * 调休配额管理·查
	 * 周五夜~周日夜1个班次1天调休、节假日则由管理员手动填写，其他正常工作时间0调休
	 */
	public function rotaVacationDaysOffManagementList(){
		$params = \input();
		$rs = [];
		$rs['code'] = 1;
		$rs['msg'] = '操作成功';

		// 首次打开页面，既搜索条件为空时
		if (!$params['isSearch']) {
			// 前端日历组件默认显示前后三个月时间
			// 声明搜索月份
			$previous_m = date('Ym',strtotime("-1 Months"));
			$this_m = date('Ym');
			$next_m = date('Ym',strtotime("+1 Months")); 
			$rotaVacationDaysOffManagementList = Db::table('rota_vacation_days_off_management')
				->where('rvdom_timestamp', 'like','%'.$previous_m.'%')
				->whereOr('rvdom_timestamp', 'like','%'.$this_m.'%')
				->whereOr('rvdom_timestamp', 'like','%'.$next_m.'%')
				->select();
			$this->assign([
				'rotaVacationDaysOffManagementList'=>$rotaVacationDaysOffManagementList,
			]);
			return $this->fetch();
		} else if ($params['isSearch']) {
			// 声明搜索月份
			$previous_m = $params['previous_m'];
			$this_m = $params['this_m'];
			$next_m = $params['next_m']; 
			$rotaVacationDaysOffManagementList = Db::table('rota_vacation_days_off_management')
				->where('rvdom_timestamp', 'like','%'.$previous_m.'%')
				->whereOr('rvdom_timestamp', 'like','%'.$this_m.'%')
				->whereOr('rvdom_timestamp', 'like','%'.$next_m.'%')
				->select();
			if (!$rotaVacationDaysOffManagementList) {
				$rs['code'] = 0;
				$rs['msg'] = '本月无其他配额';
			} else {
				$rs['data'] = $rotaVacationDaysOffManagementList;
			}
			return \json($rs);
		}
	}
	/**
	 * 调休配额管理·增
	 */
	public function addrotaVacationDaysOffManagementList(){
		$params = \input();
		$rs = [];
		$rs['code'] = 1;
		$rs['msg'] = '操作成功';
		// 判断配置的班次是否已存在
		$is_exist = Db::table('rota_vacation_days_off_management')
					->where(['rvdom_timestamp'=>$params['rvdom_timestamp'], 'rvdom_day_or_night'=>$params['rvdom_day_or_night']])
					->find();
		if (!$is_exist) {
			$insertRs = Db::table('rota_vacation_days_off_management')->insert($params);
			if (!$insertRs) {
				$rs['code'] = 0;
				$rs['msg'] = '操作失败，请联系管理员';
			}
		} else {
			$rs['code'] = 0;
			$rs['msg'] = '改班次配额信息已存在，不可重复添加';
		}
		return \json($rs);
	}
	
	/**
	 * 调休配额管理·改
	 */
	public function editrotaVacationDaysOffManagementList(){
		$params = \input();
		$rs = [];
		$rs['code'] = 1;
		$rs['msg'] = '操作成功';
		// 判断配置的班次是否已存在
		$updateRs = Db::table('rota_vacation_days_off_management')->update($params);
		if (!$updateRs) {
			$rs['code'] = 0;
			$rs['msg'] = '操作失败，请联系管理员';
		}
		return \json($rs);
	}
	/**
	 * 调休配额日历·查
	 */
	public function rotaVacationDaysOffList(){
		$params = \input();
		$rs = [];
		$rs['code'] = 1;
		$rs['msg'] = '操作成功';

		// 首次打开页面，既搜索条件为空时
		if (!$params['isSearch']) {
			// 前端日历组件默认显示前后三个月时间
			// 声明搜索月份
			$previous_m = date('Ym',strtotime("-1 Months"));
			$this_m = date('Ym');
			$next_m = date('Ym',strtotime("+1 Months")); 
			$rotaVacationDaysOffList = Db::table('rota_vacation_days_off_management')
				->where('rvdom_timestamp', 'like','%'.$previous_m.'%')
				->whereOr('rvdom_timestamp', 'like','%'.$this_m.'%')
				->whereOr('rvdom_timestamp', 'like','%'.$next_m.'%')
				->select();
			$this->assign([
				'rotaVacationDaysOffList'=>$rotaVacationDaysOffList,
			]);
			return $this->fetch();
			
		} else if ($params['isSearch']) {
			// 声明搜索月份
			$previous_m = $params['previous_m'];
			$this_m = $params['this_m'];
			$next_m = $params['next_m']; 
			$rotaVacationDaysOffList = Db::table('rota_vacation_days_off_management')
				->where('rvdom_timestamp', 'like','%'.$previous_m.'%')
				->whereOr('rvdom_timestamp', 'like','%'.$this_m.'%')
				->whereOr('rvdom_timestamp', 'like','%'.$next_m.'%')
				->select();
			if (!$rotaVacationDaysOffList) {
				$rs['code'] = 0;
				$rs['msg'] = '本月无其他配额';
			} else {
				$rs['data'] = $rotaVacationDaysOffList;
			}
			return \json($rs);
		}
	}
	/**
	 * 调休申请·查
	 */
	public function leaveApplication(){
		// 系统自动计算任务————计算大家的调休余额
		$autoCalculRotaVacationDaysOffRs = $this->autoCalculRotaVacationDaysOff();
		if ($autoCalculRotaVacationDaysOffRs['code'] != 1 && $autoCalculRotaVacationDaysOffRs['code'] != 2&& $autoCalculRotaVacationDaysOffRs['code'] != 0) {
			return \json($autoCalculRotaVacationDaysOffRs);
		}
		$leaveApplication = Db::table('rota_vacation_setting')->select();
		$this->assign([
			'leaveApplication'=>$leaveApplication
		]);
		return $this->fetch();
	}
	/**
	 * 休假日历·查
	 */
	public function everyoneSSchedule(){
		$rs = [];
		$rs['code'] = 1;
		$rs['msg'] = '操作成功';
		$params = input();
		if( $params['isSearch'] == 1 ){
			$time_stamp = $params['ym'];
		} else {
			$time_stamp = date('ym');
		}
		$everyoneSSchedule = [];
		// 获取调休申请列表
		$vacation_request_list = Db::table('vacation_request_list')
			->alias('vrl')
			->where(['vrl_state'=>['<>','-1'],'vrl.vrl_timestamp_start|vrl.vrl_timestamp_end'=>['like','%'.$time_stamp.'%']])
			->join('admin a','vrl.vrl_applicant_admin_id=a.id')
			->select();
		// 获取年假申请列表
		$annual_leave_request_list = Db::table('annual_leave_request_list')
			->alias('alrl')
			->where(['alrl_state'=>['<>','-1'],'alrl.alrl_timestamp_start|alrl.alrl_timestamp_end'=>['like','%'.$time_stamp.'%']])
			->join('admin a','alrl.alrl_aplicant_admin_id=a.id')
			->select();
		// 获取夜班列表
		// 上一月份
		$lastMonth = date('Y', strtotime($time_stamp.' -1 month')).date('m', strtotime($time_stamp.' -1 month'));
		// 下一月份
		$nextMonth = date('Y', strtotime($time_stamp.' +1 month')).date('m', strtotime($time_stamp.' +1 month'));
		// 获取前中后三个月的夜班数据
		$nightRota = Db::table('rota')
		// ->where(['is_night'=>'1','time_stamp'=>['like','%'.$time_stamp.'%']])
		->where(['is_night'=>'1','monitorPost_type'=>['<>', '代维'],'time_stamp'=>['like',['%'.$time_stamp.'%','%'.$lastMonth.'%','%'.$nextMonth.'%'], 'OR']])
		->select();
		$everyoneSSchedule['vacation_request_list'] = $vacation_request_list;
		$everyoneSSchedule['annual_leave_request_list'] = $annual_leave_request_list;
		$everyoneSSchedule['nightRota'] = $nightRota;
		$rs['data'] = $everyoneSSchedule;
		if ($params['isSearch'] == 1) {
			return \json($rs);
		} else {
			$this->assign([
				'everyoneSSchedule'=>$everyoneSSchedule
			]);
			return $this->fetch();
		}
	}
	/**
	 * 调休申请·增
	 */
	public function addleaveApplication(){
		$rs = [];
		$rs['code'] = 1;
		$rs['msg'] = '操作成功';
		$params = \input();
		$params['update_time'] = date('Y-m-d H:i:s');
		$insertForm = [];
		$insertForm['rvs_annual_leave_year'] = Db::table('rota_vacation_setting')->find()['rvs_annual_leave_year'];
		if (!$insertForm['rvs_annual_leave_year'] && $insertForm['rvs_annual_leave_year'] != 0) {
			$rs['code'] = 0;
			$rs['msg'] = '获取年假计算年度失败请联系管理员';
			$rs['err'] = $insertForm['rvs_annual_leave_year'];
			return \json($rs);
		}
		// 年假
		if ($params['vacation_type'] == 1) {
			$insertForm['alrl_job_num'] = date('YmdHis').Session::get('adminAccount');
			if (!$insertForm['alrl_job_num']) {
				$rs['code'] = 0;
				$rs['msg'] = '配置单号失败请联系管理员';
				$rs['err'] = $insertForm['alrl_job_num'];
				return \json($rs);
			}
			$insertForm['alrl_aplicant_admin_id'] = Session::get('adminId');
			if (!$insertForm['alrl_aplicant_admin_id']) {
				$rs['code'] = 0;
				$rs['msg'] = '获取用户id失败请联系管理员';
				$rs['err'] = $insertForm['alrl_aplicant_admin_id'];
				return \json($rs);
			}
			$insertForm['alrl_applicant_account'] = Session::get('adminAccount');
			if (!$insertForm['alrl_applicant_account']) {
				$rs['code'] = 0;
				$rs['msg'] = '获取用户账号失败请联系管理员';
				$rs['err'] = $insertForm['alrl_applicant_account'];
				return \json($rs);
			}
			$insertForm['alrl_applicant_nickname'] = Session::get('adminFullname');
			if (!$insertForm['alrl_applicant_nickname']) {
				$rs['code'] = 0;
				$rs['msg'] = '获取用户账号失败请联系管理员';
				$rs['err'] = $insertForm['alrl_applicant_nickname'];
				return \json($rs);
			}
			$insertForm['alrl_timestamp_start'] = $params['timestamp_start'];
			$insertForm['alrl_timestamp_end'] = $params['timestamp_end'];
			$insertForm['alrl_half_day_start'] = $params['half_day_start'];
			$insertForm['alrl_half_day_end'] = $params['half_day_end'];
			$insertForm['alrl_num_of_days_off'] = $params['num_of_days_off'];
			$insertForm['alrl_remarks'] = $params['remarks'];
			// 修改年假余额
			$alm_num_of_holidays_Available = Db::table('admin')->where('id',$insertForm['alrl_aplicant_admin_id'])->find()['alm_num_of_holidays_Available'] - $insertForm['alrl_num_of_days_off'];
			$changeAlm_num_of_holidays_AvailableRs = Db::table('admin')->update(['id'=>$insertForm['alrl_aplicant_admin_id'],'alm_num_of_holidays_Available'=>$alm_num_of_holidays_Available]);
			if (!$changeAlm_num_of_holidays_AvailableRs) {
				$rs['code'] = 0;
				$rs['msg'] = '修改年假余额失败，请联系管理员';
				return \json($rs);
			}
			$insertRs = Db::table('annual_leave_request_list')->insert($insertForm);
			if (!$insertRs) {
				$rs['code'] = 0;
				$rs['msg'] = '操作失败，请联系管理员';
				return \json($rs);
			}
		}
		// 调休
		else if ($params['vacation_type'] == 2) {
			$insertForm['vrl_job_num'] = date('YmdHis').Session::get('adminAccount');
			if (!$insertForm['vrl_job_num']) {
				$rs['code'] = 0;
				$rs['msg'] = '配置单号失败请联系管理员';
				$rs['err'] = $insertForm['vrl_job_num'];
				return \json($rs);
			}
			$insertForm['vrl_applicant_admin_id'] = Session::get('adminId');
			if (!$insertForm['vrl_applicant_admin_id']) {
				$rs['code'] = 0;
				$rs['msg'] = '获取用户id失败请联系管理员';
				$rs['err'] = $insertForm['vrl_applicant_admin_id'];
				return \json($rs);
			}
			$insertForm['vrl_applicant_account'] = Session::get('adminAccount');
			if (!$insertForm['vrl_applicant_account']) {
				$rs['code'] = 0;
				$rs['msg'] = '获取用户账号失败请联系管理员';
				$rs['err'] = $insertForm['vrl_applicant_account'];
				return \json($rs);
			}
			$insertForm['vrl_applicant_nickname'] = Session::get('adminFullname');
			if (!$insertForm['vrl_applicant_nickname']) {
				$rs['code'] = 0;
				$rs['msg'] = '获取用户账号失败请联系管理员';
				$rs['err'] = $insertForm['vrl_applicant_nickname'];
				return \json($rs);
			}
			$insertForm['vrl_timestamp_start'] = $params['timestamp_start'];
			$insertForm['vrl_timestamp_end'] = $params['timestamp_end'];
			$insertForm['vrl_half_day_start'] = $params['half_day_start'];
			$insertForm['vrl_half_day_end'] = $params['half_day_end'];
			$insertForm['vrl_num_of_days_off'] = $params['num_of_days_off'];
			$insertForm['vrl_remarks'] = $params['remarks'];
			// 添加调休审批单号
			$insertGetId = Db::table('vacation_request_list')->insertGetId($insertForm);

			if (!$insertGetId) {
				$rs['code'] = 0;
				$rs['msg'] = '操作失败，请联系管理员';
				return \json($rs);
			}
			// 修改调休余额
			for ($i = 0; $i < \sizeof($params['dol_arr']); $i++) {
				$dol_id = explode('_',$params['dol_arr'][$i])[0];
				$my_days_off_list = Db::table('days_off_list')->where('dol_id',$dol_id)->find();
				$dol_days_off_balance = $my_days_off_list['dol_days_off_balance'] - 0.5;
				$vrl_id_arr = $my_days_off_list['vrl_id_arr'].',vrl_id_'.$insertGetId;
				$changeDol_days_off_balanceRs = Db::table('days_off_list')->update([
					'dol_id'=>$dol_id,
					'vrl_id_arr'=>$vrl_id_arr,
					'dol_days_off_balance'=>$dol_days_off_balance
				]);
				if (!$changeDol_days_off_balanceRs) {
					$rs['code'] = 0;
					$rs['msg'] = '修改调休余额失败，请联系管理员';
					return \json($rs);
				}
			}
		}
		return \json($rs);
	}
	/**
	 * 管理大家的年假·查
	 */
	public function annualLeaveManagementList(){
		$params = input();
		if( $params['isSearch'] != 1 ){
			// 首次打开页面
			$annualLeaveManagementList = Db::table('admin')
				->where('is_edit_holidays',1)
				->field('id,admin_account,admin_fullname,alm_num_of_holidays,alm_num_of_holidays_Available')
				->paginate(10)->toArray();
			$this->assign([
				'annualLeaveManagementList'=>$annualLeaveManagementList
			]);
			return $this->fetch();
		} else {
			// 搜索功能
			$pagination = $params['pagination'];
			$searchForm = $params['searchForm'];
			$condition = [];
			$condition['admin_account'] = ['like','%'.$searchForm['admin_account'].'%'];
			$condition['admin_fullname'] = ['like','%'.$searchForm['admin_fullname'].'%'];
			$condition['admin_phone'] = ['like','%'.$searchForm['admin_phone'].'%'];
			$annualLeaveManagementList = Db::table('admin')
				->field('id,admin_account,admin_fullname,alm_num_of_holidays,alm_num_of_holidays_Available')
				->where('is_edit_holidays',1)
				->where($condition)
				->paginate($pagination['per_page'],false,[
					'page' => $pagination['current_page'],
					'list_rows' => $pagination['per_page'],
				]);
			return \json($annualLeaveManagementList);
		}
	}
	/**
	 * 管理大家的年假·改
	 */
	public function editannualLeaveManagementList(){
		$rs = [];
		$rs['code'] = 1;
		$rs['msg'] = '操作成功';
		$params = \input();
		$params['update_time'] = date('Y-m-d H:i:s');
		$alm_num_of_holidays_old =  Db::table('admin')
						->where('id', $params['id'])
						->find()['alm_num_of_holidays'];
		$alm_num_of_holidays_Available_old =  Db::table('admin')
						->where('id', $params['id'])
						->find()['alm_num_of_holidays_Available'];
		$changeDay = $params['alm_num_of_holidays'] - $alm_num_of_holidays_old;
		$params['alm_num_of_holidays_Available'] = $alm_num_of_holidays_Available_old + $changeDay;
		$rs['alm_num_of_holidays_Available'] = $params['alm_num_of_holidays_Available'];
		$editannualLeaveManagementListRs = Db::table('admin')
		->where('id', $params['id'])
		->update([
			'alm_num_of_holidays'=>$params['alm_num_of_holidays'],
			'alm_num_of_holidays_Available'=>$params['alm_num_of_holidays_Available'],
			'update_time'=>$params['update_time']
		]);
		if (!$editannualLeaveManagementListRs) {
			$rs['code'] = 0;
			$rs['msg'] = '操作失败，请联系管理员';
		}
		return \json($rs);
	}
	/**
	 * 年假审批·查
	 * 既大家的年假申请记录
	 */
	public function annualLeaveRequestList(){
		$params = input();
		if( $params['isSearch'] != 1 ){
			// 当前年假计算年度
			$rvs_annual_leave_year = Db::table('rota_vacation_setting')->find()['rvs_annual_leave_year'];
			// 首次打开页面
			$annualLeaveRequestList = Db::table('annual_leave_request_list')
									->where('rvs_annual_leave_year',$rvs_annual_leave_year)  // 默认加载当前年假计算年度的数据
									->order('create_time','DESC') // ASC 升序
									->paginate(10)->toArray();
			$this->assign([
				'annualLeaveRequestList'=>$annualLeaveRequestList,
				'rvs_annual_leave_year'=>$rvs_annual_leave_year
			]);
			return $this->fetch();
		} else {
			// 搜索功能
			$pagination = $params['pagination'];
			$searchForm = $params['searchForm'];
			$condition = [];
			$condition['alrl_job_num'] = ['like','%'.$searchForm['alrl_job_num'].'%'];
			$condition['rvs_annual_leave_year'] = ['like','%'.$searchForm['rvs_annual_leave_year'].'%'];
			$condition['alrl_timestamp_start'] = ['like','%'.$searchForm['alrl_timestamp_start'].'%'];
			$condition['alrl_timestamp_end'] = ['like','%'.$searchForm['alrl_timestamp_end'].'%'];
			$condition['alrl_half_day_start'] = ['like','%'.$searchForm['alrl_half_day_start'].'%'];
			$condition['alrl_half_day_end'] = ['like','%'.$searchForm['alrl_half_day_end'].'%'];
			if ($searchForm['alrl_num_of_days_off'] !== '') {
				$condition['alrl_num_of_days_off'] = $searchForm['alrl_num_of_days_off'];
			}
			if ($searchForm['alrl_state'] == 1) {
				// 除撤销（-1）状态外的其他状态
				$condition['alrl_state'] = ['<>', -1];
			} else if ($searchForm['alrl_state'] == -1) {
				$condition['alrl_state'] = -1;
			}
			$condition['alrl_applicant_account'] = ['like','%'.$searchForm['alrl_applicant_account'].'%'];
			$condition['alrl_applicant_nickname'] = ['like','%'.$searchForm['alrl_applicant_nickname'].'%'];

			$annualLeaveRequestList = Db::table('annual_leave_request_list')
										->where($condition)
										->order('create_time','DESC') // ASC 升序
										->paginate($pagination['per_page'],false,[
											'page' => $pagination['current_page'],
											'list_rows' => $pagination['per_page'],
										]);
			return \json($annualLeaveRequestList);
		}
	}
	/**
	 * 我的年假·查
	 */
	public function myAnnualLeaveBalanceList(){
		$params = input();
		$admin_account = Session::get('adminAccount');
		if( $params['isSearch'] != 1 ){
			// 当前年假计算年度
			$rvs_annual_leave_year = Db::table('rota_vacation_setting')->find()['rvs_annual_leave_year'];
			// 获取年假余额
			$alm_num_of_holidays_Available = Db::table('admin')->where('admin_account',$admin_account)->find()['alm_num_of_holidays_Available'];
			// 首次打开页面
			$myAnnualLeaveBalanceList = Db::table('annual_leave_request_list')
									->where('rvs_annual_leave_year',$rvs_annual_leave_year)  // 默认加载当前年假计算年度的数据
									->where('alrl_applicant_account',$admin_account) 
									->order('create_time','DESC') // ASC 升序
									->paginate(10)->toArray();
			$this->assign([
				'myAnnualLeaveBalanceList'=>$myAnnualLeaveBalanceList,
				'rvs_annual_leave_year'=>$rvs_annual_leave_year,
				'alm_num_of_holidays_Available'=>$alm_num_of_holidays_Available
			]);
			return $this->fetch();
		} else {
			// 搜索功能
			$pagination = $params['pagination'];
			$searchForm = $params['searchForm'];
			$condition = [];
			$condition['alrl_job_num'] = ['like','%'.$searchForm['alrl_job_num'].'%'];
			$condition['rvs_annual_leave_year'] = ['like','%'.$searchForm['rvs_annual_leave_year'].'%'];
			$condition['alrl_timestamp_start'] = ['like','%'.$searchForm['alrl_timestamp_start'].'%'];
			$condition['alrl_timestamp_end'] = ['like','%'.$searchForm['alrl_timestamp_end'].'%'];
			$condition['alrl_half_day_start'] = ['like','%'.$searchForm['alrl_half_day_start'].'%'];
			$condition['alrl_half_day_end'] = ['like','%'.$searchForm['alrl_half_day_end'].'%'];
			if ($searchForm['alrl_num_of_days_off'] !== '') {
				$condition['alrl_num_of_days_off'] = $searchForm['alrl_num_of_days_off'];
			}
			if ($searchForm['alrl_state'] == 1) {
				// 除撤销（-1）状态外的其他状态
				$condition['alrl_state'] = ['<>', -1];
			} else if ($searchForm['alrl_state'] == -1) {
				$condition['alrl_state'] = -1;
			}
			$myAnnualLeaveBalanceList = Db::table('annual_leave_request_list')
										->where($condition)
										->where('alrl_applicant_account',$admin_account) 
										->order('create_time','DESC') // ASC 升序
										->paginate($pagination['per_page'],false,[
											'page' => $pagination['current_page'],
											'list_rows' => $pagination['per_page'],
										]);
			return \json($myAnnualLeaveBalanceList);
		}
	}
	/**
	 * 撤回我的年假申请
	 */
	public function withdrawalOfMyAnnualRequest(){
		$params = \input();
		$rs = [];
		$rs['code'] = 1;
		$rs['msg'] = '操作成功';
		$alrl_id = $params['alrl_id'];
		// 判断配置的班次是否已存在
		// 修改审批单号状态
		$alrl_state_updateRs = Db::table('annual_leave_request_list')
					->where('alrl_id',$alrl_id)
					->update(['alrl_state'=>-1]);
		if (!$alrl_state_updateRs) {
			$rs['code'] = 0;
			$rs['msg'] = '操作失败，请联系管理员';
		}
		// 回退年假余额
		$my_alrl_num_of_days_off = Db::table('annual_leave_request_list')->where('alrl_id',$alrl_id)->find()['alrl_num_of_days_off'];
		$my_alm_num_of_holidays_Available = Db::table('admin')->where('id', Session::get('adminId'))->find()['alm_num_of_holidays_Available'];
		$my_alm_num_of_holidays_AvailableRs = Db::table('admin')->where('id', Session::get('adminId'))->update([
			'alm_num_of_holidays_Available'=> ($my_alm_num_of_holidays_Available + $my_alrl_num_of_days_off)
		]);
		if (!$my_alm_num_of_holidays_AvailableRs) {
			$rs['code'] = 0;
			$rs['msg'] = '操作失败，请联系管理员';
		}
		return \json($rs);
	}
	/**
	 * 调休审批·查
	 * 既大家的调休申请记录
	 */
	public function vacationRequestList(){
		$params = input();
		if( $params['isSearch'] != 1 ){
			// 首次打开页面
			$vacationRequestList = Db::table('vacation_request_list')
									->order('create_time','DESC') // ASC 升序
									->paginate(10)->toArray();
			$this->assign([
				'vacationRequestList'=>$vacationRequestList
			]);
			return $this->fetch();
		} else {
			// 搜索功能
			$pagination = $params['pagination'];
			$searchForm = $params['searchForm'];
			$condition = [];
			$condition['vrl_job_num'] = ['like','%'.$searchForm['vrl_job_num'].'%'];
			$condition['vrl_timestamp_start'] = ['like','%'.$searchForm['vrl_timestamp_start'].'%'];
			$condition['vrl_timestamp_end'] = ['like','%'.$searchForm['vrl_timestamp_end'].'%'];
			$condition['vrl_half_day_start'] = ['like','%'.$searchForm['vrl_half_day_start'].'%'];
			$condition['vrl_half_day_end'] = ['like','%'.$searchForm['vrl_half_day_end'].'%'];
			if ($searchForm['vrl_num_of_days_off'] !== '') {
				$condition['vrl_num_of_days_off'] = $searchForm['vrl_num_of_days_off'];
			}
			$condition['vrl_applicant_account'] = ['like','%'.$searchForm['vrl_applicant_account'].'%'];
			$condition['vrl_applicant_nickname'] = ['like','%'.$searchForm['vrl_applicant_nickname'].'%'];
			if ($searchForm['vrl_state'] == 1) {
				// 除撤销（-1）状态外的其他状态
				$condition['vrl_state'] = ['<>', -1];
			} else if ($searchForm['vrl_state'] == -1) {
				$condition['vrl_state'] = -1;
			}
			$vacationRequestList = Db::table('vacation_request_list')
				->where($condition)
				->order('create_time','DESC') // ASC 升序
				->paginate($pagination['per_page'],false,[
					'page' => $pagination['current_page'],
					'list_rows' => $pagination['per_page'],
				]);
			return \json($vacationRequestList);
		}
	}
	/**
	 * 大家的调休余额·查
	 */
	public function checkEveryoneSAnnualLeaveBalance(){
		// 系统自动计算任务————计算大家的调休余额
		$autoCalculRotaVacationDaysOffRs = $this->autoCalculRotaVacationDaysOff();
		if ($autoCalculRotaVacationDaysOffRs['code'] != 1 && $autoCalculRotaVacationDaysOffRs['code'] != 2&& $autoCalculRotaVacationDaysOffRs['code'] != 0) {
			return \json($autoCalculRotaVacationDaysOffRs);
		}
		$params = input();
		// 获取调休过期日期
		$rvs_time_liness = Db::table('rota_vacation_setting')->find()['rvs_time_liness'];
		$ths_earliest_day =  $yesterday = date('Ymd',strtotime("-".$rvs_time_liness." month"));
		if( $params['isSearch'] != 1 ){
			// 当前年假计算年度
			// 首次打开页面
			$checkEveryoneSAnnualLeaveBalance = Db::table('days_off_list')
									->where(['dol_timestamp'=>['>=',$ths_earliest_day]])
									->alias(['rota'=>'r','days_off_list'=>'dol','admin'=>'a'])
									
									->join('admin a','dol.admin_id=a.id')
									->order('dol_id','DESC') // ASC 升序
									// ->select()->toArray();
									->paginate(10)->toArray();
				
				for ($i = 0; $i < \sizeof($checkEveryoneSAnnualLeaveBalance['data']); $i++) {
					$checkEveryoneSAnnualLeaveBalance['data'][$i]['is_invalid'] = 1;
				}
				for ($i = 0; $i < \sizeof($checkEveryoneSAnnualLeaveBalance['data']); $i++) {
					$checkEveryoneSAnnualLeaveBalance['data'][$i]['is_invalid'] = 1;
				}
				$this->assign([
				'checkEveryoneSAnnualLeaveBalance'=>$checkEveryoneSAnnualLeaveBalance,
			]);
			return $this->fetch();
		} else {
			// 搜索功能
			$pagination = $params['pagination'];
			$searchForm = $params['searchForm'];
			$condition = [];
			$condition['dol.dol_timestamp'] = ['like','%'.$searchForm['dol_timestamp'].'%'];
			$condition['dol.dol_day_or_night'] = ['like','%'.$searchForm['dol_day_or_night'].'%'];
			if ($searchForm['dol_remarks'] !== '') {
				$condition['dol.dol_remarks'] = $searchForm['dol_remarks'];
			}
			if ($searchForm['dol_days_off'] !== '') {
				$condition['dol.dol_days_off'] = $searchForm['dol_days_off'];
			}
			$condition['a.admin_account'] = ['like','%'.$searchForm['admin_account'].'%'];
			$condition['a.admin_fullname'] = ['like','%'.$searchForm['admin_fullname'].'%'];
			if ($searchForm['dol_days_off_balance'] !== '') {
				$condition['dol.dol_days_off_balance'] = $searchForm['dol_days_off_balance'];
			}
			// is_invalid = 1 未失效调休
			if ($searchForm['is_invalid'] == 1) {
				$checkEveryoneSAnnualLeaveBalance = Db::table('days_off_list')
					->where($condition)
					->where(['dol_timestamp'=>['>=',$ths_earliest_day]])
					->alias(['rota'=>'r','days_off_list'=>'dol','admin'=>'a'])
					
					->join('admin a','dol.admin_id=a.id')
					->order('dol_id','DESC') // ASC 升序
					->paginate($pagination['per_page'],false,[
						'page' => $pagination['current_page'],
						'list_rows' => $pagination['per_page'],
					])->toArray();
				for ($i = 0; $i < \sizeof($checkEveryoneSAnnualLeaveBalance['data']); $i++) {
					$checkEveryoneSAnnualLeaveBalance['data'][$i]['is_invalid'] = 1;
				}
			} 
			// is_invalid = 2 失效调休
			else if ($searchForm['is_invalid'] == 2) {
				$checkEveryoneSAnnualLeaveBalance = Db::table('days_off_list')
				->where($condition)
				->where(['dol_timestamp'=>['<',$ths_earliest_day]])
				->alias(['rota'=>'r','days_off_list'=>'dol','admin'=>'a'])
				
				->join('admin a','dol.admin_id=a.id')
				->order('dol_id','DESC') // ASC 升序
				->paginate($pagination['per_page'],false,[
					'page' => $pagination['current_page'],
					'list_rows' => $pagination['per_page'],
				])->toArray();
				for ($i = 0; $i < \sizeof($checkEveryoneSAnnualLeaveBalance['data']); $i++) {
					$checkEveryoneSAnnualLeaveBalance['data'][$i]['is_invalid'] = 2;
				}
			}  
			// is_invalid = '' 全部调休
			else  if ($searchForm['is_invalid'] == ''){
				$checkEveryoneSAnnualLeaveBalance = Db::table('days_off_list')
				->where($condition)
				->alias(['rota'=>'r','days_off_list'=>'dol','admin'=>'a'])
				
				->join('admin a','dol.admin_id=a.id')
				->order('dol_id','DESC') // ASC 升序
				->paginate($pagination['per_page'],false,[
					'page' => $pagination['current_page'],
					'list_rows' => $pagination['per_page'],
				])->toArray();
				for ($i = 0; $i < \sizeof($checkEveryoneSAnnualLeaveBalance['data']); $i++) {
					if ($checkEveryoneSAnnualLeaveBalance['data'][$i]['dol_timestamp'] >= $ths_earliest_day) {
						$checkEveryoneSAnnualLeaveBalance['data'][$i]['is_invalid'] = 1;
					}else if ($checkEveryoneSAnnualLeaveBalance['data'][$i]['dol_timestamp'] < $ths_earliest_day) {
						$checkEveryoneSAnnualLeaveBalance['data'][$i]['is_invalid'] = 2;
					}
				}
			}
			return \json($checkEveryoneSAnnualLeaveBalance);
		}
	}
	/**
	 * 我的调休余额·查
	 */
	public function myRotaVacationBalanceList(){
		// 系统自动计算任务————计算大家的调休余额
		$autoCalculRotaVacationDaysOffRs = $this->autoCalculRotaVacationDaysOff();
		if ($autoCalculRotaVacationDaysOffRs['code'] != 1 && $autoCalculRotaVacationDaysOffRs['code'] != 2&& $autoCalculRotaVacationDaysOffRs['code'] != 0) {
			return \json($autoCalculRotaVacationDaysOffRs);
		}
		$params = input();
		$admin_account = Session::get('adminAccount');
		$dol_days_off_balance_sum = 0;
		// 获取调休过期日期
		$rvs_time_liness = Db::table('rota_vacation_setting')->find()['rvs_time_liness'];
		$ths_earliest_day =  $yesterday = date('Ymd',strtotime("-".$rvs_time_liness." month"));
		if( $params['isSearch'] != 1 ){
			// 首次打开页面
			// 调休余额
			$myRotaVacationBalanceList = Db::table('days_off_list')
									->where('admin_account',$admin_account)
									->where(['dol_timestamp'=>['>=',$ths_earliest_day]])
									->alias(['rota'=>'r','days_off_list'=>'dol','admin'=>'a'])
									
									->join('admin a','dol.admin_id=a.id')
									->order('dol_timestamp', 'DESC') // ASC 升序 DESC降序
									->paginate(10)->toArray();
			for ($i = 0; $i < \sizeof($myRotaVacationBalanceList['data']); $i++) {
				$myRotaVacationBalanceList['data'][$i]['is_invalid'] = 1;
			}
									
			// 计算调休余额
			$myRotaVacationBalanceListAll = Db::table('days_off_list')
									->where('admin_account',$admin_account) 
									->where(['dol_timestamp'=>['>=',$ths_earliest_day]])
									->alias(['rota'=>'r','days_off_list'=>'dol','admin'=>'a'])
									
									->join('admin a','dol.admin_id=a.id')
									->select();
			for ($i = 0; $i < \sizeof($myRotaVacationBalanceListAll); $i++) {
				$dol_days_off_balance_sum += $myRotaVacationBalanceListAll[$i]['dol_days_off_balance'];
			}
			$this->assign([
				'dol_days_off_balance_sum'=>$dol_days_off_balance_sum,
				'myRotaVacationBalanceList'=>$myRotaVacationBalanceList,
			]);
			return $this->fetch();
		} else {
			// 搜索功能
			$pagination = $params['pagination'];
			$searchForm = $params['searchForm'];
			$condition = [];
			$condition['dol.dol_timestamp'] = ['like','%'.$searchForm['dol_timestamp'].'%'];
			$condition['dol.dol_remarks'] = ['like','%'.$searchForm['dol_remarks'].'%'];
			$condition['dol.dol_day_or_night'] = ['like','%'.$searchForm['dol_day_or_night'].'%'];
			if ($searchForm['dol_days_off'] !== '') {
				$condition['dol.dol_days_off'] = $searchForm['dol_days_off'];
			}
			$condition['a.admin_account'] = ['like','%'.$searchForm['admin_account'].'%'];
			$condition['a.admin_fullname'] = ['like','%'.$searchForm['admin_fullname'].'%'];
			if ($searchForm['dol_days_off_balance'] !== '') {
				$condition['dol.dol_days_off_balance'] = $searchForm['dol_days_off_balance'];
			}
			if ($searchForm['is_invalid'] == 1) {
				// 获取未失效调休/正常
				$myRotaVacationBalanceList = Db::table('days_off_list')
				->where('admin_account',$admin_account) 
				->where(['dol_timestamp'=>['>=',$ths_earliest_day]])
				->where($condition)
				->alias(['rota'=>'r','days_off_list'=>'dol','admin'=>'a'])
				
				->join('admin a','dol.admin_id=a.id')
				->order('dol_timestamp', 'DESC') // ASC 升序 DESC降序
				->paginate($pagination['per_page'],false,[
					'page' => $pagination['current_page'],
					'list_rows' => $pagination['per_page'],
				])->toArray();
				for ($i = 0; $i < \sizeof($myRotaVacationBalanceList['data']); $i++) {
					$myRotaVacationBalanceList['data'][$i]['is_invalid'] = 1;
				}
			} else if ($searchForm['is_invalid'] == 2) {
				// 获取失效调休
				$myRotaVacationBalanceList = Db::table('days_off_list')
				->where(['dol_timestamp'=>['<',$ths_earliest_day]])
				->where('admin_account',$admin_account) 
				->where($condition)
				->alias(['rota'=>'r','days_off_list'=>'dol','admin'=>'a'])
				
				->join('admin a','dol.admin_id=a.id')
				->order('dol_timestamp', 'DESC') // ASC 升序 DESC降序
				->paginate($pagination['per_page'],false,[
					'page' => $pagination['current_page'],
					'list_rows' => $pagination['per_page'],
				])->toArray();
				for ($i = 0; $i < \sizeof($myRotaVacationBalanceList['data']); $i++) {
					$myRotaVacationBalanceList['data'][$i]['is_invalid'] = 2;
				}
			} else  if ($searchForm['is_invalid'] == ''){
				// 获取全部调休
				$myRotaVacationBalanceList = Db::table('days_off_list')
				->where('admin_account',$admin_account) 
				->where($condition)
				->alias(['rota'=>'r','days_off_list'=>'dol','admin'=>'a'])
				
				->join('admin a','dol.admin_id=a.id')
				->order('dol_timestamp', 'DESC') // ASC 升序 DESC降序
				->paginate($pagination['per_page'],false,[
					'page' => $pagination['current_page'],
					'list_rows' => $pagination['per_page'],
				])->toArray();
				for ($i = 0; $i < \sizeof($myRotaVacationBalanceList['data']); $i++) {
					if ($myRotaVacationBalanceList['data'][$i]['dol_timestamp'] >= $ths_earliest_day) {
						$myRotaVacationBalanceList['data'][$i]['is_invalid'] = 1;
					}else if ($myRotaVacationBalanceList['data'][$i]['dol_timestamp'] < $ths_earliest_day) {
						$myRotaVacationBalanceList['data'][$i]['is_invalid'] = 2;
					}
				}
			}
			return \json($myRotaVacationBalanceList);
		}
	}
	
	/**
	 * 我的调休记录·查
	 */
	public function myVacationRecord(){
		// 系统自动计算任务————计算大家的调休余额
		$autoCalculRotaVacationDaysOffRs = $this->autoCalculRotaVacationDaysOff();
		if ($autoCalculRotaVacationDaysOffRs['code'] != 1 && $autoCalculRotaVacationDaysOffRs['code'] != 2&& $autoCalculRotaVacationDaysOffRs['code'] != 0) {
			return \json($autoCalculRotaVacationDaysOffRs);
		}
		$params = input();
		$admin_account = Session::get('adminAccount');
		if( $params['isSearch'] != 1 ){
			// 当前年假计算年度
			$rvs_annual_leave_year = Db::table('rota_vacation_setting')->find()['rvs_annual_leave_year'];
			// 首次打开页面
			$myVacationRecord = Db::table('vacation_request_list')
									->where('vrl_applicant_account',$admin_account) 
									->where('rvs_annual_leave_year',$rvs_annual_leave_year)  // 默认加载当前年假计算年度的数据
									->order('create_time','DESC') // ASC 升序
									->paginate(10)->toArray();
			$this->assign([
				'myVacationRecord'=>$myVacationRecord,
				'rvs_annual_leave_year'=>$rvs_annual_leave_year
			]);
			return $this->fetch();
		} else {
			// 搜索功能
			$pagination = $params['pagination'];
			$searchForm = $params['searchForm'];
			$condition = [];
			$condition['vrl_job_num'] = ['like','%'.$searchForm['vrl_job_num'].'%'];
			$condition['rvs_annual_leave_year'] = ['like','%'.$searchForm['rvs_annual_leave_year'].'%'];
			$condition['vrl_timestamp_start'] = ['like','%'.$searchForm['vrl_timestamp_start'].'%'];
			$condition['vrl_timestamp_end'] = ['like','%'.$searchForm['vrl_timestamp_end'].'%'];
			$condition['vrl_half_day_start'] = ['like','%'.$searchForm['vrl_half_day_start'].'%'];
			$condition['vrl_half_day_end'] = ['like','%'.$searchForm['vrl_half_day_end'].'%'];
			if ($searchForm['vrl_num_of_days_off'] !== '') {
				$condition['vrl_num_of_days_off'] = $searchForm['vrl_num_of_days_off'];
			}
			if ($searchForm['vrl_state'] == 1) {
				// 除撤销（-1）状态外的其他状态
				$condition['vrl_state'] = ['<>', -1];
			} else if ($searchForm['vrl_state'] == -1) {
				$condition['vrl_state'] = -1;
			}
			$myVacationRecord = Db::table('vacation_request_list')
				->where('vrl_applicant_account',$admin_account) 
				->where($condition)
				->order('create_time','DESC') // ASC 升序
				->paginate($pagination['per_page'],false,[
					'page' => $pagination['current_page'],
					'list_rows' => $pagination['per_page'],
				])->toArray();
			return \json($myVacationRecord);
		}
	}
	/**
	 * 撤回我的调休申请
	 */
	public function withdrawalOfMyVacationRequest(){
		$params = \input();
		$rs = [];
		$rs['code'] = 1;
		$rs['msg'] = '操作成功';
		$vrl_id = $params['vrl_id'];
		$vrl_id_str = ',vrl_id_'.$vrl_id; // 一个vrl_id_str代表半天调休

		$vrl_data =  Db::table('vacation_request_list')->where('vrl_id',$vrl_id)->find();
		$dol_data =  Db::table('days_off_list')->where('vrl_id_arr','like','%'.$vrl_id_str.'%')->select();
		for ($i = 0; $i < \sizeof($dol_data); $i++) {
			$vrl_id_arr = explode(",", $dol_data[$i]['vrl_id_arr']);
			$return_num_of_days = $dol_data[$i]['dol_days_off_balance']; // 计算撤回的天数
			for ($j = 0; $j < \sizeof($vrl_id_arr); $j++) {
				if (','.$vrl_id_arr[$j] == $vrl_id_str) {
					$return_num_of_days += 0.5;
				}
			}
			$updateDolRs = Db::table('days_off_list')
				->where('dol_id',$dol_data[$i]['dol_id'])
				->update(['dol_days_off_balance'=>$return_num_of_days]);
			$return_num_of_days = 0; // 计算撤回的天数
		}
		// return json($return_num_of_days);
		// $updateVrlRs = Db::table('days_off_list')
		// 		->where('dol_id',$vrl_id)
		// 		->update(['vrl_state'=>-1]);
		// 判断配置的班次是否已存在
		$updateVrlRs = Db::table('vacation_request_list')
					->where('vrl_id',$vrl_id)
					->update(['vrl_state'=>-1]);
		
		if (!$updateVrlRs) {
			$rs['code'] = 0;
			$rs['msg'] = '操作失败，请联系管理员';
		}
		return \json($rs);
	}

	protected $protected = 'Protected';
	/**
	 * 大家的调休余额计算·系统自动算
	 * 系统自动计算任务————计算大家的调休余额：需要加入自动计算任务计划的模块包括：调休申请·查、大家的调休余额·查、我的调休余额·查、我的调休记录·查
	 * 注：rota_vacation_setting表中rvs_calculate_everyone_s_vacation_timestamp字段一开始不能为空，需要给定一个初始的日期，且格式为yyyymmdd
	 */
	protected function autoCalculRotaVacationDaysOff(){
		$params = \input();
		$rs = [];
		$rs['code'] = 1;
		$rs['msg'] = '操作成功';
		$rs['date'] = [];
		$dol = []; // 保存余额表数据
		$now_ymd = date('Ymd');
		$h = date('h');
		$rvs_calculate_everyone_s_vacation_timestamp = Db::table('rota_vacation_setting')->find()['rvs_calculate_everyone_s_vacation_timestamp'];
		$rvs_calculate_everyone_s_vacation_timestamp_day_or_night = Db::table('rota_vacation_setting')->find()['rvs_calculate_everyone_s_vacation_timestamp_day_or_night'];
		
		if ($now_ymd > $rvs_calculate_everyone_s_vacation_timestamp) {
			// 当前时间小于上一次自动计算时间
			for ($i = $rvs_calculate_everyone_s_vacation_timestamp; $i < $now_ymd;) {
				// 指定日期+1，循环遍历
				$i = \date('Ymd', strtotime($i."+1 day"));
				// 检查是否配额
				$is_rvdom_timestamp_list = Db::table('rota_vacation_days_off_management')
											->where('rvdom_timestamp',$i)->select();
				// 有配额
				if ($is_rvdom_timestamp_list) {
					// 遍历配额
					for ($j = 0; $j < sizeof($is_rvdom_timestamp_list); $j++) {
						if ($is_rvdom_timestamp_list[$j]['rvdom_days_off'] == 0) {
							// 配额为0直接跳过
							continue;
						}
						// 判断白班还是夜班
						$condition_j = [];
						$condition_j['r.mp_alias'] = ['<>','值班长'];// 排除重复的岗位
						$condition_j['r.time_stamp'] = $i;
						if ($is_rvdom_timestamp_list[$j]['rvdom_day_or_night'] == 1) {
							$condition_j['r.is_day'] = 1;
						} else if ($is_rvdom_timestamp_list[$j]['rvdom_day_or_night'] == 0) {
							$condition_j['r.is_night'] = 1;
						}
						$rota = Db::table('rota')
								->alias('r')
								->field('r.*,m.*,r.id as rota_id,m.id as member_id')
								->join('member m', 'r.member_name = m.name')
								->where($condition_j)
								->select();
						// 由于tp5没有多对多的联表查询关系，所以只能手动查库
						for ($z = 0; $z < \sizeof($rota); $z++) {
							// 关联查询admin_id
							$admin_id = Db::table('admin_member')->where('member_id',$rota[$z]['member_id'])->find()['admin_id'];
							$is_edit_vacations = Db::table('admin')->where('id',$admin_id)->find()['is_edit_vacations'];
							if ($is_edit_vacations) {
								$dol_item = [];
								$dol_item['dol_timestamp'] = $i;
								$dol_item['dol_day_or_night'] = $is_rvdom_timestamp_list[$j]['rvdom_day_or_night'];
								$dol_item['dol_days_off'] = $is_rvdom_timestamp_list[$j]['rvdom_days_off'];;
								$dol_item['dol_days_off_balance'] = $is_rvdom_timestamp_list[$j]['rvdom_days_off'];;
								$dol_item['rota_id'] = $rota[$z]['rota_id'];
								$dol_item['admin_id'] = $admin_id;
								$dol_item['dol_remarks'] = $is_rvdom_timestamp_list[$j]['rvdom_remarks'];
								\array_push($dol, $dol_item);
							}
						}
					}
				} 
				// 周六~周日 且未配额
				if ((date('w',strtotime($i))==6) || (date('w',strtotime($i)) == 0)) {
					// 检查是否周末默认配额  注意：若周末已被管理员配置过的日期，则不会配置默认配额
					$condition_j = [];
					$condition_j['r.time_stamp'] = $i;
					$condition_j['r.mp_alias'] = ['<>','值班长'];// 排除重复的岗位
					$rota = Db::table('rota')
							->alias('r')
							->field('r.*,m.*,r.id as rota_id,m.id as member_id')
							->join('member m', 'r.member_name = m.name')
							->where($condition_j)
							->select();
					for ($z = 0; $z < \sizeof($rota); $z++) {
						// 关联查询admin_id
						$key = 1; // 录入配额开关
						$rota[$z]['admin_id'] = Db::table('admin_member')->where('member_id',$rota[$z]['member_id'])->find()['admin_id'];
						$admin_id = Db::table('admin_member')->where('member_id',$rota[$z]['member_id'])->find()['admin_id'];
						$is_edit_vacations = Db::table('admin')->where('id',$admin_id)->find()['is_edit_vacations'];
						// 剔除手动配额周六~周日的情况
						for ($j = 0; $j < sizeof($is_rvdom_timestamp_list); $j++) {
							if ($is_rvdom_timestamp_list[$j]['rvdom_day_or_night'] == $rota[$z]['is_day']) {
								$key = 0;
								break;
							}
						}
						// 录入配额
						if ($is_edit_vacations && $key) {
							$dol_item = [];
							$dol_item['dol_timestamp'] = $i;
							$dol_item['dol_day_or_night'] = $rota[$z]['is_day'] ? 1 : 0;
							$dol_item['dol_days_off'] = 1;
							$dol_item['dol_days_off_balance'] = 1;
							$dol_item['rota_id'] = $rota[$z]['rota_id'];
							$dol_item['admin_id'] = $admin_id;
							$dol_item['dol_remarks'] = date('w',strtotime($i))==6 ? '【周六】默认调休配额' :  '【周日】默认调休配额';
							\array_push($dol, $dol_item);
						}
					}
				}
				// 周五 夜
				if (date('w',strtotime($i))==5) {
					// 检查是否周末默认配额  注意：若周末已被管理员配置过的日期，则不会配置默认配额
					$condition_j = [];
					$condition_j['r.time_stamp'] = $i;
					$condition_j['r.is_night'] = 1;
					$condition_j['r.mp_alias'] = ['<>','值班长'];// 排除重复的岗位
					$rota = Db::table('rota')
							->alias('r')
							->field('r.*,m.*,r.id as rota_id,m.id as member_id')
							->join('member m', 'r.member_name = m.name')
							->where($condition_j)
							->select();
					for ($z = 0; $z < \sizeof($rota); $z++) {
						// 关联查询admin_id
						$key = 1; // 录入配额开关
						$rota[$z]['admin_id'] = Db::table('admin_member')->where('member_id',$rota[$z]['member_id'])->find()['admin_id'];
						$admin_id = Db::table('admin_member')->where('member_id',$rota[$z]['member_id'])->find()['admin_id'];
						$is_edit_vacations = Db::table('admin')->where('id',$admin_id)->find()['is_edit_vacations'];
						// 剔除手动配额周五夜班的情况
						for ($j = 0; $j < sizeof($is_rvdom_timestamp_list); $j++) {
							if ($is_rvdom_timestamp_list[$j]['rvdom_day_or_night'] == 0) {
								$key = 0;
								break;
							}
						}
						// 录入配额
						if ($is_edit_vacations && $key) {
							$dol_item = [];
							$dol_item['dol_timestamp'] = $i;
							$dol_item['dol_day_or_night'] = 0;
							$dol_item['dol_days_off'] = 1;
							$dol_item['dol_days_off_balance'] = 1;
							$dol_item['rota_id'] = $rota[$z]['rota_id'];
							$dol_item['admin_id'] = $admin_id;
							$dol_item['dol_remarks'] = '【周五】【夜】默认调休配额';
							\array_push($dol, $dol_item);
						}
					}
				}
			}
			$updateRvs_calculate_everyone_s_vacation_timestamp = Db::table('rota_vacation_setting')->where('rvs_id',1)->update(['rvs_calculate_everyone_s_vacation_timestamp'=>$now_ymd]);
			if (!$updateRvs_calculate_everyone_s_vacation_timestamp) {
				$rs['code'] = 0;
				$rs['msg'] = '更新规则设置表格updateRvs_calculate_everyone_s_vacation_timestamp字段失败，请联系管理员';
				return $rs;
			}
			$saveAllDol = Db::table('days_off_list')->insertAll($dol);
			if (!$saveAllDol) {
				$rs['code'] = 0;
				$rs['msg'] = '没有新增数据';
				return $rs;
			}
		} else {
			$rs['code'] = 2;
			$rs['msg'] = '数据已同步，请明日再来';
		}
		// // 暂时不启用，待系统自动计算粒度到了这个级别时再说
		// else if ($now_ymd = $rvs_calculate_everyone_s_vacation_timestamp && $h > 18 && $rvs_calculate_everyone_s_vacation_timestamp_day_or_night == 1) {
		// 	// 当前时间等于上一次自动计算时间
		// 	// 当前时间为晚上，且上一次计算时间为白天  注：不用考虑上一次为白天的情况，因为上一次为白天且日期相同，那一定是已经计算过了
		// 	return 2;
		// } 
		return $rs;
	}
}