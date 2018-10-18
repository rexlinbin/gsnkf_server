<?php
/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: index.php 80342 2013-12-11 10:41:23Z wuqilin $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/lib/index.php $
 * @author $Author: wuqilin $(hoping@babeltime.com)
 * @date $Date: 2013-12-11 10:41:23 +0000 (Wed, 11 Dec 2013) $
 * @version $Revision: 80342 $
 * @brief
 *
 **/
if (! defined ( 'ROOT' ))
{
	define ( 'ROOT', dirname ( dirname ( __FILE__ ) ) );
	define ( 'LIB_ROOT', ROOT . '/lib' );
	define ( 'EXLIB_ROOT', ROOT . '/exlib' );
	define ( 'DEF_ROOT', ROOT . '/def' );
	define ( 'CONF_ROOT', ROOT . '/conf' );
	define ( 'LOG_ROOT', ROOT . '/log' );
	define ( 'MOD_ROOT', ROOT . '/module' );
	define ( 'HOOK_ROOT', ROOT . '/hook' );
	define ( 'COV_ROOT', ROOT . '/cov' );
}

require_once (DEF_ROOT . '/Define.def.php');

if (file_exists ( DEF_ROOT . '/Classes.def.php' ))
{
	require_once (DEF_ROOT . '/Classes.def.php');

	function __autoload($className)
	{

		$className = strtolower ( $className );
		if (isset ( ClassDef::$ARR_CLASS [$className] ))
		{
			require (ROOT . '/' . ClassDef::$ARR_CLASS [$className]);
		}
		else
		{
			trigger_error ( "class $className not found", E_USER_ERROR );
		}
	}
}

error_reporting ( E_ALL | E_STRICT );

function reportCoverage()
{

	$arrCoverage = xdebug_get_code_coverage ();
	xdebug_stop_code_coverage ();
	$time = intval ( microtime ( true ) * 1000 );
	$filename = COV_ROOT . '/' . $time . '_' . posix_getpid () . '.cov';
	file_put_contents ( $filename, serialize ( $arrCoverage ) );
}

if (extension_loaded ( 'xdebug' ))
{
	if (FrameworkConfig::DEBUG)
	{
		if (FrameworkConfig::COVERAGE)
		{
			xdebug_start_code_coverage ( XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE );
			register_shutdown_function ( 'reportCoverage' );
		}
	}
	else
	{
		xdebug_disable ();
	}
}

$framework = new RPCFramework ();
RPCContext::getInstance ()->setFramework ( $framework );
$framework->start ();

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */