<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Exception.class.php 50017 2013-06-05 13:38:59Z wuqilin $
 * 
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/lib/Exception.class.php $
 * @author $Author: wuqilin $(wuqilin@babeltime.com)
 * @date $Date: 2013-06-05 13:38:59 +0000 (Wed, 05 Jun 2013) $
 * @version $Revision: 50017 $
 * @brief 
 * 
 **/

class BaseException extends Exception
{

	const FAKE = 'fake';

	const INTER = 'inter';
	
	const SYS = 'sys';

	const CONFIG = 'config';

	protected $errMsg;

	public function __construct($type, $arrArg)
	{

		parent::__construct ( $type );
		
		if ($type == self::FAKE)
		{
			$ret = Logger::log ( Logger::L_WARNING, $arrArg, 2 );
		}
		else
		{
			$ret = Logger::log ( Logger::L_FATAL, $arrArg, 2 );
		}
		
		$this->errMsg = $ret;
	}

	public function getMsg()
	{

		return $this->errMsg;
	}

}


class FakeException extends BaseException
{

	public function __construct($format, $args = null, $_ = null)
	{

		$arrArg = func_get_args ();
		parent::__construct ( BaseException::FAKE, $arrArg );
	}
}

class ConfigException extends BaseException
{

	public function __construct($format, $args = null, $_ = null)
	{

		$arrArg = func_get_args ();
		parent::__construct ( BaseException::CONFIG, $arrArg );
	}
}

class InterException extends BaseException
{

	public function __construct($format, $args = null, $_ = null)
	{

		$arrArg = func_get_args ();
		parent::__construct ( BaseException::INTER, $arrArg );
	}
}

class SysException extends BaseException
{

	public function __construct($format, $args = null, $_ = null)
	{

		$arrArg = func_get_args ();
		parent::__construct ( BaseException::SYS, $arrArg );
	}
}




/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */