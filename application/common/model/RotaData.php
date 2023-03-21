<?php
namespace app\common\model;
use think\Db;
use think\Model;

/**
 * 值班表模型类
 * @author kongweihao
 *
 */
class RotaData extends Model{
	//只读字段，写入以后就不允许被更新
	protected $readonly = ['id','create_time'];
	
	/**
	 * 获取换班记录表
	 * @return array
	 */
	public function getRotaDataList($condition){
		if(sizeof($condition['time_stamp_range']) == 2){
			// time_stamp_range 包含有效时间戳范围时，进入，否则不对rota_time_stamp做筛选
			$rs = Db::table('shift_record')
				->where('equipment', 'like', '%'.$condition['equipment'].'%')
				->where('guy_be_replaced', 'like', '%'.$condition['guy_be_replaced'].'%')
				->where('monitor_post_name', 'like', '%'.$condition['monitor_post_name'].'%')
				->where('operation_account', 'like', '%'.$condition['operation_account'].'%')
				->where('replacement', 'like', '%'.$condition['replacement'].'%')
				->where('operation_guy', 'like', '%'.$condition['operation_guy'].'%')
				->where('create_time', 'between', [$condition['create_time_range'][0], $condition['create_time_range'][1]])
				->where('rota_time_stamp', 'between', [$condition['time_stamp_range'][0], $condition['time_stamp_range'][1]])
				->order(['create_time DESC'])//ASC升序
				->paginate(input('limit'));
		}else{
			$rs = Db::table('shift_record')
				->where('equipment', 'like', '%'.$condition['equipment'].'%')
				->where('guy_be_replaced', 'like', '%'.$condition['guy_be_replaced'].'%')
				->where('monitor_post_name', 'like', '%'.$condition['monitor_post_name'].'%')
				->where('operation_account', 'like', '%'.$condition['operation_account'].'%')
				->where('replacement', 'like', '%'.$condition['replacement'].'%')
				->where('operation_guy', 'like', '%'.$condition['operation_guy'].'%')
				->where('create_time', 'between', [$condition['create_time_range'][0], $condition['create_time_range'][1]])
				->order(['create_time DESC'])//ASC升序
				->paginate(input('limit'));
		}
			// paginate()方法，已集成分页功能
		return $rs;
	}

	
	/**
	 * 获取夜班数据
	 * @return array
	 */
	public function getNightRotaDataList($condition){
		$rs = Db::table('rota')
			->alias('r')
			->where('monitorPost_type', 'not in', ['值班长', '一干投诉处理','传输支撑' , '代维'])
			->join('member m','r.member_name = m.name')
			->where('is_night', 1)
			->where('member_name', 'like', '%'.$condition['member_name'].'%')
			->where('time_stamp', 'between', [$condition['time_stamp_range'][0], $condition['time_stamp_range'][1]])
			->order(['time_stamp ASC'])//ASC升序 DESC降序
			->paginate(1000);
			// paginate()方法，已集成分页功能
		return $rs;
	}
	
	/**
	 * 获取节假日加班数据
	 * @return array
	 */
	public function getHolidayOvertimeDataList($condition){
		// 多加载前一天的白班数据，再在后面用array_shift删除
		$rs = Db::table('rota')
			->alias('r')
			->where('monitorPost_type', 'not in', ['值班长', '一干投诉处理','传输支撑' , '代维'])
			->join('member m','r.member_name = m.name')
			->where('time_stamp', 'between', [date('Ymd',strtotime($condition['time_stamp_range'][0]." -1 day")), $condition['time_stamp_range'][1]])
			->paginate(1000)->toArray();
			// 先去掉前一天的白班数据
			array_shift($rs['data']);
			array_shift($rs['data']);
			// paginate()方法，已集成分页功能
		return $rs;
	}
}