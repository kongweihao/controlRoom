<?php
namespace app\system\controller;

use app\common\model\Rota as RotaModel;
use think\Request;
use app\common\model\Role;
use app\common\model\AdminRole;
use think\Controller;
use app\common\utils\SystemConfig;
use think\Db;
use Psr\Log\Logger;
use think\Session;

/**
 * 值班表类
 * @author kongweihao
 *
 */
// class Rota extends Controller{ // 调试用的，没有做权限限制
class Rota extends Auth
{ // 上线用的
	/**
	 * 公共测试接口
	 */
	public function test(Request $request, RotaModel $rota)
	{

	}
	/**
	 * 监控岗位列表
	 */
	public function monitorPostList(Request $request, RotaModel $rota)
	{
		$data = input();
		if ($data['monitorPostList'] == 1) {
			// 请求岗位列表

			//根据搜索岗位类型进行搜素
			$condition['monitorPost_type_id'] = $data['monitorPost_type_id'];
			$condition['day_or_night'] = $data['day_or_night'];
			$monitorPostList = $rota->getMonitorPostList($condition);
			// tp5新框架特性之一是模型对象：
			// 模型对象：模型类实例化猴获得的对象
			// 数据对象：获取到了原始数据的模型对象
			// 原始数据：存放在模型对象的$data属性中（$data是一个数组）
			// 数据对象：说到底，还是一个模型对象，千万不要认为是一个新的对象
			// 模型对象不是我们普通认为的对象，需要用toArray()方法把其转化为我们平时认为的对象
			$monitorPostListArr = $monitorPostList->toArray();
			// return json($condition);
			$rs['code'] = 0;
			$rs['count'] = $monitorPostListArr['total'];
			$rs['msg'] = '加载成功';
			$rs['data'] = $monitorPostListArr['data'];
			// return json($data);
			return $rs;
		} else {
			// 加载页面
			$day_or_night_search_arr = [];
			$day_or_night_search_arr[0]['value'] = -1;
			$day_or_night_search_arr[0]['name'] = '白班 / 夜班';
			$day_or_night_search_arr[1]['value'] = 1;
			$day_or_night_search_arr[1]['name'] = '仅白班';
			$day_or_night_search_arr[2]['value'] = 2;
			$day_or_night_search_arr[2]['name'] = '仅夜班';
			$day_or_night_search_arr[3]['value'] = 3;
			$day_or_night_search_arr[3]['name'] = '含白班';
			$day_or_night_search_arr[4]['value'] = 4;
			$day_or_night_search_arr[4]['name'] = '含夜班';
			$day_or_night_search_arr[5]['value'] = 5;
			$day_or_night_search_arr[5]['name'] = '全天';
			$monitorPostTypeList = Db::table('monitorpost_type')->select();
			// return json($monitorPostList);
			$monitorPostList = Db::table('monitor_post')->where('is_active', 1)->select();
			$monitorPostList_len = sizeof($monitorPostList);
			$this->assign('monitorPostList', $monitorPostList);
			$this->assign('monitorPostList_len', $monitorPostList_len);
			$this->assign('monitorPostTypeList', $monitorPostTypeList);
			$this->assign('monitorPostTypeListLen', \sizeof($monitorPostTypeList));
			$this->assign('monitorPostTypeList_selected', $data['monitorPost_type_id']);
			$this->assign('day_or_night', $data['day_or_night']);
			$this->assign('day_or_night_search_arr', $day_or_night_search_arr);
			return $this->fetch();
		}
	}
	/**
	 * 增加岗位
	 */
	public function addMonitorPost(Request $request, RotaModel $rota)
	{
		$data = input();
		if ($data['checked_postName'] == 1) {
			// 参数为checked_postName时，为检查岗位名是否存在
			$post_name = input('post_name');
			$check_post_name = Db::table('monitor_post')->where('post_name', $post_name)->select();
			return sizeof($check_post_name);
		} else {
			$monitorPost_type = Db::table('monitorpost_type')->select();
			// select() 查询全部
			$data['monitorPost_type_json'] = $monitorPost_type;
			// 如果前端别名为空，则默认为post_name字段
			$data['mp_alias'] || $data['mp_alias'] = $data['post_name'];
			// 顺序为空值则默认为1000
			$data['sort_day'] != null || $data['sort_day'] = 1000;
			$data['sort_night'] != null || $data['sort_night'] = 1000;
			// 数据回填
			$this->assign('data', $data);
			unset($data['monitorPost_type_json']); //删掉monitorPost_type_json字段
			$rs = Db::table('monitor_post')->insert($data);
			return redirect('@system/monitorPostList')->with('successTs', '添加岗位成功');
		}
	}
	/**
	 * 编辑岗位
	 */
	public function editMonitorPost(Request $request, RotaModel $rota)
	{
		$data = input();
		if ($data['update_one_ziduan'] == 1) {
			// 只更新一个字段的情况返回$rs即可
			unset($data['update_one_ziduan']);
			$rs = Db::table('monitor_post')->where('id', $data['id'])->update($data);
			return $rs;
		} else {
			// 如果前端别名为空，则默认为post_name字段
			$data['mp_alias'] || $data['mp_alias'] = $data['post_name'];
			// 顺序为空值则默认为1000
			$data['sort_day'] != null || $data['sort_day'] = 1000;
			$data['sort_night'] != null || $data['sort_night'] = 1000;
			$rs = Db::table('monitor_post')->where('id', $data['id'])->update($data);
			if ($rs == 1) {
				return redirect('@system/monitorPostList')->with('successTs', '修改成功');
			} else {
				return redirect('@system/monitorPostList')->with('successTs', '没有修改');
			}
		}
	}
	/**
	 * 删除岗位
	 */
	public function deleteMonitorPost(Request $request, RotaModel $rota)
	{
		// delete()的这种写法默认删除主键数据，数组格式也可以删除
		$id = input('id/a'); //  默认为数组格式，只有一个数据则作为单元素数组
		$rs = Db::table('monitor_post')->delete($id);
		return $rs;
	}
	/**
	 * 值班表列表·权
	 */
	public function rotaList(RotaModel $rota)
	{
		$data = input();
		// 预处理一些数据
		// 二维表格数据处理特殊，所以把一些公用的数据拿出来预处理
		$time_stamp = $data['time_stamp'];
		// return json($time_stamp);
		if ($time_stamp == '') {
			$y = getdate()['year'];
			$m = getdate()['mon'] > 9 ? getdate()['mon'] : '0' . getdate()['mon'];
			$time_stamp = $y . $m;
		}
		$condition = array();
		$condition['time_stamp'] = array('like', '%' . $time_stamp . '%'); //like是模糊搜索sql
		// return json($condition);
		$rotaList = $rota->getRotaList($condition);
		// return json($rotaList);
		$thisMon_monitorPost = [];
		// 获取该班表岗位
		$rsLen = sizeof($rotaList);
		for ($i = 0; $i < $rsLen; $i++) {
			if ($rotaList[0]['time_stamp'] == $rotaList[$i]['time_stamp']) {
				array_push($thisMon_monitorPost, $rotaList[$i]);
			} else {
				break;
			}
		}

		$thisMon_monitorPost_len = sizeof($thisMon_monitorPost);
		// 请求表格数据
		if ($data['rotaList'] == 1) {
			$rs['code'] = 0;
			$rs['msg'] = '加载成功';
			$rs['num'] = $rsLen;
			$rotaData = array();
			// return json($data);
			$eachDay;
			// 将值班数据按日处理
			// return json($rs['count']);
			// 获取账号ID
			$adminId = Session::get('adminId');
			//该管理员关联的监控人员
			$adminMember = Db::table('member')
				->alias(['admin_member' => 'am', 'admin' => 'a', 'member' => 'm'])
				->where('am.admin_id', $adminId)
				->join('admin_member am', 'm.id=am.member_id')
				->find();
			$rs['count'] = 0;
			for ($i = 0; $i < $rs['num']; ) {
				$eachDay['time_stamp'] = $rotaList[$i]['time_stamp'];
				$eachDay['week'] = $rotaList[$i]['week'];
				// 整理每日值班人员与岗位信息
				for ($j = 0; $j < $thisMon_monitorPost_len; $j++) {

					// 获取每个岗位的换班记录
					$shiftRecordCondtion = array();
					$shiftRecordCondtion['rota_time_stamp'] = array('like', '%' . $eachDay['time_stamp'] . '%');
					$shiftRecordCondtion['monitor_post_name'] = $thisMon_monitorPost[$j]['monitor_post_name'];
					// 取出换班记录，按creat_time字段排序基本保证数据记录顺序就是实际换班的顺序
					$shiftRecordList = Db::table('shift_record')->where($shiftRecordCondtion)->order(['create_time ASC'])->select();
					$shiftRecordListLen = sizeof($shiftRecordList);
					$shiftRecord = '';
					if ($rotaList[$i + $j]['member_name'] != '') {
						$rotaList[$i + $j]['member_name'] = trim($rotaList[$i + $j]['member_name']) == $adminMember['name'] ? ("<span style='color:red'>我</span>") : $rotaList[$i + $j]['member_name'];
					}
					if ($shiftRecordListLen != 0) {
						for ($k = 0; $k < $shiftRecordListLen; $k++) {
							$shiftRecord = $shiftRecord . $shiftRecordList[$k]['guy_be_replaced'] . '->';
						}
						$shiftRecord = $shiftRecord . $shiftRecordList[$shiftRecordListLen - 1]['replacement'] . '->';
						$eachDay[$thisMon_monitorPost[$j]['monitor_post_name']] = $rotaList[$i + $j]['member_name'] . '<span style="color:red">*</span><br>（换班记录：' . $shiftRecord . '）';
					} else {
						$eachDay[$thisMon_monitorPost[$j]['monitor_post_name']] = $rotaList[$i + $j]['member_name'];
					}

				}
				$i += $thisMon_monitorPost_len;
				$rs['count']++;
				array_push($rotaData, $eachDay);
			}
			$rs['thisMon_monitorPost'] = $thisMon_monitorPost; //本班表岗位
			$rs['data'] = $rotaData;

			return json($rs);
		}
		if ($data['monitorPostList'] == 1) {
			$rs['code'] = 0;
			$rs['msg'] = '加载成功';
			return json($rs);
		}
		$monitorPostList = Db::table('monitor_post')->where('is_active', 1)->select(); // 仅加载激活岗位
		$monitorPostList_len = sizeof($monitorPostList);
		// return json($monitorPostList);
		$frozenSet = Db::table('frozen_rota')->select();
		$this->assign('frozenSet', $frozenSet);
		$this->assign('time_stamp', $time_stamp);
		$this->assign('monitorPostList', $monitorPostList);
		$this->assign('monitorPostList_len', $monitorPostList_len);
		$this->assign('thisMon_monitorPost', $thisMon_monitorPost);
		$this->assign('thisMon_monitorPost_len', $thisMon_monitorPost_len);
		// return json($thisMon_monitorPost);
		return $this->fetch();
	}
	/**
	 * 每日值班表列表·权
	 */
	public function rotaList_day(RotaModel $rota)
	{
		//每日值班表列表·权，仅查看当天班表，并且展示详细信息
		$data = input('param.');
		$time_stamp = $data['time_stamp'];
		if ($time_stamp == '') {
			$y = getdate()['year'];
			$m = getdate()['mon'] > 9 ? getdate()['mon'] : '0' . getdate()['mon'];
			$d = getdate()['mday'] > 9 ? getdate()['mday'] : '0' . getdate()['mday'];
			$time_stamp = $y . $m . $d;
		}
		$rota_day = Db::table('rota')
			->alias(['rota' => 'r', 'member' => 'm'])
			->where(['r.time_stamp' => $time_stamp, 'r.is_day' => 1])
			->join('member m', 'r.member_name=m.name')
			->order(['r.sort_day ASC']) //DESC降序
			->select();
		$rota_night = Db::table('rota')
			->alias(['rota' => 'r', 'member' => 'm'])
			->where(['time_stamp' => $time_stamp, 'is_night' => 1])
			->join('member m', 'r.member_name=m.name')
			->order(['r.sort_night ASC']) //DESC降序
			->select();

		// 为每个岗位添加下拉人员选择框
		$rota_day_len = sizeof($rota_day);
		for ($i = 0; $i < $rota_day_len; $i++) {
			if ($rota_day[$i]['monitorPost_type_id'] != null) {
				$rota_day[$i]['monitorPost_type_arr'] = Db::table('member')
					->where('monitorPost_type_id', $rota_day[$i]['monitorPost_type_id'])
					->select();
			} else { //加入出现人员没有岗位的情况，则加载整个值班组
				$rota_day[$i]['monitorPost_type_arr'] = Db::table('member')
					->where('group', '监控组')
					->select();
			}
		}
		$rota_night_len = sizeof($rota_night);
		for ($i = 0; $i < $rota_night_len; $i++) {
			if ($rota_night[$i]['monitorPost_type_id'] != null) {
				$rota_night[$i]['monitorPost_type_arr'] = Db::table('member')
					->where('monitorPost_type_id', $rota_night[$i]['monitorPost_type_id'])
					->select();
			} else { //假如出现人员没有岗位的情况，则加载整个值班组
				$rota_night[$i]['monitorPost_type_arr'] = Db::table('member')
					->where('group', '监控组')
					->select();
			}
		}

		$memberId = Db::table('admin_member')->where('admin_id', $_SESSION['dd_']['adminId'])->find()['member_id'];
		$memberId ? $adminMemberName = Db::table('member')->where('id', $memberId)->find()['name'] : $adminMemberName = '高权限账号';
		$this->assign('adminMemberName', $adminMemberName);
		$this->assign('rota_day', $rota_day);
		$this->assign('rota_night', $rota_night);
		return $this->fetch();
	}

	/**
	 * 冻结值班表·权
	 */
	public function frozenRota(RotaModel $rota)
	{
		$data = input('param.');

		$rs_update = Db::table('frozen_rota')
			->update($data);
		return $rs_update;
	}
	/**
	 * 导入值班表前先进行检查，是否已存在导入月份的班表
	 */
	public function checkRota(Request $request, RotaModel $rota)
	{
		$time_stamp_y_m = input('time_stamp_y_m');
		$condition['time_stamp'] = array('like', '%' . $time_stamp_y_m . '%'); //like是模糊搜索sql
		$rs = $rota->getRotaList($condition);
		// return $rs;
		if ($rs) {
			return 1;
		} else {
			return 0;
		}

	}
	/**
	 * 导入值班表
	 */
	public function addRota(Request $request, RotaModel $rota)
	{
		$rs = [];
		$rs['code'] = 1;
		$rs['msg'] = '操作成功';
		$rs['errName'] = [];
		$data = input();
		if ($data['is_addRota'] == 1) { //只是打开页面则不进入下面
			$rotaData = $data['rotaData'];
			// 导入前先检查导入人员姓名是否有误
			for ($i = 0; $i < \sizeof($rotaData); $i++) {
				if ($rotaData[$i]['member_name'] && !Db::table('member')->where('name', $rotaData[$i]['member_name'])->find()) {
					$rs['code'] = -2;
					\array_push($rs['errName'], $rotaData[$i]['member_name']);
				}
			}
			if ($rs['code'] == 1) {
				$insertAllRotaRs = Db::table('rota')->insertAll($rotaData);
				if (!$insertAllRotaRs) {
					$rs['code'] = -1;
					$rs['msg'] = '操作失败，请联系管理员';
				}
			} else if ($rs['code'] == -2) {
				// code == 2时说明名字有误，需要删掉改次导入月份的数据
				$condition['time_stamp'] = array('like', '%' . substr($rotaData[0]['time_stamp'], 0, 6) . '%'); //like是模糊搜索sql
				$rsDelete = $rota->deleteRota($condition);
			}
			return json($rs);
		}
		$monitorPost = Db::table('monitor_post')
			->alias(['monitor_post' => 'mp', 'monitorpost_type mpt'])
			->where('is_active', 1)
			->order(['sort ASC'])
			->Join('monitorpost_type mpt', 'mp.monitorPost_type_id = mpt.monitorPost_type_id')
			->select();
		$this->assign('monitorPost', $monitorPost);
		// return json($monitorPost);
		$this->assign('data', input('data/a'));
		return $this->fetch();

	}
	/**
	 * 导入附件形式批量添加值班表
	 */

	/**
	 * 编辑值班表·权
	 */
	public function editRota(Request $request, RotaModel $rota)
	{
		$data = input();
		// 预处理一些数据
		// 二维表格数据处理特殊，所以把一些公用的数据拿出来预处理
		$time_stamp = $data['time_stamp'];
		// return json($time_stamp);
		$y = getdate()['year'];
		$m = getdate()['mon'] > 9 ? getdate()['mon'] : '0' . getdate()['mon'];
		$d = getdate()['mday'] > 9 ? getdate()['mday'] : '0' . getdate()['mday'];
		if ($time_stamp == '') {
			$time_stamp = $y . $m;
		}
		// 当前时间
		$today_time_stamp = $y . $m . $d;
		// return json($today_time_stamp);
		$condition = array();
		$condition['time_stamp'] = array('like', '%' . $time_stamp . '%'); //like是模糊搜索sql
		// return json($condition);
		$rotaList = $rota->getRotaList($condition);
		// return json($rotaList);
		$thisMon_monitorPost = [];
		// 获取该班表岗位
		$rsLen = sizeof($rotaList);
		for ($i = 0; $i < $rsLen; $i++) {
			if ($rotaList[0]['time_stamp'] == $rotaList[$i]['time_stamp']) {
				// 获取本月岗位，直接取班表数据中随便一个time_stamp相同的那组数据即可
				array_push($thisMon_monitorPost, $rotaList[$i]);
			} else {
				break;
			}
		}
		// return json($thisMon_monitorPost);
		$thisMon_monitorPost_len = sizeof($thisMon_monitorPost);
		// return json($thisMon_monitorPost_len);

		// 请求表格数据
		if ($data['rotaList'] == 1) {
			$rs['code'] = 0;
			$rs['msg'] = '加载成功';
			$rs['num'] = $rsLen;
			$rotaData = array();
			// return json($data);
			$eachDay;
			// 将值班数据按日处理
			// return json($rs['count']);
			$rs['count'] = 0;
			for ($i = 0; $i < $rs['num']; ) {
				$eachDay['time_stamp'] = $rotaList[$i]['time_stamp'];
				$eachDay['week'] = $rotaList[$i]['week'];
				// 整理每日值班人员与岗位信息
				for ($j = 0; $j < $thisMon_monitorPost_len; $j++) {
					$eachDay[$thisMon_monitorPost[$j]['monitor_post_name']] = $rotaList[$i + $j]['member_name'];
				}
				$i += $thisMon_monitorPost_len;
				$rs['count']++;
				array_push($rotaData, $eachDay);
			}
			$rs['thisMon_monitorPost'] = $thisMon_monitorPost; //本班表岗位
			// is_active 用于判断该日班表是否已成为历史，不给修改
			for ($i = 0; $i < sizeof($rotaData); $i++) {
				$rotaData[$i]['time_stamp'] >= $today_time_stamp ? $rotaData[$i]['is_active'] = 1 : $rotaData[$i]['is_active'] = 0;
			}
			$rs['data'] = $rotaData;

			return json($rs);
		}
		if ($data['monitorPostList'] == 1) {
			$rs['code'] = 0;
			$rs['msg'] = '加载成功';
			return json($rs);
		}
		$monitorPostList = Db::table('monitor_post')->select();
		$monitorPostList_len = sizeof($monitorPostList);

		// 获取各个岗位的对应人员
		$monitorPost_gay_data = Db::table('member')
			->where('monitorPost_type_id', '<>', '') //'<>' 表示不等于
			->select();
		$monitorPost_gay_data_len = sizeof($monitorPost_gay_data);

		for ($i = 0; $i < $monitorPost_gay_data_len; $i++) {
			$monitorPost_gay_data[$i]['monitorPost_type_id'] = explode(',', $monitorPost_gay_data[$i]['monitorPost_type_id']);
			# code...
		}
		$monitorPost_gay = array();
		$monitorPost_type = Db::table('monitorpost_type')->select();
		$monitorPost_type_len = sizeof($monitorPost_type);
		// return json($monitorPost_type);
		// return json($monitorPost_gay_data);
		for ($i = 0; $i < $monitorPost_type_len; $i++) {
			$monitorPost_gay[$monitorPost_type[$i]['monitorPost_type']] = array();
			for ($j = 0; $j < $monitorPost_gay_data_len; $j++) {
				$monitorPost_gay_data_monitorPostTypeIdLen = sizeof($monitorPost_gay_data[$j]['monitorPost_type_id']);
				for ($k = 0; $k < $monitorPost_gay_data_monitorPostTypeIdLen; $k++) {
					if ($monitorPost_type[$i]['monitorPost_type_id'] == $monitorPost_gay_data[$j]['monitorPost_type_id'][$k]) {
						array_push($monitorPost_gay[$monitorPost_type[$i]['monitorPost_type']], $monitorPost_gay_data[$j]);
					}
				}
			}
		}
		// return json($monitorPost_gay);
		// $some_gay = Db::table('member')->paginate(10);
		// return json($some_gay);
		// return json($monitorPostList);

		$memberId = Db::table('admin_member')->where('admin_id', $_SESSION['dd_']['adminId'])->find()['member_id'];
		$memberId ? $adminMemberName = Db::table('member')->where('id', $memberId)->find()['name'] : $adminMemberName = '高权限账号';
		$this->assign('adminMemberName', $adminMemberName);
		$this->assign('time_stamp', $time_stamp);
		$this->assign('today_time_stamp', $today_time_stamp);
		$this->assign('monitorPost_gay', $monitorPost_gay);
		$this->assign('monitorPostList', $monitorPostList);
		$this->assign('monitorPostList_len', $monitorPostList_len);
		$this->assign('thisMon_monitorPost', $thisMon_monitorPost);
		$this->assign('thisMon_monitorPost_len', $thisMon_monitorPost_len);
		// return json($thisMon_monitorPost);
		return $this->fetch();
	}
	/**
	 * 更新值班表·权
	 */
	public function updateRota(Request $request, RotaModel $rota)
	{
		$data = input('param.');
		$y = getdate()['year'];
		$m = getdate()['mon'] > 9 ? getdate()['mon'] : '0' . getdate()['mon'];
		$d = getdate()['mday'] > 9 ? getdate()['mday'] : '0' . getdate()['mday'];
		// 当前时间
		$today_time_stamp = $y . $m . $d;
		if ($today_time_stamp > $data['rota_time_stamp']) {
			// 不允许修改历史班表
			return 0;
		}
		$is_conflict = Db::table('rota')
			->where(['time_stamp' => $data['rota_time_stamp'], 'monitor_post_name' => $data['monitor_post_name']])
			->find();
		// 冲突检测 如果原班表的人已经和最新班表不同，说明发生了冲突
		if ($is_conflict['member_name'] != $data['guy_be_replaced']) {
			return -1;
		}
		$rs_insert = Db::table('shift_record')->insert($data);

		if ($rs_insert == 1) {
			$rs_update = Db::table('rota')
				->where(['time_stamp' => $data['rota_time_stamp'], 'monitor_post_name' => $data['monitor_post_name']])
				->update(['member_name' => $data['replacement']]);
		} else {
			return $rs_insert; //1成功 0失败
		}
		return $rs_update; //1成功 0失败
	}
	/**
	 * 删除值班表·权
	 */
	public function deleteRota(Request $request, RotaModel $rota)
	{
		$data = input('param.');
		$condition['time_stamp'] = array('like', '%' . $data['time_stamp'] . '%'); //like是模糊搜索sql
		$rs = $rota->deleteRota($condition);
		return $rs;
	}


	/**
	 * 值班表列表·普通
	 */
	public function rotaListOrdinary(RotaModel $rota)
	{
		$data = input();
		// 预处理一些数据
		// 二维表格数据处理特殊，所以把一些公用的数据拿出来预处理
		// 获取当前用户对应监控人员的数据
		$memberId = Db::table('admin_member')->where('admin_id', Session::get('adminId'))->find();
		$adminMember = Db::table('member')->where('id', $memberId['member_id'])->find();
		$adminMember['monitorPost_type_id'] = explode(',', $adminMember['monitorPost_type_id']);
		$adminMember['monitorPost_type'] = array();
		for ($i = 0; $i < sizeof($adminMember['monitorPost_type_id']); $i++) {
			$i_monitorPost_type = Db::table('monitorpost_type')->where('monitorPost_type_id', $adminMember['monitorPost_type_id'][$i])->find()['monitorPost_type'];
			array_push($adminMember['monitorPost_type'], $i_monitorPost_type);
		}
		$time_stamp = $data['time_stamp'];
		// return json($time_stamp);
		if ($time_stamp == '') {
			$y = getdate()['year'];
			$m = getdate()['mon'] > 9 ? getdate()['mon'] : '0' . getdate()['mon'];
			$time_stamp = $y . $m;
		}
		$condition = array();

		$condition['time_stamp'] = array('like', '%' . $time_stamp . '%');
		/**
		 * 按月获取节假日数据
		 * ROLLAPI
		 * 普通会员限制QPS为1
		 * 既每秒只能请求一次接口
		 */
		$month = $time_stamp;
		$rollHolidayAPI = 'https://www.mxnzp.com/api/holiday/list/month/' . $month . '?ignoreHoliday=false&app_id=n0suksemrqafttpd&app_secret=T1d6Z0wraTdYeTRjZktnc2VoeDUrQT09';
		$holidayData = json_decode(file_get_contents($rollHolidayAPI), true);

		// is_show_for_rotaListOrdinary_page = 0 时不显示该字段
		$condition['is_show_for_rotaListOrdinary_page'] = 1;

		// return json($condition);
		$rotaList = $rota->getRotaList($condition);
		// return json($rotaList);
		$thisMon_monitorPost = [];
		// 获取该班表岗位
		$rsLen = sizeof($rotaList);
		for ($i = 0; $i < $rsLen; $i++) {
			if ($rotaList[0]['time_stamp'] == $rotaList[$i]['time_stamp']) {
				if ($adminMember['monitorPost_type'] != [null]) {
					// 个性化显示，默认显示有自己有关的岗位
					$rotaList[$i]['hide'] = true;
					for ($j = 0; $j < sizeof($adminMember['monitorPost_type']); $j++) {
						if ($adminMember['monitorPost_type'][$j] == $rotaList[$i]['monitorPost_type']) {
							$rotaList[$i]['hide'] = false;
							break;
						}
					}
				}
				array_push($thisMon_monitorPost, $rotaList[$i]);
			} else {
				break;
			}
		}
		$thisMon_monitorPost_len = sizeof($thisMon_monitorPost);
		// 请求表格数据
		if ($data['rotaList'] == 1) {
			$rs['code'] = 0;
			$rs['msg'] = '加载成功';
			$rs['num'] = $rsLen;
			$rotaData = array();
			// return json($data);
			$eachDay = [];
			// 将值班数据按日处理
			// return json($rs['count']);
			// 获取账号ID
			$adminId = Session::get('adminId');
			//该管理员关联的监控人员
			$adminMember = Db::table('member')
				->alias(['admin_member' => 'am', 'admin' => 'a', 'member' => 'm'])
				->where('am.admin_id', $adminId)
				->join('admin_member am', 'm.id=am.member_id')
				->find();
			$rs['count'] = 0;
			for ($i = 0; $i < $rs['num']; ) {
				$eachDay['time_stamp'] = $rotaList[$i]['time_stamp'];
				$eachDay['week'] = $rotaList[$i]['week'];
				// 整理每日值班人员与岗位信息
				for ($j = 0; $j < $thisMon_monitorPost_len; $j++) {

					// 获取每个岗位的换班记录
					$shiftRecordCondtion = array();
					$shiftRecordCondtion['rota_time_stamp'] = array('like', '%' . $eachDay['time_stamp'] . '%');
					$shiftRecordCondtion['monitor_post_name'] = $thisMon_monitorPost[$j]['monitor_post_name'];

					// 取出换班记录，按creat_time字段排序基本保证数据记录顺序就是实际换班的顺序
					$shiftRecordList = Db::table('shift_record')->where($shiftRecordCondtion)->order(['create_time ASC'])->select();
					$shiftRecordListLen = sizeof($shiftRecordList);
					$shiftRecord = '';
					if ($rotaList[$i + $j]['member_name'] != '') {
						$rotaList[$i + $j]['member_name'] = trim($rotaList[$i + $j]['member_name']) == $adminMember['name'] ? ("<span style='color:red'>我</span>") : $rotaList[$i + $j]['member_name'];
					}
					if ($shiftRecordListLen != 0) {
						for ($k = 0; $k < $shiftRecordListLen; $k++) {
							$shiftRecord = $shiftRecord . $shiftRecordList[$k]['guy_be_replaced'] . '->';
						}
						$shiftRecord = $shiftRecord . $shiftRecordList[$shiftRecordListLen - 1]['replacement'] . '->';
						$eachDay[$thisMon_monitorPost[$j]['monitor_post_name']] = $rotaList[$i + $j]['member_name'] . '<span style="color:red">*</span><br>（换班记录：' . $shiftRecord . '）';
					} else {
						$eachDay[$thisMon_monitorPost[$j]['monitor_post_name']] = $rotaList[$i + $j]['member_name'];
					}

				}
				$i += $thisMon_monitorPost_len;
				$rs['count']++;
				array_push($rotaData, $eachDay);
			}
			$rs['thisMon_monitorPost'] = $thisMon_monitorPost; //本班表岗位
			$rs['data'] = $rotaData;

			// 将节假日信息假日加入值班表数组中，仅节假日接口获取成功时
			if ($holidayData['code'] == 1) {
				for ($i = 0; $i < sizeof($rs['data']); $i++) {
					$rs['data'][$i]['holidayData'] = $holidayData['data'][$i];
					if ($holidayData['data'][$i]['type'] == 0) {
						// type 类型 0 工作日 1 假日 2 节假日 如果ignoreHoliday参数为true，这个字段不返回
						$rs['data'][$i]['time_stamp'] =
							'<div>'
							. $rs['data'][$i]['time_stamp']
							. '<div><span>'
							. $holidayData['data'][$i]['typeDes']
							. '</span>   <span>'
							. $holidayData['data'][$i]['lunarCalendar']
							. '</span>   <span>'
							. $holidayData['data'][$i]['solarTerms']
							. '</span></div><div><span>宜：'
							. $holidayData['data'][$i]['suit']
							. '</span><br><span>忌：'
							. $holidayData['data'][$i]['avoid']
							. "</span></div></div>";
					} else if ($holidayData['data'][$i]['type'] == 1) {
						$rs['data'][$i]['time_stamp'] =
							'<div><span style="font-weight:bold">'
							. $rs['data'][$i]['time_stamp']
							. '</span><div><span>'
							. $holidayData['data'][$i]['typeDes']
							. '</span>   <span>'
							. $holidayData['data'][$i]['lunarCalendar']
							. '</span>   <span>'
							. $holidayData['data'][$i]['solarTerms']
							. '</span></div><div><span>宜：'
							. $holidayData['data'][$i]['suit']
							. '</span><br><span>忌：'
							. $holidayData['data'][$i]['avoid']
							. "</span></div></div>";
					} else if ($holidayData['data'][$i]['type'] == 2) {
						$rs['data'][$i]['time_stamp'] =
							'<div style="color:red;transform:translateY(-5px)">'
							. $rs['data'][$i]['time_stamp']
							. '<div style="transform: scale(0.6) translateY(-28px)"><span>'
							. $holidayData['data'][$i]['typeDes']
							. '</span>   <span>'
							. $holidayData['data'][$i]['lunarCalendar']
							. '</span>   <span>'
							. $holidayData['data'][$i]['solarTerms']
							. '</span></div><div><span>宜：'
							. $holidayData['data'][$i]['suit']
							. '</span><br><span>忌：'
							. $holidayData['data'][$i]['avoid']
							. "</span></div></div>";
					}
				}
			}
			return json($rs);
		}
		if ($data['monitorPostList'] == 1) {
			$rs['code'] = 0;
			$rs['msg'] = '加载成功';
			return json($rs);
		}
		$monitorPostList = Db::table('monitor_post')->where(['is_active' => 1])->select();
		$monitorPostList_len = sizeof($monitorPostList);
		// return json($monitorPostList);
		$this->assign('time_stamp', $time_stamp);
		$this->assign('monitorPostList', $monitorPostList);
		$this->assign('monitorPostList_len', $monitorPostList_len);
		$this->assign('thisMon_monitorPost', $thisMon_monitorPost);
		$this->assign('thisMon_monitorPost_len', $thisMon_monitorPost_len);
		// return json($thisMon_monitorPost);
		return $this->fetch();
	}
	/**
	 * 编辑值班表·普通
	 */
	public function editRotaOrdinary(Request $request, RotaModel $rota)
	{
		$data = input();
		// 预处理一些数据
		// 二维表格数据处理特殊，所以把一些公用的数据拿出来预处理
		// 获取当前用户对应监控人员的数据
		$memberId = Db::table('admin_member')->where('admin_id', $data['adminId'])->find();
		$adminMember = Db::table('member')->where('id', $memberId['member_id'])->find();
		$adminMember['monitorPost_type_id'] = explode(',', $adminMember['monitorPost_type_id']);
		$adminMember['monitorPost_type'] = array();
		for ($i = 0; $i < sizeof($adminMember['monitorPost_type_id']); $i++) {
			$i_monitorPost_type = Db::table('monitorpost_type')->where('monitorPost_type_id', $adminMember['monitorPost_type_id'][$i])->find()['monitorPost_type'];
			array_push($adminMember['monitorPost_type'], $i_monitorPost_type);
		}
		$time_stamp = $data['time_stamp'];
		// return json($time_stamp);
		$y = getdate()['year'];
		$m = getdate()['mon'] > 9 ? getdate()['mon'] : '0' . getdate()['mon'];
		$d = getdate()['mday'] > 9 ? getdate()['mday'] : '0' . getdate()['mday'];
		if ($time_stamp == '') {
			$time_stamp = $y . $m;
		}
		// 当前时间
		$today_time_stamp = $y . $m . $d;
		// return json($today_time_stamp);
		$condition = array();
		$condition['time_stamp'] = array('like', '%' . $time_stamp . '%'); //like是模糊搜索sql
		// return json($condition);
		$rotaList = $rota->getRotaList($condition);
		// return json($rotaList);
		$thisMon_monitorPost = [];
		// 获取该班表岗位
		$rsLen = sizeof($rotaList);

		for ($i = 0; $i < $rsLen; $i++) {
			if ($rotaList[0]['time_stamp'] == $rotaList[$i]['time_stamp']) {
				if ($adminMember['monitorPost_type'] != [null]) {
					// 个性化显示，默认显示有自己有关的岗位
					$rotaList[$i]['hide'] = true;
					for ($j = 0; $j < sizeof($adminMember['monitorPost_type']); $j++) {
						if ($adminMember['monitorPost_type'][$j] == $rotaList[$i]['monitorPost_type']) {
							$rotaList[$i]['hide'] = false;
							break;
						}
					}
				}
				// 获取本月岗位，直接取班表数据中随便一个time_stamp相同的那组数据即可
				array_push($thisMon_monitorPost, $rotaList[$i]);
			} else {
				break;
			}
		}
		// return json($thisMon_monitorPost);
		$thisMon_monitorPost_len = sizeof($thisMon_monitorPost);
		// return json($thisMon_monitorPost_len);

		$frozenSet = Db::table('frozen_rota')->where('is_frozen', 1)->select();
		// 请求表格数据
		if ($data['rotaList'] == 1) {
			$rs['code'] = 0;
			$rs['msg'] = '加载成功';
			$rs['num'] = $rsLen;
			$rotaData = array();
			// return json($data);
			$eachDay;
			// 将值班数据按日处理
			// return json($rs['count']);
			$rs['count'] = 0;
			for ($i = 0; $i < $rs['num']; ) {
				$eachDay['time_stamp'] = $rotaList[$i]['time_stamp'];
				$eachDay['week'] = $rotaList[$i]['week'];
				// 整理每日值班人员与岗位信息
				for ($j = 0; $j < $thisMon_monitorPost_len; $j++) {
					$eachDay[$thisMon_monitorPost[$j]['monitor_post_name']]['name'] = $rotaList[$i + $j]['member_name'];
					$eachDay[$thisMon_monitorPost[$j]['monitor_post_name']]['is_active'] = 0;

					// 判断每条数据是否被冻结
					$eachDay[$thisMon_monitorPost[$j]['monitor_post_name']]['is_frozen'] = 0;
					for ($z = 0; $z < sizeof($frozenSet); $z++) {
						if ($frozenSet[$z]['is_frozen'] == '1') {
							// 开始日期相同的情况
							if ($rotaList[$i + $j]['time_stamp'] == $frozenSet[$z]['frozen_timestamp1'] && $eachDay[$thisMon_monitorPost[$j]['monitor_post_name']]['is_frozen'] != 1) {
								if ($frozenSet[$z]['banci1'] == 1) {
									$eachDay[$thisMon_monitorPost[$j]['monitor_post_name']]['is_frozen'] = 1;
								} else if ($frozenSet[$z]['banci1'] == 2 && $rotaList[$i + $j]['is_night'] == 1) {
									$eachDay[$thisMon_monitorPost[$j]['monitor_post_name']]['is_frozen'] = 1;
								}
							}
							// 结束日期相同的情况
							if ($rotaList[$i + $j]['time_stamp'] == $frozenSet[$z]['frozen_timestamp2'] && $eachDay[$thisMon_monitorPost[$j]['monitor_post_name']]['is_frozen'] != 1) {
								if ($frozenSet[$z]['banci2'] == 2) {
									$eachDay[$thisMon_monitorPost[$j]['monitor_post_name']]['is_frozen'] = 1;
								} else if ($frozenSet[$z]['banci2'] == 1 && $rotaList[$i + $j]['is_day'] == 1) {
									$eachDay[$thisMon_monitorPost[$j]['monitor_post_name']]['is_frozen'] = 1;
								}
							}
							// 中间的情况
							if ($rotaList[$i + $j]['time_stamp'] > $frozenSet[$z]['frozen_timestamp1'] && $rotaList[$i + $j]['time_stamp'] < $frozenSet[$z]['frozen_timestamp2'] && $eachDay[$thisMon_monitorPost[$j]['monitor_post_name']]['is_frozen'] != 1) {
								$eachDay[$thisMon_monitorPost[$j]['monitor_post_name']]['is_frozen'] = 1;
							}
						}
					}
					for ($k = 0; $k < sizeof($adminMember['monitorPost_type']); $k++) {
						// 仅当操作账号关联的监控人员岗位类别与当前值班表数据中岗位对应的岗位类别一致时，is_active为1
						if ($adminMember['monitorPost_type'][$k] == $rotaList[$i + $j]['monitorPost_type']) {
							$eachDay[$thisMon_monitorPost[$j]['monitor_post_name']]['is_active'] = 1;
							break; //只要一次为1 则停止循环
						}
					}
					// $rotaData[$i]['monitorPost_type_id'] = $adminMember['monitorPost_type_id'];

				}
				$i += $thisMon_monitorPost_len;
				$rs['count']++;
				array_push($rotaData, $eachDay);
			}
			$rs['thisMon_monitorPost'] = $thisMon_monitorPost; //本班表岗位
			// is_active 用于判断该日班表是否已成为历史，不给修改
			for ($i = 0; $i < sizeof($rotaData); $i++) {
				$rotaData[$i]['time_stamp'] >= $today_time_stamp ? $rotaData[$i]['is_active'] = 1 : $rotaData[$i]['is_active'] = 0;
				// 为简化逻辑，将对人员类型的判断交给前端
			}
			$rs['data'] = $rotaData;

			return json($rs);
		}
		if ($data['monitorPostList'] == 1) {
			$rs['code'] = 0;
			$rs['msg'] = '加载成功';
			return json($rs);
		}
		$monitorPostList = Db::table('monitor_post')->select();
		$monitorPostList_len = sizeof($monitorPostList);
		$memberId = Db::table('admin_member')->where('admin_id', $_SESSION['dd_']['adminId'])->find()['member_id'];
		$memberId ? $adminMemberName = Db::table('member')->where('id', $memberId)->find()['name'] : $adminMemberName = '高权限账号';
		$this->assign('adminMemberName', $adminMemberName);
		$this->assign('time_stamp', $time_stamp);
		$this->assign('today_time_stamp', $today_time_stamp);
		$this->assign('adminMember', $adminMember);
		$this->assign('monitorPostList', $monitorPostList);
		$this->assign('monitorPostList_len', $monitorPostList_len);
		$this->assign('thisMon_monitorPost', $thisMon_monitorPost);
		$this->assign('thisMon_monitorPost_len', $thisMon_monitorPost_len);
		// return json($thisMon_monitorPost);
		return $this->fetch();
	}
	/**
	 * 更新值班表·普通
	 */
	public function updateRotaOrdinary(Request $request, RotaModel $rota)
	{

		$data = input();
		// return json($data);
		$y = getdate()['year'];
		$m = getdate()['mon'] > 9 ? getdate()['mon'] : '0' . getdate()['mon'];
		$d = getdate()['mday'] > 9 ? getdate()['mday'] : '0' . getdate()['mday'];
		// 当前时间
		$today_time_stamp = $y . $m . $d;

		$rs = array();
		$rs['code'] = 1;
		$rs['msg'] = array();
		$rs['msg']['shiftSuccess'] = array();
		$rs['msg']['shiftFail'] = array(); // 冲突检测结果
		$rs['msg']['isNightAndDay'] = array(); // 夜白连上检测结果
		// return json($today_time_stamp);
		for ($i = 0; $i < sizeof($data['data']); $i++) {
			// 禁止修改历史班表数据
			if ($today_time_stamp > $data['data'][$i]['rota_time_stamp']) {
				return 0;
			}
			$originalRota = Db::table('rota')->where(['time_stamp' => $data['data'][$i]['rota_time_stamp'], 'monitor_post_name' => $data['data'][$i]['monitor_post_name']])->find();
			$originalGuy = $originalRota['member_name'];

			// 禁止夜白连上
			$yesterdayTimeStamp = date('Ymd', strtotime($data['data'][$i]['rota_time_stamp'] . "- 1 day")); // 获取昨天的日期数据，且格式为yyyymmdd
			if (
				// 判断前一晚是否夜班
				sizeof(Db::table('rota')->where([
					'time_stamp' => $yesterdayTimeStamp,
					'member_name' => $data['data'][$i]['replacement'],
					'is_night' => 1
				])->select())
				&&
				// 且被检测班次为非夜班
				Db::table('rota')->where([
					'time_stamp' => $data['data'][$i]['rota_time_stamp'],
					'monitor_post_name' => $data['data'][$i]['monitor_post_name']
				])->find()['is_night'] == 0
			) {
				// 记录夜白连上信息
				$rs['code'] = 2;
				$brr = array();
				$brr['time_stamp'] = $data['data'][$i]['rota_time_stamp'];
				$brr['monitor_post_name'] = $data['data'][$i]['monitor_post_name'];
				$brr['member_name'] = $data['data'][$i]['member_name'];
				array_push($rs['msg']['isNightAndDay'], $brr);
			} else if ($originalGuy == $data['data'][$i]['guy_be_replaced']) {
				// 冲突检测，不冲突则正常换班
				$rs_insert = Db::table('shift_record')->insert($data['data'][$i]);
				if ($rs_insert == 1) {
					$rs_update = Db::table('rota')
						->where(['time_stamp' => $data['data'][$i]['rota_time_stamp'], 'monitor_post_name' => $data['data'][$i]['monitor_post_name']])
						->update(['member_name' => $data['data'][$i]['replacement']]);
					if ($rs_update != 1) {
						return -2; //哇喔~更新班表时服务器居然崩溃了
					}
				} else {
					return -1; //记录换班记录是服务器发生错误
				}
				$arr = array();
				$arr['time_stamp'] = $data['data'][$i]['rota_time_stamp'];
				$arr['monitor_post_name'] = $data['data'][$i]['monitor_post_name'];
				array_push($rs['msg']['shiftSuccess'], $arr);
			} else {
				// 记录冲突信息
				$rs['code'] = 2;
				$arr = array();
				$arr['time_stamp'] = $data['data'][$i]['rota_time_stamp'];
				$arr['monitor_post_name'] = $data['data'][$i]['monitor_post_name'];
				$arr['nowGuy'] = $originalGuy; // 班表上现在的人
				$arr['update_time'] = $originalRota['update_time'];
				array_push($rs['msg']['shiftFail'], $arr);
			}

		}
		return $rs; //$rs['code'] = 1 成功 $rs['code'] = 2 部分内容已被修改
	}
	/**
	 * 更新每日班表·权
	 */
	public function updateDailyRota(Request $request, RotaModel $rota)
	{
		$data = input('param.');
		$y = getdate()['year'];
		$m = getdate()['mon'] > 9 ? getdate()['mon'] : '0' . getdate()['mon'];
		$d = getdate()['mday'] > 9 ? getdate()['mday'] : '0' . getdate()['mday'];
		// 当前时间
		$today_time_stamp = $y . $m . $d;
		if ($today_time_stamp > $data['rota_time_stamp']) {
			// 不允许修改历史班表
			return 0;
		}
		$is_conflict = Db::table('rota')
			->where(['time_stamp' => $data['rota_time_stamp'], 'monitor_post_name' => $data['monitor_post_name']])
			->find();
		// 冲突检测 如果原班表的人已经和最新班表不同，说明发生了冲突
		if ($is_conflict['member_name'] != $data['guy_be_replaced']) {
			return -1;
		}
		$rs_insert = Db::table('shift_record')->insert($data);

		if ($rs_insert == 1) {
			$rs_update = Db::table('rota')
				->where(['time_stamp' => $data['rota_time_stamp'], 'monitor_post_name' => $data['monitor_post_name']])
				->update(['member_name' => $data['replacement']]);
		} else {
			return $rs_insert; //1成功 0失败
		}
		return $rs_update; //1成功 0失败
	}
}