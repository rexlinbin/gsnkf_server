<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: zk.web.php 80342 2013-12-11 10:41:23Z wuqilin $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/lib/zk.web.php $
 * @author $Author: wuqilin $(hoping@babeltime.com)
 * @date $Date: 2013-12-11 10:41:23 +0000 (Wed, 11 Dec 2013) $
 * @version $Revision: 80342 $
 * @brief
 *
 **/

class ZKWeb
{

	const ZK_HOSTS = '192.168.1.200:2181';

	private static function amfDecode($data, $uncompress = false, $flag = 7)
	{

		if ($uncompress)
		{
			$data = gzuncompress ( $data );
			if (false === $data)
			{
				Logger::fatal ( "uncompress data failed" );
				throw "inter";
			}
		}

		if ($flag & 1)
		{
			if ($data [0] != chr ( 0x11 ))
			{
				$data = chr ( 0x11 ) . $data;
			}
		}
		return amf_decode ( $data, $flag );
	}

	private static function getZkInfo($path)
	{

		$hosts = self::ZK_HOSTS;
		$zk = new Zookeeper ( $hosts );
		if (! $zk->exists ( $path ))
		{
			echo "path:$path not found<br>\n";
			self::usage ();
			exit ( 0 );
		}
		$data = $zk->get ( $path );
		$arrData = self::amfDecode ( $data );
		return $arrData;
	}

	private static function getZkChildren($path)
	{

		$hosts = self::ZK_HOSTS;
		$zk = new Zookeeper ( $hosts );
		if (! $zk->exists ( $path ))
		{
			self::usage ();
			exit ( 0 );
		}

		return $zk->getChildren ( $path );
	}

	private static function printResult($arrData)
	{

		$text = print_r ( $arrData, true );
		$text = str_replace ( "\n", "<br>\n", $text );
		$text = str_replace ( " ", "&nbsp;", $text );
		$text = str_replace ( "\t", "&nbsp;&nbsp;&nbsp;&nbsp;", $text );
		echo $text;
	}

	static function main($group, $db)
	{

		$path = '/pirate/lcserver/lcserver#' . $group;
		$arrData = self::getZkInfo ( $path );
		self::printResult ( $arrData );
	}

	static function data($group, $db)
	{

		$path = '/pirate/dataproxy/data#' . $db;
		$arrData = self::getZkInfo ( $path );
		self::printResult ( $arrData );
	}

	static function battle($group, $db)
	{

		$path = "/pirate/battle/$group";
		$arrName = self::getZkChildren ( $path );
		$arrData = array ();
		foreach ( $arrName as $name )
		{
			$arrModuleInfo = self::getZkInfo ( $path . '/' . $name );
			$arrRet [] = $arrModuleInfo;
		}
		self::printResult ( $arrRet );
	}

	static function logic($group, $db)
	{

		$path = "/pirate/logic/$group";
		$arrData = self::getZkInfo ( $path );
		self::printResult ( $arrData );
	}

	static function stat($group, $db)
	{

		$path = '/pirate/stat/stat';
		$arrData = self::getZkInfo ( $path );
		self::printResult ( $arrData );
	}

	private static function usage()
	{

		echo "Usage: http://192.168.3.131:8080/zk?module=XXX&group=YYY&db=ZZZ<br>\n";
		echo "module: main|data|battle|logic|stat<br>\n";
	}

	static function run()
	{

		$module = $_REQUEST ["module"];
		$group = $_REQUEST ["group"];
		$db = $_REQUEST ["db"];
		$arrModule = array ('main', 'battle', 'data', 'stat', 'logic' );
		if (! in_array ( $module, $arrModule ))
		{
			echo "module:$module not found<br>\n";
			self::usage ();
			return;
		}
		self::$module ( $group, $db );
	}
}

ZKWeb::run ();

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */