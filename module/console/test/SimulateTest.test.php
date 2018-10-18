<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: SimulateTest.test.php 59144 2013-08-13 09:48:26Z TiantianZhang $
 * 
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/console/test/SimulateTest.test.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2013-08-13 09:48:26 +0000 (Tue, 13 Aug 2013) $
 * @version $Revision: 59144 $
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


function test1($proxy) {
	try {
		Test ( $proxy, 'getCopyList' );
		
	} catch ( Exception $e ) {
		echo 'failed message:' . $e->getMessage () . "\n";
	}

}

function testFormation($proxy)
{
	try {
		Test ( $proxy, 'execute' ,array('getFormation'));
		
	} catch ( Exception $e ) {
		echo 'failed message:' . $e->getMessage () . "\n";
	}
}

function testHero($proxy)
{
	
}

function testHorse( $proxy )
{
	try 
	{
		Test ( $proxy, 'addHorse');
	} 
	catch ( Exception $e ) 
	{
		echo 'failed message:' . $e->getMessage () . "\n";
	}
}

/**
 * argv的值：array(filename pid uid command commandarg1 commandarg2)
 */
function main() {
	$argc	=	$_SERVER['argc'];
	$argv	=	$_SERVER['argv'];
	array_shift($argv);
	var_dump($argv);
	$pid = $argv[0];
	$uid = $argv[1];
	array_shift($argv);
	array_shift($argv);
	$proxy = new RPCProxy ( '192.168.1.58', '7777', true );
	$proxy->setClass ( 'user' );
	$proxy->setRequestType ( RequestType::DEBUG );
	$proxy->login ( $pid );
	$proxy->userLogin ( $uid );
	$proxy->setPublic ( FALSE );
	$proxy->setClass ( 'console' );
	Test ( $proxy, 'execute' ,$argv);
}

main ();
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */