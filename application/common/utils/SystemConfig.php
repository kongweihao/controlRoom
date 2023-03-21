<?php
namespace app\common\utils;

/**
 * 配置类
 * @author kongweihao
 *
 */
class SystemConfig{
	/**
	 * 开发者帐号
	 */
	public static $systemAccount = ['kwh'];		//密码：admin
	/**
	 * 超级管理员
	 */
	public static $superAccount = 'kwh';
	/**
	 * 权限放行帐号白名单（数组形式）
	 */
	public static $passPrivilege = ['kwh'];
	/**
	 * 当前系统版本
	 * @desc 用于刷新页面缓存
	 */
	public static $systemVersion = '1.502';
}