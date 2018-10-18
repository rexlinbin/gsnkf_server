<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: BossDataFixed.php 98539 2014-04-09 07:19:34Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/BossDataFixed.php $
 * @author $Author: wuqilin $(lijinfeng@babeltime.com)
 * @date $Date: 2014-04-09 07:19:34 +0000 (Wed, 09 Apr 2014) $
 * @version $Revision: 98539 $
 * @brief 
 *  
 **/


class BossDataFixed extends BaseScript
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

		if(!isset(btstore_get()->BOSS[$bossId]))
		{
			$msg = sprintf("invalid boss id:%d",$bossId);
			printf("%s\n", $msg);
			Logger::info('%s', $msg);
		}
		
		if (!BossUtil::isBossTime( $bossId ))
		{
			$startTime = BossUtil::getBossStartTime( $bossId );
			$endTime = BossUtil::getBossEndTime($bossId);
			
			self::resetBossInfo($bossId, $startTime);
			
			self::resetBossTimer($bossId, $startTime);
			
			$msg = sprintf("set boss ok. start:%s, end:%s",
					date('Y-m-d H:i:s', $startTime ),
					date('Y-m-d H:i:s', $endTime) );
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
