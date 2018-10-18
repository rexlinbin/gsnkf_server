<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: SQLModify.class.php 112678 2014-06-05 10:48:36Z HaidongJia $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/trunk/pirate/rpcfw/script/mergeServer/module/SQLModify/SQLModify.class.php $
 * @author $Author: HaidongJia $(jhd@babeltime.com)
 * @date $Date: 2014-06-05 18:48:36 +0800 (星期四, 05 六月 2014) $
 * @version $Revision: 112678 $
 * @brief
 *
 **/

class SQLModify
{
	/**
	 *
	 * 缓冲TableColumn
	 *
	 * @var array
	 */
	private static $TableColumns = array();

	/**
	 *
	 * 缓冲TableList
	 * @var array
	 */
	private static $TableList = array();

	/**
	 *
	 * 缓冲最大的Id
	 * @var array
	 */
	private static $MaxId = array();

	/**
	 *
	 * 缓冲最小的Id
	 * @var array
	 */
	private static $MinId = array();
	
	/**
	 * 缓冲是否存在表
	 */
	private static $TableExists = array();
	
	/**
	 * 缓冲id转换
	 *
	 * @var array
	 */
	private static $Id2NId = array();
	
	/**
	 * 清理id cache
	 *
	 * @param string $game_id
	 * @param string $id_name
	 *
	 * @return NULL
	 */
	public static function clearIdCache($game_id)
	{
		self::$Id2NId[$game_id] = 'cleared';
	}
	
	/**
	 * 获取某个id在合服后的id
	 * @param string $game_id
	 * @param string $id_name
	 * @param string $table
	 * @return int
	 */
	public static function getNewId($game_id, $id_name, $old_id_value)
	{
		$table = SQLTableConf::$SQLMODIFYID[$id_name];
		
		if ( isset( SQLTableConf::$SQLMODIFY_ID_REARRANGE[$id_name] ) )
		{
			//如果曾经清除过id映射，就不应该再次获取
			if ( isset( self::$Id2NId[$game_id] ) && !is_array(self::$Id2NId[$game_id]) )
			{
				$msg = sprintf('already clear id cache of game:%s, cant get id. data:%s', $game_id, self::$Id2NId[$game_id]);
				throw new Exception($msg);
			}
			
			if ( !empty(self::$Id2NId[$game_id][$id_name][$old_id_value]) )
			{
				return self::$Id2NId[$game_id][$id_name][$old_id_value];
			}
			else
			{
				$new_id = IdGenerator::nextId($id_name, $old_id_value, $game_id);
				self::$Id2NId[$game_id][$id_name][$old_id_value] = $new_id;
				return $new_id;
			}
		}
		else
		{
			return $old_id_value + self::getIdOffset($game_id, $id_name, $table);
		}
	}

	/**
	 *
	 * 得到ID的偏移量
	 *
	 * @param string $id
	 */
	public static function getIdOffset($game_id, $id, $table)
	{
		$merge_server_ids = MergeGlobal::getMergeServerIDs();
		$key = array_search($game_id, $merge_server_ids);
		if ( $key == 0 )
		{
			return 0;
		}
		else
		{
			//此处原本偏移是+1，后因hid无法计算准确的最小值（可能比实际大4），就简单的将此处改为+10
			return self::getIdOffset($merge_server_ids[$key-1], $id, $table) + 10 
				+ self::getMaxId($merge_server_ids[$key-1], $id, $table)
				- self::getMinId($game_id, $id, $table);
		}
	}

	/**
	 *
	 * 得到最大的ID
	 *
	 * @param int $id
	 *
	 * @return int
	 */
	public static function getMaxId($game_id, $id, $table)
	{
		if ( isset(self::$MaxId[$game_id.$id.$table]) )
		{
			return self::$MaxId[$game_id.$id.$table];
		}
		$table_list = SQLModifyDAO::getTablesList($game_id, $table);
		if ($id == 'uid') 
		{
			$table_list[] = 't_slim_user';
		}
		$max_id = 0;
		foreach ( $table_list as $sub_table )
		{
			$table_max_id = SQLModifyDAO::getMaxId($game_id, $sub_table, $id);
			if ( !empty($table_max_id) && $table_max_id > $max_id )
			{
				$max_id = $table_max_id;
			}
		}
		self::$MaxId[$game_id.$id.$table] = $max_id;
		return $max_id;
	}

	/**
	 *
	 * 得到最小的ID
	 *
	 * @param int $id
	 *
	 * @return int
	 */
	public static function getMinId($game_id, $id, $table)
	{
		if ( isset(self::$MinId[$game_id.$id.$table]) )
		{
			return self::$MinId[$game_id.$id.$table];
		}
		$table_list = SQLModifyDAO::getTablesList($game_id, $table);
		if ($id == 'uid')
		{
			$table_list[] = 't_slim_user';
		}
		$min_id = 2000000000;
		foreach ( $table_list as $sub_table )
		{
			$table_min_id = SQLModifyDAO::getMinId($game_id, $sub_table, $id);
			if ( !empty($table_min_id) && $table_min_id < $min_id )
			{
				$min_id = $table_min_id;
			}
		}
		if ( $min_id == 2000000000 )
		{
			$min_id = 0;
		}
		self::$MinId[$game_id.$id.$table] = $min_id;
		return $min_id;
	}

	/**
	 *
	 * 得到某个表的相关分表
	 *
	 * @param string $game_id
	 * @param string $table
	 *
	 * @return array
	 */
	public static function getTableList($game_id, $table)
	{
		if ( isset(self::$TableList[$game_id.$table]) )
		{
			return self::$TableList[$game_id.$table];
		}
		$table_list = SQLModifyDAO::getTablesList($game_id, $table);
		self::$TableList[$game_id.$table] = $table_list;
		return self::$TableList[$game_id.$table];
	}

	/**
	 *
	 * 得到某个表的所有column
	 *
	 * @param string $table
	 *
	 * @return array
	 */
	public static function getTableColumns($game_id, $table)
	{
		if ( isset(self::$TableColumns[$game_id.$table]) )
		{
			return self::$TableColumns[$game_id.$table];
		}
		else
		{
			$result = SQLModifyDAO::getTableColumns($game_id, $table);
			self::$TableColumns[$game_id.$table] = $result;
			return $result;
		}
	}

	/**
	 *
	 * 得到表需要更新的字段
	 *
	 * @param string $table
	 *
	 * @return array
	 */
	/*
	public static function getTableColumnModify($game_id, $main_table, $table)
	{
		$return = array();
		if ( !empty(SQLTableConf::$SQLMODIFYTABLE[$main_table][$table]) )
		{
			foreach ( SQLTableConf::$SQLMODIFYTABLE[$main_table][$table] as $column => $id )
			{
				//if ( strrpos(strtolower($column), $id) !== FALSE )
				
				$return[$column] = self::getIdOffset($game_id, $id, SQLTableConf::$SQLMODIFYID[$id]);
				
			}
		}

		if ( isset(SQLTableConf::$SQLMODIFYNAME[$table]) )
		{
			$return[SQLTableConf::$SQLMODIFYNAME[$table]] = Util::getSuffixName($game_id);
		}
		return $return;
	}
	*/

	/**
	 *
	 * 得到相关联的数据
	 *
	 * @param string $table
	 * @param string $relative_column
	 * @param mixed $value
	 *
	 * @return array
	 */
	public static function getRelativeData($game_id, $table, $relative_column, $value)
	{
		$result = SQLModifyDAO::getRelativeData($game_id, $table, $relative_column, $value);
		return $result;
	}
	
	
	/**
	 * 执行sql。没有处理分表问题
	 *
	 * @param string $game_id
	 * @param string $sql
	 */
	public static function directSql($game_id, $sql)
	{
		return SQLModifyDAO::directSql($game_id, $sql);
	}
	

	/**
	 *
	 * 导出数据
	 *
	 * @param array $row
	 * @param string $table
	 */
	public static function exportData($game_id, $rows, $table, $key, $value)
	{
		$last_table = $table;
		$table = partitionTable::getTableName($table, $key, $value);
		if ( $table != $last_table )
		{
			$table_id = intval(substr($table, strlen($last_table)+1));
			if ( !isset(self::$TableExists[$game_id][$last_table . '_' . $table_id]) )
			{
				self::createTable($game_id, $last_table, $table_id);
			}
		}

		$columns = "";
		$column_values = "";
		foreach ( $rows as $row => $row_value )
		{
			if ( !empty($columns) )
			{
				$columns .= ", ";
			}
			$columns .= "`$row`";

			if ( !empty($column_values) )
			{
				$column_values .= ", ";
			}

			//deal va
			if ( strpos($row, "va_") === 0 )
			{
				$row_value = Util::AMFEncode($row_value);
				$column_values .= "UNHEX(\"" . bin2hex($row_value) . "\")";
			}
			else
			{
				if ( is_string($row_value) )
				{
					$column_values .= "UNHEX(\"" . bin2hex($row_value) . "\")";
				}
				else
				{
					$column_values .= "\"$row_value\"";
				}
			}
		}

		$sql = "INSERT IGNORE INTO $table ($columns) values($column_values)";
		SQLModifyDAO::exportData($game_id, $sql);
	}
	
	/**
	 * 创建表
	 *
	 * @param int $game_id
	 * @param string $table
	 * @param string $table_id
	 *
	 * @import 目前只支持分表模式为div和mod，如果增加其他需要额外的处理
	 *
	 */
	public static function createTable($game_id, $table, $table_id)
	{
		$create_table_id = $table_id;
		while ( $create_table_id >= 0 )
		{
			if ( SQLModifyDAO::checkExistTable($game_id, $table . '_' . $create_table_id) )
			{
				self::$TableExists[$game_id][$table . '_' . $create_table_id] = true;
				break;
			}
			$create_table_id--;
		}
	
		for ( $i = $create_table_id + 1; $i <= $table_id; $i++ )
		{
			SQLModifyDAO::createTable($game_id, $table . "_" . $i, $table . "_" . $create_table_id);
			self::$TableExists[$game_id][$table . "_" . $i] = true;
		}
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */