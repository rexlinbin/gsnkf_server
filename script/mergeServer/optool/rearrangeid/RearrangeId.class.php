<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: MergeServer.class.php 100294 2014-04-15 07:44:47Z HaidongJia $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/trunk/pirate/rpcfw/script/mergeServer/module/MergeServer/MergeServer.class.php $
 * @author $Author: HaidongJia $(jhd@babeltime.com)
 * @date $Date: 2014-04-15 15:44:47 +0800 (星期二, 15 四月 2014) $
 * @version $Revision: 100294 $
 * @brief
 *
 **/

class Rearrange
{
	
	public static $SQLMODIFYTABLE = array(

			't_user' => array(

					't_bag' => array(
							'item_id' => 'item_id',
					),
											
					't_hero' => array(
					),
			),
	);
	

	public static function merge($merge_server_ids, $game_id, $target_game_id)
	{
		sort($merge_server_ids);
		MergeGlobal::setMergeServerIDs($merge_server_ids);
		MergeGlobal::setMergeServerTargetID($target_game_id);
	
		if ( in_array($game_id, $merge_server_ids) == FALSE )
		{
			echo "GAME ID $game_id need in merge server id!\n";
			exit;
		}
		
		SQLTableConf::$SQLMODIFYTABLE = self::$SQLMODIFYTABLE;
		SQLTableConf::$SQLMODIFYMAINTABLE = array();
	
		try
		{
			self::__merge($game_id, $target_game_id);
			SQLModify::clearIdCache($game_id);
		}
		catch (Exception $e)
		{
			var_dump($e);
			exit(1);
		}
	
		echo "MERGE SERVER $game_id => $target_game_id done!\n";
		echo "MERGE SERVER => $target_game_id done!\n";
	}
	
	private static function __merge($game_id, $target_game_id)
	{
		// deal user table
		self::setRetainUser($game_id, $target_game_id);
	
		//deal all retain user
		$start_uid = 0;
		for ( $i = 0; $i < CommonDef::MAX_LOOP_NUM; $i++ )
		{
			$user_list = MergeServer::getRetainUser($target_game_id, $game_id, $start_uid, DataDef::MAX_LIMIT);
	
			if ( count($user_list) == 0 )
			{
				break;
			}
	
			foreach ( $user_list as $user )
			{
				if ( $start_uid < $user['uid'] )
				{
					$start_uid = $user['uid'];
				}
	
				self::dealUser($game_id, $target_game_id, $user);
			}
		}
	}
	
	
	public static function dealUser($game_id, $target_game_id, $user)
	{
		$uid = $user['uid'];
		$deal_status = $user['deal'];
		if ( $deal_status == 1 )
		{
			return;
		}
	
		$new_uid = $uid;
	
		foreach( SQLTableConf::$SQLMODIFYTABLE['t_user'] as $relative_table => $relative_data)
		{
			//check table exist, if not, continue.
			$relative_table_array = SQLModify::getTableList($game_id, $relative_table);
			if ( empty($relative_table_array) )
			{
				continue;
			}
				
			$rows = SQLModify::getRelativeData($game_id, $relative_table, 'uid', $uid);
			foreach ( $rows as $row )
			{
				//deal item
				if ( isset(SQLTableConf::$SQLMODIFYITEM[$relative_table]) )
				{
					$va_call_back = SQLTableConf::$SQLMODIFYITEM[$relative_table];
					$item_ids = Items::$va_call_back($row);
					foreach ( $item_ids as $item_id )
					{
						MergeServer::dealItem($game_id, $target_game_id, $item_id);
					}
				}
				
				//deal modify columns
				$row = MergeServer::dealModifyColumn( $row, $game_id, 't_user', $relative_table);
	
				//deal va
				$row = MergeServer::dealVA($row, $game_id, $relative_table);
	
				// export data
				SQLModify::exportData($target_game_id, $row, $relative_table, 'uid', $new_uid );
	
			}
		}
		//标记当前用户已经处理完毕
		UserDao::setDealUser($target_game_id, $game_id, $uid, $new_uid );
		echo "deal gameid:$game_id user:$uid done!\n";
		//usleep(200000);
	}

	
	

	private static function setRetainUser($game_id, $target_game_id)
	{
		$start_uid = 0;
		for ( $i = 0; $i < CommonDef::MAX_LOOP_NUM; $i++ )
		{
			$users = UserDao::getRetainUser($target_game_id, $game_id, $start_uid, DataDef::MAX_LIMIT);
			if ( count($users) == 0 )
			{
				break;
			}
			foreach ( $users as $user )
			{
				if ( $start_uid < $user['uid'] )
				{
					$start_uid = $user['uid'];
				}
			}
		}

		for ( $i = 0; $i < CommonDef::MAX_LOOP_NUM; $i++ )
		{
			$users = UserDao::getUser($game_id, $start_uid, DataDef::MAX_LIMIT);
			if ( count($users) == 0 )
			{
				break;
			}
			foreach ( $users as $user )
			{
				UserDao::setRetainUser($target_game_id, $game_id, $user['uid'], $user['pid'], $user['uname']);
			}
			if ( $start_uid < $user['uid'] )
			{
				$start_uid = $user['uid'];
			}
		}
	}
	
	
}









/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */