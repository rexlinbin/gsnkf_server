<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldCarnival.cfg.php 197316 2015-09-08 08:07:34Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/conf/WorldCarnival.cfg.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-09-08 08:07:34 +0000 (Tue, 08 Sep 2015) $
 * @version $Revision: 197316 $
 * @brief 
 *  
 **/
 
class WorldCarnivalConf
{
	const FIGHTER_COUNT			= 4;
	
	public static $subRoundCount = array
	(
			1 => 3,
			2 => 3,
			3 => 5,
	);
	
	public static $mapMaxLoseTimes = array
	(
			1 => 2, // 三局两胜
			2 => 2, // 三局两胜
			3 => 3, // 五局三胜
	);
	
	public static $curRank = array
	(
			1 => 4,
			2 => 4,
			3 => 2,
	);
	
	public static $winRank = array
	(
			1 => 2,
			2 => 2,
			3 => 1,
	);
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */