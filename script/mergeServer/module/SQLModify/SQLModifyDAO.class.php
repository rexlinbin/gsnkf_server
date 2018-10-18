<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: SQLModifyDAO.class.php 28054 2012-09-23 09:05:00Z HaidongJia $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/trunk/pirate/rpcfw/script/mergeServer/module/SQLModify/SQLModifyDAO.class.php $
 * @author $Author: HaidongJia $(jhd@babeltime.com)
 * @date $Date: 2012-09-23 17:05:00 +0800 (星期日, 23 九月 2012) $
 * @version $Revision: 28054 $
 * @brief
 *
 **/

class SQLModifyDAO
{
	/**
	 *
	 * 得到某个表的所有列
	 *
	 * @param string $game_id
	 * @param string $table
	 *
	 * @return array
	 */
	public static function getTableColumns($game_id, $table)
	{
		$table = trim($table);
		$tables = self::getTablesList($game_id, $table);
		$mysql = MysqlManager::getMysql($game_id);
		$query = $mysql->query("desc " . $tables[0] .";");
		$result = array();
		foreach ( $query as $value )
		{
			$result[] = $value['Field'];
		}
		return $result;
	}

	/**
	 *
	 * 得到相关的数据
	 * @param string $game_id
	 * @param string $table
	 * @param string $relative_column		相关列
	 * @param string $value					相关值
	 */
	public static function getRelativeData($game_id, $table, $relative_column, $value)
	{
		$mysql = MysqlManager::getMysql($game_id);
		$table = partitionTable::getTableName($table, $relative_column, $value);
		$return = $mysql->query("select * from $table where $relative_column = '$value'");
		return $return;
	}

	/**
	 *
	 * 得到某个分表的列表
	 * @param string $table
	 */
	public static function getTablesList($game_id, $table)
	{
		$mysql = MysqlManager::getMysql($game_id);
		$query = $mysql->query("show tables like '$table%'");
		$result = array();
		foreach ( $query as $data )
		{
			foreach ( $data as $key => $value )
			{
				if ( strlen($value) > strlen($table)+1 &&
					 $value[strlen($table)+1] >= chr(48) &&
					 $value[strlen($table)+1] <= chr(57) )
				{
					$result[] = $value;
				}

				if ( $value == $table )
				{
					$result[] = $value;
				}
			}
		}
		return $result;
	}

	/**
	 *
	 * 得到最大的ID
	 *
	 * @param string $table
	 * @param string $idname
	 *
	 * @return int
	 */
	public static function getMaxId($game_id, $table, $idname)
	{
		$mysql = MysqlManager::getMysql($game_id);
		$query = $mysql->query("select max($idname) as max_id from $table");
		if ( empty($query) )
		{
			throw new Exception('query max id err in table' . $table);
		}
		else
		{
			return $query[0]['max_id'];
		}
	}

	/**
	 *
	 * 得到最小的ID
	 *
	 * @param string $table
	 * @param string $idname
	 *
	 * @return int
	 */
	public static function getMinId($game_id, $table, $idname)
	{
		$mysql = MysqlManager::getMysql($game_id);
		$query = $mysql->query("select min($idname) as min_id from $table");
		if ( empty($query) )
		{
			throw new Exception('query min id err in table' . $table);
		}
		else
		{
			return $query[0]['min_id'];
		}
	}
	
	/**
	 * 执行sql
	 *
	 * @param string $game_id
	 * @param string $sql
	 */
	public static function directSql($game_id, $sql)
	{
		$mysql = MysqlManager::getMysql($game_id);
		return $mysql->query($sql);
	}

	/**
	 *
	 * 导出数据
	 *
	 * @param string $game_id
	 * @param string $sql
	 */
	public static function exportData($game_id, $sql)
	{
		$mysql = MysqlManager::getMysql($game_id);
		$query = $mysql->query($sql);
	}
	
	/**
	 *
	 * 检查表是否存在
	 *
	 * @param int $game_id
	 * @param string $table
	 *
	 * @return boolean
	 */
	public static function checkExistTable($game_id, $table)
	{
		$mysql = MysqlManager::getMysql($game_id);
		$query = $mysql->query("show tables like '$table%'");
		return count($query) > 0;
	}
	
	/**
	 *
	 * 创建表
	 *
	 * @param int $game_id
	 * @param string $table
	 * @param string $table_like
	 */
	public static function createTable($game_id, $table, $table_like)
	{
		$mysql = MysqlManager::getMysql($game_id);
		$query = $mysql->query("create table if not exists $table like $table_like");
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */