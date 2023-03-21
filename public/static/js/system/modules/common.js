/**
 * 【layui模块绑定】
 * 注意：
 * 1、导航依赖element模块
 * 2、表单依赖form模块
 */
layui.define(['element','form'], function(exports){
	var element = layui.element;
	var form = layui.form;
	exports('common', {}); //注意，这里是模块输出的核心，模块名必须和use时的模块名一致
});