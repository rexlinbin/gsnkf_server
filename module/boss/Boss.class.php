<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: Boss.class.php 160128 2015-03-05 03:49:55Z ShiyuZhang $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/boss/Boss.class.php $
 * @author $Author: ShiyuZhang $(jhd@babeltime.com)
 * @date $Date: 2015-03-05 03:49:55 +0000 (Thu, 05 Mar 2015) $
 * @version $Revision: 160128 $
 * @brief
 *
 **/

class Boss implements IBoss
{		
	public function getBossOffset()
	{
		return GameConf::BOSS_OFFSET;
	}

	public function enterBoss( $bossId ) 
	{
		if (!EnSwitch::isSwitchOpen( SwitchDef::BOSS ))
		{
			throw new FakeException( 'boss not open' );
		}
		
		$uid = RPCContext::getInstance()->getUid();
		if ( empty( $uid ) )
		{
			throw new FakeException( 'invalid uid: %d', $uid );
		}
		$ret = BossLogic::enterBoss( $uid , $bossId);
		return $ret;
	}


	public function leaveBoss()
	{
		if (!EnSwitch::isSwitchOpen( SwitchDef::BOSS ))
		{
			throw new FakeException( 'boss not open' );
		}
		
		$uid = RPCContext::getInstance()->getUid();
		if ( empty( $uid ) )
		{
			throw new FakeException( 'invalid uid: %d', $uid );
		}
		RPCContext::getInstance()->unsetSession( SPECIAL_ARENA_ID::SESSION_KEY );
	}

	public function inspireBySilver()
	{
		if (!EnSwitch::isSwitchOpen( SwitchDef::BOSS ))
		{
			throw new FakeException( 'boss not open' );
		}
		
		$uid = RPCContext::getInstance()->getUid();
		if ( empty( $uid ) )
		{
			throw new FakeException( 'invalid uid: %d', $uid );
		}
		return BossLogic::inspireBySilver( $uid );
	}

	public function inspireByGold() 
	{
		if (!EnSwitch::isSwitchOpen( SwitchDef::BOSS ))
		{
			throw new FakeException( 'boss not open' );
		}
		
		$uid = RPCContext::getInstance()->getUid();
		if ( empty( $uid ) )
		{
			throw new FakeException( 'invalid uid: %d', $uid );
		}
		return BossLogic::inspireByGold( $uid );
	}
	
	public function revive()
	{
		if (!EnSwitch::isSwitchOpen( SwitchDef::BOSS ))
		{
			throw new FakeException( 'boss not open' );
		}
		
		$uid = RPCContext::getInstance()->getUid();
		if ( empty( $uid ) )
		{
			throw new FakeException( 'invalid uid: %d', $uid );
		}
		return BossLogic::revive( $uid );
	}
	
	public function attack()
	{
		if (!EnSwitch::isSwitchOpen( SwitchDef::BOSS ))
		{
			throw new FakeException( 'boss not open' );
		}
		
		$uid = RPCContext::getInstance()->getUid();
		if ( empty( $uid ) )
		{
			throw new FakeException( 'invalid uid: %d', $uid );
		}
		$atkRet = BossLogic::attack( $uid );
		
		EnActive::addTask( ActiveDef::BOSS );
		EnWeal::addKaPoints( KaDef::BOSS );
		
		return $atkRet;
	}
	
	public function over()
	{
		if (!EnSwitch::isSwitchOpen( SwitchDef::BOSS ))
		{
			throw new FakeException( 'boss not open' );
		}
		
		$uid = RPCContext::getInstance()->getUid();
		if ( empty( $uid ) )
		{
			throw new FakeException( 'invalid uid: %d', $uid );
		}
		return BossLogic::over( $uid );
	}
	
	public function getMyRank()
	{
		if (!EnSwitch::isSwitchOpen( SwitchDef::BOSS ))
		{
			throw new FakeException( 'boss not open' );
		}
		
		$uid = RPCContext::getInstance()->getUid();
		if ( empty( $uid ) )
		{
			throw new FakeException( 'invalid uid: %d', $uid );
		}
		
		return BossLogic::getMyRank($uid);
	}

	public function getSuperHero( $bossId )
	{
		if (!EnSwitch::isSwitchOpen( SwitchDef::BOSS ))
		{
			throw new FakeException( 'boss not open' );
		}
		
		return BossLogic::getSuperHero($bossId);
	}
	
	public function getAtkerRank($bossId) 
	{
		if (!EnSwitch::isSwitchOpen( SwitchDef::BOSS ))
		{
			throw new FakeException( 'boss not open' );
		}
		
		$ret = BossLogic::getAtkerRank($bossId);
		
		return $ret;
	}
	
	public function bossComing($boss_id)
	{
		//发公告
		ChatTemplate::sendBossComing($boss_id);

		$time = BossUtil::getBossStartTime($boss_id);
		TimerTask::addTask(2, $time, 'boss.bossStart', array($boss_id));

	}
	
	public function bossStart( $bossId )
	{
		//发公告
		ChatTemplate::sendBossStart( $bossId );
	
		$startTime = BossUtil::getBossStartTime( $bossId );
		$endTime = BossUtil::getBossEndTime( $bossId );
		TimerTask::addTask(2, $endTime, 'boss.rewardForTimer', array( $bossId, $startTime, $endTime));
		TimerTask::addTask(2, $endTime + BossConf::BOSS_END_TIME_SHIFT, 'boss.bossEnd',
		array( $bossId, $startTime, $endTime));
	}
	
	

	public function rewardForTimer( $bossId, $startTime, $endTime )
	{
		$bossInfo = BossDAO::getBoss( $bossId );
	
		//已经由于被击杀而处理完毕
		if ( $bossInfo[BossDef::BOSS_HP] == 0 )
		{
			Logger::INFO('boss has killed!boos_id:%d', $bossId);
			return;
		}
		else
		{
			$bossInfo[BossDef::BOSS_VA][BossDef::BOSS_KILLER] = array();
			BossDAO::setVaBoss($bossId, $bossInfo[BossDef::BOSS_VA]);
			Util::asyncExecute('boss.reward', array($bossId, $bossInfo[BossDef::BOSS_LEVEL], $startTime, $endTime));
		}
	}
	
	public function reward($boss_id, $boss_level, $start_time, $end_time, $killer=NULL)
	{
		BossLogic::reward($boss_id, $boss_level, $start_time, $end_time, $killer);
	}
	
	public function bossEnd($boss_id, $start_time, $end_time)
	{
		BossLogic::bossEnd($boss_id, $start_time, $end_time);
	}
	
	public function setBossFormation( $bossId )
	{
		$uid = RPCContext::getInstance()->getUid();
		BossLogic::setBossFormation($bossId, $uid);
	}
	
	public function setFormationSwitch( $bossId, $switch )
	{
		$uid = RPCContext::getInstance()->getUid();
		BossLogic::setFormationSwitch($uid, $bossId, $switch);	
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
