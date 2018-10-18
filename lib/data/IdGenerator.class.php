<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IdGenerator.class.php 36620 2013-01-22 06:38:47Z HaopingBai $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/trunk/pirate/rpcfw/lib/data/IdGenerator.class.php $
 * @author $Author: HaopingBai $(hoping@babeltime.com)
 * @date $Date: 2013-01-22 14:38:47 +0800 (周二, 22 一月 2013) $
 * @version $Revision: 36620 $
 * @brief
 *
 **/

class IdGenerator
{

	/**
	 * PHPProxy
	 * @var PHPProxy
	 */
	static $proxy = null;

	static function nextId($id, $db = null)
	{

		$arrRet = self::nextMultiId ( $id, 1, $db );
		return intval ( $arrRet [0] );
	}

	static function setId($id, $num, $db = null)
	{

		if (self::$proxy == null)
		{
			self::$proxy = new PHPProxy ( 'data' );
			self::$proxy->setClass ( 'id' );
		}

		if ($db === null)
		{
			$db = RPCContext::getInstance ()->getFramework ()->getDb ();
		}

		if (empty ( $db ))
		{
			return self::$proxy->set ( $id, $num );
		}
		else
		{
			self::$proxy->setDb ( $db );
			return self::$proxy->set ( $id, $num, $db );
		}
	}

	static function showId($id, $db = null)
	{

		if (self::$proxy == null)
		{
			self::$proxy = new PHPProxy ( 'data' );
			self::$proxy->setClass ( 'id' );
		}

		if ($db === null)
		{
			$db = RPCContext::getInstance ()->getFramework ()->getDb ();
		}

		if (empty ( $db ))
		{
			return self::$proxy->show ( $id );
		}
		else
		{
			self::$proxy->setDb ( $db );
			return self::$proxy->show ( $id, $db );
		}
	}

	static function nextMultiId($id, $num, $db = null)
	{

		if (self::$proxy == null)
		{
			self::$proxy = new PHPProxy ( 'data' );
			self::$proxy->setClass ( 'id' );
		}

		if ($db === null)
		{
			$db = RPCContext::getInstance ()->getFramework ()->getDb ();
		}

		if (empty ( $db ))
		{
			return self::$proxy->next ( $id, $num );
		}
		else
		{
			self::$proxy->setDb ( $db );
			return self::$proxy->next ( $id, $num, $db );
		}
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
