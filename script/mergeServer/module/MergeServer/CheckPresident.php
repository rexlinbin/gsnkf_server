<?php
/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CheckPresident.php 29637 2012-10-16 08:21:26Z HaidongJia $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/trunk/pirate/rpcfw/script/mergeServer/module/MergeServer/CheckPresident.php $
 * @author $Author: HaidongJia $(jiangzhichao@babeltime.com)
 * @date $Date: 2012-10-16 16:21:26 +0800 (星期二, 16 十月 2012) $
 * @version $Revision: 29637 $
 * @brief
 *
 **/

class CheckPresident
{
	public static function isPresident($game_id, $uid)
	{
		return self::checkByUid($game_id, $uid);
	}

	private static function checkByUid($game_id, $uid)
	{
		$ret = self::getGuildRecord($game_id, $uid);
		if (Empty($ret) || Empty($ret[0]) || Empty($ret[0]['uid']))
		{
			//Logger::debug('the user is not the President of guild. uid = [%s], game_id = [%s]', $uid, $game_id);
			return FALSE;
		}

		return TRUE;
	}

	private static function getGuildRecord($game_id, $uid)
	{
		$mysql = MysqlManager::getMysql($game_id);
		$return = $mysql->query("select uid from t_guild_member where uid = $uid and member_type = '1' ");
		return $return;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */