<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: FixGuildLevel.php 168469 2015-04-20 03:49:45Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/tools/FixGuildLevel.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-04-20 03:49:45 +0000 (Mon, 20 Apr 2015) $
 * @version $Revision: 168469 $
 * @brief 
 *  
 **/
class FixGuildLevel extends BaseScript
{
	protected function executeScript ($arrOption)
	{
		$fix = false;
		if(isset($arrOption[0]) &&  $arrOption[0] == 'fix')
		{
			$fix = true;
		}
		
		$type = GuildDef::GUILD;
		$guildId = intval($arrOption[1]);
		try
		{
			//获得军团信息
			$guild = GuildObj::getInstance($guildId, array(GuildDef::VA_INFO));
			$guildInfo = $guild->getInfo();
			$guildLevel = $guildInfo[GuildDef::GUILD_LEVEL];
			$buildLevel = $guildInfo[GuildDef::VA_INFO][$type][GuildDef::LEVEL];
			if ($guildLevel != $buildLevel)
			{
				printf("guildId:%d, guildLevel:%d, buildLevel:%d\n", $guildId, $guildLevel, $buildLevel);
				Logger::info('guildId:%d, guildLevel:%d, buildLevel:%d', $guildId, $guildLevel, $buildLevel);
				$confname = GuildDef::$TYPE_TO_CONFNAME[$type];
				$conf = btstore_get()->$confname;
				$expId = $conf[GuildDef::GUILD_EXP_ID];
				$totalExp = btstore_get()->EXP_TBL[$expId][$guildLevel];
				$currExp = $guild->getBuildExp($type);
				Logger::info('guildId:%d, currExp:%d, totalExp:%d', $guildId, $currExp, $totalExp);
				$guild->addBuildExp($type, $totalExp - $currExp);
				$guild->setBuildLevel(GuildDef::GUILD, $guildLevel);
				if ($fix) 
				{
					$guild->update();
				}
			}
		}
		catch(Exception $e)
		{
		    $guild->unlockArrField();
		    throw $e;
		}
		
		echo "ok\n";
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */