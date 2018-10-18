<?php
/***************************************************************************
 * 
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: Arena.def.php 147686 2014-12-20 08:13:43Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Arena.def.php $
 * @author $Author: BaoguoMeng $(lanhongyu@babeltime.com)
 * @date $Date: 2014-12-20 08:13:43 +0000 (Sat, 20 Dec 2014) $
 * @version $Revision: 147686 $
 * @brief 
 *  
 **/

class ArenaDef
{
	const ARENA_ID = 1;
	
	//历史排名奖励状态0无1未发2已发
	const NONE = 0;
	const HAVE = 1;
	const REWARD = 2;
}

class ArenaOpponentType
{
	const PASS = 1;			// 过关斩将
	
	public static $offset = array
	(
		self::PASS => 18000, // 凌晨5点刷新
	);
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */