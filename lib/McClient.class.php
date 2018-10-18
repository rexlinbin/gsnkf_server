<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: McClient.class.php 80342 2013-12-11 10:41:23Z wuqilin $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/lib/McClient.class.php $
 * @author $Author: wuqilin $(hoping@babeltime.com)
 * @date $Date: 2013-12-11 10:41:23 +0000 (Wed, 11 Dec 2013) $
 * @version $Revision: 80342 $
 * @brief
 *
 **/

class McClient
{

	/**
	 * PHP的代理对象
	 * @var PHPProxy
	 */
	private static $proxy;

	/**
	 * 当前使用的db
	 * @var string
	 */
	private static $db;

	/**
	 * 获取当前的phpproxy对象
	 * @return PHPProxy
	 */
	private static function getProxy()
	{

		if (empty ( self::$proxy ))
		{
			self::$proxy = new PHPProxy ( 'data' );
		}
		return self::$proxy;
	}

	/**
	 * 设置当前的db
	 * @param string $db
	 */
	static function setDb($db)
	{

		self::$db = $db;
	}

	/**
	 * 根据键值获取数值
	 * @param string $key 键值
	 * @param int $flag 键值对应的flag
	 * @return mixed 得到的值，如果没有返回null
	 */
	static function get($key, &$flag = null)
	{

		$db = self::$db;
		if (empty ( $db ))
		{
			$db = RPCContext::getInstance ()->getFramework ()->getDb ();
		}
		else
		{
			self::$db = null;
		}
		
		if (! empty ( $db ))
		{
			$key = $db . '.' . $key;
		}
		
		$proxy = self::getProxy ();
		$proxy->setDb ( $db );
		$arrRet = $proxy->mcGet ( $key );
		if (empty ( $arrRet ))
		{
			return null;
		}
		
		$flag = $arrRet ['flag'];
		return $arrRet ['data'];
	}

	/**
	 * 向mc中设置一个变量
	 * @param string $key 键值
	 * @param mixed $value 要设置的值，可以是array
	 * @param int $expiredTime 过期时间，0表示永远不过期
	 * @param int $flag 一个标识，在get时会传回来
	 * @return string STORED 表示存储成功
	 */
	static function set($key, $value, $expiredTime = 0, $flag = 0)
	{

		$db = self::$db;
		if (empty ( $db ))
		{
			$db = RPCContext::getInstance ()->getFramework ()->getDb ();
		}
		else
		{
			self::$db = null;
		}
		
		if (! empty ( $db ))
		{
			$key = $db . '.' . $key;
		}
		
		$proxy = self::getProxy ();
		$proxy->setDb ( $db );
		return $proxy->mcSet ( $key, $value, $flag, $expiredTime );
	}

	/**
	 * 向mc中设置一个变量
	 * @param string $key 键值
	 * @param mixed $value 要设置的值，可以是array
	 * @param int $expiredTime 过期时间，0表示永远不过期
	 * @param int $flag 一个标识，在get时会传回来
	 * @return string STORED 表示存储成功, NOT_STORED 表示存储不成功
	 */
	static function add($key, $value, $expiredTime = 0, $flag = 0)
	{

		$db = self::$db;
		if (empty ( $db ))
		{
			$db = RPCContext::getInstance ()->getFramework ()->getDb ();
		}
		else
		{
			self::$db = null;
		}
		
		if (! empty ( $db ))
		{
			$key = $db . '.' . $key;
		}
		$proxy = self::getProxy ();
		$proxy->setDb ( $db );
		return $proxy->mcAdd ( $key, $value, $flag, $expiredTime );
	}

	/**
	 * 从mc中删除一个变量
	 * @param string $key 键值
	 * @return string DELETED 表示删除成功, NOT_FOUND 表示没有找到
	 */
	static function del($key)
	{

		$db = self::$db;
		if (empty ( $db ))
		{
			$db = RPCContext::getInstance ()->getFramework ()->getDb ();
		}
		else
		{
			self::$db = null;
		}
		
		if (! empty ( $db ))
		{
			$key = $db . '.' . $key;
		}
		$proxy = self::getProxy ();
		return $proxy->mcDel ( $key );
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */