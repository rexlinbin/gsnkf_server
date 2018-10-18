<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: BossBattleGenerator.php 89102 2014-02-07 08:55:02Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/BossBattleGenerator.php $
 * @author $Author: wuqilin $(lijinfeng@babeltime.com)
 * @date $Date: 2014-02-07 08:55:02 +0000 (Fri, 07 Feb 2014) $
 * @version $Revision: 89102 $
 * @brief 
 *  
 **/

/*
 * 开一场世界boss，
 * 使用方法： btscript BossBattleGenerator bossId delay = (5*60 sec)
 */

class BossBattleGenerator extends BaseScript
{
	
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	 */
	protected function executeScript($arrOption) 
	{

		if( count($arrOption) < 1 )
		{
			printf("invalid parm.  btscript gamexxx $0 bossId  delay = (5*60 sec) \n");
			return;
		} 
		
		$bossId = intval($arrOption[0]);
		$delay = 5*60;//默认5分钟后开始
		 
		if( isset( $arrOption[1] )  )
		{
			$delay = intval( $arrOption[1] );
		}
		
		if(!isset(btstore_get()->BOSS[$bossId]))
		{
			$msg = sprintf("invalid boss id:%d",$bossId);
			printf("%s\n", $msg);
			Logger::info('%s', $msg);
			return;
		}
		
		
		$group = $this->group;
				
		if (!BossUtil::isBossTime( $bossId ))
		{
			$preOffset = GameConf::BOSS_OFFSET;
			$startTime = BossUtil::getBossStartTime( $bossId ) - $preOffset;
			$endTime = BossUtil::getBossEndTime($bossId) - $preOffset;
			$hms = date('H:i:s', $startTime);
			$ymd = date('Y-m-d');
			
			$startTimeNoOffset = strtotime("$ymd $hms");
			
			$now = time();
			$needStartTime = $now + $delay;
			
			Logger::debug('starTimeNoOffset:%s, needStartTime:%s', 
					date('Y-m-d H:i:s', $startTimeNoOffset),
					date('Y-m-d H:i:s', $needStartTime));
			
			$offset = $needStartTime - $startTimeNoOffset;
	
			
			popen("/bin/sed -i '/BOSS_OFFSET/{s/[0-9-]\+/$offset/;}' /home/pirate/rpcfw/conf/gsc/$group/Game.cfg.php", 'r');
			
			
			self::resetBossInfo($bossId, $needStartTime);
			
			self::resetBossTimer($bossId, $needStartTime);
			
			$msg = sprintf("set boss ok. start:%s, end:%s",
					date('Y-m-d H:i:s', $needStartTime ),
					date('Y-m-d H:i:s', $endTime + $offset - $preOffset) );
			printf("%s\n", $msg);
			Logger::info('%s', $msg);
		}
		else
		{
			$msg = sprintf("is boss time. start:%s, end:%s", 
				date('Y-m-d H:i:s', BossUtil::getBossStartTime($bossId)),
				date('Y-m-d H:i:s', BossUtil::getBossEndTime($bossId)) );
			printf("%s\n", $msg);
			Logger::info('%s', $msg);
		}
		
	}
	
	
	
	protected static function resetBossInfo($bossId, $battleTime)
	{
		$boss_info = BossDAO::getBoss($bossId);
		
		$arrField = array(
				BossDef::BOSS_HP 		=> BossUtil::getBossMaxHp($bossId, $boss_info[BossDef::BOSS_LEVEL]),
				BossDef::START_TIME 	=> $battleTime
		);
		
		$wheres = array (
				array (BossDef::BOSS_ID, '=', $bossId),
		);
		
		BossDAO::updateBoss($arrField, $wheres);
		
	}
	
	
	
	protected static function resetBossTimer($bossId, $bossStartTime)
	{
		$data = new CData();
		$arrField = array (
				'tid', 
				'uid', 
				'status', 
				'execute_count', 
				'execute_method',
				'execute_time',
				'va_args',
		);
		$arrTimerInfo = $data->select ( $arrField )->from ( 't_timer' )
						->where ( 'status', '=',TimerStatus::UNDO )
						->where ( 'execute_method', 'LIKE', 'boss.%' )
						->query ();
		
		$comingTime = $bossStartTime - BossConf::BOSS_COMING_TIME;
		$exist = false;
		foreach ( $arrTimerInfo as $timerInfo )
		{
			if ( $timerInfo['va_args'][0] == $bossId )
			{
				if ( $timerInfo['execute_method'] == 'boss.bossComing' && $timerInfo['execute_time'] == $comingTime )
				{
					$exist = true;
					Logger::debug('found coming. bossId:%d, time:%s', $bossId, date('Y-m-d H:i:s', $timerInfo['execute_time']) );
					continue;
				}
				
				TimerTask::cancelTask($timerInfo['tid']);
				$msg = sprintf("bossId:%d, method:%s, time:%s, tid:%d, exist. cancel it", 
							$bossId, $timerInfo['execute_method'], date('Y-m-d H:i:s', $timerInfo['execute_time']), $timerInfo['tid']);
				printf("%s\n", $msg);
				Logger::fatal('%s', $msg);
			}
		}
		
		if($exist)
		{
			$msg = sprintf('bossComing timer exist. bossId:%d, time:%s', $bossId, $bossId, date('Y-m-d H:i:s', $comingTime) );
			printf("%s\n", $msg);
			Logger::info('%s', $msg);
			return;
		}

		TimerTask::addTask(0, $comingTime, 'boss.bossComing', array($bossId) );
		$msg = sprintf("add new boss comming timer of bossId:%d, time:%s", $bossId, date('Y-m-d H:i:s', $comingTime) );
		printf("%s\n", $msg);
		Logger::info('%s', $msg);
		
	}
	
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
