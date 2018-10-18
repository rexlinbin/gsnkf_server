<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: SimulateTest_ActBase.php 54198 2013-07-06 09:10:59Z TiantianZhang $
 * 
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/acopy/test/SimulateTest_ActBase.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2013-07-06 09:10:59 +0000 (Sat, 06 Jul 2013) $
 * @version $Revision: 54198 $
 * @brief 
 * 
 **/

if (! defined ( 'ROOT' )) 
{
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

if (file_exists ( DEF_ROOT . '/Classes.def.php' )) 
{
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

function Test($proxy, $func_name, $args = array()) 
{
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
	$proxy->setClass ( 'acopy' );
	return $proxy;
}

/**
 * 进入副本1 攻击据点1的简单难度
 * 1.获取所有的副本getCopyList
 * 2.进入副本enterCopy
 * 3.进入某个难度级别enterBaseLevel
 * 4.攻击据点的多个怪物doBattle
 */
function test1($proxy) 
{
	try {
	Test ( $proxy, 'getCopyList' );
	Test ( $proxy, 'enterCopy', array (300001 ) );	
	//攻击副本50001的三个难度    难度的攻击是非线性的
	for($i=1;$i>0;$i--)
	{
		Test ($proxy,  'enterBaseLevel',array(300001,$i));
		Test ( $proxy, 'doBattle', array (300001,$i,300001,array()) );
		$proxy = reLogin($proxy);
		Test ( $proxy, 'doBattle', array (300001,$i,300002,array()) );
		Test ( $proxy, 'doBattle', array (300001,$i,300003,array()) );
		Test ( $proxy, 'getCopyInfo', array(300001));
	}	
	} catch ( Exception $e ) {
		echo sprintf ( "fail error message:%s.\n", $e->getMessage () );
	}
}

function test2($proxy) 
{
	try {
		Test ( $proxy, 'getCopyList' );
		Test ( $proxy, 'enterCopy', array (50001 ) );
		
		Test ($proxy,  'enterBaseLevel',array(50001,1));
		Test ( $proxy, 'doBattle', array (50001,1,16) );
		
	} catch ( Exception $e ) {
	echo sprintf ( "fail error message:%s.\n", $e->getMessage () );
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
	$proxy->setClass ( 'acopy' );
	test1 ( $proxy );

}

main ();
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */