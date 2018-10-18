<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildDao.class.php 32634 2012-12-10 05:08:47Z HaidongJia $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/trunk/pirate/rpcfw/script/mergeServer/module/MergeServer/GuildDao.class.php $
 * @author $Author: HaidongJia $(jhd@babeltime.com)
 * @date $Date: 2012-12-10 13:08:47 +0800 (星期一, 10 十二月 2012) $
 * @version $Revision: 32634 $
 * @brief
 *
 **/

class GuildDao
{
	public static function getGuild($game_id, $start_id, $limit)
	{
		$mysql = MysqlManager::getMysql($game_id);
		$return = $mysql->query("select * from t_guild where status != 0 and guild_id > $start_id order by guild_id asc limit 0, $limit;");
		return $return;
	}

	public static function getGuildMemberNum($target_game_id, $guild_id)
	{
		$mysql = MysqlManager::getMysql($target_game_id);
		$return = $mysql->query("select count(1) as guild_member_num from t_guild_member where guild_id = $guild_id");
		return $return[0]['guild_member_num'];
	}

	public static function setRetainGuild($target_game_id, $game_id, $guild_id, $guild_name)
	{
		$mysql = MysqlManager::getMysql($target_game_id);
		$return = $mysql->query("insert into t_tmp_guild (guild_id, game_id, name) values ($guild_id, '$game_id', '$guild_name');");
	}

	public static function setDealGuild($target_game_id, $game_id, $guild_id, $new_guild_id)
	{
		$mysql = MysqlManager::getMysql($target_game_id);
		$return = $mysql->query("update t_tmp_guild set deal = 1, new_guild_id = $new_guild_id where guild_id = $guild_id and game_id = '$game_id'");
	}

	public static function getRetainGuild($target_game_id, $game_id, $start_id, $limit)
	{
		$mysql = MysqlManager::getMysql($target_game_id);
		$return = $mysql->query("select * from t_tmp_guild where guild_id > $start_id and game_id = '$game_id' order by guild_id asc limit 0, $limit;");
		return $return;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */