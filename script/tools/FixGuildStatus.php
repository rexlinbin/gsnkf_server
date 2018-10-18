<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: FixGuildStatus.php 154162 2015-01-21 08:35:36Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/tools/FixGuildStatus.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-01-21 08:35:36 +0000 (Wed, 21 Jan 2015) $
 * @version $Revision: 154162 $
 * @brief 
 *  
 **/
class FixGuildStatus extends BaseScript
{
	protected function executeScript ($arrOption)
	{
		$fix = false;
		if(isset($arrOption[0]) &&  $arrOption[0] == 'fix')
		{
			$fix = true;
		}
		
		$guildId = intval($arrOption[1]);
		try 
		{
			$guild = GuildObj::getInstance($guildId);
		}
		catch(Exception $e)
		{
		    echo "guild is not exist.\n";
		    return ;
		}
		
		$memberList = GuildDao::getMemberList($guildId, array(GuildDef::USER_ID));
		if (!empty($memberList)) 
		{
			echo "guild has member.\n";
			return ;
		}
		
		echo "guild status is invalid.\n";
		if ($fix) 
		{
			$guild->setStatus(GuildStatus::DEL);
			$guild->update();
		}
		return "ok\n";
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */