<?php
namespace app\system\controller;
use app\common\model\RotaData as RotaDataModel;
use think\Request;
use app\common\model\Role;
use app\common\model\AdminRole;
use think\Controller;
use app\common\utils\SystemConfig;
use Psr\Log\Logger;
use think\Db;


/**
 * 值班表类
 * @author kongweihao
 *
 */
// class RotaData extends Controller{ // 调试用的，没有做权限限制
class RotaData extends Auth{ // 上线用的
	/**
	 * 公共测试接口
	 */
	public function test(Request $request){
		
	}
	/**
	 * 值班数据统计列表
	 */
	public function rotaDataStatisticsList(Request $request){
		$data = input();
		// 初次加载页面时，会先进入else中

		$last= strtotime("-1 month", time());

		$timestamp_start = $data['timestamp_start'];
		$timestamp_end = $data['timestamp_end'];
		// return json($time_stamp);
		if($timestamp_start == ''){
			$timestamp_start = date('Ym01', $last);//上个月第一天
		}
		if($timestamp_end == ''){
			$timestamp_end = date("Ymt", $last);//上个月最后一天
		}
		// 初始化时间范围搜索条件
		if($data['banci_start'] == 1){
			$timestamp_start_search_day = $timestamp_start;
			$timestamp_end_search_day = $timestamp_end;
		}else{
			$timestamp_start_search_day = $timestamp_start+1;
			$timestamp_end_search_day = $timestamp_end;
		}
		if($data['banci_end'] == 0){
			$timestamp_start_search_night = $timestamp_start;
			$timestamp_end_search_night = $timestamp_end;
		}else{
			$timestamp_start_search_night = $timestamp_start;
			$timestamp_end_search_night = $timestamp_end-1;
		}
		if($data['getList'] == 1){
			$rs['code'] = 0;
			$rs['msg'] = '加载成功';
			// table所需要的数据
			$tableData = array();
			// 获取值班人员信息
			$memberList = Db::table('member')
							->where(['is_monitor'=>1])
							->where('name','like','%'.$data['member_name'].'%')
							->select();
			$memberListLen = sizeof($memberList);
			// 按其余条件进行搜索
			for($i = 0; $i< $memberListLen; $i++){
				$memberList[$i]['monitorPost_type_id'] = explode(',', $memberList[$i]['monitorPost_type_id']);
				// 换班次数搜索
				$memberListMonitorPost_type_idLen = sizeof($memberList[$i]['monitorPost_type_id']);
				$memberList[$i]['shiftRecordTime'] = \sizeof(Db::table('shift_record')
				->where('create_time', 'between', [$timestamp_start_search_day, $timestamp_end_search_night])
				->where('operation_guy',$memberList[$i]['name'])
				->where('monitor_post_name','<>','值班长白班') // 2022年3月后的值班长数据已经剔除了
				->where('monitor_post_name','<>','值班长夜班') // 这里保留下来是为了剔除历史数据中的值班长数据，后续若历史数据不需要了也可以删了，提高代码简洁性
				->select());
				
				// 按岗位类型搜索
				for($j = 0; $j < $memberListMonitorPost_type_idLen; $j++){
					// 白班数据
					$memberList[$i]['dayRota'] = Db::table('rota')
													->where('time_stamp', 'between', [$timestamp_start_search_day, $timestamp_end_search_day])
													->where('member_name','like', '%'.$memberList[$i]['name'].'%')
													->where('is_day',1)
													->where('monitor_post_name','<>','值班长白班')
													->select();
					// 夜班数据
					$memberList[$i]['nightRota'] = Db::table('rota')
													->where('time_stamp', 'between', [$timestamp_start_search_night, $timestamp_end_search_night])
													->where('member_name','like', '%'.$memberList[$i]['name'].'%')
													->where('monitor_post_name','<>','值班长夜班')
													->where('is_night',1)
													->select();
					$memberList[$i]['dayTimes'] = sizeof($memberList[$i]['dayRota']);							
					$memberList[$i]['dayWorkTimes'] = $memberList[$i]['dayTimes'] * 9;							
					$memberList[$i]['nightTimes'] = sizeof($memberList[$i]['nightRota']);							
					$memberList[$i]['nightWorkTimes'] = $memberList[$i]['nightTimes'] * 15;							
					$memberList[$i]['day_nightTimes'] = $memberList[$i]['dayTimes'] + $memberList[$i]['nightTimes'];							
					$memberList[$i]['day_nightWorkTimes'] = $memberList[$i]['dayWorkTimes'] + $memberList[$i]['nightWorkTimes'];							
					
					if($data['monitorPost_type_id'] == -1){
						array_push($tableData, $memberList[$i]);
						break;	
					}else if($memberList[$i]['monitorPost_type_id'][$j] == $data['monitorPost_type_id']){
						array_push($tableData, $memberList[$i]);
						break;	
					}
				}
			}
			$rs['count'] = sizeof($tableData);
			$rs['data'] = $tableData;
			// 初次加载页面时，会先进入else中
			return json($rs);

		}else{
			
			$monitorPostTypeList = Db::table('monitorpost_type')->select();
			// return json($monitorPostList);
			$monitorPostList = Db::table('monitor_post')->select();
			$monitorPostList_len = sizeof($monitorPostList);
			$this->assign('timestamp_start', $timestamp_start);
			$this->assign('timestamp_end', $timestamp_end);
			$this->assign('monitorPostList', $monitorPostList);
			$this->assign('monitorPostList_len', $monitorPostList_len);
			$this->assign('monitorPostTypeList', $monitorPostTypeList);
			$this->assign('monitorPostTypeList_selected', $data['monitorPost_type_id']);
			$this->assign('day_or_night', $data['day_or_night']);
			return $this->fetch();
		}
	}
	
	/**
	 * 换班记录统计列表
	 */
	public function shiftRecordList(Request $request, RotaDataModel $rotaData){
		$data = input();
		// return json(Db::table('shift_record')->select());
		if($data['getList'] == 1){
			// 初次加载页面时，会先进入else中
			
			$rs['code'] = 0;
			$rs['msg'] = '加载成功';
			// table所需要的数据
			$rotaDataList = $rotaData->getRotaDataList($data)->toArray();
			$rs['data'] = $rotaDataList['data'];
			$rs['count'] = $rotaDataList['total'];
			$rs['params'] = $data;
			return json($rs);

		}else{
			$monitorPostList = Db::table('monitor_post')->select();
			$this->assign('monitorPostList', $monitorPostList);
			// return json($monitorPostList);
			return $this->fetch();
		}
	}
	/**
	 * 夜班数据统计列表
	 */
	public function nightShiftData(Request $request, RotaDataModel $rotaData){
		$data = input();
		// return json(Db::table('shift_record')->select());
		if($data['getList'] == 1){
			// 初次加载页面时，会先进入else中
			$rs['code'] = 0;
			$rs['msg'] = '加载成功';
			// table所需要的数据
			$rotaDataList = $rotaData->getNightRotaDataList($data)->toArray();
			$memberList = [];
			// 整理出表格数据
			for ($i = 0 ; $i < \sizeof($rotaDataList['data']); $i++) {
				$z = 0;
				for ($j = 0 ; $j < \sizeof($memberList); $j++) {
					if ($rotaDataList['data'][$i]['member_name'] == $memberList[$j]['member_name']){
						break;
					} else {
						$z++;
					}
				}
				if ($z == \sizeof($memberList)) {
					// 配置member字段数据
					\array_push($memberList, [
												'member_name' => $rotaDataList['data'][$i]['member_name'],
												'nightNum'=> 0 // 记录夜班数
											]);
					// 配置日期字段对应夜班数据
					for ($x = 0; $x < \sizeof($rotaDataList['data']); $x++){
						if ($rotaDataList['data'][$x]['member_name'] == $memberList[\sizeof($memberList) - 1]['member_name']) {
							$memberList[\sizeof($memberList) - 1][$rotaDataList['data'][$x]['time_stamp']] = 1;
							$memberList[\sizeof($memberList) - 1]['nightNum']++;
						}
					}
					$memberList[\sizeof($memberList) - 1]['nightShiftFee'] = 80*$memberList[\sizeof($memberList) - 1]['nightNum'];
					$memberList[\sizeof($memberList) - 1]['department'] = '广州运营中心';
					$memberList[\sizeof($memberList) - 1]['job_number'] = $rotaDataList['data'][$i]['job_number'];
				}
			}
			// 整理出动态表头
			$col_arr = [
					['field'=>'department', 'title'=>'处室', 'minWidth'=>'120'],
					['field'=>'member_name', 'title'=>'姓名', 'sort'=>true, 'minWidth'=>'80'],
					['field'=>'job_number', 'title'=>'工号', 'sort'=>true, 'minWidth'=>'80'],
					['field'=>'nightNum', 'title'=>'天数', 'sort'=>true, 'minWidth'=>'80','totalRow'=> true],
					['field'=>'nightShiftFee', 'title'=>'夜班费（80元/天）', 'sort'=>true, 'minWidth'=>'180','totalRow'=> true],
				];
			for ($i = 0 ; $i < \sizeof($rotaDataList['data']); $i++) {
				$z = 0;
				for ($j = 0 ; $j < \sizeof($col_arr); $j++) {
					if ($rotaDataList['data'][$i]['time_stamp'] == $col_arr[$j]['field']){
						break;
					} else {
						$z++;
					}
				}
				if ($z == \sizeof($col_arr)) {
					// 配置表头数据
					\array_push($col_arr,  [
											'field'=>$rotaDataList['data'][$i]['time_stamp'], 
											'title'=>substr($rotaDataList['data'][$i]['time_stamp'], 6), 
											'minWidth'=>'80',
											'totalRow'=> true // 开启合并行
											]);

					// // 配置表格数据中夜班为0的日期
					// for ($x = 0 ; $x < \sizeof($memberList); $x++) {
					// 	if ($memberList[$x][$rotaDataList['data'][$i]['time_stamp']] != 1) {
					// 		$memberList[$x][$rotaDataList['data'][$i]['time_stamp']] = 0;
					// 	}
					// }
				}
			}
			$rs['rotaDataListData'] = $rotaDataList['data'];
			$rs['data'] = $memberList;
			// $rs['memberList'] = $memberList;
			$rs['col_arr'] = $col_arr;
			$rs['count'] = $rotaDataList['total'];
			$rs['params'] = $data;
			return json($rs);

		}else{
			$monitorPostList = Db::table('monitor_post')->select();
			$this->assign('monitorPostList', $monitorPostList);
			// return json($monitorPostList);
			return $this->fetch();
		}
	}
	/**
	 * 节假日加班统计
	 */
	public function holidayOvertimeData(Request $request, RotaDataModel $rotaData){
		$data = input();
		// return json(Db::table('shift_record')->select());
		if($data['getList'] == 1){
			// 初次加载页面时，会先进入else中
			$rs['code'] = 0;
			$rs['msg'] = '加载成功';
			// table所需要的数据
			$rotaDataList = $rotaData->getHolidayOvertimeDataList($data);
			// 整理出开始时间、结束时间以及小时数字段数据
			for ($i = 0 ; $i < \sizeof($rotaDataList['data']); $i++) {
				$item = $rotaDataList['data'][$i];
				if ($i <= 1) {
					$rotaDataList['data'][$i]['start_time'] = date('Y-m-d 00:00:00',strtotime($item['time_stamp']." +1 day"));
					$rotaDataList['data'][$i]['end_time'] = date('Y-m-d 09:00:00',strtotime($item['time_stamp']." +1 day"));
					$rotaDataList['data'][$i]['hours'] = 9;
				} else if ($i >= \sizeof($rotaDataList['data'])-2) {
					$rotaDataList['data'][$i]['start_time'] =  date('Y-m-d 18:00:00',strtotime($item['time_stamp']));
					$rotaDataList['data'][$i]['end_time'] =  date('Y-m-d 00:00:00',strtotime($item['time_stamp']." +1 day"));
					$rotaDataList['data'][$i]['hours'] = 6;
				} else {
					if($item['is_day']) {
						$rotaDataList['data'][$i]['start_time'] = date('Y-m-d 09:00:00',strtotime($item['time_stamp']));
						$rotaDataList['data'][$i]['end_time'] = date('Y-m-d 18:00:00',strtotime($item['time_stamp']));
						$rotaDataList['data'][$i]['hours'] = 9;
					}
					if($item['is_night']) {
						$rotaDataList['data'][$i]['start_time'] =  date('Y-m-d 18:00:00',strtotime($item['time_stamp']));
						$rotaDataList['data'][$i]['end_time'] =  date('Y-m-d 09:00:00',strtotime($item['time_stamp']." +1 day"));
						$rotaDataList['data'][$i]['hours'] = 15;
					}
				}
			}
			// 整理出动态表头
			$col_arr = [
				['field'=>'department', 'title'=>'处室', 'minWidth'=>'120'],
				['field'=>'member_name', 'title'=>'姓名', 'sort'=>true, 'minWidth'=>'80'],
				['field'=>'job_number', 'title'=>'工号', 'sort'=>true, 'minWidth'=>'80'],
				['field'=>'rvdom_remarks', 'title'=>'加班事由', 'sort'=>true, 'minWidth'=>'180'],
				['field'=>'start_time', 'title'=>'起始时间', 'sort'=>true, 'minWidth'=>'180'],
				['field'=>'end_time', 'title'=>'结束时间', 'sort'=>true, 'minWidth'=>'180'],
				['field'=>'hours', 'title'=>'小时数', 'sort'=>true, 'minWidth'=>'180','totalRow'=> true],
			];
			$rs['data'] = $rotaDataList['data'];
			// $rs['memberList'] = $memberList;
			$rs['col_arr'] = $col_arr;
			$rs['count'] = $rotaDataList['total'];
			$rs['params'] = $data;
			return json($rs);

		}else{
			$monitorPostList = Db::table('monitor_post')->select();
			$this->assign('monitorPostList', $monitorPostList);
			// return json($monitorPostList);
			return $this->fetch();
		}
	}
}