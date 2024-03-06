<?php
use think\Route;

/***************************************************customer模块***************************************************************************/
// Rota控制器 前端值班新增展示模块
Route::group(['prefix' => 'customer/Rota/'], function () {
	Route::get('customer/get_rotaList_day_for_alarm_topo', 'get_rotaList_day_for_alarm_topo'); // 获取每日值班表列表_告警topo共享接口
	Route::get('customer/rotaList_day', 'rotaList_day'); // 打开值班表页面，同时获取每日值班信息
	Route::get('customer/rotaList_day_happyNewYear2020', 'rotaList_day_happyNewYear2020'); // 打开值班表页面，同时获取每日值班信息
	Route::get('customer/rotaList_day_happyNewYear2021', 'rotaList_day_happyNewYear2021'); // 打开值班表页面，同时获取每日值班信息
	Route::get('customer/rotaList_day_happyNewYear', 'rotaList_day_happyNewYear'); // 打开值班表页面，同时获取每日值班信息
	//  短期开放微信小程序测试接口
	Route::get('customer/rotaListByTimeRange', 'rotaListByTimeRange'); // 获取值班表，按时间范围获取
	Route::get('customer/rotaList', 'rotaList'); // 获取值班表，按日期获取
	Route::get('customer/editRotaList', 'editRotaList'); // 更换班表
	Route::get('customer/getTMMonitor', 'getTMMonitor'); // 获取今明两天值班人员信息——短信接口
	Route::get('customer/getTodayMonitor', 'getTodayMonitor'); // 获取当日值班信息

	Route::any('customer/getWeatherForecasts', 'getWeatherForecasts'); // 按天获取节假日信息

	Route::any('customer/bs', 'bs'); // 广运精灵现场演示视频
});
// rota_situation控制器 值班信息模块
Route::group(['prefix' => 'customer/rota_situation/'], function () {
	Route::any('customer/getRsrmLevelTree', 'getRsrmLevelTree'); // 获取值班信息结构某个三级表的字段
	Route::any('customer/getDateRangByDateLength', 'getDateRangByDateLength'); // 获取字段类型为计算时长的管理时间范围数据
	Route::any('customer/addDutySummary', 'addDutySummary'); // 记录大值班总结
});
// schedule_management控制器 日程管理模块
Route::group(['prefix' => 'customer/schedule_management/'], function () {
	Route::any('customer/getMyAnnualLeaveBalance', 'getMyAnnualLeaveBalance'); // 获取我的年假余额
	Route::any('customer/getMyVacationBalance', 'getMyVacationBalance'); // 获取我的调休余额
});

// 小程序 值班表管理模块
Route::group(['prefix' => 'customer/WxUser/'], function () {
	Route::get('total/:date', 'total', [], ['date' => '\d{16}']);
	Route::rule('wxlogin', 'wxlogin');
	Route::rule('index', 'index');
	Route::rule('changedata', 'changedata');
});

/***************************************************微信模块***************************************************************************/
Route::group(['prefix' => 'test/index/'], function () {
	Route::any('testIf', 'testIf'); // 微信通知url
	Route::any('testBCode', 'testBCode'); // 微信通知url
	Route::any('testHttp', 'testHttp'); // 微信通知url
	Route::any('testUpload', 'testUpload'); // 微信通知url
	Route::any('testSevenTime', 'testSevenTime'); // 微信通知url
	Route::any('testWeekData', 'testWeekData'); // 微信通知url
	Route::any('testMonthData', 'testMonthData'); // 微信通知url
	Route::any('testFileWrite', 'testFileWrite'); // 微信通知url
	Route::any('getExercises', 'getExercises'); // 微信通知url
	Route::any('getTheCharts', 'getTheCharts'); // 微信通知url
	Route::any('getQuestion', 'getQuestion'); // 微信通知url
	Route::any('testKC', 'testKC'); // 微信通知url
});

/****************************index模块***********************************/
Route::get('/', 'system/login/login'); // 首页
// Index控制器（此控制器无拦截）
Route::group(['prefix' => 'index/index/'], function () {
	Route::any('wx_index', 'wx_index'); // 微信通知url
	Route::get('definedMenu', 'definedMenu'); // 定义微信菜单
	Route::get('dingshui', 'dingshui'); // 公众号入口
	Route::get('getUserOpenId', 'getUserOpenId'); // 获取用户openid
});


/****************************system自带模块**********************************/
// Login控制器
Route::group(['prefix' => 'system/login/'], function () {
	Route::any('system/login', 'login'); // 登录页面
	Route::any('system/logout', 'logout'); // 退出登录
});

// Admin控制器
Route::group(['prefix' => 'system/admin/'], function () {
	Route::get('system/adminList', 'adminList'); // 管理员列表
	Route::any('system/addAdmin', 'addAdmin'); // 添加管理员
	Route::any('system/editAdmin', 'editAdmin'); // 修改管理员
	Route::get('system/deleteAdmin', 'deleteAdmin'); // 删除管理员
	Route::post('system/uploadAdminHead', 'uploadAdminHead'); // 上传管理员头像
});

// Rule控制器
Route::group(['prefix' => 'system/rule/'], function () {
	Route::get('system/ruleList', 'ruleList'); // 权限列表
	Route::any('system/addRule', 'addRule'); // 添加权限
	Route::any('system/editRule', 'editRule'); // 修改权限
	Route::get('system/sortRule', 'sortRule'); // 权限排序
	Route::get('system/deleteRule', 'deleteRule'); // 删除权限
});

// Role控制器
Route::group(['prefix' => 'system/role/'], function () {
	Route::get('system/roleList', 'roleList'); // 角色列表
	Route::any('system/addRole', 'addRole'); // 添加角色
	Route::any('system/editRole', 'editRole'); // 修改角色
	Route::get('system/deleteRole', 'deleteRole'); // 删除角色
});

// Setting控制器
Route::group(['prefix' => 'system/setting/'], function () {
	Route::get('system/setting', 'setting'); // 网站设置页面
	Route::post('system/editSetting', 'editSetting'); // 编辑网站设置
});

// WxWelcome控制器
Route::group(['prefix' => 'system/wx_welcome/'], function () {
	Route::any('system/welcomeSetting', 'welcomeSetting'); // 微信关注回复设置
});

// WxMenu控制器
Route::group(['prefix' => 'system/wx_menu/'], function () {
	Route::get('system/menuList', 'menuList'); // 菜单列表
	Route::any('system/addMenu', 'addMenu'); // 添加菜单
	Route::any('system/editMenu', 'editMenu'); // 编辑菜单
	Route::get('system/deleteMenu', 'deleteMenu'); // 删除菜单
	Route::get('system/sortMenu', 'sortMenu'); // 菜单排序
	Route::get('system/pushWxMenu', 'pushWxMenu'); // 推送微信菜单
});

// WxMaterial控制器
Route::group(['prefix' => 'system/wx_material/'], function () {
	Route::get('system/getNewsList', 'getNewsList'); // 获取永久图文素材列表
	Route::get('system/getImageList', 'getImageList'); // 获取永久素材图片列表
	Route::get('system/get_contents', 'get_contents'); // 输出文件图片流
});


/***************************************************system二次开发模块***************************************************************************/
// SelfResearch控制器 广运总览模块
Route::group(['prefix' => 'system/self_research/'], function () {
	Route::any('system/selfResearchList', 'selfResearchList'); // 自研成果总览
	Route::any('system/commonURLList', 'commonURLList'); // 常用网址汇总
	Route::any('system/widgetsList', 'widgetsList'); // 小工具库
	Route::any('system/selfResearchDetails', 'selfResearchDetails'); // 自主开发展示
});

// Member控制器 人员管理模块
Route::group(['prefix' => 'system/member/'], function () {
	Route::any('system/memberList', 'memberList'); // 人员列表
	Route::any('system/addMember', 'addMember'); // 添加人员
	Route::any('system/addMoreMember', 'addMoreMember'); // 导入附件形式批量添加人员
	Route::any('system/editMember', 'editMember'); // 编辑人员信息
	Route::any('system/updateMember', 'updateMember'); // 更新人员信息
	Route::any('system/deleteMember', 'deleteMember'); // 删除人员
	Route::any('system/monitorGuyList', 'monitorGuyList'); // 值班人员管理
});
// Rota控制器 值班表管理模块
Route::group(['prefix' => 'system/rota/'], function () {
	Route::any('system/rota/test', 'test'); // 监控值班岗位列表
	Route::any('system/monitorPostList', 'monitorPostList'); // 监控值班岗位列表
	Route::any('system/addMonitorPost', 'addMonitorPost'); // 增加岗位
	Route::any('system/editMonitorPost', 'editMonitorPost'); // 编辑岗位
	Route::any('system/deleteMonitorPost', 'deleteMonitorPost'); // 删除岗位
	Route::any('system/rotaList', 'rotaList'); // 值班表列表·权
	Route::any('system/addRota', 'addRota'); // 添加值班表
	//  Route::any('system/addMoreMember', 'addMoreMember'); // 导入附件形式批量添加值班表
	Route::any('system/editRota', 'editRota'); // 编辑值班表·权
	Route::any('system/editRotaOrdinary', 'editRotaOrdinary'); // 编辑值班表
	Route::post('system/deleteRota', 'deleteRota'); // 删除值班表
	Route::any('system/checkRota', 'checkRota'); // 检查值班表
	Route::any('system/updateRota', 'updateRota'); // 更新值班表·权
	Route::any('system/updateRotaOrdinary', 'updateRotaOrdinary'); // 更新值班表
	Route::any('system/updateDailyRota', 'updateDailyRota'); // 更新每日班表
	Route::any('system/rotaList_day', 'rotaList_day'); // 每日值班表system/

	Route::any('system/frozenRota', 'frozenRota'); // 冻结值班表·权

	//  普通账号仅开放查询与编辑权限，且只能编辑自己
	Route::any('system/rotaListOrdinary', 'rotaListOrdinary'); // 值班表列表·普通账号


});
//  rota_data控制器 值班数据统计模块
Route::group(['prefix' => 'system/rota_data/'], function () {
	Route::any('system/rotaDataStatisticsList', 'rotaDataStatisticsList'); // 值班数据统计列表
	Route::any('system/shiftRecordList', 'shiftRecordList'); // 值班数据统计列表
	Route::any('system/nightShiftData', 'nightShiftData'); // 夜班数据统计
	Route::any('system/holidayOvertimeData', 'holidayOvertimeData'); // 节假日加班统计
});
// ArtificialIntelligence控制器   智慧大楼模块
Route::group(['prefix' => 'system/Ai/'], function () {
	Route::any('system/faceDetect', 'faceDetect'); // 人脸识别
});
// PersonalCenter控制器   个人中心模块
Route::group(['prefix' => 'system/PersonalCenter/'], function () {
	Route::any('system/personalInformation', 'personalInformation'); // 个人信息
	Route::any('system/editHead', 'editHead'); // 个人信息
});
// rota_situation控制器 值班信息模块
Route::group(['prefix' => 'system/rota_situation/'], function () {
	// 大值班管理页
	Route::any('system/dutySummaryManagement', 'dutySummaryManagement'); // 值班总结管理·查
	Route::any('system/dutySummaryManagementUpdate', 'dutySummaryManagementUpdate'); // 值班总结管理·更新（含新增与删除）
	// 值班信息结构管理页
	Route::any('system/rotaSituationRecordManagement', 'rotaSituationRecordManagement'); // 值班信息结构管理·查
	Route::any('system/editRotaSituationRecordManagement', 'editRotaSituationRecordManagement'); // 值班信息结构管理·改
	Route::any('system/addRotaSituationRecordManagement', 'addRotaSituationRecordManagement'); // 值班信息结构管理·增
	Route::any('system/deleteRotaSituationRecordManagement', 'deleteRotaSituationRecordManagement'); // 值班信息结构管理·删（仅超级管理员权限）
	// 值班信息登记页·权
	Route::any('system/rotaSituationRecordAdmin', 'rotaSituationRecordAdmin'); // 值班信息登记·权·查
	Route::any('system/addRotaSituationRecordAdmin', 'addRotaSituationRecordAdmin'); // 值班信息登记·权·增
	Route::any('system/editRotaSituationRecordAdmin', 'editRotaSituationRecordAdmin'); // 值班信息登记·权·改
	Route::any('system/deleteRotaSituationRecordAdmin', 'deleteRotaSituationRecordAdmin'); // 值班信息登记·权·删
	// 值班信息登记页
	Route::any('system/rotaSituationRecord', 'rotaSituationRecord'); // 值班信息登记·查
	Route::any('system/addRotaSituationRecord', 'addRotaSituationRecord'); // 值班信息登记·增
	Route::any('system/editRotaSituationRecord', 'editRotaSituationRecord'); // 值班信息登记·改
	Route::any('system/deleteRotaSituationRecord', 'deleteRotaSituationRecord'); // 值班信息登记·删
	// 故障看板
	Route::any('system/cmccFaultPanel', 'cmccFaultPanel'); // 故障看板·查
	Route::any('system/editCmccFaultPanel', 'editCmccFaultPanel'); // 故障看板·改
	// 大值班历史数据页
	Route::any('system/dutySummaryHistory', 'dutySummaryHistory'); // 大值班历史数据·查
	// 生成大值班·增+查
	Route::any('system/getDutySummary', 'getDutySummary'); // 获取大值班总结
});

// transport_support控制器 支撑日志模块
Route::group(['prefix' => 'system/transport_support/'], function () {
	// 支撑日志结构管理
	Route::any('system/materialAccessoriesManagementManagement', 'materialAccessoriesManagementManagement'); // 支撑日志结构管理·查
	Route::any('system/editTransportSupportLogManagement', 'editTransportSupportLogManagement'); // 支撑日志结构管理·改
	Route::any('system/addTransportSupportLogManagement', 'addTransportSupportLogManagement'); // 支撑日志结构管理·增
	Route::any('system/deleteTransportSupportLogManagement', 'deleteTransportSupportLogManagement'); // 支撑日志结构管理·删（仅超级管理员权限）
	// 支撑日志登记页·权
	Route::any('system/materialAccessoriesManagementAdmin', 'materialAccessoriesManagementAdmin'); // 支撑日志登记·权·查
	Route::any('system/addTransportSupportLogAdmin', 'addTransportSupportLogAdmin'); // 支撑日志登记·权·增
	Route::any('system/editTransportSupportLogAdmin', 'editTransportSupportLogAdmin'); // 支撑日志登记·权·改
	Route::any('system/deleteTransportSupportLogAdmin', 'deleteTransportSupportLogAdmin'); // 支撑日志登记·权·删
	// 支撑日志登记页
	Route::any('system/materialAccessoriesManagement', 'materialAccessoriesManagement'); // 支撑日志登记·查
	Route::any('system/addTransportSupportLog', 'addTransportSupportLog'); // 支撑日志登记·增
	Route::any('system/editTransportSupportLog', 'editTransportSupportLog'); // 支撑日志登记·改
	Route::any('system/deleteTransportSupportLog', 'deleteTransportSupportLog'); // 支撑日志登记·删
});
// change_shifts控制器 交接班
Route::group(['prefix' => 'system/change_shifts/'], function () {

	Route::any('system/changeShiftsMouldManagement', 'changeShiftsMouldManagement'); // 交接班模具管理·查
	Route::any('system/addTaskItem', 'addTaskItem'); // 交接班模具管理·工作项管理·增
	Route::any('system/deleteTaskItem', 'deleteTaskItem'); // 交接班模具管理·工作项管理·删
	Route::any('system/editTaskItem', 'editTaskItem'); // 交接班模具管理·工作项管理·改
	Route::any('system/addMt_ti', 'addMt_ti'); // 交接班模具管理·岗位类型与工作项关联关系管理·增
	Route::any('system/deleteMt_ti', 'deleteMt_ti'); // 交接班模具管理·岗位类型与工作项关联关系管理·删
	Route::any('system/editMt_ti', 'editMt_ti'); // 交接班模具管理·岗位类型与工作项关联关系管理·改

	Route::any('system/iWantchangeShifts', 'iWantchangeShifts'); // 交接班填写·查
	Route::any('system/editDraft', 'editDraft'); // 交接班填写·编辑草稿（含更新草稿以及交接班功能）
	Route::any('system/editRate', 'editRate'); // 交接班填写·编辑人员评价（含新增与更新）
	Route::any('system/getPostLogData', 'getPostLogData'); // 交接班填写·获取岗位日志数据

	Route::any('system/changeShiftsHistoryData', 'changeShiftsHistoryData'); // 交接班历史数据·查
	Route::any('system/evaluationOfOnDutyPersonnel', 'evaluationOfOnDutyPersonnel'); // 人员评价·查

	Route::any('system/complaintHandover', 'complaintHandover'); // 投诉交班·查
	Route::any('system/editComplaintHandover', 'editComplaintHandover'); // 投诉交班·改

});

// inventory_management控制器 社区物资申领模块
Route::group(['prefix' => 'system/inventory_management/'], function () {
	// 库存管理
	Route::any('system/inventoryManagementList', 'inventoryManagementList'); // 库存管理·查
	Route::any('system/editInventoryManagementList', 'editInventoryManagementList'); // 库存管理·改
	Route::any('system/addInventoryManagementList', 'addInventoryManagementList'); // 库存管理·增
	Route::any('system/deleteInventoryManagementList', 'deleteInventoryManagementList'); // 库存管理·删
	// 申领请求列表
	Route::any('system/materialRequisitionList', 'materialRequisitionList'); // 申领请求列表·查
	Route::any('system/editMaterialRequisitionList', 'editMaterialRequisitionList'); // 申领请求列表·改（通过/驳回）
	// 物资附件管理
	Route::any('system/materialAccessoriesManagement', 'materialAccessoriesManagement'); // 物资附件管理·查
	Route::any('system/addMaterialAccessoriesManagement', 'addMaterialAccessoriesManagement'); // 物资附件管理·增
	Route::any('system/deleteMaterialAccessoriesManagement', 'deleteMaterialAccessoriesManagement'); // 物资附件管理·删
	// 我要申领
	Route::any('system/iNeedSupplies', 'iNeedSupplies'); // 我要申领·查
	Route::any('system/addINeedSupplies', 'addINeedSupplies'); // 我要申领·增
	// 我的申领记录
	Route::any('system/myMaterialRequisitionList', 'myMaterialRequisitionList'); // 我的申领记录·查
	Route::any('system/editMyMaterialRequisitionList', 'editMyMaterialRequisitionList'); // 我的申领记录·改（当被驳回时，可进行重新编辑提交申请）
});

// schedule_management控制器 日程管理模块
Route::group(['prefix' => 'system/schedule_management/'], function () {
	// 查看日历
	Route::any('system/calendar_list', 'calendar_list'); // 查看日历
	Route::any('system/create_calendar', 'create_calendar'); // 创建日历
	// 调休申请
	Route::any('system/leaveApplication', 'leaveApplication'); // 调休申请·查
	Route::any('system/addleaveApplication', 'addleaveApplication'); // 调休申请·增
	// 大家的日程
	Route::any('system/everyoneSSchedule', 'everyoneSSchedule'); // 大家的日程·查

	// 调休配额管理
	Route::any('system/rotaVacationDaysOffManagementList', 'rotaVacationDaysOffManagementList'); // 调休配额管理·查
	Route::any('system/editrotaVacationDaysOffManagementList', 'editrotaVacationDaysOffManagementList'); // 调休配额管理·改
	Route::any('system/addrotaVacationDaysOffManagementList', 'addrotaVacationDaysOffManagementList'); // 调休配额管理·增
	Route::any('system/deleterotaVacationDaysOffManagementList', 'deleterotaVacationDaysOffManagementList'); // 调休配额管理·删
	// 调休配额日历
	Route::any('system/rotaVacationDaysOffList', 'rotaVacationDaysOffList'); // 调休配额日历·查
	// 管理大家的年假
	Route::any('system/annualLeaveManagementList', 'annualLeaveManagementList'); // 管理大家的年假·查
	Route::any('system/editannualLeaveManagementList', 'editannualLeaveManagementList'); // 管理大家的年假·改
	Route::any('system/addannualLeaveManagementList', 'addannualLeaveManagementList'); // 管理大家的年假·增
	Route::any('system/deleteannualLeaveManagementList', 'deleteannualLeaveManagementList'); // 管理大家的年假·删
	// 大家的调休余额
	Route::any('system/checkEveryoneSAnnualLeaveBalance', 'checkEveryoneSAnnualLeaveBalance'); // 大家的调休余额·查
	// 我的调休余额
	Route::any('system/myRotaVacationBalanceList', 'myRotaVacationBalanceList'); // 我的调休余额·查

	// 假期设置管理——规则设置
	Route::any('system/rotaVacationSettingManagementList', 'rotaVacationSettingManagementList'); // 假期设置·查
	Route::any('system/editrotaVacationSettingManagementList', 'editrotaVacationSettingManagementList'); // 假期设置·改
	Route::any('system/addrotaVacationSettingManagementList', 'addrotaVacationSettingManagementList'); // 假期设置·增
	Route::any('system/deleterotaVacationSettingManagementList', 'deleterotaVacationSettingManagementList'); // 假期设置·删
	// 休假审批
	// ————年假审批：显示所有年假申请
	Route::any('system/annualLeaveRequestList', 'annualLeaveRequestList'); // 年假审批·查
	Route::any('system/editannualLeaveRequestList', 'editannualLeaveRequestList'); // 年假审批·改
	Route::any('system/addannualLeaveRequestList', 'addannualLeaveRequestList'); // 年假审批·增
	Route::any('system/deleteannualLeaveRequestList', 'deleteannualLeaveRequestList'); // 年假审批·删
	// ————调休审批：显示所有调休申请
	Route::any('system/vacationRequestList', 'vacationRequestList'); // 调休审批·查
	Route::any('system/editvacationRequestList', 'editvacationRequestList'); // 调休审批·改
	Route::any('system/addvacationRequestList', 'addvacationRequestList'); // 调休审批·增
	Route::any('system/deletevacationRequestList', 'deletevacationRequestList'); // 调休审批·删
	// 我的年假
	Route::any('system/myAnnualLeaveBalanceList', 'myAnnualLeaveBalanceList'); // 我的年假·查
	Route::any('system/withdrawalOfMyAnnualRequest', 'withdrawalOfMyAnnualRequest'); // 撤回我的年假申请
	// 我的调休记录
	Route::any('system/myVacationRecord', 'myVacationRecord'); // 我的调休记录·查
	Route::any('system/withdrawalOfMyVacationRequest', 'withdrawalOfMyVacationRequest'); // 撤回我的调休申请

});

// feedback控制器 我要反馈模块
Route::group(['prefix' => 'system/feedback/'], function () {
	Route::any('system/iNeedFeedback', 'iNeedFeedback'); // 有话要说
	Route::any('system/addFeedback', 'addFeedback'); // 提交反馈意见·有话要说·增
	Route::any('system/feedbackList', 'feedbackList'); // 大家的反馈
	Route::any('system/editFeedbackList', 'editFeedbackList'); // 处理反馈数据·反馈列表·改
});


// collaborative_office控制器 广运网盘模块
Route::group(['prefix' => 'system/collaborative_office/'], function () {
	Route::any('system/coHomePage', 'coHomePage'); // 广运网盘·首页
});

// controlroom_private控制器 生产信息管理系统模块
Route::group(['prefix' => 'system/controlroom_private/'], function () {
	Route::any('system/cpHomePage', 'cpHomePage'); // 生产信息管理系统·首页
});

// for_demo控制器 在岗技术革新演示模块
Route::group(['prefix' => 'system/for_demo/'], function () {
	Route::any('system/getZbglList', 'getZbglList'); // 打开周报管理页面
	Route::any('system/getPxglList', 'getPxglList'); // 打开培训管理页面
});


/***************************************************404模块***************************************************************************/
Route::miss('system/error/notFound', 'GET'); // 404未找到页面