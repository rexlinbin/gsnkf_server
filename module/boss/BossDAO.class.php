<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: BossDAO.class.php 160128 2015-03-05 03:49:55Z ShiyuZhang $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/boss/BossDAO.class.php $
 * @author $Author: ShiyuZhang $(jhd@babeltime.com)
 * @date $Date: 2015-03-05 03:49:55 +0000 (Thu, 05 Mar 2015) $
 * @version $Revision: 160128 $
 * @brief
 *
 **/

class BossDAO
{
	public static function getBoss($boss_id, $no_throw_exception = FALSE)
	{
		$select = array (
			BossDef::BOSS_ID,
			BossDef::BOSS_HP,
			BossDef::BOSS_LEVEL,
			BossDef::START_TIME,
			BossDef::SUPERHERO_REFRESH_TIME,
			BossDef::BOSS_VA,
		);

		$wheres = array (
			array(BossDef::BOSS_ID, '=', $boss_id)
		);

		$return = self::selectBoss($select, $wheres);
		if ( empty($return) )
		{
			if ( $no_throw_exception == TRUE )
			{
				return array();
			}
			else
			{
				//TODO 自动初始化？
				throw new InterException( 'boss table need init!' );
			}
		}
		else
		{
			return $return[0];
		}
	}

	public static function setBoss($boss_id, $hp, $level, $start_time)
	{
		$values = array (
			BossDef::BOSS_HP			=> intval($hp),
			BossDef::BOSS_LEVEL		=>	intval($level),
			BossDef::START_TIME	=>	intval($start_time),
		);

		$wheres = array (
			array (BossDef::BOSS_ID, '=', $boss_id),
		);

		$return = self::updateBoss($values, $wheres);

		if ( $return[DataDef::AFFECTED_ROWS] != 1 )
		{
			throw new InterException( 'update boss affected rows != 1' );
		}
	}
	
	public static function setVaBoss( $bossId, $va )
	{
		//TODO 多人同时修改 要锁吗
		$values = array (
				BossDef::SUPERHERO_REFRESH_TIME	=>	Util::getTime(),
				BossDef::BOSS_VA			=>	$va,
		);
		
		$wheres = array (
				array (BossDef::BOSS_ID, '=', $bossId),
		);
		
		$return = self::updateBoss($values, $wheres);
		
		if ( $return[DataDef::AFFECTED_ROWS] != 1 )
		{
			throw new InterException( 'update boss affected rows != 1' );
		}
	}

	public static function initBoss($boss_id, $hp, $level, $start_time)
	{
		$values = array (
			BossDef::BOSS_ID				=> intval($boss_id),
			BossDef::BOSS_HP				=> intval($hp),
			BossDef::BOSS_LEVEL				=>	intval($level),
			BossDef::START_TIME				=>	intval($start_time),
			BossDef::SUPERHERO_REFRESH_TIME	=> intval( Util::getTime() ),
			BossDef::BOSS_VA				=> array(),
		);

		self::insertBoss($values);
	}

	public static function subBossHP($boss_id, $hp)
	{
		$hp = intval($hp);

		$values = array (
			BossDef::BOSS_HP			=> new DecOperator($hp),
		);

		$wheres = array (
			array (BossDef::BOSS_ID, '=', $boss_id),
			array (BossDef::BOSS_HP, '>', $hp),
		);

		$return = self::updateBoss($values, $wheres);

		return $return[DataDef::AFFECTED_ROWS];
	}

	public static function setBossHP($boss_id, $hp)
	{
		$values = array (
			BossDef::BOSS_HP			=> intval($hp),
		);

		$wheres = array (
			array (BossDef::BOSS_ID, '=', $boss_id),
		);

		$return = self::updateBoss($values, $wheres);

		if ( $return[DataDef::AFFECTED_ROWS] != 1 )
		{
			throw new InterException( 'update boss affected rows != 1' );
		}
	}

	public static function getAtkerRank( $atkHp, $bossId, $startTime, $endTime )
	{
		$data = new CData();
		$ret = $data->selectCount()->from( 't_boss_atk' )
		->where(array( BossDef::ATK_HP,'>', $atkHp ))
		->where(array( BossDef::LAST_ATK_TIME, 'BETWEEN', array( $startTime, $endTime)) )
		->query();
		
		return intval( $ret[0]['count'] )+1;
	}
	
	public static function getBossAttack($boss_id, $uid)
	{
		$select = array (
			BossDef::BOSS_ID,
			BossDef::ATK_UID,
			BossDef::ATK_UID,
			BossDef::LAST_ATK_TIME,
			BossDef::ATK_HP,
			BossDef::ATK_NUM,
			BossDef::LAST_INSPIRE_TIME,
			BossDef::LAST_INSPIRE_TIME_GOLD,
			BossDef::REVIVE,
			BossDef::INSPIRE,
			BossDef::FLAGS,
			BossDef::FORMATION_SWITCH,
			BossDef::VA_BOSS_ATK,
				
		);

		$wheres = array (
			array (BossDef::BOSS_ID, '=', $boss_id),
			array (BossDef::ATK_UID, '=', $uid),
		);

		$return = self::selectBossAttack($select, $wheres);

		if ( empty($return) )
		{
			return $return;
		}
		else
		{
			return $return[0];
		}
	}


	public static function getBossAttackList($boss_id, $boss_start_time, $boss_end_time)
	{
		$select = array (
			BossDef::ATK_UID,
			BossDef::ATK_HP
		);

		$wheres = array (
			array (BossDef::BOSS_ID, '=', $boss_id),
			array (BossDef::ATK_HP, '>', 0),
			array (BossDef::LAST_ATK_TIME, 'BETWEEN',
				 array($boss_start_time, $boss_end_time)),
		);
		$return = self::selectBossAttack($select, $wheres);

		return $return;
	}


	public static function getBossAttackHpTop($boss_id,
		$boss_start_time, $boss_end_time, $topN)
	{
		$select = array (
			BossDef::ATK_UID,
			BossDef::ATK_HP
		);

		$wheres = array (
			array (BossDef::BOSS_ID, '=', $boss_id),
			array (BossDef::ATK_HP, '>', 0),
			array (BossDef::LAST_ATK_TIME, 'BETWEEN',
				 array($boss_start_time, $boss_end_time)),
		);

		$data = new CData();
		$data->select($select)->from(BossDef::ATK_TABLE);
		foreach ( $wheres as $where )
			$data->where($where);
		$data->orderBy(BossDef::ATK_HP, FALSE);
		$data->limit(0, $topN);
		$return = $data->query();

		return $return;
	}

	public static function updateBossAtk( $bossId, $uid, $arrValue )
	{
		if ( empty( $arrValue ) )
			return; 	
		
		$data = new CData();
		$return = $data->update( BossDef::ATK_TABLE )
		->set( $arrValue )
		->where( array( BossDef::BOSS_ID, '=', $bossId ) )
		->where( array( 'uid', '=', $uid ) )
		->query();
		
		if ( $return[DataDef::AFFECTED_ROWS] != 1 )
		{
			throw new InterException( 'fail to update t_boss_atk,values: %s', $arrValue );
		}
		
	}

	public static function initBossAtk( $val )
	{
		$data = new CData();
		$return = $data->insertInto(BossDef::ATK_TABLE)->values( $val )->query();
		if ( $return[DataDef::AFFECTED_ROWS] != 1 )
		{
			throw new InterException( 'insert t_boss_attack failed' );
		}
	}

	public static function selectBoss($select, $wheres)
	{
		$data = new CData();
		$data->select($select)->from(BossDef::BOSS_TABLE);
		foreach ( $wheres as $where )
			$data->where($where);
		$return = $data->query();
		return $return;
	}

	public static function updateBoss($values, $wheres)
	{
		$data = new CData();
		$data->update(BossDef::BOSS_TABLE)->set($values);
		foreach ( $wheres as $where )
			$data->where($where);
		$return = $data->query();
		return $return;
	}

	public static function insertBoss($values)
	{
		$data = new CData();
		$return = $data->insertInto(BossDef::BOSS_TABLE)->values($values)->query();
		if ( $return[DataDef::AFFECTED_ROWS] != 1 )
		{
			Logger::FATAL('insert t_boss failed');
			throw new Exception('fake');
		}
		return $return;
	}

	public static function selectBossAttack($select, $wheres)
	{
		$data = new CData();
		$data->select($select)->from(BossDef::ATK_TABLE);
		foreach ( $wheres as $where )
			$data->where($where);
		$return = $data->query();
		return $return;
	}
	
	public static function getBossBotList($boss_id, $boss_start_time, $boss_end_time)
	{
		$select = array (
				BossDef::ATK_UID,
				BossDef::FLAGS,
		);
	
		$wheres = array (
				array (BossDef::BOSS_ID, '=', $boss_id),
				array (BossDef::LAST_ATK_TIME, '=', $boss_start_time),
		);
		$return = self::selectBossAttack($select, $wheres);
	
		return $return;
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */