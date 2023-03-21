/**
 * 后台系统公共js
 */
//控制左侧菜单隐藏/显示
function expandLeftNav(){
	if($(".layui-body").css("left")=="0px"){
		//展开
		$(".layui-body").animate({"left":"200px"});
		$(".layui-footer").animate({"left":"200px"});
		$(".layui-breadcrumb").animate({"left":"200px"});
		setCookie('system_isExpand','on',1);//设置cookie
	}else{
		//收起
		$(".layui-body").animate({"left":"0px"});
		$(".layui-footer").animate({"left":"0px"});
		$(".layui-breadcrumb").animate({"left":"0px"});
		setCookie('system_isExpand','off',1);//设置cookie
	}	
}