<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: fixGuildGoods.php 88272 2014-01-22 02:48:02Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/tools/fixGuildGoods.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-01-22 02:48:02 +0000 (Wed, 22 Jan 2014) $
 * @version $Revision: 88272 $
 * @brief 
 *  
 **/
class FixGuildGoods extends BaseScript
{
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	*/
	protected function executeScript ($arrOption)
	{
		$arrCond = array(array(GuildDef::STATUS, '=', GuildStatus::OK));
		$count = GuildDao::getGuildCount($arrCond);
		for ($i = 0; $i < $count; $i++)
		{
			$cond = array(GuildDef::GUILD_ID, '>', 0);
			$arrField = array(GuildDef::GUILD_ID, GuildDef::VA_INFO);
			$arrRet = GuildDao::getGuildList($cond, $arrField, $i, 1);
			$guildId = $arrRet[0][GuildDef::GUILD_ID];
			$vaInfo = $arrRet[0][GuildDef::VA_INFO];
			if (isset($vaInfo[GuildDef::ALL][GuildDef::GOODS]))
			{
				$vaInfo[GuildDef::ALL][GuildDef::GOODS] = array();
				printf("fix guild:%d goods\n", $guildId);
			}
			if (isset($vaInfo[GuildDef::ALL][GuildDef::REFRESH_LIST])) 
			{
				$vaInfo[GuildDef::ALL][GuildDef::REFRESH_LIST] = array();
			}
			if (isset($vaInfo[GuildDef::ALL][GuildDef::REFRESH_CD]))
			{
				$vaInfo[GuildDef::ALL][GuildDef::REFRESH_CD] = 0;
			}
			$arrField = array(GuildDef::VA_INFO => $vaInfo);
			GuildDao::updateGuild($guildId, $arrField);
		}
		
		echo "ok\n";
	}
		
	private function usage()
	{
		echo "usage: btscript game001 fixGuildGoods.php \n";
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */