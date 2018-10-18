<?php
/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GenCoverage.php 60629 2013-08-21 09:51:53Z wuqilin $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/GenCoverage.php $
 * @author $Author: wuqilin $(hoping@babeltime.com)
 * @date $Date: 2013-08-21 09:51:53 +0000 (Wed, 21 Aug 2013) $
 * @version $Revision: 60629 $
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

require_once (EXLIB_ROOT . '/phpunit/PHP/CodeCoverage.php');
require_once (EXLIB_ROOT . '/phpunit/PHP/CodeCoverage/Report/HTML.php');
require_once (CONF_ROOT . '/Framework.cfg.php');

function generate()
{

	$coverage = new PHP_CodeCoverage ();
	$dir = opendir ( COV_ROOT );
	if (empty ( $dir ))
	{
		echo COV_ROOT . " can't be opened, please check if it exists\n";
		return;
	}

	$arrCoverage = array ();
	$arrFile = array ();
	while ( true )
	{
		$file = readdir ( $dir );
		if (empty ( $file ))
		{
			break;
		}
		if ($file == '.' || $file == '..')
		{
			continue;
		}
		$file = COV_ROOT . '/' . $file;
		$arrFile [] = $file;
		$data = file_get_contents ( $file );
		$arrData = unserialize ( $data );
		if (empty ( $arrCoverage ))
		{
			$arrCoverage = $arrData;
			continue;
		}

		foreach ( $arrData as $file => $arrLine )
		{
			if (! isset ( $arrCoverage [$file] ))
			{
				$arrCoverage [$file] = $arrLine;
				continue;
			}

			foreach ( $arrLine as $line => $flag )
			{
				if (! isset ( $arrCoverage [$file] [$line] ))
				{
					$arrCoverage [$file] [$line] = $flag;
					continue;
				}

				if ($arrCoverage [$file] [$line] < $flag)
				{
					$arrCoverage [$file] [$line] = $flag;
				}
			}
		}
	}

	if (count ( $arrFile ) > 1)
	{
		$time = intval ( microtime ( true ) * 1000 );
		$filename = COV_ROOT . '/' . $time . '_' . posix_getpid () . '.cov';
		file_put_contents ( $filename, serialize ( $arrCoverage ) );

		foreach ( $arrFile as $file )
		{
			unlink ( $file );
		}
	}

	$coverage->append ( $arrCoverage, 'cov' );
	$generator = new PHP_CodeCoverage_Report_HTML ();
	$generator->process ( $coverage, FrameworkConfig::COVERAGE_ROOT );
	echo sprintf ( "generate cov file in '%s' to '%s', done\n", COV_ROOT,
			FrameworkConfig::COVERAGE_ROOT );
}

generate ();

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
