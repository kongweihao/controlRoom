<?php

namespace app\customer\controller;

use think\Controller;
use think\Db;
use think\Request;
use app\common\model\Rota as RotaModel;

class Rota extends Controller
{
	/**
	 * 获取最近四天天气数据
	 * ROLLAPI
	 * 普通会员限制QPS为1
	 * 既每秒只能请求一次接口
	 */
	public function getWeatherForecasts()
	{
		// %E5%B9%BF%E5%B7%9E%E5%B8%82 = 广州市
		$weatherForecastsApi = 'https://www.mxnzp.com/api/weather/forecast/%E5%B9%BF%E5%B7%9E%E5%B8%82?app_id=n0suksemrqafttpd&app_secret=T1d6Z0wraTdYeTRjZktnc2VoeDUrQT09';
		$weatherForecasts = json_decode(file_get_contents($weatherForecastsApi), true);
		return json($weatherForecasts);
	}
	/**
	 * 每日值班表列表
	 */
	public function rotaList_day(RotaModel $rota)
	{
		//每日值班表列表，仅查看当天班表，并且展示详细信息
		$y = getdate()['year'];
		$m = getdate()['mon'];
		$h = getdate()['hours'];
		if ($h >= 9 && $h < 18) {
			// 白班
			$m = getdate()['mon'] > 9 ? getdate()['mon'] : '0' . getdate()['mon'];
			$d = getdate()['mday'] > 9 ? getdate()['mday'] : '0' . getdate()['mday'];
			$time_stamp = $y . $m . $d;

			$rs = Db::table('rota')
				->alias(['rota' => 'r', 'member' => 'm'])
				->where(['r.time_stamp' => $time_stamp, 'r.is_day' => 1])
				->where('r.monitorPost_type', '<>', '代维')
				->join('member m', 'r.member_name=m.name')
				->order(['r.sort_day ASC']) //DESC降序
				->select();
			$rs['banci'] = 1; //白班
		} else {
			// 夜班 由于经过凌晨12点，时间段被 分隔成两段，情况比较特殊因此还需要对时间戳进一步处理
			if ($h < 9) {
				$m = getdate()['mon'] > 9 ? getdate()['mon'] : '0' . getdate()['mon'];
				$d = getdate()['mday'] - 1 > 9 ? getdate()['mday'] - 1 : '0' . (getdate()['mday'] - 1);
			} else {
				$m = getdate()['mon'] > 9 ? getdate()['mon'] : '0' . getdate()['mon'];
				$d = getdate()['mday'] > 9 ? getdate()['mday'] : '0' . (getdate()['mday']);
			}
			$time_stamp = $y . $m . $d;
			$rs = Db::table('rota')
				->alias(['rota' => 'r', 'member' => 'm'])
				->where(['r.time_stamp' => $time_stamp, 'r.is_night' => 1])
				->where('r.monitorPost_type', '<>', '代维')
				->join('member m', 'r.member_name=m.name')
				->order(['r.sort_night ASC']) //DESC降序
				->select();
			$rs['banci'] = 0; //夜班
		}
		// <!-- 此处为所有空白情况做特殊处理，至少保证前端展示不要出现乱版 -->
		if (!$rs['2']) {
			$rs['2'] = $rs['1'];
			$rs['3'] = $rs['1'];
			$rs['4'] = $rs['1'];
			$rs['5'] = $rs['1'];
			$rs['6'] = $rs['1'];
		} else if (!$rs['3']) {
			$rs['3'] = $rs['1'];
			$rs['4'] = $rs['1'];
			$rs['5'] = $rs['1'];
			$rs['6'] = $rs['1'];
		} else if (!$rs['4']) {
			$rs['4'] = $rs['1'];
			$rs['5'] = $rs['1'];
			$rs['6'] = $rs['1'];
		} else if (!$rs['5']) {
			$rs['5'] = $rs['1'];
			$rs['6'] = $rs['1'];
		} else if (!$rs['6']) { //夜班没有投诉岗
			$rs['6'] = $rs['1'];
		}
		// $rs['date_hour'] = 12;
		$rs['date_hour'] = getdate()['hours'];
		// return json($rs);
		$this->assign('rotaList_day', $rs);
		return $this->fetch();
	}
	/**
	 * 通用春节版——每日值班表列表
	 */
	public function rotaList_day_happyNewYear(RotaModel $rota)
	{
		//每日值班表列表，仅查看当天班表，并且展示详细信息
		$y = getdate()['year'];
		$m = getdate()['mon'];
		$h = getdate()['hours'];
		if ($h >= 9 && $h < 18) {
			// 白班
			$m = getdate()['mon'] > 9 ? getdate()['mon'] : '0' . getdate()['mon'];
			$d = getdate()['mday'] > 9 ? getdate()['mday'] : '0' . getdate()['mday'];
			$time_stamp = $y . $m . $d;

			$rs = Db::table('rota')
				->alias(['rota' => 'r', 'member' => 'm'])
				->where(['r.time_stamp' => $time_stamp, 'r.is_day' => 1])
				->join('member m', 'r.member_name=m.name')
				->order(['r.sort_day ASC']) //DESC降序
				->select();
			$rs['banci'] = 1; //白班
			// return json($rs);
		} else {
			// 夜班 由于经过凌晨12点，时间段被 分隔成两段，情况比较特殊因此还需要对时间戳进一步处理
			if ($h < 9) {
				$m = getdate()['mon'] > 9 ? getdate()['mon'] : '0' . getdate()['mon'];
				$d = getdate()['mday'] - 1 > 9 ? getdate()['mday'] - 1 : '0' . (getdate()['mday'] - 1);
			} else {
				$m = getdate()['mon'] > 9 ? getdate()['mon'] : '0' . getdate()['mon'];
				$d = getdate()['mday'] > 9 ? getdate()['mday'] : '0' . (getdate()['mday']);
			}
			$time_stamp = $y . $m . $d;
			$rs = Db::table('rota')
				->alias(['rota' => 'r', 'member' => 'm'])
				->where(['r.time_stamp' => $time_stamp, 'r.is_night' => 1])
				->join('member m', 'r.member_name=m.name')
				->order(['r.sort_night ASC']) //DESC降序
				->select();
			$rs['banci'] = 0; //夜班
		}
		// <!-- 此处为当前移动云的值班情况做一个特殊处理，夜班以及周末时移动云监控与投诉同感，因此只要判断当前数组没有第六个元素既将移动云监控补上 -->
		if (!$rs['5']) { //周末没有移动云支撑以及投诉岗
			$rs['5'] = $rs['1'];
			$rs['6'] = $rs['1'];
		} else if (!$rs['6']) { //夜班没有投诉岗
			$rs['6'] = $rs['1'];
		}
		// $rs['date_hour'] = 12;
		$rs['date_hour'] = getdate()['hours'];
		// return json($rs);
		$this->assign('rotaList_day', $rs);
		return $this->fetch();
	}
	/**
	 * 2021春节版——每日值班表列表
	 */
	public function rotaList_day_happyNewYear2021(RotaModel $rota)
	{
		//每日值班表列表，仅查看当天班表，并且展示详细信息
		$y = getdate()['year'];
		$m = getdate()['mon'];
		$h = getdate()['hours'];
		if ($h >= 9 && $h < 18) {
			// 白班
			$m = getdate()['mon'] > 9 ? getdate()['mon'] : '0' . getdate()['mon'];
			$d = getdate()['mday'] > 9 ? getdate()['mday'] : '0' . getdate()['mday'];
			$time_stamp = $y . $m . $d;

			$rs = Db::table('rota')
				->alias(['rota' => 'r', 'member' => 'm'])
				->where(['r.time_stamp' => $time_stamp, 'r.is_day' => 1])
				->join('member m', 'r.member_name=m.name')
				->order(['r.sort_day ASC']) //DESC降序
				->select();
			$rs['banci'] = 1; //白班
			// return json($rs);
		} else {
			// 夜班 由于经过凌晨12点，时间段被 分隔成两段，情况比较特殊因此还需要对时间戳进一步处理
			if ($h < 9) {
				$m = getdate()['mon'] > 9 ? getdate()['mon'] : '0' . getdate()['mon'];
				$d = getdate()['mday'] - 1 > 9 ? getdate()['mday'] - 1 : '0' . (getdate()['mday'] - 1);
			} else {
				$m = getdate()['mon'] > 9 ? getdate()['mon'] : '0' . getdate()['mon'];
				$d = getdate()['mday'] > 9 ? getdate()['mday'] : '0' . (getdate()['mday']);
			}
			$time_stamp = $y . $m . $d;
			$rs = Db::table('rota')
				->alias(['rota' => 'r', 'member' => 'm'])
				->where(['r.time_stamp' => $time_stamp, 'r.is_night' => 1])
				->join('member m', 'r.member_name=m.name')
				->order(['r.sort_night ASC']) //DESC降序
				->select();
			$rs['banci'] = 0; //夜班
		}
		// <!-- 此处为当前移动云的值班情况做一个特殊处理，夜班以及周末时移动云监控与投诉同感，因此只要判断当前数组没有第六个元素既将移动云监控补上 -->
		if (!$rs['5']) { //周末没有移动云支撑以及投诉岗
			$rs['5'] = $rs['1'];
			$rs['6'] = $rs['1'];
		} else if (!$rs['6']) { //夜班没有投诉岗
			$rs['6'] = $rs['1'];
		}
		// $rs['date_hour'] = 12;
		$rs['date_hour'] = getdate()['hours'];
		// return json($rs);
		$this->assign('rotaList_day', $rs);
		return $this->fetch();
	}
	/**
	 * 2020春节版——每日值班表列表
	 */
	public function rotaList_day_happyNewYear2020(RotaModel $rota)
	{
		//每日值班表列表，仅查看当天班表，并且展示详细信息
		$y = getdate()['year'];
		$m = getdate()['mon'];
		$h = getdate()['hours'];
		if ($h >= 9 && $h < 18) {
			// 白班
			$m = getdate()['mon'] > 9 ? getdate()['mon'] : '0' . getdate()['mon'];
			$d = getdate()['mday'] > 9 ? getdate()['mday'] : '0' . getdate()['mday'];
			$time_stamp = $y . $m . $d;

			$rs = Db::table('rota')
				->alias(['rota' => 'r', 'member' => 'm'])
				->where(['r.time_stamp' => $time_stamp, 'r.is_day' => 1])
				->join('member m', 'r.member_name=m.name')
				->order(['r.sort_day ASC']) //DESC降序
				->select();
			$rs['banci'] = 1; //白班
			// return json($rs);
		} else {
			// 夜班 由于经过凌晨12点，时间段被 分隔成两段，情况比较特殊因此还需要对时间戳进一步处理
			if ($h < 9) {
				$m = getdate()['mon'] > 9 ? getdate()['mon'] : '0' . getdate()['mon'];
				$d = getdate()['mday'] - 1 > 9 ? getdate()['mday'] - 1 : '0' . (getdate()['mday'] - 1);
			} else {
				$m = getdate()['mon'] > 9 ? getdate()['mon'] : '0' . getdate()['mon'];
				$d = getdate()['mday'] > 9 ? getdate()['mday'] : '0' . (getdate()['mday']);
			}
			$time_stamp = $y . $m . $d;
			$rs = Db::table('rota')
				->alias(['rota' => 'r', 'member' => 'm'])
				->where(['r.time_stamp' => $time_stamp, 'r.is_night' => 1])
				->join('member m', 'r.member_name=m.name')
				->order(['r.sort_night ASC']) //DESC降序
				->select();
			$rs['banci'] = 0; //夜班
		}
		// <!-- 此处为当前移动云的值班情况做一个特殊处理，夜班以及周末时移动云监控与投诉同感，因此只要判断当前数组没有第六个元素既将移动云监控补上 -->
		if (!$rs['5']) { //周末没有移动云支撑以及投诉岗
			$rs['5'] = $rs['1'];
			$rs['6'] = $rs['1'];
		} else if (!$rs['6']) { //夜班没有投诉岗
			$rs['6'] = $rs['1'];
		}
		// $rs['date_hour'] = 12;
		$rs['date_hour'] = getdate()['hours'];
		// return json($rs);
		$this->assign('rotaList_day', $rs);
		return $this->fetch();
	}
	/**
	 * 按月份获取值班表
	 */
	public function rotaList(RotaModel $rota)
	{
		$data = input();
		$time_stamp = $data['time_stamp'];
		if ($time_stamp == '') {
			$y = getdate()['year'];
			$m = getdate()['mon'] > 9 ? getdate()['mon'] : '0' . getdate()['mon'];
			$time_stamp = $y . $m;
		}
		$condition = array();

		$condition['time_stamp'] = array('like', '%' . $time_stamp . '%');
		$rotalist = Db::table('rota_copy')->where($condition)->select();
		return json($rotalist);
	}
	/**
	 * 我要换班
	 * $data['id'] rota_copy表id
	 * $data['member_name'] 换班者
	 */
	public function editRotaList(RotaModel $rota)
	{
		$data = input();
		$rs = Db::table('rota_copy')->where('id', $data['id'])->update(['member_name' => $data['member_name']]);
		return json($rs);
	}
	/**
	 * 短信接口
	 * 获取今明两天值班人员信息
	 */
	public function getTMMonitor(RotaModel $rota)
	{
		$rs = [];
		$yesterday = date('Ymd', strtotime("-1 day"));
		$today = date('Ymd');
		$tomorrow = date("Ymd", strtotime("+1 day"));
		\array_push($rs, Db::table('rota')->where('time_stamp', $yesterday)->select());
		\array_push($rs, Db::table('rota')->where('time_stamp', $today)->select());
		\array_push($rs, Db::table('rota')->where('time_stamp', $tomorrow)->select());
		// return json($today.'-'.$tomorrow);
		return json($rs);
	}
	/**
	 * 获取当日值班信息
	 * 需求人：王如玥
	 * 用途：为岗位日志大值班自动生成总结提供数据源
	 */
	public function getTodayMonitor(RotaModel $rota)
	{
		$today = date('Ymd');
		$rs = Db::table('rota')->where('time_stamp', $today)->select();
		return json($rs);
	}
}