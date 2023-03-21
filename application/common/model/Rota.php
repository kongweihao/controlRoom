<?php
namespace app\common\model;
use think\Db;
use think\Model;

/**
 * 值班表模型类
 * @author kongweihao
 *
 */
class Rota extends Model{
	//只读字段，写入以后就不允许被更新
	protected $readonly = ['id','create_time'];
	
	/**
	 * 获取监控值班岗位表
	 * @return array
	 */
	public function getMonitorPostList($condition){
		$where = [];
		// 按岗位搜索
		if($condition['monitorPost_type_id'] != -1 && $condition['monitorPost_type_id'] != null){//-1表示查询全部
			$where['mp.monitorPost_type_id'] = $condition['monitorPost_type_id'];
		}
		// 按白夜班搜索
		// $day_or_night_search_arr[0]['value'] = -1;
		// $day_or_night_search_arr[0]['name'] = '白班 / 夜班';
		// $day_or_night_search_arr[1]['value'] = 1;
		// $day_or_night_search_arr[1]['name'] = '仅白班';
		// $day_or_night_search_arr[2]['value'] = 2;
		// $day_or_night_search_arr[2]['name'] = '仅夜班';
		// $day_or_night_search_arr[3]['value'] = 3;
		// $day_or_night_search_arr[3]['name'] = '含白班';
		// $day_or_night_search_arr[4]['value'] = 4;
		// $day_or_night_search_arr[4]['name'] = '含夜班';
		// $day_or_night_search_arr[5]['value'] = 5;
		// $day_or_night_search_arr[5]['name'] = '全天';
		if($condition['day_or_night'] != -1 && $condition['day_or_night'] != null){//-1表示查询全部
			if($condition['day_or_night'] == 1){
				$where['mp.is_day'] = 1;
				$where['mp.is_night'] = 0;
			}else if($condition['day_or_night'] == 2){
				$where['mp.is_night'] = 1;
				$where['mp.is_day'] = 0;
			}else if($condition['day_or_night'] == 3){
				$where['mp.is_day'] = 1;
			}else if($condition['day_or_night'] == 4){
				$where['mp.is_night'] = 1;
			}else if($condition['day_or_night'] == 5){
				$where['mp.is_day'] = 1;
				$where['mp.is_night'] = 1;
			}
		}
		$rs = Db::table('monitor_post')
			->alias(['monitor_post'=>'mp', 'monitorpost_type'=>'mpt'])
			->where($where)
			->Join('monitorpost_type mpt','mp.monitorPost_type_id = mpt.monitorPost_type_id')
			->order(['mp.sort_day ASC'])//DESC降序
			->paginate(input('limit'));
			// paginate()方法，已集成分页功能
		return $rs;
	}
	/**
	 * 获取指定月份值班表
	 * @return array
	 */
	public function getRotaList($condition){
		$rs = Db::table('rota')
			->where($condition)
			->order(['id ASC'])//DESC降序
			->select();
			return $rs;
	}
	/**
	 * 删除指定月份值班表
	 * @return array
	 */
	public function deleteRota($condition){
		$rs = Db::table('rota')
			->where($condition)
			->delete();
			return $rs;
	}

}