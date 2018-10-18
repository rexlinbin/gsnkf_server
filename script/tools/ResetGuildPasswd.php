<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ResetGuildPasswd.php 136771 2014-10-20 06:44:55Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/tools/ResetGuildPasswd.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-10-20 06:44:55 +0000 (Mon, 20 Oct 2014) $
 * @version $Revision: 136771 $
 * @brief 
 *  
 **/
class ResetGuildPasswd extends BaseScript
{
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	*/
	protected function executeScript($arrOption)
	{
		$usage = "usage::btscript game001 ResetGuildPasswd.php guildId\n";
		
		$passwd = "";
		$arrField = array();
		$guildId = $arrOption[0];
		$guildInfo = GuildDao::selectGuild($guildId);
		if (empty($guildInfo)) 
		{
			return ;
		}
		$arrField[GuildDef::VA_INFO] = $guildInfo[GuildDef::VA_INFO];
		$arrField[GuildDef::VA_INFO][GuildDef::ALL][GuildDef::PASSWD] = $passwd;
		GuildDao::updateGuild($guildId, $arrField);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */