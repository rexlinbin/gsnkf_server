<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: SimulateTest.php 58285 2013-08-08 06:35:08Z TiantianZhang $
 * 
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/ecopy/test/SimulateTest.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2013-08-08 06:35:08 +0000 (Thu, 08 Aug 2013) $
 * @version $Revision: 58285 $
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


function reLogin($proxy)
{
	$proxy = null;
	$uid = 20014;
	$proxy = new RPCProxy ( '192.168.1.58', '7777', true );
	$proxy->setClass('user');
	$proxy->setRequestType(RequestType::DEBUG);
	$proxy->login ( $uid );
	$proxy->userLogin ( $uid );
	$proxy->setPublic(FALSE);
	$proxy->setClass ( 'ncopy' );
	echo "user login\n";
	Test($proxy, 'getAtkInfoOnEnterGame');
	$proxy->setClass ( 'ecopy' );
	return $proxy;
}

/**
 * 进入副本1 攻击据点1的简单难度
 * 1.获取所有的副本getEliteCopyInfo
 * 2.进入副本enterCopy
 * 3.攻击据点的多个怪物doBattle
 */
function test1($proxy) 
{
	try {
	Test ( $proxy, 'getEliteCopyInfo' );
	
	//进入副本10001
	Test ( $proxy, 'enterCopy', array (200001 ) );
	Test ( $proxy, 'doBattle', array (200001, 200001 ,array()) );
	$proxy = reLogin($proxy);
	Test ( $proxy, 'doBattle', array (200001, 200002 ,array()) );
	Test ( $proxy, 'doBattle', array (200001, 200003 ,array()) );
	//通关副本10001，可以开启副本10002的攻击状态  可是10002还没有开启显示状态所有暂时不能开启
	Test ( $proxy, 'getEliteCopyInfo' );
	Test ( $proxy, 'getCopyDefeatInfo',array(200001));
	
	} catch ( Exception $e ) {
		echo sprintf ( "fail error message::%s\n", $e->getMessage () );
	}
	
	
}

function main() 
{
	$uid = 20014;
	$proxy = new RPCProxy ( '192.168.1.58', '7777', true );
	$proxy->setClass ( 'user' );
	$proxy->setRequestType ( RequestType::DEBUG );
	$proxy->login ( $uid );
	$proxy->userLogin ( $uid );
	$proxy->setPublic ( FALSE );
	$proxy->setClass ( 'ecopy' );
	test1 ( $proxy );

}

main ();
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */