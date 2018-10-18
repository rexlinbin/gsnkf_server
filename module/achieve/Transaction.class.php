<?php
/***************************************************************************
 * 
 * Copyright (c) 2014 babeltime.com, Inc. All Rights Reserved
 * $Id: Transaction.class.php 109731 2014-05-21 04:01:29Z QiangHuang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/achieve/Transaction.class.php $
 * @author $Author: QiangHuang $(huangqiang@babeltime.com)
 * @date $Date: 2014-05-21 04:01:29 +0000 (Wed, 21 May 2014) $
 * @version $Revision: 109731 $
 * @brief 
 *  
 **/
 
class Transaction
{
	private static $waitCommitObjs = array();
	
	public static function add($key, $obj)
	{
		self::$waitCommitObjs[$key] = $obj;
	}
	
	public static function commit()
	{
		foreach(self::$waitCommitObjs as $key => $obj)
		{
			try 
			{
				$obj->commit();
				Logger::debug("Transaction. [$key] commit.");
			}
			catch(Exception $e)
			{
				Logger::warning("transaction commit fail!. [$key] exception:%s!", $e);
			}
		}
	}
}
 