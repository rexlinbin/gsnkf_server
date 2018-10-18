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

class MergeServer
{
	/**
	 *
	 * @var array
	 */
	//public static $MERGE_SERVER_IDS = array();

	/**
	 *
	 * 合并服务器
	 *
	 * @param array $merge_server_ids		合并的所有服务器game_ids
	 * @param int $game_id					合并的服务器game_id
	 * @param string $target_game_id		合并的目标服务器game_id
	 *
	 * @return NULL
	 */
	public static function merge($merge_server_ids, $game_id, $target_game_id)
	{
		sort($merge_server_ids);
		//self::$MERGE_SERVER_IDS = $merge_server_ids;
		MergeGlobal::setMergeServerIDs($merge_server_ids);
		MergeGlobal::setMergeServerTargetID($target_game_id);

		if ( in_array($game_id, $merge_server_ids) == FALSE )
		{
			echo "GAME ID $game_id need in merge server id!\n";
			exit;
		}

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

	/**
	 *
	 * 处理单个用户
	 * @param array $merge_server_ids
	 * @param string $target_game_id
	 * @param string $game_id
	 * @param int $uid
	 *
	 * @return NULL
	 */
	public static function mergeOneUser($merge_server_ids, $target_game_id, $game_id, $uid)
	{
		sort($merge_server_ids);
		//self::$MERGE_SERVER_IDS = $merge_server_ids;
		MergeGlobal::setMergeServerIDs($merge_server_ids);
		MergeGlobal::setMergeServerTargetID($target_game_id);

		$users = self::getRetainUser($target_game_id, $game_id, $uid, 1);
		if ( count($users) == 0 )
		{
			echo "FATAL invalid uid:$uid in server id:$game_id!\n";
			return;
		}
		$user = $users[0];
		self::dealUser($game_id, $target_game_id, $user);
	}

	/**
	 *
	 * 合并单个服务器
	 *
	 * @param string $game_id			用于合并的服务器ID
	 * @param string $target_game_id	合并到的服务器ID
	 *
	 * @return NULL
	 */
	private static function __merge($game_id, $target_game_id)
	{
		// deal user table
		self::setRetainUser($game_id, $target_game_id);

		//deal all retain user
		$start_uid = 0;
		for ( $i = 0; $i < CommonDef::MAX_LOOP_NUM; $i++ )
		{
			$user_list = self::getRetainUser($target_game_id, $game_id, $start_uid, DataDef::MAX_LIMIT);

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
		
		// deal slim user table
		self::setRetainSlimUser($game_id, $target_game_id);
		
		//deal all retain slim user
		$start_uid = 0;
		for ( $i = 0; $i < CommonDef::MAX_LOOP_NUM; $i++ )
		{
			$user_list = self::getRetainSlimUserAll($target_game_id, $game_id, $start_uid, DataDef::MAX_LIMIT);
		
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
		
				self::dealSlimUser($game_id, $target_game_id, $user);
			}
		}

		// deal guild table
		self::setRetainGuild($game_id, $target_game_id);
		// deal all retain guild
		$start_guild = 0;
		for ( $i = 0; $i < CommonDef::MAX_LOOP_NUM; $i++ )
		{
			$guild_list = self::getRetainGuild($target_game_id, $game_id, $start_guild, DataDef::MAX_LIMIT);

			if ( count($guild_list) == 0 )
			{
				break;
			}

			foreach ( $guild_list as $guild )
			{
				if ( $start_guild < $guild['guild_id'] )
				{
					$start_guild = $guild['guild_id'];
				}

				self::dealGuild($game_id, $target_game_id, $guild);
			}
		}
		
	}

	/**
	 *
	 * 处理单个用户
	 *
	 * @param string $game_id
	 * @param string $target_game_id
	 * @param array $user
	 *
	 * @return NULL
	 */
	public static function dealUser($game_id, $target_game_id, $user)
	{
		$uid = $user['uid'];
		$deal_status = $user['deal'];
		if ( $deal_status == 1 )
		{
			return;
		}
		
		$new_uid = SQLModify::getNewId($game_id, 'uid', $uid);
		
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
						self::dealItem($game_id, $target_game_id, $item_id);
					}
				}

				//deal modify columns
				$row = self::dealModifyColumn( $row, $game_id, 't_user', $relative_table);

				//deal va
				$row = self::dealVA($row, $game_id, $relative_table);

				//对于需要增加game_id字段的表进行处理
				if ( in_array($relative_table, SQLTableConf::$SQLADDGAMEID) &&
					!isset($row['server_id']) )
				{
					$row['server_id'] = intval($game_id);
				}

				// export data
				SQLModify::exportData($target_game_id, $row, $relative_table, 'uid', $new_uid );

			}
		}
		//标记当前用户已经处理完毕
		UserDao::setDealUser($target_game_id, $game_id, $uid, $new_uid );
		echo "deal gameid:$game_id user:$uid done!\n";
		usleep(200000);
	}
	
	public static function dealSlimUser($game_id, $target_game_id, $user)
	{
		$uid = $user['uid'];
		$deal_status = $user['deal'];
		if ( $deal_status == 1 )
		{
			return;
		}
		
		$source = $user['source'];
		$new_uid = SQLModify::getNewId($game_id, 'uid', $uid);
	
		foreach( SQLTableConf::$SQLMODIFYTABLE['t_slim_user'] as $relative_table => $relative_data)
		{
			//check table exist, if not, continue.
			$relative_table_array = SQLModify::getTableList($game_id, $relative_table);
			if ( empty($relative_table_array) )
			{
				continue;
			}

			$data_table = $relative_table == 't_slim_user' && $source == 1 ? 't_user' : $relative_table;
			$rows = SQLModify::getRelativeData($game_id, $data_table, 'uid', $uid);
			foreach ( $rows as $row )
			{
				//deal modify columns
				$row = self::dealModifyColumn( $row, $game_id, 't_slim_user', $relative_table);
	
				//对于需要增加game_id字段的表进行处理
				if ( in_array($data_table, SQLTableConf::$SQLADDGAMEID) &&
					!isset($row['server_id']) )
				{
					$row['server_id'] = intval($game_id);
				}
				
				if ($relative_table == 't_slim_user' && $source == 1) 
				{
					$row['game_id'] = $row['server_id'];
					
					foreach ($row as $key => $value)
					{
						if (!in_array($key, array('uid','game_id','pid','uname','vip','level','gold_num'))) 
						{
							unset($row[$key]);
						}
					}
				}
	
				// export data
				SQLModify::exportData($target_game_id, $row, $relative_table, 'uid', $new_uid );
			}
		}
		//标记当前用户已经处理完毕
		UserDao::setDealSlimUser($target_game_id, $game_id, $uid, $new_uid, $source );
		echo "deal gameid:$game_id slim user:$uid done!\n";
		usleep(200000);
	}

	/**
	 *
	 * 处理单个公会的信息
	 *
	 * @param string $game_id
	 * @param string $target_game_id
	 * @param array $guild
	 *
	 * @return NULL
	 */
	private static function dealGuild($game_id, $target_game_id, $guild)
	{
		$guild_id = $guild['guild_id'];

		$deal_status = $guild['deal'];

		if ( $deal_status == 1 )
		{
			return;
		}
		
		$new_guild_id = SQLModify::getNewId($game_id, 'guild_id', $guild_id);

		foreach( SQLTableConf::$SQLMODIFYTABLE['t_guild'] as $relative_table => $relative_data)
		{
			//check table exist, if not, continue.
			$relative_table_array = SQLModify::getTableList($game_id, $relative_table);
			if ( empty($relative_table_array) )
			{
				continue;
			}
			$rows = SQLModify::getRelativeData($game_id, $relative_table, 'guild_id', $guild_id);
			foreach ( $rows as $row )
			{
				//deal modify columns
				$row = self::dealModifyColumn( $row, $game_id, 't_guild', $relative_table);

				// deal va data
				$row = self::dealVA($row, $game_id, $relative_table);

				// export data
				SQLModify::exportData($target_game_id, $row, $relative_table, 't_guild', $new_guild_id );
			}
		}
		
		self::fixGuild($target_game_id, $new_guild_id);
		
		GuildDao::setDealGuild($target_game_id, $game_id, $guild_id, $new_guild_id);
		echo "deal gameid:$game_id guild:$guild_id done!\n";
		usleep(200000);
	}
	
	public static function fixGuild($target_game_id, $guild_id)
	{
		$sql = sprintf("select uid from t_guild_member where guild_id = %d and member_type = 1", $guild_id);
		$ret = SQLModify::directSql($target_game_id, $sql);
		if(empty($ret))
		{
			fwrite(STDERR, sprintf("not found president for guild:%d\n", $guild_id) );
			exit(1);
		}
		if( count($ret) > 1 )
		{
			fwrite(STDERR, sprintf("not only one president for guild:%d\n", $guild_id) );
			exit(1);
		}
		$create_uid = $ret[0]['uid'];
		
		$sql = sprintf("select uid from t_guild_member where guild_id = %d ", $guild_id);
		$ret = SQLModify::directSql($target_game_id, $sql);
		if( empty($ret) )
		{
			fwrite(STDERR, sprintf("no member in guild:%d\n", $guild_id) );
			exit(1);
		}
		$arrUid = array();
		foreach($ret as $value)
		{
			$arrUid[] = $value['uid'];
		}

		$sql = sprintf("select sum(fight_force) as total_fight_force from t_user where uid in ( %s ) ", implode(',', $arrUid) );
		$ret = SQLModify::directSql($target_game_id, $sql);
		$fight_force = $ret[0]['total_fight_force'];
		
		$sql = sprintf("update t_guild set create_uid = %d, fight_force = %d where guild_id = %d", $create_uid, $fight_force, $guild_id);
		$ret = SQLModify::directSql($target_game_id, $sql);
	}

	/**
	 *
	 * 处理物品
	 *
	 * @param string $game_id
	 * @param string $target_game_id
	 * @param int $item_id
	 *
	 * @return NULL
	 */
	public static function dealItem($game_id, $target_game_id, $item_id)
	{
		$relative_table = 't_item';
		$relative_id = 'item_id';
		$rows = SQLModify::getRelativeData($game_id, $relative_table, $relative_id, $item_id);
		
		if ( !empty($rows) )
		{
			$row = $rows[0];

			$new_item_id = SQLModify::getNewId($game_id, 'item_id', $item_id);
			//deal va
			$row_modify = self::dealVA($row, $game_id, SQLTableConf::$SQLMODIFYID[$relative_id]);

			//recursion deal item
			if ( isset(SQLTableConf::$SQLMODIFYITEM[$relative_table]) )
			{
				$va_call_back = SQLTableConf::$SQLMODIFYITEM[$relative_table];
				$__item_ids = Items::$va_call_back($row);
				foreach ( $__item_ids as $__item_id )
				{
					self::dealItem($game_id, $target_game_id, $__item_id);
				}
			}

			// export data
			$row_modify[$relative_id] = $new_item_id;
			SQLModify::exportData($target_game_id, $row_modify, $relative_table, $relative_id,
				$row_modify[$relative_id] );
		}
	}

	/**
	 *
	 * 处理VA字段
	 *
	 * @param array $row
	 * @param string $game_id
	 * @param string $relative_table
	 *
	 * @param array
	 */
	public static function dealVA($row, $game_id, $relative_table)
	{
		// deal va data
		if ( isset(SQLTableConf::$SQLMODIFYVA[$relative_table]) )
		{
			foreach ( SQLTableConf::$SQLMODIFYVA[$relative_table] as $va_column => $va_info )
			{
				$va_call_back = $va_info['callback'];
				$row[$va_column] = VACallback::$va_call_back($row[$va_column], $game_id);
				
				if( isset($va_info['fieldInfo']) )
				{
					$fieldInfo = $va_info['fieldInfo'];
					foreach($row[$va_column] as $k => $v)
					{
						//关于类型的检查，因为可能有int/string/bool混乱的问题，所以只考虑是否是array
						if(!isset($fieldInfo[$k]) 
						|| ( is_array($fieldInfo[$k]) && gettype($v) != gettype($fieldInfo[$k]) ) )
						{
							$msg = sprintf('something may be wrong. va:%s, field:%s, data:%s', 
										$va_column, $k, var_export($row[$va_column],true));
							throw new Exception($msg);
						}
					}
				}
			}
		}
		return $row;
	}


	private static $arrChargeUid = null;

	/**
	 *
	 * 得到所有需要保留的User ID(将所有的需要保留的数据插入的一个新的表)
	 *
	 * @param int $game_id				需要处理的服务器ID
	 * @param int $target_game_id		合并后的服务器ID
	 *
	 * @return NULL
	 */
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

		//得到此服务器已经冲过值的用户列表
		$arrChargeUid = UserDao::getChargeUid($game_id);

		for ( $i = 0; $i < CommonDef::MAX_LOOP_NUM; $i++ )
		{
			$users = UserDao::getUser($game_id, $start_uid, DataDef::MAX_LIMIT);
			if ( count($users) == 0 )
			{
				break;
			}
			foreach ( $users as $user )
			{
				$retain = false;
				//$hero = UserDao::getHero($game_id, $user['master_hid']);
				// 如果主角等级大约50级
				if ( (isset($user['server_id']) && $user['level'] > 90) || (!isset($user['server_id']) && $user['level'] > 55) )
				{
					$retain = true;
				}
				// 保留充值用户，这里只处理非精简的充值用户
				else if ( in_array($user['uid'], $arrChargeUid) && !self::isSlimUser($user) )
				{
					$retain = true;
				}
				// 如果上次登录时间距离合服当天小于30天
				else if (((time() - $user['last_login_time']) / 86400) < 30)
				{
					$retain = true;
				}
				// 如果此用户是某个公会的会长
				else if (CheckPresident::isPresident($game_id, $user['uid']))
				{
					$retain = true;
				}

				if ( $retain )
				{
					UserDao::setRetainUser($target_game_id, $game_id, $user['uid'], $user['pid'], $user['uname']);
				}
			}
			if ( $start_uid < $user['uid'] )
			{
				$start_uid = $user['uid'];
			}
		}
	}
	
	/**
	 * 保留精简的用户信息的条件
	 * 
	 * 1  二次合服或者二次以上合服
	 * 2 上次登录在1年前
	 * 3 等级小于等于90
	 * 4 vip小于3
	 */
	private static function isSlimUser($user)
	{
		if (isset($user['server_id'])
			&& ((time() - $user['last_login_time']) / 86400) >= 365
			&& $user['level'] <= 90
			&& $user['vip'] <= 3)
		{
			return true;
		}
		
		return false;
	}
	
	private static function setRetainSlimUser($game_id, $target_game_id)
	{
		// 这次合服的正常玩家中需要精简的用户
		self::setRetainSlimUserFromNew($game_id, $target_game_id);
		// 以前合服继承的需要精简的用户
		self::setRetainSlimUserFromOld($game_id, $target_game_id);
	}
	
	private static function setRetainSlimUserFromNew($game_id, $target_game_id)
	{
		$start_uid = 0;
		for ( $i = 0; $i < CommonDef::MAX_LOOP_NUM; $i++ )
		{
			$users = UserDao::getRetainSlimUser($target_game_id, $game_id, $start_uid, DataDef::MAX_LIMIT, 1);
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
	
		//得到此服务器已经冲过值的用户列表
		$arrChargeUid = UserDao::getChargeUid($game_id);
	
		for ( $i = 0; $i < CommonDef::MAX_LOOP_NUM; $i++ )
		{
			$users = UserDao::getUser($game_id, $start_uid, DataDef::MAX_LIMIT);
			if ( count($users) == 0 )
			{
				break;
			}
			foreach ( $users as $user )
			{
				$retain = false;
				
				if (in_array($user['uid'], $arrChargeUid)
					&& self::isSlimUser($user)
					&& !CheckPresident::isPresident($game_id, $user['uid']))
				{
					$retain = true;
				}
	
				if ( $retain )
				{
					UserDao::setRetainSlimUser($target_game_id, $game_id, $user['uid'], $user['pid'], $user['uname'], 1);
				}
			}
			if ( $start_uid < $user['uid'] )
			{
				$start_uid = $user['uid'];
			}
		}
	}
	
	private static function setRetainSlimUserFromOld($game_id, $target_game_id)
	{
		$start_uid = 0;
		for ( $i = 0; $i < CommonDef::MAX_LOOP_NUM; $i++ )
		{
			$users = UserDao::getRetainSlimUser($target_game_id, $game_id, $start_uid, DataDef::MAX_LIMIT, 2);
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
	
		$arrSlimUid = UserDao::getSlimUid($game_id);	
		for ( $i = 0; $i < CommonDef::MAX_LOOP_NUM; $i++ )
		{
			$users = UserDao::getSlimUser($game_id, $start_uid, DataDef::MAX_LIMIT);
			if ( count($users) == 0 )
			{
				break;
			}
			foreach ( $users as $user )
			{
				UserDao::setRetainSlimUser($target_game_id, $game_id, $user['uid'], $user['pid'], $user['uname'], 2);
			}
			if ( $start_uid < $user['uid'] )
			{
				$start_uid = $user['uid'];
			}
		}
	}
	
	

	/**
	 *
	 * 得到保留的用户uid,从start开始读取limit个
	 * @param int $start
	 * @param int $limit
	 *
	 * @return array
	 */
	public static function getRetainUser($target_game_id, $game_id, $start_uid, $limit)
	{
		$users = UserDao::getRetainUser($target_game_id, $game_id, $start_uid, $limit);
		return $users;
	}
	
	public static function getRetainSlimUserAll($target_game_id, $game_id, $start_uid, $limit)
	{
		$users = UserDao::getRetainSlimUserAll($target_game_id, $game_id, $start_uid, $limit);
		return $users;
	}

	/**
	 *
	 * 得到所有需要保留的Guild_id(将所有的需要保留的数据插入的一个新的表)
	 *
	 * @param string $game_id
	 * @param string $target_game_id
	 *
	 * @return NULL
	 */
	private static function setRetainGuild($game_id, $target_game_id)
	{
		$start_guild = 0;
		for ( $i = 0; $i < CommonDef::MAX_LOOP_NUM; $i++ )
		{
			$guilds = GuildDao::getRetainGuild($target_game_id, $game_id, $start_guild, DataDef::MAX_LIMIT);
			if ( count($guilds) == 0 )
			{
				break;
			}
			foreach ( $guilds as $guild )
			{
				if ( $start_guild < $guild['guild_id'] )
				{
					$start_guild = $guild['guild_id'];
				}
			}
		}

		for ( $i = 0; $i < CommonDef::MAX_LOOP_NUM; $i++ )
		{
			$guilds = GuildDao::getGuild($game_id, $start_guild, DataDef::MAX_LIMIT);
			if ( count($guilds) == 0 )
			{
				break;
			}
			foreach ( $guilds as $guild )
			{
				if ( GuildDao::getGuildMemberNum($target_game_id, SQLModify::getNewId($game_id, 'guild_id', $guild['guild_id']) ) > 0 )
				{
					GuildDao::setRetainGuild($target_game_id, $game_id, $guild['guild_id'], $guild['guild_name']);
				}
			}
			if ( $start_guild < $guild['guild_id'] )
			{
				$start_guild = $guild['guild_id'];
			}
		}
	}

	/**
	 *
	 * 得到保留的用户guild_id,从start开始读取limit个
	 * @param int $start
	 * @param int $limit
	 *
	 * @return array
	 */
	private static function getRetainGuild($target_game_id, $game_id, $start_guild, $limit)
	{
		$guilds = GuildDao::getRetainGuild($target_game_id, $game_id, $start_guild, $limit);
		return $guilds;
	}

	/**
	 *
	 * 处理需要修正字段的列
	 *
	 * @param array $row
	 * @param string $game_id
	 * @param string $relative_table
	 *
	 * @return array
	 */
	public static function dealModifyColumn($row, $game_id, $main_table, $table)
	{
		$modify_columns = array();
	 	if ( !empty(SQLTableConf::$SQLMODIFYTABLE[$main_table][$table]) )
        {
        	$modify_columns = SQLTableConf::$SQLMODIFYTABLE[$main_table][$table];
        }
			
		foreach ( $row as $key => $value )
		{
			if ( isset($modify_columns[$key]) )
			{
				//如果某个ID是0,则不需要处理
				if ( !empty($value) )
				{
					$row[$key] = SQLModify::getNewId($game_id, $modify_columns[$key], $value) ;
				}
			}
		}
		
		if ( isset(SQLTableConf::$SQLMODIFYNAME[$table]) )
		{
			$key = SQLTableConf::$SQLMODIFYNAME[$table];
			
			// 如果没有合过服的，是第一次合服，还走以前的逻辑，直接往名字后面加后缀
			if ( strpos($game_id, '_') === FALSE )
			{
				$row[$key] = $row[$key] . Util::getSuffixName($game_id);
			}
			else // 如果是二次合服，或者多次合服，需要找到最原始的game_id作为后缀，如果后缀发生了变化或者修改过，需要重新加上，防止冲突
			{
				$orginGameId = 0;
				if ($table === 't_user') 
				{
					$orginGameId = $row['server_id'];
				}
				else
				{
					$belongUidColumn = SQLTableConf::$SQLMODIFYNAMEBELONGUID[$table];
					$belongUid = $row[$belongUidColumn];
					$orginGameId = UserDao::getOrginGameIdByUid($game_id, $belongUid);
				}
				
				if (!empty($orginGameId)) 
				{
					$suffixName = Util::getSuffixName($orginGameId);
					$row[$key] = $row[$key] . $suffixName;
					/*if (strlen($row[$key]) < strlen($suffixName) 
						|| substr($row[$key], strlen($row[$key]) - strlen($suffixName)) !== $suffixName) 
					{
						$row[$key] = $row[$key] . $suffixName;
					}*/
				}				
			}
		}
		
		return $row;
	}
	/*
	private static function dealModifyColumn($row, $modify_columns)
	{
		foreach ( $row as $key => $value )
		{
			if ( isset($modify_columns[$key]) )
			{
				if ( is_string($modify_columns[$key]) )
				{
					$row[$key] = $row[$key] . $modify_columns[$key];
				}
				else
				{
					//如果某个ID是0,则不需要处理
					if ( !empty($value) )
					{
						$row[$key] += $modify_columns[$key];
					}
				}
			}
		}
		return $row;
	}
	*/
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */