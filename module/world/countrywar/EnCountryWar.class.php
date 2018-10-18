<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnCountryWar.class.php 236952 2016-04-07 08:01:57Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/countrywar/EnCountryWar.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-04-07 08:01:57 +0000 (Thu, 07 Apr 2016) $
 * @version $Revision: 236952 $
 * @brief 
 *  
 **/
 
class EnCountryWar
{
	public static function loginToGetReward($uid)
	{
		try 
		{
			list($serverId, $pid) = CountryWarUtil::getServerIdAndPidFromInner();
			if (!CountryWarUtil::isTeamIdRight($serverId) || !CountryWarUtil::isUserLvRight($uid))
			{
				return;
			}
			
			CountryWarLogic::checkAndReward($serverId, $pid);
		} 
		catch (Exception $e) 
		{
			Logger::fatal('EnCountryWar::loginToGetReward occur exception[%s]', $e->getMessage());
		}
	}
	
	public static function getRetrieveInfo($uid)
	{
		try 
		{
			// 等级还不够的，无法资源追回
			$user = EnUser::getUserObj($uid);
			$curLevel = $user->getLevel();
			$needLevel = CountryWarConfig::reqLevel();
			if ($curLevel < $needLevel)
			{
				Logger::trace('cat not retrieve, cur level[%d], need level[%d]', $curLevel, $needLevel);
				return FALSE;
			}
			
			// 没有分组的，无法资源追回
			list($serverId, $pid) = CountryWarUtil::getServerIdAndPidFromInner();
			$teamObj = CountryWarTeamObj::getInstance();
			$teamId = $teamObj->getTeamIdByServerId($serverId);
			if ($teamId <= 0)
			{
				Logger::trace('cat not retrieve, team id[%d]', $teamId);
				return FALSE;
			}
			
			// 处于国战非膜拜阶段，无法资源追回
			$nowStage = CountryWarConfig::getStageByTime(Util::getTime());
			if ($nowStage != CountryWarStage::WORSHIP)
			{
				Logger::trace('cat not retrieve, cur stage[%s]', $nowStage);
				return FALSE;
			}
			
			// 报名的玩家，无法资源追回
			$crossUser = CountryWarCrossUser::getInstance($serverId, $pid);
			$signTime = $crossUser->getSignTime();
			if ($signTime > 0)
			{
				Logger::trace('cat not retrieve, sign time[%s]', strftime('%Y%m%d %H%M%S', $signTime));
				return FALSE;
			}
			
			// 助威某个选手的玩家，无法资源追回
			$innerUser = CountryWarInnerUser::getInstance($serverId, $pid);
			if ($innerUser->alreadySupportOneUser())
			{
				Logger::trace('cat not retrieve, support one user, server id[%s], pid[%d]', $innerUser->getSupportServerId(), $innerUser->getSupportPid());
				return FALSE;
			}
			
			// 助威某个势力的玩家，无法资源追回
			if ($innerUser->alreadySupportFinalSide())
			{
				Logger::trace('cat not retrieve, support one side[%s]', $innerUser->getSupportFinalSide());
				return FALSE;
			}
			
			// 终于可以追回啦 TODO 这里有个依赖就是一次国战的完整周期是一个礼拜
			$beforeEndTime = CountryWarConfig::getStageStartTime(Util::getTime(), CountryWarStage::WORSHIP);
			$nextStartTime = CountryWarConfig::getStageStartTime(Util::getTime(), CountryWarStage::TEAM) + SECONDS_OF_DAY * 7;
			Logger::trace('can retrieve, before end time[%s], next start time[%s]', strftime('%Y%m%d %H%M%S', $beforeEndTime), strftime('%Y%m%d %H%M%S', $nextStartTime));
			return array($beforeEndTime, $nextStartTime);
		} 
		catch (Exception $e) 
		{
			Logger::fatal('cat not retrieve, occur exception[%s]', $e->getTraceAsString());
			return FALSE;
		}
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */