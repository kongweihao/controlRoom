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
 *传输支撑日志类
 * @author kongweihao
 *
 */
// class TransportSupport extends Controller{ // 调试用的，没有做权限限制
class TransportSupport extends Auth{ // 上线用的
	/**
	 * 公共测试接口
	 */
	public function test(Request $request){
		
	}

	/**
	 * 支撑日志结构管理列表
	 */
	public function TransportSupportLogManagement(Request $request){
		$params = \input();
		// $params['getNextLevel'] 如果为空则表示只获取一级表数据，如果为1则获取2/3级数据
		if($params['getNextLevel'] == ''){
			// 打开页面并获取一级表
			// 获取一级表
			$getLevelOne = Db::table('transport_support_log_management') 
			-> where('tslm_level', 1) 
			-> order(['tslm_sort asc'])
			-> select();
			// 获取一二级表数据，用于加载【级别】下拉框
			$LevelOne_Two = $getLevelOne;
			for($i = 0; $i < \sizeof($getLevelOne); $i++){
				// 根据一级表获取二级表
				$getLevelTwo = Db::table('transport_support_log_management') 
								-> where('tslm_parent_id', $getLevelOne[$i]['tslm_id']) 
								-> order(['tslm_sort asc'])
								-> select();
				$LevelOne_Two[$i]['children'] = $getLevelTwo;
				if($getLevelTwo){
					// 配合前端懒加载  
					$getLevelOne[$i]['hasChildren'] = true;
				}
	
			}
			$this->assign([
				'LevelOne_Two'=>$LevelOne_Two,
				'transportSupportLogManagementList'=>$getLevelOne,
			]);
			return $this->fetch();
		}else{
			// 获取2、3级表
			$getLevelTwoOrThree = Db::table('transport_support_log_management') 
			-> where('tslm_parent_id', $params['tslm_parent_id']) 
			-> order(['tslm_sort asc'])
			-> select();
			
			for($i = 0; $i < \sizeof($getLevelTwoOrThree); $i++){
				// 根据一级表获取二级表
				$getNextLevel = Db::table('transport_support_log_management') 
								-> where('tslm_parent_id', $getLevelTwoOrThree[$i]['tslm_id']) 
								-> order(['tslm_sort asc'])
								-> select();
				if($getNextLevel){
					// 配合前端懒加载  
					$getLevelTwoOrThree[$i]['hasChildren'] = true;
				}
			}
			return json($getLevelTwoOrThree);
		}
	}
	/**
	 * 新增支撑日志结构·管理模块
	 */
	public function addTransportSupportLogManagement(Request $request){
		$data = input();
		$rs = [];
		// 检测主键是否存在
		$tslm_is_key = $data['tslm_is_key'];
		if ($tslm_is_key) {
			$is_key_repeat = Db::table('transport_support_log_management')
			->where(['tslm_level'=> 3, 'tslm_parent_id'=>$data['tslm_parent_id'], 'tslm_is_key'=>1])->find();
			if ($is_key_repeat) {
				$rs['code'] = 0;
				$rs['msg'] = '添加失败：该表格下已存在主键字段。';
				return $rs;
			}
		}
		// 检测字段名是否重复

		$is_name_repeat = Db::table('transport_support_log_management')
				->where(['tslm_level'=>$data['tslm_level'], 'tslm_name'=>$data['tslm_name'], 'tslm_parent_id'=>$data['tslm_parent_id']])->find();
		if($is_name_repeat){
			$rs['code'] = 0;
			$rs['msg'] = '添加失败：同级别中该名称已存在，请换一个名称。';
		}else{
			$add = Db::table('transport_support_log_management')->insert($data);
			if ($add) {
				$rs['code'] = 1;
				$rs['msg'] = '添加成功！';
			} else {
				$rs['code'] = 0;
				$rs['msg'] = '添加失败：发生未知错误，请联系管理员。';
			}
		}
		return $rs;
	}
	/**
	 * 编辑支撑日志结构·管理模块
	 */
	public function editTransportSupportLogManagement(Request $request){
		$data = input();
		// dataJson = {
		// 		key ： 数据表中的字段
		// 		value： 数据表中的字段值
		// 	}
		// return \json($data);
		unset($data['dataJson']['hasChildren']);
		$rs = Db::table('transport_support_log_management')
				->where('tslm_id',$data['tslm_id'])->update($data['dataJson']);
		return json($rs);
	}
	/**
	 * 删除支撑日志结构·管理模块
	 */
	public function deleteTransportSupportLogManagement(Request $request){
		$params = \input();
		$rs = Db::table('transport_support_log_management')->where('tslm_id',$params['tslm_id'])->delete();
		return \json($rs);
	}
	
	/**
	 * 支撑日志登记·查·权
	 * 难题与解决方案
	 * 1.支撑日志数据无法常规搜索-递归+模糊搜索算法
	 * 2.新增字段无法常规搜索-仅向后台发送有值的搜索数据，如果搜索条件为空则加载整张表数据
	 */
	public function TransportSupportLogAdmin(Request $request){
		$params = input();
		/* 获取待跟进数据 */
		function handlGetIncompleteData($tslm_id_l2){
			// 加载结束时间（督办获取）为空的数据
			$endTimeId = Db::table('transport_support_log_management')
			->where(['tslm_parent_id'=>$tslm_id_l2,'tslm_name'=>'结束时间（督办获取）'])
			->find()['tslm_id'];
			$tableDataBySQL = Db::table('transport_support_log_data')
			// notlike 模糊搜索 非值
			->where(['tslm_id_l2'=>$tslm_id_l2,'tslmD_data'=>['notlike','%"'.$endTimeId.'":"%']])
			-> order(['tslmD_id DESC'])// 降序
			->select();
			return $tableDataBySQL;
		}

		// $params['getNextLevelData'] 如果为空则表示只获取1,2级表数据，如果为1则获取3级字段数据
		if(empty($params['getNextLevelData'])){
			/** 
			 * empty() 函数用于检查一个变量是否为空。
			 * empty() 判断一个变量是否被认为是空的。当一个变量并不存在，或者它的值等同于 FALSE，那么它会被认为不存在。如果变量不存在的话，empty()并不会产生警告。
			 * 当 var 存在，并且是一个非空非零的值时返回 FALSE 否则返回 TRUE。
			 * 以下的变量会被认为是空的：
			 * "" (空字符串)
			 * 0 (作为整数的0)
			 * 0.0 (作为浮点数的0)
			 * "0" (作为字符串的0)
			 * NULL
			 * FALSE
			 * array() (一个空数组)
			 * $var; (一个声明了，但是没有值的变量)
			*/

			// 获取
			$getLevelOne = Db::table('transport_support_log_management')->where(['tslm_level'=>1,'tslm_is_active'=>1])
							->order((['tslm_sort asc']))
							->select();
			// $params['levelOneid'] != '' || $params['levelOneid'] = 1;
			for($i=0; $i<\sizeof($getLevelOne); $i++){
				$getLevelTwo = Db::table('transport_support_log_management')->where(['tslm_parent_id'=>$getLevelOne[$i]['tslm_id'],'tslm_is_active'=>1])
								->order((['tslm_sort asc']))
								->select();
				for($j=0; $j<\sizeof($getLevelTwo); $j++){
					$getLevelTree = Db::table('transport_support_log_management')->where(['tslm_parent_id'=>$getLevelTwo[$j]['tslm_id'],'tslm_is_active'=>1])
									->order((['tslm_sort asc']))
									->select();
					for($z=0; $z<\sizeof($getLevelTree); $z++){
						if($getLevelTree[$z]['files[0].name.split('.').pop()'] != ''){
							$getLevelTree[$z]['files[0].name.split('.').pop()'] = explode ( ',', $getLevelTree[$z]['files[0].name.split('.').pop()'] );
						}
					}
					$getLevelTwo[$j]['children'] = $getLevelTree;
	
				}
				$getLevelOne[$i]['children'] = $getLevelTwo;
			}
			// 如果初次打开页面，自动加载第一张表格的数据
			$first_l1_l2_tslm_id = $getLevelOne[0]['children'][0]['tslm_id'];
			$tableDataBySQL = handlGetIncompleteData($first_l1_l2_tslm_id);
			$pagination['pageSizeArr'] = [10, 20, 30, 40, 50, 60, 70, 80, 90, 100, 200, 300, 400];
			$pagination['pageSize'] = $pagination['pageSizeArr'][0];
			$pagination['total'] = sizeof($tableDataBySQL);
			$pagination['currentPage'] = 1;
			$this->assign([
				'tableDataBySQL'=>array_slice($tableDataBySQL, 0, $pagination['pageSize']),
				'levelOneList'=>$getLevelOne,
				'pagination'=>$pagination,
			]);
			// return \json($endTimeId);
			// return \json($tableDataBySQL);
			// return \json($getLevelOne);
			return $this->fetch();
		}else{
			$searchKeyWordsJson = $params['searchKeyWordsJson'];
			// return json($searchKeyWordsJson);
			$rs = [];
			$tableData = [];
			$pagination = [];
			if(empty($searchKeyWordsJson['ADN']) && empty($searchKeyWordsJson['OR']) && empty($searchKeyWordsJson['other']['create_time']) && empty($searchKeyWordsJson['other']['update_time'])){
				// 如果没有搜索条件则默认加载该张表所有待跟进数据（既结束时间（督办获取）为空的数据）
				$tableData = handlGetIncompleteData($params['tslm_id_l2']);
			}else{
				// php调用函数要放在函数声明后面
				$AndQueryArr = []; // 交集查询数组
				for($i = 0; $i < \sizeof($searchKeyWordsJson['ADN']); $i++){
					array_push($AndQueryArr, '%'.html_entity_decode($searchKeyWordsJson['ADN'][$i]).'%');
				}
				$ORQueryArr = []; // 并集查询数组
				for($i = 0; $i < \sizeof($searchKeyWordsJson['OR']); $i++){
					array_push($ORQueryArr, '%'.html_entity_decode($searchKeyWordsJson['OR'][$i]).'%');
				}
				if(empty($AndQueryArr)){
					// 两个查询条件都为空的的话不会进入这个大循环
					$AndQueryArr = ['%%'];
				}
				if(empty($ORQueryArr)){
					$ORQueryArr = ['%%'];
				}
				$createTimeArr = $searchKeyWordsJson['other']['create_time'];
				$updateTimeArr = $searchKeyWordsJson['other']['update_time'];
				if($createTimeArr == ''){
					$createTimeArr = ['1970-01-10', '2100-01-10'];
				}
				if($updateTimeArr == ''){
					$updateTimeArr = ['1970-01-10', '2100-01-10'];
				}
				// TP5高级查询————区间查询！！
				$tableData = Db::table('transport_support_log_data')
					-> where('tslm_id_l2', $params['tslm_id_l2'])
					-> where('tslmD_data', 'like', $AndQueryArr)
					-> where('tslmD_data', 'like', $ORQueryArr, 'OR')
					-> where("update_time between '".$updateTimeArr[0]."' and '".$updateTimeArr[1]."'")
					-> where("create_time between '".$createTimeArr[0]."' and '".$createTimeArr[1]."'")
					-> order(['tslmD_id DESC'])// 降序
					// -> whereTime('create_time', 'between', $createTimeArr) // whereTime方法 有bug。。。。
					// -> whereTime('update_time', 'between', $updateTimeArr)
					-> select();
			}
			$pagination = $params['pagination'];
			$pagination['total'] = \sizeof($tableData);
			$currentPage = $params['pagination']['currentPage'];
			$pageSize = $params['pagination']['pageSize'];
			$rs['pagination'] = $pagination;
			if($tableData){
				$rs['tableData'] = array_slice($tableData, ( $currentPage - 1 ) * $pageSize, $pageSize);
			}else{
				$rs['tableData'] = [];
			}
			return $rs;
		}
	}
	/**
	 * 支撑日志登记·查
	 * 难题与解决方案
	 * 1.支撑日志数据无法常规搜索-递归+模糊搜索算法
	 * 2.新增字段无法常规搜索-仅向后台发送有值的搜索数据，如果搜索条件为空则加载整张表数据
	 */
	public function TransportSupportLog(Request $request){
		$params = input();
		/* 获取待跟进数据 */
		function handlGetIncompleteData($tslm_id_l2){
			// 加载结束时间（督办获取）为空的数据
			$endTimeId = Db::table('transport_support_log_management')
			->where(['tslm_parent_id'=>$tslm_id_l2,'tslm_name'=>'结束时间（督办获取）'])
			->find()['tslm_id'];
			$tableDataBySQL = Db::table('transport_support_log_data')
			// notlike 模糊搜索 非值
			->where(['tslm_id_l2'=>$tslm_id_l2,'tslmD_data'=>['notlike','%"'.$endTimeId.'":"%']])
			->order(['tslmD_id DESC'])// 降序
			->select();
			return $tableDataBySQL;
		}

		// $params['getNextLevelData'] 如果为空则表示只获取1,2级表数据，如果为1则获取3级字段数据
		if(empty($params['getNextLevelData'])){
			/** 
			 * empty() 函数用于检查一个变量是否为空。
			 * empty() 判断一个变量是否被认为是空的。当一个变量并不存在，或者它的值等同于 FALSE，那么它会被认为不存在。如果变量不存在的话，empty()并不会产生警告。
			 * 当 var 存在，并且是一个非空非零的值时返回 FALSE 否则返回 TRUE。
			 * 以下的变量会被认为是空的：
			 * "" (空字符串)
			 * 0 (作为整数的0)
			 * 0.0 (作为浮点数的0)
			 * "0" (作为字符串的0)
			 * NULL
			 * FALSE
			 * array() (一个空数组)
			 * $var; (一个声明了，但是没有值的变量)
			*/

			// 获取
			$getLevelOne = Db::table('transport_support_log_management')->where(['tslm_level'=>1,'tslm_is_active'=>1])
							->order((['tslm_sort asc']))
							->select();
			// $params['levelOneid'] != '' || $params['levelOneid'] = 1;
			for($i=0; $i<\sizeof($getLevelOne); $i++){
				$getLevelTwo = Db::table('transport_support_log_management')->where(['tslm_parent_id'=>$getLevelOne[$i]['tslm_id'],'tslm_is_active'=>1])
								->order((['tslm_sort asc']))
								->select();
				for($j=0; $j<\sizeof($getLevelTwo); $j++){
					$getLevelTree = Db::table('transport_support_log_management')->where(['tslm_parent_id'=>$getLevelTwo[$j]['tslm_id'],'tslm_is_active'=>1])
									->order((['tslm_sort asc']))
									->select();
					for($z=0; $z<\sizeof($getLevelTree); $z++){
						if($getLevelTree[$z]['files[0].name.split('.').pop()'] != ''){
							$getLevelTree[$z]['files[0].name.split('.').pop()'] = explode ( ',', $getLevelTree[$z]['files[0].name.split('.').pop()'] );
						}
					}
					$getLevelTwo[$j]['children'] = $getLevelTree;
	
				}
				$getLevelOne[$i]['children'] = $getLevelTwo;
			}
			// 如果初次打开页面，自动加载第一张表格的数据
			$first_l1_l2_tslm_id = $getLevelOne[0]['children'][0]['tslm_id'];
			$tableDataBySQL = handlGetIncompleteData($first_l1_l2_tslm_id);
			$pagination['pageSizeArr'] = [10, 20, 30, 40, 50, 60, 70, 80, 90, 100, 200, 300, 400];
			$pagination['pageSize'] = $pagination['pageSizeArr'][0];
			$pagination['total'] = sizeof($tableDataBySQL);
			$pagination['currentPage'] = 1;
			$this->assign([
				'tableDataBySQL'=>array_slice($tableDataBySQL, 0, $pagination['pageSize']),
				'levelOneList'=>$getLevelOne,
				'pagination'=>$pagination,
			]);
			// return \json($endTimeId);
			// return \json($tableDataBySQL);
			// return \json($getLevelOne);
			return $this->fetch();
		}else{
			$searchKeyWordsJson = $params['searchKeyWordsJson'];
			// return json($searchKeyWordsJson);
			$rs = [];
			$tableData = [];
			$pagination = [];
			if(empty($searchKeyWordsJson['ADN']) && empty($searchKeyWordsJson['OR']) && empty($searchKeyWordsJson['other']['create_time']) && empty($searchKeyWordsJson['other']['update_time'])){
				// 如果没有搜索条件则默认加载该张表所有待跟进数据（既结束时间（督办获取）为空的数据）
				$tableData = handlGetIncompleteData($params['tslm_id_l2']);
			}else{
				// php调用函数要放在函数声明后面
				$AndQueryArr = []; // 交集查询数组
				for($i = 0; $i < \sizeof($searchKeyWordsJson['ADN']); $i++){
					array_push($AndQueryArr, '%'.html_entity_decode($searchKeyWordsJson['ADN'][$i]).'%');
				}
				$ORQueryArr = []; // 并集查询数组
				for($i = 0; $i < \sizeof($searchKeyWordsJson['OR']); $i++){
					array_push($ORQueryArr, '%'.html_entity_decode($searchKeyWordsJson['OR'][$i]).'%');
				}
				if(empty($AndQueryArr)){
					// 两个查询条件都为空的的话不会进入这个大循环
					$AndQueryArr = ['%%'];
				}
				if(empty($ORQueryArr)){
					$ORQueryArr = ['%%'];
				}
				$createTimeArr = $searchKeyWordsJson['other']['create_time'];
				$updateTimeArr = $searchKeyWordsJson['other']['update_time'];
				if($createTimeArr == ''){
					$createTimeArr = ['1970-01-10', '2100-01-10'];
				}
				if($updateTimeArr == ''){
					$updateTimeArr = ['1970-01-10', '2100-01-10'];
				}
				// TP5高级查询————区间查询！！
				$tableData = Db::table('transport_support_log_data')
					-> where('tslm_id_l2', $params['tslm_id_l2'])
					-> where('tslmD_data', 'like', $AndQueryArr)
					-> where('tslmD_data', 'like', $ORQueryArr, 'OR')
					-> where("update_time between '".$updateTimeArr[0]."' and '".$updateTimeArr[1]."'")
					-> where("create_time between '".$createTimeArr[0]."' and '".$createTimeArr[1]."'")
					-> order(['tslmD_id DESC'])// 降序
					// -> whereTime('create_time', 'between', $createTimeArr) // whereTime方法 有bug。。。。
					// -> whereTime('update_time', 'between', $updateTimeArr)
					-> select();
			}
			$pagination = $params['pagination'];
			$pagination['total'] = \sizeof($tableData);
			$currentPage = $params['pagination']['currentPage'];
			$pageSize = $params['pagination']['pageSize'];
			$rs['pagination'] = $pagination;
			if($tableData){
				$rs['tableData'] = array_slice($tableData, ( $currentPage - 1 ) * $pageSize, $pageSize);
			}else{
				$rs['tableData'] = [];
			}
			return $rs;
		}
	}
	/**
	 * 支撑日志登记·增·行/批量
	 */
	public function addTransportSupportLog(Request $request){
		if(!\input()['import']){
			// 新增一行数据
			$params['tslm_id_l2'] = \input()['tslm_id_l2'];
			$params['tslmD_data'] = html_entity_decode(\input()['tslmD_data']);
			$tslm_is_keyData = \input()['tslm_is_keyData'];
			// 检测主键字段是否重复
			$is_key_repeat = Db::table('transport_support_log_data')
							->where('tslmD_data', ['like', '%"'.$tslm_is_keyData['tslm_id'].'":"'.$tslm_is_keyData['value'].'"%'])->find();
			
			$rs = [];
			if ( !$is_key_repeat ) {
				// 添加一条数据，并返回id
				$tslmD_id = Db::table('transport_support_log_data')->insertGetId($params);
				if ( $tslmD_id ) {
					$rs['code'] = 1;
					$rs['msg'] = '新增成功';
					$rs['tslmD_id'] = $tslmD_id;
				} else {
					$rs['code'] = 0;
					$rs['msg'] = '新增失败，请刷新重试或联系管理员';
				}
			} else {
				$rs['code'] = 0;
				$rs['msg'] = '【错误类型：主键重复】：'.$tslm_is_keyData['tslm_name'].'不可重复，请修改该字段值';
			}
			return \json($rs);
		}else{
			// 批量导入数据
			$excelData = \input()['excelData'];
			$rs = [];
			$rs['code'] = 1;
			$rs['msg'] = '导入成功！';
			$rs['is_key_errMsg'] = []; // 存储主键字段重复数据错误信息
			$rs['is_import_errMsg'] = []; // 存储导入时的错误

			for($i = 0; $i < \sizeof($excelData); $i++){
				$params['tslm_id_l2'] = $excelData[$i]['tslm_id_l2'];
				$params['tslmD_data'] = html_entity_decode($excelData[$i]['tslmD_data']);
				
				// 检测主键字段是否重复
				$isKeyStr = html_entity_decode($excelData[$i]['isKeyStr']);
				if( $isKeyStr ) {
					$is_key_repeat =  Db::table('transport_support_log_data')
						->where('tslm_id_l2', $params['tslm_id_l2'])
						->where('tslmD_data', ['like', '%'.$isKeyStr.'%'])
						->find();
				}
				
				if ($is_key_repeat) {
					// 发现主键字段值重复
					\array_push($rs['is_key_errMsg'], $params);
				} else {
					// 添加一条数据，并返回id
					$importRs = Db::table('transport_support_log_data')->insert($params);
					if($importRs !== 1){
						\array_push($rs['is_import_errMsg'], $params);
					}
				}
			}
			if (sizeof($rs['is_key_errMsg']) !== 0 || sizeof($rs['is_import_errMsg']) !== 0) {
				$rs['code'] = -1; // code = 0（系统自带） 用于判断用户是否具有操作权限
				$rs['msg'] = '导入失败';
			} 
			
			return $rs;
		}
	}
	/**
	 * 支撑日志登记·权·改·行 
	 * 去除冲突检测，可对数据进行任意修改，管理员权限
	 */
	public function editTransportSupportLogAdmin(Request $request){
		$params['tslmD_id'] = \input()['tslmD_id'];
		// 如果star_state字段存在则对其进行更新，若不存在该字段则跳过
		if (array_key_exists('star_state',\input())) {
			$params['star_state'] = \input()['star_state'];	
		}
		// 如果flag_state字段存在则对其进行更新，若不存在该字段则跳过
		if (array_key_exists('flag_state',\input())) {
			$params['flag_state'] = \input()['flag_state'];	
		}
		$rs = [];
		// 检测主键字段的数据是否重复
		$tslm_id_l2 = Db::table('transport_support_log_data')
							->where('tslmD_id',$params['tslmD_id'])
							->find()['tslm_id_l2'];
		$keyData = html_entity_decode( \input()['keyData']);
		if ($keyData !== '') {
			$is_key_repeat = Db::table('transport_support_log_data')
				->where('tslmD_data',['like', '%'.$keyData.'%'])
				->where('tslmD_id','<>', $params['tslmD_id'])
				->where('tslm_id_l2',$tslm_id_l2)
				->select();
		}
		if (\sizeof($is_key_repeat) > 0) {
			$rs['code'] = -1;
			$rs['msg'] = '操作失败：该字段值已存在。';
		} else {
			$params['tslmD_data'] = html_entity_decode(\input()['tslmD_data']);
			$params['update_time'] = date('Y-m-d H:i:s');
			$upd = Db::table('transport_support_log_data')->update($params);
			if ($upd) {
				$rs['code'] = 1;
				$rs['msg'] = '操作成功';
			} else {
				$rs['code'] = 0;
				$rs['msg'] = '操作失败：未知错误，请联系管理员';
			}
		}				
		return $rs;
	}
	/**
	 * 支撑日志登记·改·行
	 */
	public function editTransportSupportLog(Request $request){
		$params['tslmD_id'] = \input()['tslmD_id'];
		// 如果star_state字段存在则对其进行更新，若不存在该字段则跳过
		if (array_key_exists('star_state',\input())) {
			$params['star_state'] = \input()['star_state'];	
		}
		// 如果flag_state字段存在则对其进行更新，若不存在该字段则跳过
		if (array_key_exists('flag_state',\input())) {
			$params['flag_state'] = \input()['flag_state'];	
		}
		$rs = [];
		// 检测主键字段的数据是否重复
		$tslm_id_l2 = Db::table('transport_support_log_data')
			->where('tslmD_id',$params['tslmD_id'])
			->find()['tslm_id_l2'];
		$keyData = html_entity_decode(\input()['keyData']);
		if ($keyData !== '') {
			$is_key_repeat = Db::table('transport_support_log_data')
				->where('tslmD_data',['like', '%'.$keyData.'%'])
				->where('tslmD_id','<>', $params['tslmD_id'])
				->where('tslm_id_l2',$tslm_id_l2)
				->select();
		}
		$rs['is_key_repeat'] = $is_key_repeat;
		if (\sizeof($is_key_repeat) > 0) {
			$rs['code'] = -1;
			$rs['msg'] = '操作失败：该字段值已存在。';
			return $rs;
		} 
		

		$params['tslmD_data'] = html_entity_decode(\input()['tslmD_data']);
		$last_tslmD_data = html_entity_decode(\input()['last_tslmD_data']);
		$params['update_time'] = date('Y-m-d H:i:s');
		// return \json($params);
		// 冲突检测  注：$isConflict有值的时候说明没有冲突，没有值的时候说明有冲突
		$isConflict = Db::table('transport_support_log_data')
			->where('tslmD_id',$params['tslmD_id'])
			->where('tslmD_data' , $last_tslmD_data)
			->find();
		if (!$isConflict) {
			$rs['code'] = 0;
			$rs['msg'] = '该数据已被其他同事修改，已帮您获取最新数据，请重新编辑';
			// 返回最新数据
			$data = Db::table('transport_support_log_data')->where('tslmD_id',$params['tslmD_id'])->find();;
			$rs['data'] = $data;
			$rs['params'] = $params;
		} else {
			$params['update_time'] = date('Y-m-d H:i:s');
			$upd = Db::table('transport_support_log_data')->update($params);
			if ($upd) {
				$rs['code'] = 1;
				$rs['msg'] = '操作成功';
			} else {
				$rs['code'] = 0;
				$rs['msg'] = '操作失败：未知错误，请联系管理员';
			}
		}				
		return $rs;

	}
	/**
	 * 支撑日志登记·删·行
	 */
	public function deleteTransportSupportLog(Request $request){
		$params = \input();
		$rs = Db::table('transport_support_log_data')->where('tslmD_id',$params['tslmD_id'])->delete();
		return \json($rs);
	}
	
	/**
	 * RSA公钥和私钥配置
	 */
	public function RSA(Request $request){
		# RSA公钥与私钥配置

		$RSA_PUBLIC_KEY = '-----BEGIN PUBLIC KEY-----
		MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDD0DpbuIwTqHvCVEuigoC9yPce
		SQ8oTVpjgDGJuBeVj9lAd6St8nVEahB1BQuIZ3+NtR78iLQu23wT+MhstIDTzR48
		c+7lpy7ogEMQ/tCD17zhoQH6TrcYeaLFv63t7Ls0ii5VeLQ4yHMLz8fTEQFu12pL
		fViW+xDz7SJvb83fnwIDAQAB
		-----END PUBLIC KEY-----';
		$RSA_PRIVATE_KEY = '-----BEGIN RSA PRIVATE KEY-----
		MIICXQIBAAKBgQDD0DpbuIwTqHvCVEuigoC9yPceSQ8oTVpjgDGJuBeVj9lAd6St
		8nVEahB1BQuIZ3+NtR78iLQu23wT+MhstIDTzR48c+7lpy7ogEMQ/tCD17zhoQH6
		TrcYeaLFv63t7Ls0ii5VeLQ4yHMLz8fTEQFu12pLfViW+xDz7SJvb83fnwIDAQAB
		AoGBAKQY+u8mx20qAx0mG4SDLXTe2AmvXF6ABMHiCqHJfyo9tOlL2txTlmbRJB2N
		Ls8PPVv6b49e2PFzypXKJdzDoDlmT55E5yOHOmeBwTzjnZC+C7jzdEVy2XIRM2Ig
		un+sb1ZAnEojP1WxIvZ1vlmaSnY7lJY1prhpXkvkvMGcjtqxAkEA9zD3CYcF5SzZ
		50TKd4js6sBPvGz3mnABNZkLnSWuzTL0p24CEAsfY6v1bxfD+ZSy53dHcW1y8nVT
		4zta2aaI7QJBAMrKj71xPbAQGjGfvofT2fR4Ylpave2kngt0QZswHvKQ5b+hsHA2
		zaNrqlaPVzcaBi4UI5LRPjqZpJesYE44dTsCQQCcVDw3q0vgJyBb8ZZ2SINE54DF
		8sgYxLMPGY5NwTIulgZCCQGG8fHVEEB1FLudERyf5ECrjIOAsRDviW8obPj1AkAK
		pcIQT07O3LmTW3DUjuIFvQBlABiyzo7hyRPcwxUM5WC6xBGQgsAfUXrbGqGYqgwj
		BTms7sGWsBR9Rja0RLCbAkAampkYTbyd3pCaShMjMvxKJUmGN52FM1gfegeQzKOo
		y5HSY56lr8CaWytXDa75CpCEgqR0oIsGe/A0co6+zdsX
		-----END RSA PRIVATE KEY-----';
		return $RSA_PUBLIC_KEY;
	}
}