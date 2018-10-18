<?php
/***************************************************************************
 * 
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: ApiManager.class.php 80342 2013-12-11 10:41:23Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/lib/api/ApiManager.class.php $
 * @author $Author: wuqilin $(lanhongyu@babeltime.com)
 * @date $Date: 2013-12-11 10:41:23 +0000 (Wed, 11 Dec 2013) $
 * @version $Revision: 80342 $
 * @brief 
 *  
 **/

class ApiManager
{
	public static function getApi($noServer=false)
	{
		//以前这里判断只有$serverId < 20000的服在使用PlatformApi， 其他服被认为是联运使用PlatformApiDefault。
		//现在不需要这个判断
		return new PlatformApi();
	}	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */