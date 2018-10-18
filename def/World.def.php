<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: World.def.php 214418 2015-12-08 02:58:08Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/World.def.php $
 * @author $Author: BaoguoMeng $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-12-08 02:58:08 +0000 (Tue, 08 Dec 2015) $
 * @version $Revision: 214418 $
 * @brief 
 *  
 **/
class WorldDef
{
	const WORLD_GENERAL_PRE = 'pirate_worldgeneral_';
	const WORLD_VIP_CLOSE = 0;
	const WORLD_VIP_CLOSE_BUT_WRITING = 1;
	const WORLD_VIP_OPEN = 2;
}

class WolrdActivityName
{
	const LORDWAR = 'lordwar';
	const GUILDWAR = 'guildwar';
	const WORLDPASS = 'worldpass';
	const WORLDARENA = 'worldarena';
    const WORLDGROUPON = 'worldgroupon';
    const WORLDCARNIVAL = 'worldcarnival';
    const WORLDCOMPETE = 'worldcompete';
    const MISSION = 'mission';
    const COUNTRYWAR = 'countrywar';
	
	public static $arrNeedNearConfig = array
	(
			self::WORLDPASS,
			self::WORLDARENA,
			self::WORLDCOMPETE,
			self::COUNTRYWAR,
	);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */