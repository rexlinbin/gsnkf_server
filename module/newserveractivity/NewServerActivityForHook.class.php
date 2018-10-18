<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: NewServerActivityForHook.class.php 242177 2016-05-11 10:56:34Z JiexinLin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/newserveractivity/NewServerActivityForHook.class.php $
 * @author $Author: JiexinLin $(linjiexin@babeltime.com)
 * @date $Date: 2016-05-11 10:56:34 +0000 (Wed, 11 May 2016) $
 * @version $Revision: 242177 $
 * @brief “开服7天乐”hook类(每个前端请求执行后额外附加的操作)
 *  
 **/
class NewServerActivityForHook
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
				$obj->save();
				Logger::debug("NewServerActivityForHook. [%s] commit.", $key);
			}
			catch(Exception $e)
			{
				Logger::warning("NewServerActivityForHook commit fail!. [%s] exception:%s!", $key, $e);
			}
		}
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */