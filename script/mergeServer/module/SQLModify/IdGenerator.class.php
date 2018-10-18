<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id$
 * 
 **************************************************************************/

 /**
 * @file $HeadURL$
 * @author $Author$(wuqilin@babeltime.com)
 * @date $Date$
 * @version $Revision$
 * @brief 
 *  
 **/

class IdGenerator
{

	public static $ID_TABLE_CONF = array(
		'item_id' => array(
			'table' => 't_tmp_item_id',
			'partition' => array(
				'method' => 'mod',
				'value' => 10
			),
		)
	);
	
	private static $sArrIdInfo = array();
	
	public static function init($arrMergeGameId, $targetGameId)
	{
		foreach (SQLTableConf::$SQLMODIFY_ID_REARRANGE  as $idName => $idInfo )
		{
			printf("start calcuate offset for %s\n", $idName);
			if ( empty( self::$ID_TABLE_CONF[$idName] ) )
			{
				printf("not found conf for %\n", $idName);
				return false;
			}
			
			self::$sArrIdInfo = self::getIdInfo($targetGameId);
			
			$curId = $idInfo['default'];
			$step = $idInfo['step'];
			$hasInit = false;//只能从前到后依次初始化，如果出现某个服没有初始化，但是后面某个服已经初始化，说明数据错了
			foreach( $arrMergeGameId as $gameId )
			{
				if ( empty( self::$sArrIdInfo[$idName][ $gameId ] ) )
				{
					$minId = $curId;
					$maxId = 0;
					switch ($idName)
					{
						case 'item_id':
							$maxId = $curId + self::getNeedItemIdNum($gameId);
							break;
						default:
							printf("cant calcuate offset for %s\n", $idName);
							return false;
							break;
					}
					
					$sql = "insert into t_tmp_id_info values('$idName', '$gameId', $minId, $maxId)";
					SQLModifyDAO::directSql($targetGameId, $sql);
					
					printf("id offset. idName:%s, gameId:%s, min:%d, max:%d\n", $idName, $gameId, $minId, $maxId);
					
					self::$sArrIdInfo[$idName][ $gameId ] = array(
						'min_id' => $minId,
						'max_id' => $maxId,
						'cur_id' => $curId,
					);
					
					//$curId = $maxId + 1000;
					$hasInit = true;
				}
				else
				{
					if ($hasInit)
					{
						printf("item info erro. %s\n", var_export(self::$sArrIdInfo, true));
						return false;
					}
					
					printf("already done for idName:%s, game:%s, curId:%d\n", $idName, $gameId, $curId);
				}
				
				$curId = self::$sArrIdInfo[$idName][ $gameId ]['max_id'] + 1000;
			}
			
			$tableName = self::$ID_TABLE_CONF[$idName]['table'];
			$arrTableName = array();
			if ( empty( self::$ID_TABLE_CONF[$idName]['partition'] ) )
			{
				$arrTableName = array( sprintf('%s_%s', $tableName, $gameId) );
			}
			else
			{
				foreach( $arrMergeGameId as $gameId )
				{
					if ( self::$ID_TABLE_CONF[$idName]['partition']['method'] == 'mod' )
					{
						for( $i = 0; $i < self::$ID_TABLE_CONF[$idName]['partition']['value']; $i++)
						{
							$arrTableName[] = sprintf('%s_%s_%d', $tableName, $gameId, $i);
						}
					}
				}
			}
			
			foreach( $arrTableName as $tableName )
			{
				$createTableSql = "CREATE TABLE IF NOT EXISTS $tableName LIKE t_tmp_id_proto";
					
				SQLModifyDAO::directSql($targetGameId, $createTableSql);
			}
		}
		
		printf("id info:%s\n", var_export(self::$sArrIdInfo, true));
		
		return true;
	}
	
	public static function getNeedItemIdNum($gameId)
	{
		$table = SQLTableConf::$SQLMODIFYID['item_id'];
		$tableList = SQLModifyDAO::getTablesList($gameId, $table);
		$sum = 0;
		foreach ( $tableList as $subTable )
		{
			$sql = "select count(*) as num from $subTable where item_deltime = 0";
			$ret = SQLModifyDAO::directSql($gameId, $sql);
			$num = intval( $ret[0]['num'] );
			$sum += $num;
		}
		
		return $sum;
	}
	
	
	public static function getIdInfo($targetGameId)
	{

		$arrRet = SQLModifyDAO::directSql($targetGameId, "select * from t_tmp_id_info");
			
		$arrIdInfo = array();
		foreach( $arrRet as $row )
		{
			$arrIdInfo[ $row['id_name'] ][ $row['game_id'] ] =  $row;
		}
		
		foreach( $arrIdInfo as $idName => $idInfo )
		{
			foreach( $idInfo as $gameId => $idGameInfo )
			{
				$maxId = self::getMaxId($targetGameId, 'new_id', self::$ID_TABLE_CONF[$idName]['table'].'_'.$gameId);
				if($maxId <= 0 )
				{
					$maxId = $idGameInfo['min_id'];
				}
				else
				{
					$maxId += SQLTableConf::$SQLMODIFY_ID_REARRANGE[$idName]['step'];
				}
				$arrIdInfo[$idName][$gameId]['cur_id'] = $maxId;
			}
		}
		
		return $arrIdInfo;
	}
	
	public static function getMaxId($gameId, $id, $table)
	{
		$arrTable = SQLModifyDAO::getTablesList($gameId, $table);
		$maxId = 0;
		foreach ( $arrTable as $subTable )
		{
			$tableMaxId = SQLModifyDAO::getMaxId($gameId, $subTable, $id);
			$tableMaxId = intval($tableMaxId);
			if ( !empty($tableMaxId) && $tableMaxId > $maxId )
			{
				$maxId = $tableMaxId;
			}
		}

		return $maxId;
	}
	
	public static function getTableNameById($idName, $gameId, $idValue)
	{
		$tableName = self::$ID_TABLE_CONF[$idName]['table'];
		if ( empty(self::$ID_TABLE_CONF[$idName]['partition']) )
		{
			return sprintf('%s_%s', $tableName, $gameId);
		}
		
		$method = self::$ID_TABLE_CONF[$idName]['partition']['method'];
		$value = self::$ID_TABLE_CONF[$idName]['partition']['value'];
		switch( $method )
		{
			case 'mod':
				return sprintf('%s_%s_%d', $tableName, $gameId, $idValue % $value);
				break;
			case 'div':
				return sprintf('%s_%s_%d', $tableName, $gameId, intval( $idValue / $value ) );
				break;
			default:
				$msg = sprintf('invalid partition method:%s', $method);
				throw new Exception($msg);
				break;
		}
	}
	
	public static function nextId($idName, $idOld, $gameId)
	{
		if ( empty($idOld) )
		{
			$msg = sprintf("invalid id. idName:%s, gameId:%s, old:%d\n", $idName, $gameId, $idOld);
			throw new Exception($msg);
		}
		
		$targetGameId = MergeGlobal::getMergeServerTargetID();
		
		$tableName = self::getTableNameById($idName, $gameId, $idOld);
		$sql = "select new_id from $tableName where old_id = $idOld ";
		$ret = SQLModifyDAO::directSql($targetGameId, $sql);
		
		if ( empty($ret) )
		{
			$newId = self::$sArrIdInfo[$idName][$gameId]['cur_id'];
			self::$sArrIdInfo[$idName][$gameId]['cur_id'] += SQLTableConf::$SQLMODIFY_ID_REARRANGE[$idName]['step'];
			if ( $newId > self::$sArrIdInfo[$idName][$gameId]['max_id'] )
			{
				$msg = sprintf('invalid id. idName:%s, gameId:%s, idOld:%d, minId:%d, maxId:%d, curId:%d',
						$idName, $gameId, $idOld, 
						self::$sArrIdInfo[$idName][$gameId]['min_id'],
						self::$sArrIdInfo[$idName][$gameId]['max_id'], $newId);
				throw new Exception($msg);
			}
			$sql = "insert into $tableName values($idOld, $newId)";
			SQLModifyDAO::directSql($targetGameId, $sql);
			return $newId;
		}
		else
		{
			return intval($ret[0]['new_id']);
		}
	}
	
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */