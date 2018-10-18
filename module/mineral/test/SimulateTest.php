<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: SimulateTest.php 50369 2013-06-07 06:22:38Z TiantianZhang $
 * 
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/mineral/test/SimulateTest.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2013-06-07 06:22:38 +0000 (Fri, 07 Jun 2013) $
 * @version $Revision: 50369 $
 * @brief 
 * 
 **/

if (! defined ( 'ROOT' )) {
	define ( 'ROOT', dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) );
	define ( 'LIB_ROOT', ROOT . '/lib' );
	define ( 'EXLIB_ROOT', ROOT . '/exlib' );
	define ( 'DEF_ROOT', ROOT . '/def' );
	define ( 'CONF_ROOT', ROOT . '/conf' );
	define ( 'LOG_ROOT', ROOT . '/log' );
	define ( 'MOD_ROOT', ROOT . '/module' );
	define ( 'HOOK_ROOT', ROOT . '/hook' );
}

require_once (DEF_ROOT . '/Define.def.php');

if (file_exists ( DEF_ROOT . '/Classes.def.php' )) {
	require_once (DEF_ROOT . '/Classes.def.php');
	
	function __autoload($className) {
		
		$className = strtolower ( $className );
		if (isset ( ClassDef::$ARR_CLASS [$className] )) {
			require_once (ROOT . '/' . ClassDef::$ARR_CLASS [$className]);
		} else {
			trigger_error ( "class $className not found", E_USER_ERROR );
		}
	}
}

function Test($proxy, $func_name, $args = array()) {
	echo "Test $func_name start...\n";
	$ret = '';
	if (empty ( $args ))
		$ret = $proxy->$func_name ();
	else
		$ret = call_user_func_array ( array ($proxy, $func_name ), $args );
	
	if ($ret === FALSE) {
		echo sprintf ( "$func_name failed, ret:%s\n", var_export ( $ret, TRUE ) );
	} else {
		echo sprintf ( "$func_name ok, ret:%s\n", var_export ( $ret, TRUE ) );
	}	
	echo "Test $func_name end...\n\n";
	return $ret;
}

/**
 * 1.获取玩家的爬塔信息
 * 2.进入爬塔的某个塔层
 * 3.购买攻击次数   can_defeat_num++ gold_defeat_num++ last_defeat_time更新
 */
function test1($proxy) {
	try 
	{		
		Test ( $proxy, 'getPitsByDomain', array (1 ) );
		Test ( $proxy, 'getPitInfo', array (1, 1 ) );
		Test ( $proxy, 'getSelfPitsInfo' );
		Test ( $proxy, 'explorePit' );
		Test ( $proxy, 'capturePit', array (1, 1) );
		Test ( $proxy, 'getSelfPitsInfo' );
	} 
	catch ( Exception $e ) 
	{
		echo sprintf ( " failed error message:%s.\n", $e->getMessage () );
	}
}

function test2($proxy) {
	try
	{
		Test ( $proxy, 'getPitsByDomain', array (1 ) );
		Test ( $proxy, 'getPitInfo', array (1, 1 ) );
		Test ( $proxy, 'getSelfPitsInfo' );
		Test ( $proxy, 'giveUpPit' , array (1, 1));
		Test ( $proxy, 'getSelfPitsInfo' );
		Test ( $proxy, 'capturePit', array (1, 2) );
		Test ( $proxy, 'getSelfPitsInfo' );
	}
	catch ( Exception $e )
	{
		echo sprintf ( " failed error message:%s.\n", $e->getMessage () );
	}
}



function main() 
{
	
	ob_start ();
	Logger::init ( LOG_ROOT . '/' . FrameworkConfig::LOG_NAME, FrameworkConfig::LOG_LEVEL );
	
	$uid = 20014;
	$proxy = new RPCProxy ( '192.168.1.58', '7777', true );
	$proxy->setClass ( 'user' );
	$proxy->setRequestType ( RequestType::DEBUG );
	$proxy->login ( $uid );
	$proxy->userLogin ( $uid );
	$proxy->setPublic ( FALSE );
	$proxy->setClass ( 'mineral' );
	test2 ( $proxy );
}

main ();
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */