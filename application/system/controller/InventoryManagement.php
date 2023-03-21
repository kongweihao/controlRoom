<?php
namespace app\system\controller;
use think\Request;
use think\Db;
use think\Session;
use think\Validate;
/**
 * 社区物资管理类
 * @author kongweihao
 *
 */
class InventoryManagement extends Auth{
	// 是否批量验证
    protected $batchValidate = true;
	
	// 库存管理
	/**
	 * 库存管理·查
	 */
	public function inventoryManagementList(){
		$params = input();
		if( $params['isSearch'] != 1 ){
			// 首次打开页面
			$inventoryManagementList = Db::table('inventory_management')
							->order(['im_id DESC'])
							->paginate(10);
			$this->assign([
				'inventoryManagementList'=>$inventoryManagementList
			]);
			return $this->fetch();
		} else {
			// 搜索功能
			$pagination = $params['pagination'];
			$searchForm = $params['searchForm'];
			$condition = [];
			$condition['im_name'] = ['like','%'.$searchForm['im_name'].'%'];
			if($searchForm['create_time'] != []){
				$condition['create_time'] = ['between time',$searchForm['create_time']];
			}
			if($searchForm['update_time'] != []){
				$condition['update_time'] = ['between time',$searchForm['update_time']];
			}
			$inventoryManagementList = Db::table('inventory_management')
				->order(['im_id DESC'])
				->where($condition)
				->paginate($pagination['per_page'],false,[
					'page' => $pagination['current_page'],
					'list_rows' => $pagination['per_page'],
				]);
			return \json($inventoryManagementList);
		}
	}
	/**
	 * 库存管理·改
	 */
	public function editInventoryManagementList(){
		$params = input();
		$rs = [];
		$rs['code'] = 1;
		$rule = [
			'im_id'  => 'require|integer',
			'im_name'  => 'require',
			'im_num' => 'require|number|integer|egt:0'
		];
		
		$msg = [
			'im_id.require'  => '数据有误请刷新重试或联系管理员，错误信息：im_id缺失',
			'im_id.integer'  => '数据有误请刷新重试或联系管理员，错误信息：im_id非整数',
			'im_name.require'  => '物资名称不能为空',
			'im_num.require' => '库存数量不能为空',
			'im_num.number' => '库存数量必须为整数',
			'im_num.integer' => '库存数量不能为小数',
			'im_num.egt' => '库存数量不能小于0',
		];
		$validate = Validate::make($rule,$msg);
		
		if (!$validate->check($params)) {
			$rs['code'] = -1;
			$rs['msg'] = $validate->getError();
			return $rs;
		} else {
			$im_key = $params['key'];
			$im_id = $params['im_id'];
			$updateRs = '';
			// 修改物资名称
			if ($im_key == 'im_name') {
				$updateRs = Db::table('inventory_management')
					->where('im_id', $im_id)
					->update(['im_name'=>$params['im_name']]);
			// 修改物资数量
			} else if ($im_key == 'im_num') {
				$old_data = Db::table('inventory_management')->where('im_id', $params['im_id'])->find();
				$params['im_can_apply_num'] = $old_data['im_can_apply_num'] + ($params['im_num'] - $old_data['im_num']);
				$rs['im_can_apply_num'] = $params['im_can_apply_num'];
				$updateRs = Db::table('inventory_management')
					->where('im_id', $im_id)
					->update(['im_num'=>$params['im_num'], 'im_can_apply_num'=>$params['im_can_apply_num']]);
			}
			if ($updateRs) {
				$rs['code'] = 1;
				$rs['msg'] = '修改成功';
			} else {
				$rs['code'] = 0;
				$rs['msg'] = '修改失败，请联系管理员';
			}
			return $rs;
		}
	}
	/**
	 * 库存管理·增
	 */
	public function addInventoryManagementList(){
		$params = input();
		$rs = [];
		$rs['code'] = 1;
		$rs['msg'] = '新增成功';
		// 检验物资名称是否已存在
		$isNameAlreadyExists = Db::table('inventory_management')->where('im_name',$params['im_name'])->find();
		if ($isNameAlreadyExists) {
			$rs['code'] = -1;
			$rs['msg'] = '该物资已存在';
		} else {
			$params['im_can_apply_num'] = $params['im_num'];
			$addRs = Db::table('inventory_management')->insert($params);
			if (!$addRs) {
				$rs['code'] = -1;
				$rs['msg'] = '添加失败请，联系管理员';
			}
		}
		return $rs;
	}
	/**
	 * 库存管理·删
	 */
	public function deleteInventoryManagementList(){
		$params = input();
		$rs = [];
		$rs['code'] = 1;
		$rs['msg'] = '删除成功';
		$deleteRs = Db::table('inventory_management')->delete($params);
		if (!$deleteRs) {
			$rs['code'] = -1;
			$rs['msg'] = '添加失败请，联系管理员';
		}
		return $rs;
	}

	// 申领请求列表
	/**
	 * 申领请求列表·查
	 */
	public function materialRequisitionList(){
		$params = input();
		if( $params['isSearch'] != 1 ){
			// 首次打开页面 默认加载待审核数据以及位置导致的错误数据
			$mList = Db::table('inventory_management')
				->alias('im')
				->join('material_requisition_list mrl','mrl.im_id = im.im_id')
				->order(['mrl_state ASC'])
				->paginate(10);
			$materialRequisitionListData = $mList->all();
			for ($i = 0 ; $i < sizeof($materialRequisitionListData); $i++) {
				$steps_item = explode("->",$materialRequisitionListData[$i]['mrl_remarks']);
				$statement = [];
				$materialRequisitionListData[$i]['steps'] = [];
				for ($j = 0; $j < \sizeof($steps_item); $j++){
					// 流程
					$statement_temp = explode("|*|",$steps_item[$j]);
					$statement['step'] = $statement_temp[0];
					$statement['statement'] = explode("|#|",$statement_temp[1])[0];
					$statement['time'] = explode("|#|",$statement_temp[1])[1];
					array_push($materialRequisitionListData[$i]['steps'], $statement);
				}
				// 最后一步
				$materialRequisitionListData[$i]['lastSteps'] = $materialRequisitionListData[$i]['steps'][\sizeof($materialRequisitionListData[$i]['steps'])-1];
			}

			$materialRequisitionList = [];
			$materialRequisitionList['total'] = $mList->total();
			$materialRequisitionList['per_page'] = $mList->listRows();
			$materialRequisitionList['current_page'] = $mList->currentPage();
			$materialRequisitionList['last_page'] = $mList->lastPage();
			$materialRequisitionList['data'] = $materialRequisitionListData;
			$this->assign([
				'materialRequisitionList'=>$materialRequisitionList
			]);
			return $this->fetch();
		} else {
			// 搜索功能
			$pagination = $params['pagination'];
			$searchForm = $params['searchForm'];
			$condition = [];
			$condition['mrl_job_num'] = ['like','%'.$searchForm['mrl_job_num'].'%'];
			$condition['mrl_applicant'] = ['like','%'.$searchForm['mrl_applicant'].'%'];
			$condition['mrl_applicant_nickname'] = ['like','%'.$searchForm['mrl_applicant_nickname'].'%'];
			$condition['mrl_checker'] = ['like','%'.$searchForm['mrl_checker'].'%'];
			$condition['mrl_checker_nickname'] = ['like','%'.$searchForm['mrl_checker_nickname'].'%'];
			if ($searchForm['mrl_state'] != '') {
				$condition['mrl_state'] = $searchForm['mrl_state'];
			}
			$mList = Db::table('inventory_management')
				->alias('im')
				->join('material_requisition_list mrl','mrl.im_id = im.im_id')
				->order(['mrl_state ASC'])
				->where($condition)
				->paginate($pagination['per_page'],false,[
					'page' => $pagination['current_page'],
					'list_rows' => $pagination['per_page'],
				]);
			$materialRequisitionListData = $mList->all();
			for ($i = 0 ; $i < sizeof($materialRequisitionListData); $i++) {
				$steps_item = explode("->",$materialRequisitionListData[$i]['mrl_remarks']);
				$statement = [];
				$materialRequisitionListData[$i]['steps'] = [];
				for ($j = 0; $j < \sizeof($steps_item); $j++){
					// 流程
					$statement_temp = explode("|*|",$steps_item[$j]);
					$statement['step'] = $statement_temp[0];
					$statement['statement'] = explode("|#|",$statement_temp[1])[0];
					$statement['time'] = explode("|#|",$statement_temp[1])[1];
					array_push($materialRequisitionListData[$i]['steps'], $statement);
				}
				// 最后一步
				$materialRequisitionListData[$i]['lastSteps'] = $materialRequisitionListData[$i]['steps'][\sizeof($materialRequisitionListData[$i]['steps'])-1];
			}

			$materialRequisitionList = [];
			$materialRequisitionList['total'] = $mList->total();
			$materialRequisitionList['per_page'] = $mList->listRows();
			$materialRequisitionList['current_page'] = $mList->currentPage();
			$materialRequisitionList['last_page'] = $mList->lastPage();
			$materialRequisitionList['data'] = $materialRequisitionListData;
			return \json($materialRequisitionList);
		}
	}
	/**
	 * 申领请求列表·改（通过/驳回）
	 */
	public function editMaterialRequisitionList(){
		$params = input();
		$rs = [];
		$rs['code'] = 1;
		$rs['msg'] = '操作成功';
		$old_data = Db::table('material_requisition_list')->where('mrl_id', $params['mrl_id'])->find();
		$update_record = [];
		$update_record['mrl_state'] = $params['mrl_state'];
		$update_record['mrl_checker'] = Session::get('adminAccount');
		$update_record['mrl_checker_nickname'] = Session::get('adminFullname');
		// 审核通过
		if ($params['mrl_state'] == 1) {
			$update_record['mrl_remarks'] = $old_data['mrl_remarks'].'->'. Session::get('adminAccount').'通过审核|*|通过说明：'.$params['statement'].'|#|通过时间：'.date('Y-m-d H:i:s');
			// 库存减去对应数量
			$old_num = Db::table('inventory_management')
						->where('im_id', $params['im_id'])
						->find()['im_num'];
			$new_num = $old_num - $params['mrl_num'];
			$reduceNumRs = Db::table('inventory_management')
				->where('im_id', $params['im_id'])
				->update(['im_num'=>$new_num]);
			if (!$reduceNumRs) {
				$rs['code'] = 0;
				$rs['msg'] = '未知错误，请联系系统管理员';
				return $rs;
			}
		} 
		// 驳回请求
		else if ($params['mrl_state'] == 2) {
			$update_record['mrl_remarks'] = $old_data['mrl_remarks'].'->'. Session::get('adminAccount').'驳回申请|*|驳回说明：'.$params['statement'].'|#|驳回时间：'.date('Y-m-d H:i:s');
			// 可申请数量加回对应数量
			$old_can_apply_num = Db::table('inventory_management')
				->where('im_id', $params['im_id'])
				->find()['im_can_apply_num'];
			$new_can_apply_num = $old_can_apply_num + $params['mrl_num'];
			$addCanApplyNumRs = Db::table('inventory_management')
			->where('im_id', $params['im_id'])
			->update(['im_can_apply_num'=>$new_can_apply_num]);
			if (!$addCanApplyNumRs) {
				$rs['code'] = 0;
				$rs['msg'] = '未知错误，请联系系统管理员';
				return $rs;
			}
		}
		$editRs = Db::table('material_requisition_list')
			->where('mrl_id', $params['mrl_id'])
			->update($update_record);
		if (!$editRs) {
			$rs['code'] = 0;
			$rs['msg'] = '未知错误，请联系系统管理员';
		}
		return $rs;
	}

