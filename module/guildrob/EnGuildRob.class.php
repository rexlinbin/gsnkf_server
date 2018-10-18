<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnGuildRob.class.php 195257 2015-08-28 08:27:26Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guildrob/EnGuildRob.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-08-28 08:27:26 +0000 (Fri, 28 Aug 2015) $
 * @version $Revision: 195257 $
 * @brief 
 *  
 **/
 
/**********************************************************************************************************************
* Class       : EnGuildRob
* Description : 军团粮仓内部接口类
* Inherit     :
**********************************************************************************************************************/
class EnGuildRob
{
	public static function isInRobBattle($guildId)
	{
		// 只要在抢粮活动期间，就不能做分粮等一些列操作，这里直接用effectTime判断
		$gap = 10;
		return GuildRobUtil::checkEffectTime($gap);
		
		//return GuildRobUtil::getGuildRobId($guildId) > 0;
	}
	
	public static function guildRobInfoChanged($guildId)
	{	
		return GuildRobUtil::guildRobInfoChanged($guildId);
	}
	
	public static function canRobGrain($currGrainNum, $grainUpperLimit)
	{
		return GuildRobUtil::getCanRobGrain($currGrainNum, $grainUpperLimit);
	}
	
	public static function guildShelterTime($guildId)
	{
		$lastDefendTime = GuildRobUtil::getLastDefendTime($guildId);
		$shelterTime = $lastDefendTime + intval(btstore_get()->GUILD_ROB['after_defend_cd_time']);
		return (Util::getTime() >= $shelterTime ? 0 : $shelterTime);
	}
	
	public static function getEffectTime()
	{
		return GuildRobUtil::getEffectTime();
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */