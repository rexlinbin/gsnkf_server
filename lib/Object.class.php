<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Object.class.php 80342 2013-12-11 10:41:23Z wuqilin $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/lib/Object.class.php $
 * @author $Author: wuqilin $(hoping@babeltime.com)
 * @date $Date: 2013-12-11 10:41:23 +0000 (Wed, 11 Dec 2013) $
 * @version $Revision: 80342 $
 * @brief
 *
 **/
class Object
{

	protected $arrData = array ();

	function __set($key, $value)
	{

		$this->arrData [$key] = $value;
	}

	function __get($key)
	{

		if (array_key_exists($key, $this->arrData))
		{
			return $this->arrData [$key];
		}
		else
		{
			trigger_error ( "undefined key $key");
			Logger::warning("undefined key $key");
			return null;
		}
	}

	function __isset($key)
	{

		return isset ( $this->arrData [$key] );
	}

	function __unset($key)
	{

		unset ( $this->arrData [$key] );
	}

	function getData()
	{

		return $this->arrData;
	}

	function setData($arrData)
	{

		if (! is_array ( $arrData ))
		{
			trigger_error ( "invalid type, array required", E_NOTICE );
			$arrData = array ();
		}
		$this->arrData = $arrData + $this->arrData;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