	// 物资附件管理
	/**
	 * 物资附件管理·查
	 */
	public function materialAccessoriesManagement(){
		$params = input();
		$admin_account = Session::get('adminAccount');
		return json($params);
	}
	/**
	 * 物资附件管理·增
	 */
	public function addMaterialAccessoriesManagement(){
		$params = input();
		$admin_account = Session::get('adminAccount');
		return json($params);
	}
	/**
	 * 物资附件管理·删
	 */
	public function deleteMaterialAccessoriesManagement(){
		$params = input();
		$admin_account = Session::get('adminAccount');
		return json($params);
	}

	// 我要申领
	/**
	 * 我要申领·查
	 */
	public function iNeedSupplies(){
		$params = input();
		if( $params['isSearch'] != 1 ){
			// 首次打开页面
			$iNeedSupplies = Db::table('inventory_management')
							->where('im_can_apply_num','<>',0)
							->order(['im_id DESC'])
							->paginate(10);
			$this->assign([
				'iNeedSupplies'=>$iNeedSupplies
			]);
			return $this->fetch();
		} else {
			// 搜索功能
			$pagination = $params['pagination'];
			$searchForm = $params['searchForm'];
			$condition = [];
			// im_can_apply_num_state = 2 表示只显示可申请数量大于0的物资
			if ($searchForm['im_can_apply_num_state'] == 2) {
				$condition['im_can_apply_num'] = ['<>', 0];
			}
			$condition['im_name'] = ['like','%'.$searchForm['im_name'].'%'];
			if($searchForm['create_time'] != []){
				$condition['create_time'] = ['between time',$searchForm['create_time']];
			}
			if($searchForm['update_time'] != []){
				$condition['update_time'] = ['between time',$searchForm['update_time']];
			}
			$iNeedSupplies = Db::table('inventory_management')
				->order(['im_id DESC'])
				->where($condition)
				->paginate($pagination['per_page'],false,[
					'page' => $pagination['current_page'],
					'list_rows' => $pagination['per_page'],
				]);
			return \json($iNeedSupplies);
		}
	}
	/**
	 * 我要申领·增
	 */
	public function addINeedSupplies(){
		$params = input();
		$rs = [];
		$rs['code'] = 1;
		$rs['msg'] = '申领成功，等待管理员审核';
		$old_data = Db::table('inventory_management')->where('im_id',$params['im_id'])->find();
		if ($old_data['im_can_apply_num'] >= $params['im_num_of_applications']) {
			// 先录入申领记录
			$admin_account = Session::get('adminAccount');
			$app_record = [];
			$app_record['mrl_job_num'] = date('YmdHis').Session::get('adminAccount');
			$app_record['mrl_applicant'] = Session::get('adminAccount');
			$app_record['mrl_applicant_nickname'] = Session::get('adminFullname');
			$app_record['im_id'] = $params['im_id'];
			$app_record['mrl_num'] = $params['im_num_of_applications'];
			$app_record['mrl_remarks'] = Session::get('adminAccount').'提交申请|*|申请说明：'.$params['statement'].'|#|申请时间：'.date('Y-m-d H:i:s');
			$entry_of_application_record_id = Db::table('material_requisition_list')->insertGetId($app_record);
			if ($entry_of_application_record_id) {
				$old_data['im_can_apply_num'] = $old_data['im_can_apply_num'] - $params['im_num_of_applications'];
				$appleRs =  Db::table('inventory_management')->update($old_data);
				if ($appleRs) {
					$rs['code'] = 1;
					$rs['msg'] = '申领成功，等待管理员审核';
				} else {
					$rs['code'] = 0;
					$rs['msg'] = '未知错误，请刷新重试或联系管理员';
				}
			} else {
				// 将申请记录状态置为-1，发生未知错误
				Db::table('material_requisition_list')->where('mrl_id',$entry_of_application_record_id)->update(['mrl_state'=>-1]);
				$rs['code'] = 0;
				$rs['msg'] = '未知错误，请刷新重试或联系管理员';
			}
		} else {
			$rs['code'] = 0;
			$rs['msg'] = '申领失败，【'+ $params['im_name'] +'】刚刚被其他人申领了，数量仅剩：'+ $params['im_can_apply_num'] +'，请刷新，重新申请';
		}
		return $rs;
	}

	// 我的申领记录
	/**
	 * 我的申领记录·查
	 */
	public function myMaterialRequisitionList(){
		$params = input();
		if( $params['isSearch'] != 1 ){
			// 首次打开页面 默认加载待审核数据以及未知原因导致的错误数据
			$mList = Db::table('inventory_management')
				->alias('im')
				->join('material_requisition_list mrl','mrl.im_id = im.im_id')
				->where('mrl_applicant', Session::get('adminAccount'))
				->whereIn('mrl_state', '0,1,2')
				->order(['mrl_state DESC'])
				->paginate(10);
				// 若无数据，则返回其他状态数据
				if (\sizeof($mList) == 0) {
					$mList =  Db::table('inventory_management')
					->alias('im')
					->join('material_requisition_list mrl','mrl.im_id = im.im_id')
					->where('mrl_applicant', Session::get('adminAccount'))
					->order(['mrl_state DESC'])
					->paginate(10);
				}
			$myMaterialRequisitionListData = $mList->all();
			for ($i = 0 ; $i < sizeof($myMaterialRequisitionListData); $i++) {
				$steps_item = explode("->",$myMaterialRequisitionListData[$i]['mrl_remarks']);
				$statement = [];
				$myMaterialRequisitionListData[$i]['steps'] = [];
				for ($j = 0; $j < \sizeof($steps_item); $j++){
					// 流程
					$statement_temp = explode("|*|",$steps_item[$j]);
					$statement['step'] = $statement_temp[0];
					$statement['statement'] = explode("|#|",$statement_temp[1])[0];
					$statement['time'] = explode("|#|",$statement_temp[1])[1];
					array_push($myMaterialRequisitionListData[$i]['steps'], $statement);
				}
				// 最后一步
				$myMaterialRequisitionListData[$i]['lastSteps'] = $myMaterialRequisitionListData[$i]['steps'][\sizeof($myMaterialRequisitionListData[$i]['steps'])-1];
			}

			$myMaterialRequisitionList = [];
			$myMaterialRequisitionList['total'] = $mList->total();
			$myMaterialRequisitionList['per_page'] = $mList->listRows();
			$myMaterialRequisitionList['current_page'] = $mList->currentPage();
			$myMaterialRequisitionList['last_page'] = $mList->lastPage();
			$myMaterialRequisitionList['data'] = $myMaterialRequisitionListData;
			$this->assign([
				'myMaterialRequisitionList'=>$myMaterialRequisitionList
			]);
			return $this->fetch();
		} else {
			// 搜索功能
			$pagination = $params['pagination'];
			$searchForm = $params['searchForm'];
			$condition = [];
			$condition['mrl_applicant'] = Session::get('adminAccount');
			if ($searchForm['mrl_state'] != '') {
				$condition['mrl_state'] = $searchForm['mrl_state'];
			}
			$mList = Db::table('inventory_management')
				->alias('im')
				->join('material_requisition_list mrl','mrl.im_id = im.im_id')
				->order(['mrl_state DESC'])
				->where($condition)
				->paginate($pagination['per_page'],false,[
					'page' => $pagination['current_page'],
					'list_rows' => $pagination['per_page'],
				]);
			$myMaterialRequisitionListData = $mList->all();
			for ($i = 0 ; $i < sizeof($myMaterialRequisitionListData); $i++) {
				$steps_item = explode("->",$myMaterialRequisitionListData[$i]['mrl_remarks']);
				$statement = [];
				$myMaterialRequisitionListData[$i]['steps'] = [];
				for ($j = 0; $j < \sizeof($steps_item); $j++){
					// 流程
					$statement_temp = explode("|*|",$steps_item[$j]);
					$statement['step'] = $statement_temp[0];
					$statement['statement'] = explode("|#|",$statement_temp[1])[0];
					$statement['time'] = explode("|#|",$statement_temp[1])[1];
					array_push($myMaterialRequisitionListData[$i]['steps'], $statement);
				}
				// 最后一步
				$myMaterialRequisitionListData[$i]['lastSteps'] = $myMaterialRequisitionListData[$i]['steps'][\sizeof($myMaterialRequisitionListData[$i]['steps'])-1];
			}

			$myMaterialRequisitionList = [];
			$myMaterialRequisitionList['total'] = $mList->total();
			$myMaterialRequisitionList['per_page'] = $mList->listRows();
			$myMaterialRequisitionList['current_page'] = $mList->currentPage();
			$myMaterialRequisitionList['last_page'] = $mList->lastPage();
			$myMaterialRequisitionList['data'] = $myMaterialRequisitionListData;
			return \json($myMaterialRequisitionList);
		}
	}
	/**
	 * 我的申领记录·改（当被驳回时，可进行重新编辑提交申请）
	 */
	public function editMyMaterialRequisitionList(){
		$params = input();
		$rs = [];
		$rs['code'] = 1;
		$rs['msg'] = '操作成功';
		$old_data = Db::table('material_requisition_list')->where('mrl_id', $params['mrl_id'])->find();
		$update_record = [];
		$update_record['mrl_num'] = $params['mrl_num'];
		// 关单
		if ($params['mrl_state'] == 1) {
			$update_record['mrl_state'] = 3;
			$update_record['mrl_remarks'] = $old_data['mrl_remarks'].'->'. Session::get('adminAccount').'已确认关单|*||#|关单时间：'.date('Y-m-d H:i:s');
		} 
		// 再次提交
		else if ($params['mrl_state'] == 2) {
			$update_record['mrl_state'] = 0;
			$update_record['mrl_remarks'] = $old_data['mrl_remarks'].'->'. Session::get('adminAccount').'再次提交|*|提交说明：'.$params['reSubmitStatement'].'|#|提交时间：'.date('Y-m-d H:i:s');
			// 库存减去对应数量
			$old_im_can_apply_num = Db::table('inventory_management')
						->where('im_id', $params['im_id'])
						->find()['im_can_apply_num'];
			$new_im_can_apply_num = $old_im_can_apply_num - $params['mrl_num'];
			if ($new_im_can_apply_num < 0) {
				$rs['code'] = -1;
				$rs['msg'] = '申请数量不可大于可申请库存';
				return $rs;
			}
			$reduceCanApplyNumRs = Db::table('inventory_management')
				->where('im_id', $params['im_id'])
				->update(['im_can_apply_num'=>$new_im_can_apply_num]);
			if (!$reduceCanApplyNumRs) {
				$rs['code'] = 0;
				$rs['msg'] = '未知错误，请联系系统管理员';
				return $rs;
			}
		}
		// 驳回后取消申请并关单
		else if ($params['mrl_state'] == 4) {
			$update_record['mrl_state'] = 3;
			$update_record['mrl_remarks'] = $old_data['mrl_remarks'].'->'. Session::get('adminAccount').'驳回后取消申请并关单|*||#|关单时间：'.date('Y-m-d H:i:s');
		}
		$editRs = Db::table('material_requisition_list')
			->where('mrl_id', $params['mrl_id'])
			->update($update_record);
		if (!$editRs) {
			$rs['code'] = 0;
			$rs['msg'] = '未知错误，请联系系统管理员';
		}
		return $rs;
	}

}