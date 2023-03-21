<?php
namespace app\system\controller;
use think\Request;
use think\Db;
use think\Session;

/**
 * 我要反馈类
 * @author kongweihao
 *
 */
class Feedback extends Auth{
	/**
	 * 有话要说
	 */
	public function iNeedFeedback(){
		$params = input();
		$admin_account = Session::get('adminAccount');
		if( $params['isSearch'] != 1 ){
			// 首次打开页面
			$feedbacklist = Db::table('feedback_list')
							->where('admin_account', $admin_account)
							->order(['fb_is_read ASC'])
							->order(['fb_id DESC'])
							->paginate(10);
			$this->assign([
				'feedbacklist'=>$feedbacklist,
				'admin_account'=>$admin_account
			]);
			return $this->fetch();
		} else {
			// 搜索功能
			$pagination = $params['pagination'];
			$searchForm = $params['searchForm'];
			$condition = [];
			$condition['admin_account'] = ['like','%'.$searchForm['admin_account'].'%'];
			if($searchForm['fb_type'] != ''){
				$condition['fb_type'] = $searchForm['fb_type'];
			}
			$condition['fb_content'] = ['like','%'.$searchForm['fb_content'].'%'];
			$condition['fb_is_read'] = ['like','%'.$searchForm['fb_is_read'].'%'];
			$condition['fb_is_handle'] = ['like','%'.$searchForm['fb_is_handle'].'%'];
			$condition['fb_solution'] = ['like','%'.$searchForm['fb_solution'].'%'];
			if($searchForm['create_time'] != []){
				$condition['create_time'] = ['between time',$searchForm['create_time']];
			}
			if($searchForm['update_time'] != []){
				$condition['update_time'] = ['between time',$searchForm['update_time']];
			}
			$feedbacklist = Db::table('feedback_list')
							->order(['fb_is_read ASC'])
							->order(['fb_id DESC'])
							->where($condition)
							->where('admin_account', $admin_account)
							->paginate($pagination['per_page'],false,[
								'page' => $pagination['current_page'],
								'list_rows' => $pagination['per_page'],
							]);
			return \json($feedbacklist);
		}
	}
	/**
	 * 提交反馈意见·有话要说·增
	 */
	public function addFeedback(){
		$params = \input();
		$rs = Db::table('feedback_list')->insert($params);
		return $rs;
	}
	/**
	 * 大家的反馈
	 */
	public function feedbackList(){
		$params = input();
		if( $params['isSearch'] != 1 ){
			// 首次打开页面
			$feedbacklist = Db::table('feedback_list')
							->order(['fb_is_read ASC'])
							->order(['fb_id DESC'])
							->paginate(10);
			$this->assign([
				'feedbacklist'=>$feedbacklist
			]);
			return $this->fetch();
		} else {
			// 搜索功能
			$pagination = $params['pagination'];
			$searchForm = $params['searchForm'];
			$condition = [];
			$condition['admin_account'] = ['like','%'.$searchForm['admin_account'].'%'];
			if($searchForm['fb_type'] != ''){
				$condition['fb_type'] = $searchForm['fb_type'];
			}
			$condition['fb_content'] = ['like','%'.$searchForm['fb_content'].'%'];
			$condition['fb_is_read'] = ['like','%'.$searchForm['fb_is_read'].'%'];
			$condition['fb_is_handle'] = ['like','%'.$searchForm['fb_is_handle'].'%'];
			$condition['fb_solution'] = ['like','%'.$searchForm['fb_solution'].'%'];
			if($searchForm['create_time'] != []){
				$condition['create_time'] = ['between time',$searchForm['create_time']];
			}
			if($searchForm['update_time'] != []){
				$condition['update_time'] = ['between time',$searchForm['update_time']];
			}
			$feedbacklist = Db::table('feedback_list')
							->order(['fb_is_read ASC'])
							->order(['fb_id DESC'])
							->where($condition)
							->paginate($pagination['per_page'],false,[
								'page' => $pagination['current_page'],
								'list_rows' => $pagination['per_page'],
							]);
			return \json($feedbacklist);
		}
	}
	/**
	 * 处理反馈数据·大家的反馈·改
	 */
	public function editFeedbackList(){
		$params = input();
		$rs = Db::table('feedback_list')
				->update($params);
		return $rs;
	}
}