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
 * 交接班管理类
 * @author kongweihao
 *
 */
// class RotaSituation extends Controller{ // 调试用的，没有做权限限制
class ChangeShifts extends Auth{ // 上线用的
	/**
	 * 公共测试接口
	 */
	public function test(Request $request){
		
	}
	
	/**
	 * 交接班模具管理列表·查
	 * GET：初次加载以及搜索
	 * POST：其他操作
	 * 包含：工作项管理查询以及岗位类型与工作项关联关系查询
	 */
	public function changeShiftsMouldManagement(Request $request) {
		/**
		* 生成无限级树算法
		* @author Baiyu 2014-04-01
		* @param array $arr        输入数组
		* @param number $pid        根级的pid
		* @param string $column_name    列名,id|pid父id的名字|children子数组的键名
		* @return array $ret
		*/
		function make_tree($arr, $pid = 0, $column_name = 'id|pid|children') {
			list($idname, $pidname, $cldname) = explode('|', $column_name);
			$ret = array();
			foreach ($arr as $k => $v) {
				if ($v [$pidname] == $pid) {
					$tmp = $arr [$k];
					unset($arr [$k]);
					$tmp [$cldname] = make_tree($arr, $v [$idname], $column_name);
					if ($tmp [$cldname] === []) {
						unset($tmp [$cldname]);
					}
					$ret [] = $tmp;
				}
			}
			return $ret;
		}
		if ($request->isGet()) {
			$getNextLevel = \input('getNextLevel') !== null ? \input('getNextLevel') : 1;
			$tim_parent_id = \input('tim_parent_id') !== null ? \input('tim_parent_id') : 0;

			$timList = Db::table('task_items_management')
					->where('tim_level', $getNextLevel)
					->where('tim_parent_id', $tim_parent_id)
					->order(['tim_sort ASC']) // DESC降序
					->select();
			for ($i = 0; $i < \sizeof($timList); $i++) {
				$hasChildren = Db::table('task_items_management')
								->where('tim_parent_id', $timList[$i]['tim_id'])
								->find();
				// 引用类型
				if ($timList[$i]['tim_type'] === 2) {
					$rsrml3 = Db::table('rotasituationrecordmanagement')
								->where('ctrr_rsrm_id', $timList[$i]['ctrr_rsrm_id'])
								->find();
					$rsrml2 = Db::table('rotasituationrecordmanagement')
								->where('ctrr_rsrm_id', $rsrml3['ctrr_rsrm_parent_id'])
								->find();
					$timList[$i]['rsrm'] = Db::table('rotasituationrecordmanagement')
								->where('ctrr_rsrm_id', $rsrml2['ctrr_rsrm_parent_id'])
								->find();	
					$rsrml2['children'] = $rsrml3;				
					$timList[$i]['rsrm']['children'] = $rsrml2;				
				}
				if ($hasChildren) {
					$timList[$i]['hasChildren'] = true;
				}
			}
			if (\input('getNextLevel') === null){
				// 初次加载页面时没有getNextLevel参数
				$this->assign('timList', $timList);

				// 同时初次加载岗位类型与工作项对应关系数据
				$mtList = Db::table('monitorpost_type')->select();
				$mt_tiList = Db::table('monitorposttype_taskitems')
							->alias('mt_ti')
							->join('monitorpost_type mt','mt_ti.monitorPost_type_id = mt.monitorPost_type_id')
							->order(['mptti_sort ASC']) // DESC降序
							->select();
					// 【关键算法】
					// 处理tim_id_str字段
					// 说明：tim_id_str字段格式：全路径1||全路径2||全路径3...，其中路径是由逗号拼接起来的任务项id
				for ($i = 0; $i < \sizeof($mt_tiList); $i++) {
					$tim_id_arr1 = explode("||", $mt_tiList[$i]['tim_id_str']);
					$mt_tiList[$i]['tim_id_arr'] = [];  // 构造工作项全路径数组
					$mt_tiList[$i]['tim_id_tree'] = []; // 构造工作项树形数组
					$mt_tiList[$i]['taskItems'] = [];   // 获取所有相关工作项并放在同一级
					for ($j = 0; $j < \sizeof($tim_id_arr1); $j++) {
						$tim_id_arr2 = \explode(',', $tim_id_arr1[$j]);
						array_push($mt_tiList[$i]['tim_id_arr'], $tim_id_arr2);
						
						for ($z = 0; $z < \sizeof($tim_id_arr2); $z++) {
							if (!$mt_tiList[$i]['taskItems'][$tim_id_arr2[$z]]) { // 算法优化，将所有路径所包含的所有工作项同级加载出来，不分父子，且已经加载的无需再次运算
								$taskItem = Db::table('task_items_management')->where('tim_id', $tim_id_arr2[$z])->find();
								if ($taskItem) {
									// 如果工作项已经被删除，则忽略
									$mt_tiList[$i]['taskItems'][$tim_id_arr2[$z]] = $taskItem;
								}
							}
						}

					}
					// 构造树形数据结构：
					$mt_tiList[$i]['tim_id_tree'] = make_tree($mt_tiList[$i]['taskItems'], $pid = 0, 'tim_id|tim_parent_id|children');
				}
				// 构造整张工作项管理表格的树形数据结构：
				$timAll = Db::table('task_items_management')->select();
				$timTree = make_tree($timAll, $pid = 0, 'tim_id|tim_parent_id|children');

				$this->assign('timTree', $timTree);
				$this->assign('mtList', $mtList);
				$this->assign('mt_tiList', $mt_tiList);

				return $this->fetch();
			} else {
				$rs = [];
				$rs['code'] = 1;
				$rs['msg'] = '数据获取成功';
				$rs['data'] = $timList;
				return $rs;
			}
		} else if ($request->isPost()) {
			// 获取值班信息所有以及工作项某一级数据结构
			// 由于工作项可以设计无限级数，因此不要一开始就获取所有工作项级数
			$rs = [];
			$rs['code'] = 1;
			if (\input('getRsrmAllLevel') === '1') {
				// 获取第一级值班信息
				$rsrmL1 = Db::table('rotasituationrecordmanagement')
						->where('ctrr_rsrm_level', 1)
						->select();
				for ($i = 0; $i < \sizeof($rsrmL1); $i++) {
					$rsrmL2 = Db::table('rotasituationrecordmanagement')
							->where('ctrr_rsrm_level', 2)
							->where('ctrr_rsrm_parent_id', $rsrmL1[$i]['ctrr_rsrm_id'])
							->select();
					for ($j = 0; $j < \sizeof($rsrmL2); $j++) {
						$rsrmL3 = Db::table('rotasituationrecordmanagement')
						->where('ctrr_rsrm_level', 3)
						->where('ctrr_rsrm_parent_id', $rsrmL2[$j]['ctrr_rsrm_id'])
						->select();	
						$rsrmL2[$j]['children'] = $rsrmL3;				
					}
					$rsrmL1[$i]['children'] = $rsrmL2;				
				}
				if ($rsrmL1) {
					$rs['msg'] = '值班信息结构数据获取成功';
					$rs['data'] = $rsrmL1;
				} else {
					$rs['msg'] = '出现未知错误，请联系管理员';
				}
			} else if (\input('getOneOfTimLevel') === '1') {
				// 获取工作项某一级数据
				$parentLevel = \input('parentLevel');
				if ($parentLevel === 0) {
					// 获取第一级数据
					$oneTimLevel = Db::table('task_items_management')
						->where('tim_level', ($parentLevel + 1))
						->order(['tim_sort ASC']) // DESC降序
						->select();
				} else {
					// 获取下一级数据
					$parentId = \input('parentId');
					$oneTimLevel = Db::table('task_items_management')
						->where('tim_level', ($parentLevel + 1))
						->where('tim_parent_id', $parentId)
						->order(['tim_sort ASC']) // DESC降序
						->select();
				}
				if ($oneTimLevel) {
					$rs['msg'] = '【'.html_entity_decode(\input('tim_name')).'】的子工作项数据获取成功';
					$rs['data'] = $oneTimLevel;
				} else {
					$rs['code'] = 0;
					$rs['msg'] = '已没有子工作项';
				}
			}
			return $rs;
		}
	}


	// 工作项管理模块
	/**
	 * 交接班模具管理列表·工作项管理·增
	 * 
	 */
	public function addTaskItem(Request $request) {
		
		$rs = [];
		$rs['code'] = 1;
		$rs['msg']['tips'] = '操作成功';
		
		// end() 获取参数中的tim_parent_id数组的最后一项
		$tim_parent_id = end(\input('tim_parent_id/a'));

		// 检查工作项名称是否重复
		$tim_name = html_entity_decode(\input('tim_name'));
		$is_repeat_tim_name = Db::table('task_items_management')
			-> where('tim_name', $tim_name)
			-> where('tim_parent_id', $tim_parent_id)
			-> find();
		if ($is_repeat_tim_name) {
			$rs['code'] = -1;
			$rs['msg']['tips'] = '同级别的工作项名称已存在';
			$rs['msg']['errMsg']['tim_name'] = '同级别的工作项名称已存在';
		}
		// 检查父级id是否有误
		if ($tim_parent_id !== '0') {
			// 等于-1时 表示新增一个第一级，否则检测是否存在该父级，且该父级不能为引用类型
			$is_right_tim_parent_id = Db::table('task_items_management')
				-> where('tim_id', $tim_parent_id)
				->find();
			if (!$is_right_tim_parent_id) {
				$rs['code'] = -1;
				$rs['msg']['tips'] = '该父级已经不存在，请刷新重试';
				$rs['msg']['errMsg']['tim_parent_id'] = '该父级已经不存在，请刷新重试';
			} else if ($is_right_tim_parent_id['tim_type'] === 2) {
				$rs['code'] = -1;
				$rs['msg']['tips'] = '【'.$is_right_tim_parent_id['tim_name'].'】为引用类型，不可作为父级';
				$rs['msg']['errMsg']['tim_parent_id'] = '【'.$is_right_tim_parent_id['tim_name'].'】为引用类型，不可作为父级';
			}
		}
		// 检查工作项类型是否有误
		$tim_type = \input('tim_type');
		if ($tim_type !== '1' && $tim_type !== '2') {
			$rs['code'] = -1;
			$rs['msg']['tips'] = '类型选择器出现故障，请刷新重试';
			$rs['msg']['errMsg']['tim_type'] = '类型选择器出现故障，请刷新重试';
		}
		// 检测引用类型字段
		if ($tim_type === '2') {
			// 1、引用类型不能作为最高父级
			if ((int)$tim_parent_id === 0) {
				$rs['code'] = -1;
				$rs['msg']['tips'] = '引用类型不能作为最高父级';
				$rs['msg']['errMsg']['tim_parent_id'] = '引用类型不能作为最高父级';
			} 
			// 2、检测该引用类型字段数据源是否还存在
			$is_right_ctrr_rsrm_id = Db::table('rotasituationrecordmanagement')
				->where('ctrr_rsrm_id', end(\input('ctrr_rsrm_id/a')))
				->where('ctrr_rsrm_level',3)
				->find();
			if (!$is_right_ctrr_rsrm_id) {
				$rs['code'] = -1;
				$rs['msg']['tips'] = '该数据源已不存在，请刷新重试';
				$rs['msg']['errMsg']['ctrr_rsrm_id'] = '该数据源已不存在，请刷新重试';
			}
		}
		
		// 类型互斥原则 + 数据源同源原则（引用类型）
		$findBaseType = Db::table('task_items_management')
			->where('tim_parent_id', $tim_parent_id)
			->select();
		if ($findBaseType) {
			foreach ($findBaseType as $k => $v) {
				// 检查其父级是否已存在其他类型
				if ((int)$v['tim_type'] != (int)\input('tim_type')) {
					$rs['code'] = -1;
					$rs['msg']['tips'] = '违反类型互斥原则：父级下已存在其他类型字段，不可引入当前类型';
					$rs['msg']['errMsg']['tim_type'] = '违反类型互斥原则：父级下已存在其他类型字段，不可引入当前类型';
					break;
				}
				if ($tim_type === '2') {
					// 检查同一父级下其是否与其他引用类型字段属于同一张表格
					$ctrr_rsrm_parent_id1 = Db::table('rotasituationrecordmanagement')
						->where('ctrr_rsrm_id', end(\input('ctrr_rsrm_id/a')))
						->where('ctrr_rsrm_level',3)
						->find()['ctrr_rsrm_parent_id'];
					$ctrr_rsrm_parent_id2 = Db::table('rotasituationrecordmanagement')
						->where('ctrr_rsrm_id', $v['ctrr_rsrm_id'])
						->where('ctrr_rsrm_level',3)
						->find()['ctrr_rsrm_parent_id'];	
					if ((int)$ctrr_rsrm_parent_id1 != (int)$ctrr_rsrm_parent_id2) {
						$rs['code'] = -1;
						$rs['msg']['tips'] = '违反数据源同源原则：不是同一张表，该引用类型与同级下的引用类型不属于同一数据源（不属于同一张表）';
						$rs['msg']['errMsg']['tim_type'] = '违反数据源同源原则：不是同一张表，该引用类型与同级下的引用类型不属于同一数据源（不属于同一张表）';
						break;
					}
				}
			}
		}

		// 上面的校验通过之后正式新增一个工作项
		if ($rs['code'] !== -1) {
			$params = [];
			$params['tim_name'] = html_entity_decode(\input('tim_name'));
			$params['tim_sort'] = \input('tim_sort');
			$params['tim_parent_id'] = $tim_parent_id;
			$params['tim_type'] = \input('tim_type');
			$params['tim_tips'] =  html_entity_decode(\input('tim_tips'));
			$params['ctrr_rsrm_id'] = $params['tim_type'] === '1' ? '' : end(\input('ctrr_rsrm_id/a'));
			$tim_level = Db::table('task_items_management')
				->where('tim_id', $tim_parent_id)
				->find()['tim_level'];
			$params['tim_level'] = $tim_level ? ++$tim_level : 1;
			$addRs = Db::table('task_items_management')->insert($params);
		}

		return $rs;
	}
	/**
	 * 交接班模具管理列表·工作项管理·删
	 * 
	 */
	public function deleteTaskItem(Request $request) {
		// 由于是无限级设计，如果删除的是父级节点，不会删除其下的子节点，这个以后会产生垃圾数据
		// 不过由于该模块所需管理的数据量极小，因此设计允许存在少量这样的垃圾数据，未来手动清除也不会太麻烦
		$rs = [];
		$rs['code'] = 1;
		$rs['msg']['tips'] = '操作成功';
		$tim_id = \input('tim_id');
		$delRs = Db::table('task_items_management') -> where('tim_id', $tim_id) ->delete();
		if (!$delRs) {
			$rs['code'] = -1;
			$rs['msg']['tips'] = '删除失败，请重试';
			$rs['msg']['data'] = $delRs;
		}
		return $rs;
	}
	/**
	 * 交接班模具管理列表·工作项管理·改
	 * 
	 */
	public function editTaskItem(Request $request) {
		$rs = [];
		$rs['code'] = 1;
		$rs['msg']['tips'] = '操作成功';
		$rs['msg']['data'] = \input();
		$tim_id = \input('tim_id');
		
		// end() 获取参数中的tim_parent_id数组的最后一项
		$tim_parent_id = end(\input('tim_parent_id/a'));

		// 检查工作项名称是否重复
		$tim_name = html_entity_decode(\input('tim_name'));
		$is_repeat_tim_name = Db::table('task_items_management')
			-> where('tim_name', $tim_name)
			-> where('tim_parent_id', $tim_parent_id)
			-> where('tim_id', '<>', $tim_id)
			-> find();
		if ($is_repeat_tim_name) {
			$rs['code'] = -1;
			$rs['msg']['tips'] = '【名称重复】：同级别中该工作项名称已存在';
			$rs['msg']['errMsg']['tim_name'] = '【名称重复】：同级别中该工作项名称已存在';
		}	

		// 检查工作项类型是否有误
		$tim_type = \input('tim_type');
		if ($tim_type !== '1' && $tim_type !== '2') {
			$rs['code'] = -1;
			$rs['msg']['tips'] = '类型选择器出现故障，请刷新重试';
			$rs['msg']['errMsg']['tim_type'] = '类型选择器出现故障，请刷新重试';
		}
		

		// 若为引用类型，检查是否已存在子工作项（引用类型不能有子工作项）
		if ($tim_type === '2') {
			$has_children = Db::table('task_items_management')
				-> where('tim_parent_id', $tim_id)
				-> find();
			if ($has_children) {
				$rs['code'] = -1;
				$rs['msg']['tips'] = '该工作项含子工作项，不能设置为引用类型';
				$rs['msg']['errMsg']['tim_type'] = '该工作项含子工作项，不能设置为引用类型';
			}
		}

		if ($rs['code'] === 1) {
			$params = [];
			$params['tim_name'] = html_entity_decode(\input('tim_name'));
			$params['tim_sort'] = \input('tim_sort');
			$params['tim_parent_id'] = $tim_parent_id;
			$params['tim_type'] = \input('tim_type');
			$params['tim_tips'] =  html_entity_decode(\input('tim_tips'));
			$params['ctrr_rsrm_id'] = $params['tim_type'] === '1' ? '' : end(\input('ctrr_rsrm_id/a'));
			$updateRs = Db::table('task_items_management') -> where('tim_id', $tim_id) ->update($params);
			if (!$updateRs) {
				$rs['code'] = -2;
				$rs['msg']['tips'] = '数据没变化';
				$rs['msg']['data'] = $updateRs;
			}
		}
		return $rs;
	}

	// 岗位类型与工作项关联关系管理模块
	/**
	 * 交接班模具管理列表·岗位类型与工作项关联关系管理·增
	 * 
	 */
	public function addMt_ti(Request $request) {
		
		$rs = [];
		$rs['code'] = 1;
		$rs['msg']['tips'] = '操作成功';
		// 检测是否该岗位关联是否已存在
		$monitorPost_type_id = \input('monitorPost_type_id');
		$is_repeat = Db::table('monitorposttype_taskitems')->where('monitorPost_type_id', $monitorPost_type_id)->find();
		if ($is_repeat) {
			$rs['code'] = -1;
			$rs['msg']['tips'] = '该岗位的关联关系已存在，使用编辑功能即可';
			$rs['msg']['errMsg']['monitorPost_type_id'] = '该岗位的关联关系已存在，使用编辑功能即可';
		}
		// 新增
		if ($rs['code'] === 1) {
			$params = [];
			$params['monitorPost_type_id'] = \input('monitorPost_type_id');
			// 构造tim_id_str
			$tim_id_arr = \input('tim_id_arr/a');
			$arr = [];
			for ($i = 0; $i < \sizeof($tim_id_arr); $i++) {
				array_push($arr, \implode(',', $tim_id_arr[$i]));
			}
			$tim_id_str = \implode('||', $arr);
			$params['tim_id_str'] = $tim_id_str;
			$addRs = Db::table('monitorposttype_taskitems')->insert($params);
			if (!$addRs) {
				$rs['code'] = -1;
				$rs['msg']['tips'] = '操作失败：服务器错误，请联系管理员';
			}
		}
		return $rs;
	}
	/**
	 * 交接班模具管理列表·岗位类型与工作项关联关系管理·删
	 * 
	 */
	public function deleteMt_ti(Request $request) {
		// 如果删除的是父级节点，不会删除其下的子节点，这个以后会产生垃圾数据
		// 不过由于该模块所需管理的数据量极小，因此设计允许存在少量这样的垃圾数据，未来手动清除也不会太麻烦
		$rs = [];
		$rs['code'] = 1;
		$rs['msg']['tips'] = '操作成功';
		$mptti_id = \input('mptti_id');
		$deleteRs = Db::table('monitorposttype_taskitems')->where('mptti_id', $mptti_id)->delete();
		if (!$deleteRs) {
			$rs['code'] = -1;
			$rs['msg']['tips'] = '删除失败，请联系管理员';
		}
		return $rs;
	}
	/**
	 * 交接班模具管理列表·岗位类型与工作项关联关系管理·改
	 * 
	 */
	public function editMt_ti(Request $request) {
		$rs = [];
		$rs['code'] = 1;
		$rs['msg']['tips'] = '操作成功';
		$tim_id_arr = \input('tim_id/a');
		$params = [];
		$params['monitorPost_type_id'] = \input('monitorPost_type_id');
		$params['mptti_sort'] = \input('mptti_sort');
		$mptti_id = \input('mptti_id');
		// 构造tim_id_str字段
		$tim_id_arr = \input('tim_id_arr/a');
		$arr = [];
		for ($i = 0; $i < \sizeof($tim_id_arr); $i++) {
			array_push($arr, \implode(',', $tim_id_arr[$i]));
		}
		$tim_id_str = \implode('||', $arr);
		$params['tim_id_str'] = $tim_id_str;

		$updateRs = Db::table('monitorposttype_taskitems')->where('mptti_id', $mptti_id)->update($params);
		if (!$updateRs) {
			$rs['code'] = -2;
			$rs['msg']['tips'] = '没有任何更新';
		}
		return $rs;
	}

	// 交接班填写模块
	/**
	 * 交接班填写模块
	 */
	public function iWantchangeShifts(Request $request){
		$adminId = Session::get('adminId');
		$adminInfo = Db::table('admin')
			->alias('ad')
			->join('admin_member adm', 'ad.id = adm.admin_id')
			->join('member m', 'm.id = adm.member_id')
			->where('ad.id', $adminId)
			->find();
		$mt_tiList = [];
		$this->assign('adminInfo',$adminInfo);
		
		// 2~3级所有字段数据
		$l2_to_l3_allFiled = [];
		$l2_allFiled = Db::table('rotasituationrecordmanagement')->where('ctrr_rsrm_level', 2)->select();
		for ($i = 0; $i < \sizeof($l2_allFiled); $i++) {
			$l3_allFiled = Db::table('rotasituationrecordmanagement')->where('ctrr_rsrm_parent_id', $l2_allFiled[$i]['ctrr_rsrm_id'])->select();
			$l2_to_l3_allFiled[$l2_allFiled[$i]['ctrr_rsrm_id']] = [];
			for ($j = 0; $j < \sizeof($l3_allFiled); $j++) {
				$l2_to_l3_allFiled[$l2_allFiled[$i]['ctrr_rsrm_id']][$l3_allFiled[$j]['ctrr_rsrm_id']] = $l3_allFiled[$j];
			}

		}
		$this->assign([
			'l2_to_l3_allFiled' => $l2_to_l3_allFiled,
		]);

		// 是否值班人员
		if($adminInfo) {
			/** 构造交接班树形数据三步走
			 * 第一步：制作原始树形结构数据：将已设计好的工作项构造成树形结构，并对每一个数据项进行岗位类型鉴权，disabled = true时表示没有权限
			 * 		   同时获取引用类型原始数据；
			 * 第二步：提升子级：遍历树：利用make_tree制作好的素材，将引用类型相关信息提前到父级中；
			 * 第三步：制作交接班数据：再次遍历树：make_tree_toMakeData制作好素材后，将素材合成交接班数据；
			 */

			/**
			 * 第一步 制作原始树形结构数据
			* 生成无限级树算法
			* @author Baiyu 2014-04-01
			* @param array $arr        输入数组
			* @param number $pid        根级的pid
			* @param string $column_name    列名,id|pid父id的名字|children子数组的键名
			* @param taskItem $json        输入json数组，岗位工作项权限json数组，利用json的键名索引的特性，将tim_id作为键名进行鉴权判断时的索引
			* @return array $ret
			*/
			function make_tree($arr, $pid = 0, $column_name = 'id|pid|children', $taskItem = []) {
				list($idname, $pidname, $cldname) = explode('|', $column_name);
				$ret = array();
				foreach ($arr as $k => $v) {
					// 赋予权限
					$taskItem[$v[$idname]] ? ( $arr[$k]['disabled'] = false) : ( $arr[$k]['disabled'] = true ) ;
					// 构造确认信息字段，保存当前确认状态、确认驳回流程（由‘确认者->驳回者->确认者->驳回者->确认者'的形式构成）
					$arr[$k]['confirmMsg'] = [];
					$arr[$k]['confirmMsg']['is_confirm'] = false;
					$arr[$k]['confirmMsg']['msg'] = ' ';

					// 加载引用类型数据
					if ($v['tim_type'] == 2) {
						// 获取待跟进数据：默认为当前班次数据，判断字段为【结束时间（督办获取）】
						$ctrr_rsrm_id = $v['ctrr_rsrm_id'];
						$rsrmL2Id = Db::table('rotasituationrecordmanagement')->where('ctrr_rsrm_id', $v['ctrr_rsrm_id'])->find()['ctrr_rsrm_parent_id'];
						// 同级别表格中的待跟进事项id
						$endTimeId = Db::table('rotasituationrecordmanagement')
							->where('ctrr_rsrm_parent_id', $rsrmL2Id)
							->where('ctrr_rsrm_name', '结束时间（督办获取）')
							->find()['ctrr_rsrm_id'];
						// 获取交接班数据：待跟进事项全部加载逻辑十分简单（加载该表格下所有结束时间（督办获取）为空的数据）
						$data = Db::table('rotasituationrecordmanagementdata')
							->where('ctrr_rsrm_id_l2', $rsrmL2Id)
							->where('ctrr_rsrmD_data','notlike','%"'.$endTimeId.'":"%')
							->select();
						for ($i = 0; $i < \sizeof($data); $i++) {
							$data[$i]['ctrr_rsrmD_data'] = \json_decode($data[$i]['ctrr_rsrmD_data'], true); // 加true转换为数组
						}
						$arr[$k]['rsrmL2Id'] = $rsrmL2Id;
						$arr[$k]['endTimeId'] = $endTimeId;
						$arr[$k]['data'] = $data;
					}

					// 树形结构构造
					if ($v [$pidname] == $pid) {
						$tmp = $arr [$k];
						unset($arr [$k]);
						$tmp [$cldname] = make_tree($arr, $v [$idname], $column_name, $taskItem);
						if ($tmp [$cldname] == []) {
							unset($tmp [$cldname]);
						}
						$ret [] = $tmp;
					}
				}
				return $ret;
			}
			
			// 第二步 提升子级
			function make_tree_promoteChildrenMsg($tree) {
				for ($i = 0; $i < \sizeof($tree); $i++) {
					// 构造父级索引
					if ($tree[$i]['index'] == null) {
						$tree[$i]['index'] = (string)$i;
					}
					if ($tree[$i]['children']) {
						$tree[$i]['is_leaf'] = false;
						// 存放类型2的子级字段顺序
						$tree[$i]['type2ChildrenKeyForSort'] = [];
						// 存放类型2的子级cttr_rsrm_id 与 tim_name对应关系
						$tree[$i]['type2Children'] = [];
						// 存放类型2的子级数据
						$tree[$i]['type2ChildrenDataSourceMaterial'] = [];
						// 将子级类型为引用类型的数据提前到父级中
						foreach ($tree[$i]['children'] as $j => $treeChildrenItem) {
							// 为每个元素加上索引
							// 我的索引包含所有父级信息
							$tree[$i]['children'][$j]['index'] = $tree[$i]['index'].'|'.$j;
							if ($treeChildrenItem['tim_type'] == 2) {
								array_push($tree[$i]['type2ChildrenKeyForSort'], $treeChildrenItem['ctrr_rsrm_id']);
								$tree[$i]['type2Children'][$treeChildrenItem['ctrr_rsrm_id']] =  $treeChildrenItem['tim_name'];
								// 去除引用类型工作项，否则前端加载会有垃圾数据
								unset($tree[$i]['children'][$j]);
								--$j; // 索引回退
							}
							// 由于模块设计缺陷，导致同一父级下的每个引用类型都带上了该级别下的所有引用类型数据（既每个同父级的引用类型中所带的数据都一样），
							// 因此这里获取子级数据的时候做一下判断，曾经获取一次之后便不再获取了
							if (!$tree[$i]['type2ChildrenDataSourceMaterial']) {
								$tree[$i]['type2ChildrenDataSourceMaterial'] = $treeChildrenItem['data'];
							}
						}
						$tree[$i]['children'] = make_tree_promoteChildrenMsg($tree[$i]['children']);
					}
				}
				return $tree;
			}

			// 第三步 制作交接班数据
			function make_tree_MakeData($tree) {
				for ($i = 0; $i < \sizeof($tree); $i++) {
					$tree[$i]['type2ChildrenData'] = [];
					// 制作字符串
					for ($j = 0; $j < \sizeof($tree[$i]['type2ChildrenDataSourceMaterial']); $j++) {
						$str = ($j + 1).'、';
						for ($z = 0; $z <\sizeof($tree[$i]['type2ChildrenKeyForSort']); $z++) {
							if ($tree[$i]['type2Children'][$tree[$i]['type2ChildrenKeyForSort'][$z]] == '故障原因' || $tree[$i]['type2Children'][$tree[$i]['type2ChildrenKeyForSort'][$z]] == '处理进展' ) {
								$str .= "\r\n".$tree[$i]['type2Children'][$tree[$i]['type2ChildrenKeyForSort'][$z]].'：'.$tree[$i]['type2ChildrenDataSourceMaterial'][$j]['ctrr_rsrmD_data'][$tree[$i]['type2ChildrenKeyForSort'][$z]];  //如何输出换行符："\r\n"，注意一定要双引号
							} else {
								$str .= $tree[$i]['type2ChildrenDataSourceMaterial'][$j]['ctrr_rsrmD_data'][$tree[$i]['type2ChildrenKeyForSort'][$z]]."  "; 
							}
						}
						array_push($tree[$i]['type2ChildrenData'], $str);
					}
					$tree[$i]['type2ChildrenDataStr'] = implode("\r\n", $tree[$i]['type2ChildrenData']);
					if ($tree[$i]['children']) {
						$tree[$i]['children'] = make_tree_MakeData($tree[$i]['children']);
					} else {
						// 如果子级已经为空，也将子级删除，否则这些垃圾数据会影响交接班前端的确认与驳回功能
						unset($tree[$i]['children']);
						// $tree[$i]['children'] = 1;
						$tree[$i]['is_leaf'] = true;
					}
				}
				return $tree;
			}

			/**
			 * 另外的 如果时加载草稿数据，则需要对草稿数据中的每一级的disabled字段进行重新鉴权赋值
			* @author kwh 2028-08-27
			* @param array $csh_data          输入json解码后的交接班历史数据
			* @param array（json） $taskItem   输入json数组，岗位工作项权限json数组，利用json的键名索引的特性，将tim_id作为键名进行鉴权判断时的索引
			* @return 空
			*/
			function authForDraft ($csh_data, $taskItem = []) {
				foreach ($csh_data as $k => $v) {
					// 重新鉴权
					$taskItem[$csh_data[$k]['tim_id']] ? ( $csh_data[$k]['disabled'] = false) : ( $csh_data[$k]['disabled'] = true ) ;
					if ($csh_data[$k]['children']) {
						$csh_data[$k]['children'] = authForDraft ($csh_data[$k]['children'], $taskItem);
					}
				}
				return $csh_data;
			}

			/**
			 * 制作新的草稿（适用于两种情况：1、系统第一次运行，数据表为空；2、上一班次已完成交接动作，要开始新的班次）
			 * @author kwh 2028-09-01
			 * @param String $time_stamp    输入要创建草稿的日期
			 * @param int $is_day_shift    白班还是夜班
			 * @return array（json） $treeForFrontend 前端用于构造数据的数
			*/
			function makeDraft($time_stamp, $is_day_shift, $nowMyTask) {
				// 构造交接班树形结构数据
				$allTask = Db::table('task_items_management')->order('tim_sort','ASC')->select();
				$treeForFrontend = make_tree($allTask, 0, 'tim_id|tim_parent_id|children', $nowMyTask);
				$treeForFrontend = make_tree_promoteChildrenMsg($treeForFrontend);
				$treeForFrontend = make_tree_MakeData($treeForFrontend);
				// 插入草稿
				$insertParams = [];
				$insertParams['is_day_shift'] = $is_day_shift;
				$insertParams['time_stamp'] = $time_stamp;

				$insertParams['csh_data'] = \json_encode($treeForFrontend, JSON_UNESCAPED_UNICODE); // JSON_UNESCAPED_UNICODE转义时保留中文，必须PHP5.4+
				$insert = Db::table('change_shifts_history')->insert($insertParams);
				return $treeForFrontend;
			}

			/**
			 * 重新获取最新数据（适用于1种情况：值班长强制获取最新数据并清空草稿）
			 * @author kwh 2028-09-02
			 * @param Json $draft   待重置的草稿
			 * @return array（json） $treeForFrontend 前端用于构造数据的数
			*/
			function updateDraft($draft, $nowMyTask, $isUpdate) {
				// $isUpdate 1或0 是否更新草稿箱数据，有时候只需要获取最新数据
				// 构造交接班树形结构数据
				$allTask = Db::table('task_items_management')->order('tim_sort','ASC')->select();
				$treeForFrontend = make_tree($allTask, 0, 'tim_id|tim_parent_id|children', $nowMyTask);
				$treeForFrontend = make_tree_promoteChildrenMsg($treeForFrontend);
				$treeForFrontend = make_tree_MakeData($treeForFrontend);
				// 重置草稿
				if ($isUpdate === 1) {
					$update = Db::table('change_shifts_history')
					->where('time_stamp', $draft['time_stamp'])
					->where('is_day_shift', $draft['is_day_shift'])
					->update(['csh_data' => \json_encode($treeForFrontend, JSON_UNESCAPED_UNICODE)]); // JSON_UNESCAPED_UNICODE转义时保留中文，必须PHP5.4+
				}
				return $treeForFrontend;
			}
			// 获取最后一份草稿
			$lastDraft = Db::name('change_shifts_history')->order("csh_id desc")->find();
			$monitorPost_type_idArr = [];
			// 获取当天时间戳
			$y = getdate()['year'];
			$m = getdate()['mon'] > 9 ? getdate()['mon'] : '0'.getdate()['mon'];
			$d = getdate()['mday'] > 9 ? getdate()['mday'] : '0'.getdate()['mday'];
			$h = getdate()['hours'] > 9 ? getdate()['hours'] : '0'.getdate()['hours'];
			$time_stamp = $y.$m.$d; // 该变量下面可能会发生变化
			$time_stamp_now = $y.$m.$d; // 该变量记录当前时间固定不变
			$is_day_shift = (getdate()['hours'] >= 9 && getdate()['hours'] < 18) ? 1 : 0;
			
			// is_changeShifts 是否可进行交接  由于交接班时间范围落在同一天内，不会有夸过凌晨0点的情况，因此此处的判断逻辑还比较简单
			$is_changeShifts = true;
			// 交接班时段不可交接班情况1：早上交接时间段内，夜班已交，白班已接时
			if ($lastDraft['time_stamp'] == $time_stamp && $lastDraft['is_day_shift'] == 1 && $lastDraft['is_succession'] && $h >=8 && $h < 10) {
				$is_changeShifts = false;
			}
			// 交接班时段不可交接班情况2：晚上交接时间段内，白班已交，夜班已接时
			if ($lastDraft['time_stamp'] == $time_stamp && $lastDraft['is_day_shift'] == 0 && $lastDraft['is_succession'] && $h >=17 && $h < 19) {
				$is_changeShifts = false;
			}
			
			// 加载当前班次值班人员信息，不一定是当天
			$lastDraftTime = $lastDraft['time_stamp'];
			// 判断当前交接班次是白班还是夜班
			$lastDraftDayShift = $lastDraft['is_day_shift'];
			if ( !$lastDraft['time_stamp']) {
				$lastDraftTime = $time_stamp;
				$lastDraftDayShift = $is_day_shift;
			} else if ($lastDraft['time_stamp'] < $time_stamp && $lastDraft['is_shiftOver'] && $lastDraft['is_succession']) {
				// 夜班才需要加一天
				if ($lastDraft['is_day_shift'] == 1) {
					$lastDraftDayShift = 0;
				}
				if ($lastDraft['is_day_shift'] == 0) {
					$lastDraftTime = date('Ymd',strtotime($lastDraft['time_stamp']."+ 1 day")); // 获取明天的日期数据 
					$lastDraftDayShift = 1;
				}
			}
			$this->assign('lastDraftTime', $lastDraftDayShift);
			
			// 获取该用户当天值班信息并判断用户是否值班长
			$is_day_shiftWhere = [];
			if ($lastDraftDayShift) {
				$is_day_shiftWhere['is_day'] = 1;
			} else {
				$is_day_shiftWhere['is_night'] = 1;
			}
			$rota = Db::table('rota')
				->where('time_stamp', $lastDraftTime)
				->where($is_day_shiftWhere)
				->where('member_name', $adminInfo['name'])
				->select();
			$is_shiftLeader = 0; // 判断是否值班长
			$this->assign('is_shiftLeader', $is_shiftLeader);
			foreach ($rota as $k => $v) {
				$mptID = Db::table('monitorpost_type')
					->where('monitorPost_type', $v['monitorPost_type'])
					->find()['monitorPost_type_id'];
				if ($v['monitorPost_type'] == '值班长' && !$is_shiftLeader) {
					$is_shiftLeader = 1;
					$this->assign('is_shiftLeader', $is_shiftLeader);
				}
				\array_push($monitorPost_type_idArr, (string)$mptID);
			}

			// 构造登陆用户交接班权限
			$nowMyTask = [];
			for ($i = 0; $i < \sizeof($monitorPost_type_idArr); $i++) {
				$mt_tiListItem = Db::table('monitorposttype_taskitems')->where('monitorPost_type_id', $monitorPost_type_idArr[$i])->find();
				if ($mt_tiListItem) {
					$arr = \explode('||', $mt_tiListItem['tim_id_str']);
					$tim_id_arrItem = [];
					// 用户工作项树形结构鉴权模具：nowMyTask（获取用户当前值班岗位类型的对应工作项权限）
					for ($j = 0; $j < \sizeof($arr); $j++) {
						$arrItemArr = \explode(',', $arr[$j]);
						\array_push($tim_id_arrItem, $arrItemArr);
						for ($z = 0; $z < \sizeof($arrItemArr); $z++) {
							if (!$nowMyTask[$arrItemArr[$z]]) {
								$nowMyTask[$arrItemArr[$z]] = $arrItemArr[$z];
							}
						}
					}
					$mt_tiListItem['tim_id_arr'] = $tim_id_arrItem;
					array_push($mt_tiList, $mt_tiListItem);
				}
			}
			$rateDetailsArr = [];
			// 是否当班次值班人员
			$this->assign('is_monitor', $nowMyTask ? true: false);
			
			// 交接班时间与班次交接规则
			/* 交接班超时规则 如果没有当班次草稿数据，则说明有两种可能
			* （由于交接班内容主要是判断数据源【结束时间（督办获取）】字段是否为空，那么我们把交接班超时的情况设计为如下两种情况将大大简化程序设计难度）
			* 第一种情况：数据库一开始没有交接班数据的时候
			* 第二种情况：最新的草稿存在未交接的情况，需要先完成交接 ———— 以班次为粒度
			* 第三种情况：最新的草稿已经交接完毕，但是班次出现断层  $lastDraft['time_stamp'] < $time_stamp
						或：正常白班交接完毕到夜班的时候 $lastDraft['time_stamp'] = $time_stamp 的时候

			* 第四种情况：当csh_data字段为空时，为该字段重复获取最新数据（特别满足值班人员要刷新交接班数据的需求）
			* 注：$time_stamp不能超过当前世界时间
			*/
			// is_time_division 交接班是否出现班次断层，既是否存在未完成的交接班
			$this->assign('is_time_division', false);
			
			// 第1种情况
			if (!$lastDraft) {
				$allTaskTreeAndAuth = makeDraft($time_stamp, $is_day_shift, $nowMyTask);
			}
			// 第4种情况
			else if ( !$lastDraft['csh_data'] ) {
				$allTaskTreeAndAuth = updateDraft($lastDraft, $nowMyTask, 1);
			}
			// 第3种情况——班次断层
			else if ( ($lastDraft['time_stamp'] <= $time_stamp) && $lastDraft['is_shiftOver'] && $lastDraft['is_succession']) {
				$this->assign('is_time_division', true);
				if ($lastDraft['time_stamp'] == $time_stamp) {
					$this->assign('is_time_division', false);
				}
				// 在白班出断层，则需补回当天夜班的交接班内容
				if ($lastDraft['is_day_shift'] == 1) {
					$is_day_shift = 0;
					$time_stamp = $lastDraft['time_stamp']; 
				} 
				// 在夜班出断层，则需要加一天
				else {
					$is_day_shift = 1;
					$time_stamp = date('Ymd',strtotime($lastDraft['time_stamp']."+ 1 day")); // 获取明天的日期数据 
				}
				// $this->assign('log', $is_day_shift);

				$allTaskTreeAndAuth = makeDraft($time_stamp, $is_day_shift, $nowMyTask);
			} 
			// 第2种情况
			else {
				$this->assign('is_time_division', true);
				// 排除当天当班次已完成交/接的情况
				// 白班接班后，且还没到夜班交班时间
				// is_shiftOver 是否交班
				// is_succession 是否接班
				if ($time_stamp == $lastDraft['time_stamp'] && $lastDraft['is_day_time'] == 1 && $lastDraft['is_succession']) {
					$this->assign('is_time_division', false);
				}
				if ($time_stamp == $lastDraft['time_stamp'] && $lastDraft['is_day_time'] == 0 && $lastDraft['is_succession']) {
					$this->assign('is_time_division', false);
				}
				
				$time_stamp = $lastDraft['time_stamp'];
				$is_day_shift = $lastDraft['is_day_shift'];
				// 加载草稿
				$allTaskTreeAndAuth = authForDraft(json_decode($lastDraft['csh_data'], true), $nowMyTask);
				
			}
			$this->assign('draft', $lastDraft);

			// 人员评价模块 
			// 只能对当前班次人员进行评价，其他断层班次不再做评价
			if ($time_stamp_now == $lastDraft['time_stamp'] ) {
				// 获取当前处理'班次'值班人员信息
				// 白夜班
				$is_day_shiftWhere2 = [];
				if ($is_day_shift) {
					$is_day_shiftWhere2['is_day'] = 1;
				} else {
					$is_day_shiftWhere2['is_night'] = 1;
				}
				// 用户值班岗位信息
				$user = Db::table('rota')
					->where('time_stamp', $time_stamp)
					->where('member_name', $adminInfo['name'])
					->where($is_day_shiftWhere2)
					->select(); 
				
				// 先查一下是否已存在评价
				$does_evaluate_exist = Db::table('evaluation_of_on_duty_personnel')
					->where('time_stamp', $time_stamp)
					->where('reviewers', $adminInfo['name'])
					->where('is_day_shift', $is_day_shift)
					->field('create_time, update_time',true)
					->select();
				// 存在评价则直接返回
				if ($does_evaluate_exist) {
					$rateDetailsArr = $does_evaluate_exist;
					// 评论是否被确认-既是否可编辑状态 0 可编辑
					// $this->assign('evaluateIsConfirm', $does_evaluate_exist['is_confirm']);
				} 
				// 评价不存在则重新构造评价数据结构
				else {
					// 获取当班次人员值班信息
					$informationOfDutyPersonnel = Db::table('rota')
						->where('time_stamp', $time_stamp)
						->where('member_name', '<>' ,$adminInfo['name'])
						->where($is_day_shiftWhere2)
						->select();
					// 为每个成员构造评分字段，并写入数据库作为原始评分评价数据
					foreach ($informationOfDutyPersonnel as $k => $v) {
						$insertRate = [];
						$insertRate['time_stamp'] = $time_stamp;
						$insertRate['is_day_shift'] = $is_day_shift;
						$insertRate['reviewers'] = $adminInfo['name'];
						$insertRate['appraisee'] = $informationOfDutyPersonnel[$k]['member_name'];
						$insertRate['score'] = 0;
						$insertRate['evaluate'] = '';
						$insertRate['appraisee_post'] = $v['monitor_post_name'];
						$reviewers_postArr =  Db::table('rota')
							->where('time_stamp', $time_stamp)
							->where('member_name',$adminInfo['name'])
							->where($is_day_shiftWhere2)
							->select();
						$reviewers_post = '';
						foreach($reviewers_postArr as $k => $v) {
							$reviewers_post = $reviewers_post.$v['monitor_post_name'].'|$|';
						}
						$insertRate['reviewers_post'] = $reviewers_post; // 评论人岗位，由于评论人可能有多个岗位，因此用'|$|'隔开
						$eoodp_id = Db::table('evaluation_of_on_duty_personnel')->insertGetId($insertRate);
						// 插入之后还需要补充一些前端需要但是后台不需要的字段
						$insertRate['eoodp_id'] = $eoodp_id;
						array_push($rateDetailsArr, $insertRate);
					}
					// 评论是否被确认-既是否可编辑状态 0 可编辑
					$this->assign('evaluateIsConfirm', 0);
				}
				$this->assign('user', $user);

				$this->assign('rateDetailsArr', $rateDetailsArr);
			}
			
			// 最后再获取一次最新数据，用于加载数据同步看板
			$latestData = updateDraft($lastDraft, $nowMyTask, 0);
			
		} else {
			// 非值班人员禁止访问
			$this->assign('is_monitor', 0);
		}
		$this->assign('allTaskTreeAndAuth', $allTaskTreeAndAuth);
		// 获取最新数据的主键，因为用户一定是在编辑最新这条数据
		$this->assign('csh_id', Db::name('change_shifts_history')->order("csh_id desc")->find()['csh_id']);
		
		// 经过上面的运行之后数据库中的最新交接班历史很可能发生了变化，重新赋值$lastDraft
		$lastDraft = Db::name('change_shifts_history')->order("csh_id desc")->find();
		// 判断交班，接班以及非交接班状态
		$state = false;
		if ($lastDraft) {
			if ($lastDraft['is_succession'] && $lastDraft['is_shiftOver']) {
				if ($time_stamp != $lastDraft['time_stamp']) {
					$state = '接班';
				} else {
					$state = false;
				}
			} else {
				if ($lastDraft['is_succession']) {
					$state = '交班';
				} else if ($lastDraft['is_shiftOver']) {
					$state = '接班';
				} else {
					$state = '接班';
				}
			}
		} else {
			$state = '接班';
		}
		// $state = $lastDraft ? ($lastDraft['is_succession'] ? ($lastDraft['is_shiftOver'] ? false : "交班") : "接班") : "接班";
		$stateStr = $state ? $state : '当前班次已完成交接，交接信息如下';
		// 构造交接标题信息
		$time_stampMsg = [];
		$time_stampMsg['time_stamp'] = [];
		$time_stampMsg['time_stamp']['year'] = \substr($time_stamp, 0, 4);
		$time_stampMsg['time_stamp']['month'] =  \substr($time_stamp, 4, 2);
		$time_stampMsg['time_stamp']['day'] =  \substr($time_stamp, 6, 2);
		$time_stampMsg['dayOrNight'] = $is_day_shift == 1 ? '白班' : '夜班';
		$time_stampMsg['stateStr'] = $stateStr;

		$this->assign('is_changeShifts', $is_changeShifts); // is_changeShifts 判断是否可进行交接
		$this->assign('time_stampMsg',$time_stampMsg);
		$this->assign('time_stamp',$time_stamp);
		$this->assign('is_day_shift',$is_day_shift);
		$this->assign('state', $state);
		$this->assign('latestData', $latestData);
		// return \json($allTaskTreeAndAuth);

		return $this->fetch();
	}
	/**
	 * 交接班填写模块·编辑草稿（含更新草稿以及交接班功能）
	 */
	public function editDraft(Request $request){
		$rs = [];
		$rs['code'] = 1;
		$rs['msg'] = '操作成功';
		/**
		 * 更新草稿
		* @author kwh 2020-08-28
		* @param int input('type') == 'updateDraft' 请求类型，更新草稿
		* @param String input('csh_data')     草稿数据
		* @return Object $rs
		* 注：每一次更新草稿需要检查是否已被交班，若是则需要返回前端锁定数据，且提示不可继续编辑
		*/
		if (\input('type') == 0) {
			$updateRs = Db::table('change_shifts_history')
				->where('csh_id', \input('csh_id'))
				// html_entity_decode 将被转义的html字符解码后，原样保存
				->update(['csh_data' => html_entity_decode(json_encode(\input('csh_data/a'), JSON_UNESCAPED_UNICODE))]); // JSON_UNESCAPED_UNICODE转义时保留中文，必须PHP5.4+
			if ($updateRs) {
				$rs['msg'] = '草稿更新成功！';
			} else {
				$rs['code'] = 0;
				$rs['msg'] = '交接班数据没有变化';
			}
		}
		/**
		 * 确认交班
		* @author kwh 2020-08-28
		* @param int input('type') == 1 请求类型，接班
		* @param String input('csh_data')     交接班数据
		* @return Object $rs
		*/
		else if (\input('type') == 1) {
			$is_shiftOver = Db::table('change_shifts_history')
				->where('csh_id', \input('csh_id'))
				->update(['is_shiftOver' => \input('account')]);
			if ($is_shiftOver) {
				$rs['msg'] = '交班成功！';
			} else {
				$rs['code'] = 0;
				$rs['msg'] = '交班失败，请刷新重试';
			}
		}
		/**
		 * 确认接班
		* @author kwh 2020-08-28
		* @param int input('type') == 2 请求类型，接班
		* @return Object $rs
		*/
		else if (\input('type') == 2) {
			$is_succession = Db::table('change_shifts_history')
			->where('csh_id', \input('csh_id'))
			->update(['is_succession' => \input('account')]);
			if ($is_succession) {
				$rs['msg'] = '接班成功！';
			} else {
				$rs['code'] = 0;
				$rs['msg'] = '接班失败，请刷新重试';
			}
		}
		
		/**
		 * 重新获取交接班数据，此操作将覆盖已有草稿，仅值班长有权限
		* @author kwh 2020-08-28
		* @param int input('type') == 3 请求类型，获取最新数据并覆盖草稿
		* @param String input('time_stamp')
		* @param String input('is_day_shift')
		* @return Object $rs
		*/
		else if (\input('type') == 3) {
			$update = Db::table('change_shifts_history')
				->where('time_stamp', \input('time_stamp'))
				->where('is_day_shift', \input('is_day_shift'))
				->update(['csh_data' => '']);
			if (!$update) {
				$rs['code'] = 0;
				$rs['msg'] = '操作失败，请刷新重试';
			}
		}
		else {
			$rs['code'] = 0;
			$rs['msg'] = '参数错误请检查请求参数';
		}
		return $rs;
	}
	/**
	 * 交接班填写模块·编辑草稿（含更新草稿以及交接班功能）
	 */
	public function editRate(Request $request){
		$rs = [];
		$rs['code'] = 1;
		$rs['msg'] = '操作成功';
		$params = \input();
		// 判断评论是否已存在，存在则更新评论，不存在则返回错误提示
		$does_it_exist = Db::table('evaluation_of_on_duty_personnel')
			->where('eoodp_id', $params['eoodp_id'])
			->find();
		// 存在则更新评论
		if ($does_it_exist) {
			$insert = Db::table('evaluation_of_on_duty_personnel')
				->where('eoodp_id', $does_it_exist['eoodp_id'])
				->update($params);
		} 
		// 不存在则返回错误提示
		else {
			$rs['code'] = 0;
			$rs['msg'] = '发现未知错误，请联系管理员';
		}
		$rs['data'] = \input();
		return $rs;
	}

	/**
	 * 交接班填写模块·获取岗位日志数据
	 * 该模块复用了大值班总结功能
	 */
	public function getPostLogData(Request $request){
		$params = \input();
		$timeRangeArr = $params['timeRangeArr'];
		if(empty($timeRangeArr)){
			return '参数为空';
		}
		for($i = 0; $i < \sizeof($timeRangeArr); $i++){
			$timeRangeArr[$i] = \html_entity_decode($timeRangeArr[$i]);
		}
		// 获取用于生成大值班的表格数据源
		$getDutySummary = Db::table('duty_summary_management')->select();
		$levelTwo_ids = [];
		for($i = 0; $i < \sizeof($getDutySummary); $i++){
			\array_push($levelTwo_ids, $getDutySummary[$i]['ctrr_rsrm_l2_id']);
		}
		// 按表格获取岗位日志原始数据
		function getPostLogData($levelTwo_ids, $timeRangeArr) {
			/**
			 * $levelTwo_ids(array) 表格id数组
			 * $timeRangeArr(array) 时间范围数组 
			 */
			// 获取【结束时间（督办获取）】字段结构数据
			$getEndDateFields = Db::table('rotasituationrecordmanagement')
			->where('ctrr_rsrm_parent_id','in', $levelTwo_ids)
			->where('ctrr_rsrm_name', '结束时间（督办获取）')
			->select();
			// return \json($getEndDateFields);
			// 获取待跟进故障
			$incompletedData = [];
			for($i = 0; $i < \sizeof($getEndDateFields); $i++){
				$incompletedData_item = Db::table('rotasituationrecordmanagementdata')
					->where('ctrr_rsrm_id_l2', $getEndDateFields[$i]['ctrr_rsrm_parent_id'])
					->where('ctrr_rsrmD_data', ['notlike','%"'.$getEndDateFields[$i]['ctrr_rsrm_id'].'":"%'] )
					->select();
				\array_push($incompletedData, $incompletedData_item);
			}
			// 获取消除故障
			$completedData = [];
			for($i = 0; $i < \sizeof($getEndDateFields); $i++){
				$completedData_item_search = [];
				for($j = 0; $j < \sizeof($timeRangeArr); $j++){
					\array_push($completedData_item_search, '%'.$getEndDateFields[$i]['ctrr_rsrm_id'].$timeRangeArr[$j].'%');
				}
				$completedData_item = Db::table('rotasituationrecordmanagementdata')
										->where('ctrr_rsrm_id_l2', $getEndDateFields[$i]['ctrr_rsrm_parent_id'])
										->where('ctrr_rsrmD_data', 'like', $completedData_item_search, 'OR')
										->select();
				\array_push($completedData, $completedData_item );
			}
			$rs['incompletedData'] = $incompletedData;
			$rs['completedData'] = $completedData;
			return $rs;
		}
		$postLogData = [];
		$postLogData['faultOrdersData'] = getPostLogData($levelTwo_ids, $timeRangeArr);
		$postLogData['complaintWorkOrdersData'] = getPostLogData([337], $timeRangeArr);
		return $postLogData;
	}

	//  历史数据管理模块
	/**
	 * 历史数据管理模块
	 */
	public function changeShiftsHistoryData(Request $request){
		$params = input();
		$pagination = $params['pagination'];
		$searchForm = $params['searchForm'];
		$condition = [];
		$condition['time_stamp'] = ['like','%'.$searchForm['time_stamp'].'%'];
		if($searchForm['is_day_shift'] != ''){
			$condition['is_day_shift'] = $searchForm['is_day_shift'];
		}
		$condition['csh_data'] = ['like','%'.$searchForm['csh_data'].'%'];
		$condition['is_shiftOver'] = ['like','%'.$searchForm['is_shiftOver'].'%'];
		$condition['is_succession'] = ['like','%'.$searchForm['is_succession'].'%'];
		if (!$pagination) {
			$pagination['per_page'] = 10;
			$pagination['current_page'] = 1;
		}
		$draftList = Db::table('change_shifts_history')
			->where($condition)
			->order("create_time desc")
			->paginate($pagination['per_page'],false,[
				'page' => $pagination['current_page'],
				'list_rows' => $pagination['per_page'],
			])->toArray(); // paginate()获取到的数据需要先转化为数组才能获取其内部的值
		// csh_data字符串转json
		foreach ($draftList['data'] as $k => $v) {
			$draftList['data'][$k]['csh_data'] = json_decode($draftList['data'][$k]['csh_data'], true);
		}

		$this->assign('draftList', $draftList);
		if (input('isSearch')) {
			// 搜索时
			return $draftList;
		} else {
			// 首次加载页面
			return $this->fetch();
		}
	}
	// 人员评价管理模块
	/**
	 * 人员评价管理模块
	 */
	public function evaluationOfOnDutyPersonnel(Request $request){
		$rs = [];
		$rs['code'] = 1;
		$rs['msg'] = '加载成功';
		$params = input();
		$pagination = $params['pagination'];
		$searchForm = $params['searchForm'];
		$condition = [];
		// $condition['time_stamp'] = ['like','%'.$searchForm['time_stamp'].'%'];
		$condition['time_stamp'] = ['between time', [(int)$searchForm['time_stamp'][0] ? (int)$searchForm['time_stamp'][0] : 0, (int)$searchForm['time_stamp'][1] ? (int)$searchForm['time_stamp'][1] : 30000000]];
		if($searchForm['is_day_shift'] != ''){
			$condition['is_day_shift'] = $searchForm['is_day_shift'];
		}
		$condition['reviewers'] = ['like','%'.$searchForm['reviewers'].'%'];
		$condition['appraisee'] = ['like','%'.$searchForm['appraisee'].'%'];
		$condition['score'] = ['BETWEEN', [(int)$searchForm['score'][0] ? $searchForm['score'][0] : 0, (int)$searchForm['score'][1] ? $searchForm['score'][1] : 5]];
		switch ($searchForm['evaluate']) {
			case '所有评价': 
				$condition['evaluate'] = ['like', '%%'];
			break;
			case '写了评价的': 
				$condition['evaluate'] = ['<>', ''];
			break;
			case '评价为空的': 
				$condition['evaluate'] = ['eq', ''];
			break;
			default: 
				$condition['evaluate'] = ['like', '%'.$searchForm['evaluate'].'%'];
			break;
		}
		$condition['reviewers_post'] = ['like','%'.$searchForm['reviewers_post'].'%'];
		$condition['appraisee_post'] = ['like','%'.$searchForm['appraisee_post'].'%'];
		if (!$pagination) {
			$pagination['per_page'] = 10;
			$pagination['current_page'] = 1;
		}
		$eoodpList = Db::table('evaluation_of_on_duty_personnel')
			->where($condition)
			->order("create_time desc")
			->paginate($pagination['per_page'],false,[
				'page' => $pagination['current_page'],
				'list_rows' => $pagination['per_page'],
			])->toArray(); // paginate()获取到的数据需要先转化为数组才能获取其内部的值

		$this->assign('eoodpList', $eoodpList);
		if (input('isSearch')) {
			// 搜索时
			$rs['data'] = $eoodpList;

			return $rs;
		} else {
			// 首次加载页面
			return $this->fetch();
		}
	}// 投诉交接班模块
	/**
	 * 投诉交接班模块·查
	 */
	public function complaintHandover(Request $request){
		$params = input();
		$admin_account = Session::get('adminAccount');
		if( $params['isSearch'] != 1 ){
			// 首次打开页面
			$complaintHandoverData = Db::table('complaint_handover')
							->where('ch_state',0) // 0代表未交班
							->find();
			$complaintHandoverDataList = Db::table('complaint_handover')
							->order(['time_stamp DESC'])
							->where('ch_state',1) // 1代表已交班
							->paginate(10);
			$this->assign([
				'complaintHandoverData'=>$complaintHandoverData,
				'complaintHandoverDataList'=>$complaintHandoverDataList,
				'admin_account'=>$admin_account
			]);
			return $this->fetch();
		} else {
			// 搜索功能
			$pagination = $params['pagination'];
			$searchForm = $params['searchForm'];
			$condition = [];
			$condition['submitter'] = ['like','%'.$searchForm['submitter'].'%'];
			$condition['ch_content'] = ['like','%'.$searchForm['ch_content'].'%'];
			$condition['banci'] = ['like','%'.$searchForm['banci'].'%'];
			$condition['ch_state'] = 1;  // 1代表已交班
			$condition['time_stamp'] = ['like','%'.$searchForm['time_stamp'].'%'];
			if($searchForm['ch_submit_time'] != []){
				$condition['ch_submit_time'] = ['between time',$searchForm['ch_submit_time']];
			}
			$complaintHandoverDataList = Db::table('complaint_handover')
							->order(['banci DESC'])
							->order(['time_stamp DESC'])
							->where($condition)
							->paginate($pagination['per_page'],false,[
								'page' => $pagination['current_page'],
								'list_rows' => $pagination['per_page'],
							]);
			return \json($complaintHandoverDataList);
		}
	}
	
	/**
	 * 投诉交接班模块·改
	 */
	public function editComplaintHandover(Request $request){
		$params = input();
		$admin_account = Session::get('adminAccount');
		// return date('Ymd',strtotime($params['ruleForm']['time_stamp'].'+1 day'));
		if( $params['isSubmit'] == 1 ){ // 1 表示交班 0表示暂存
			$ruleForm = $params['ruleForm'];
			$ruleForm['ch_submit_time'] = date('Y-m-d H:i:s');
			$ruleForm['ch_state'] = 1;
			$rs = [];
			$rs['code'] = 1;
			// 判断是否已被交班
			$ch_data = Db::table('complaint_handover')->where('ch_id',$ruleForm['ch_id'])->find();
			$ch_state = $ch_data['ch_state'];
			if ($ch_state == 1) {
				$rs['code'] = 0;
				$rs['msg'] = '该班次已被'.$ch_data['submitter'].'交班，请刷新加载最新内容';
				return $rs;
			}
			$code = Db::table('complaint_handover')
				->update($ruleForm);
			// $rs['code'] = $code;
			if ($code) {
				// 交班后，生成下一次待交班数据
				$nextComplaintHandoverData = [];
				if (abs($ruleForm['time_stamp'] - date('Ymd')) > 1) {
					// 忘记交班的情况
					if (date('H') < 8) {
						$nextComplaintHandoverData['banci'] = 0;
					} else {
						$nextComplaintHandoverData['banci'] = 1;
					}
					$nextComplaintHandoverData['time_stamp'] = date('Ymd');
				} else {
					// 按时交班的情况
					$nextComplaintHandoverData['banci'] = $ruleForm['banci'] == 0 ? 1 : 0; // 默认，白夜交接，初始数据超管把控
					if ($ruleForm['banci'] == 0) {
						$nextComplaintHandoverData['time_stamp'] = date('Ymd',strtotime($ruleForm['time_stamp'].'+1 day'));
					} else {
						$nextComplaintHandoverData['time_stamp'] = date('Ymd',strtotime($ruleForm['time_stamp']));
					}
				}
				$nextComplaintHandoverData['ch_content'] = $ruleForm['ch_content'];
				$nextComplaintHandoverData['ch_state'] = 0;
				$insertCode = Db::table('complaint_handover')
						->insert($nextComplaintHandoverData);
				if ($insertCode) {
					$rs['msg'] = '交班成功！';
				} else {
					$rs['code'] = 0;
					$rs['msg'] = '交班成功，但自动生成下次交班数据失败，请联系管理员';
				}
			} else {
				$rs['code'] = 0;
				$rs['msg'] = '交班失败，请联系管理员';
			}
			return $rs;
		} else if( $params['isSubmit'] == 0 ) { // 1 表示交班 0表示暂存
			$ruleForm = $params['ruleForm'];
			$rs = [];
			unset($ruleForm['submitter']);
			// return is_array($ruleForm); 
			// return $ruleForm; 
			$code = Db::table('complaint_handover')
				->update($ruleForm);
			$rs['code'] = $code;
			if ($code) {
				$rs['msg'] = '保存成功';
			} else {
				$rs['msg'] = '数据无变化';
			}
			return $rs;
		}
	}
}