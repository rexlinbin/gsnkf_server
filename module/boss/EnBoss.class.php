<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnBoss.class.php 241880 2016-05-10 08:23:31Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/boss/EnBoss.class.php $
 * @author $Author: BaoguoMeng $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-05-10 08:23:31 +0000 (Tue, 10 May 2016) $
 * @version $Revision: 241880 $
 * @brief 
 *  
 **/
class EnBoss
{
	/**
	 * 只支持拉取玩家自己的boss排名（如果在boss期间则拉取当前排名，否则拉取上一次boss排名，为0时表示没有排名）
	 * @param unknown $uid
	 * @return number
	 */
// 	public static function getUserRank($uid)
// 	{
// 		//只支持玩家拉取自己的
// 		return BossLogic::getMyRank($uid);
// 	}

	public static function getRetrieveInfo($uid, $bossId)
	{
		Logger::trace('EnBoss::getRetrieveInfo uid[%d] boss[%d] begin...', $uid, $bossId);
		
		if (empty($bossId) 
			|| !isset(btstore_get()->BOSS[$bossId])	
			|| !EnSwitch::isSwitchOpen(SwitchDef::BOSS))
		{
			Logger::trace('EnBoss::getRetrieveInfo uid[%d] boss[%d] is empty or not in config or boss switch not open, return FALSE',$uid, $bossId);
			return FALSE;
		}
		
		$now = Util::getTime();
		$bossStartTime = BossUtil::getBossStartTime($bossId);
		$bossEndTime = BossUtil::getBossEndTime($bossId);
		if ($now >= $bossStartTime && $now < $bossEndTime)
		{
			Logger::trace('EnBoss::getRetrieveInfo uid[%d] boss[%d] is in curr boss time[now:%s,start:%s,end:%s], return FALSE', $uid, $bossId, strftime('%Y%m%d %H%M%S', $now), strftime('%Y%m%d %H%M%S', $bossStartTime), strftime('%Y%m%d %H%M%S', $bossEndTime));
			return FALSE;
		}
		
		$lastAttackTime = Atker::getInstance($uid, $bossId, FALSE)->getAtkTime();
		$bossBeforeStartTime = BossUtil::getBeforeBossStartTime($bossId);
		$bossBeforeEndTime = BossUtil::getBeforeBossEndTime($bossId);
		if ($lastAttackTime >= $bossBeforeStartTime) 
		{
			Logger::trace('EnBoss::getRetrieveInfo uid[%d] boss[%d] attack in last boss time[lastAttackTime:%s,lastStartTime:%s,lastEndTime:%s], return FALSE', $uid, $bossId, strftime('%Y%m%d %H%M%S', $lastAttackTime), strftime('%Y%m%d %H%M%S', $bossBeforeStartTime), strftime('%Y%m%d %H%M%S', $bossBeforeEndTime));
			return FALSE;
		}
		
		$ret = array($bossBeforeEndTime, $bossStartTime);
		Logger::trace('EnBoss::getRetrieveInfo uid[%d] boss[%d] not attack in last boss time[lastAttackTime:%s,lastStartTime:%s,lastEndTime:%s], return retrieveInfo[%s]', $uid, $bossId, strftime('%Y%m%d %H%M%S', $lastAttackTime), strftime('%Y%m%d %H%M%S', $bossBeforeStartTime), strftime('%Y%m%d %H%M%S', $bossBeforeEndTime), $ret);
		return $ret;
	}
	
	public static function getTimeConfig()
	{
		// 这里只取默认的boss的开始时间和结束时间
		$defaultBossId = 1;
		return array('begin_time' => BossUtil::getBossStartTime($defaultBossId), 'end_time' => BossUtil::getBossEndTime($defaultBossId));
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */