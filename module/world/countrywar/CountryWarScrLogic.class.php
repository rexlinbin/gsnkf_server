<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CountryWarScrLogic.class.php 235604 2016-03-30 06:53:53Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/countrywar/CountryWarScrLogic.class.php $
 * @author $Author: BaoguoMeng $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-03-30 06:53:53 +0000 (Wed, 30 Mar 2016) $
 * @version $Revision: 235604 $
 * @brief 
 *  
 **/
class CountryWarScrLogic
{
	static function syncAllTeamFromPlat2Cross($commit = TRUE, $force = TRUE)
	{
		// 是否处在分组阶段
		if (!CountryWarUtil::isStage(CountryWarStage::TEAM) && !$force)
		{
			Logger::warning('SYNC_ALL_TEAM : not in signup stage, can not sync, cur stage[%s]', CountryWarConfig::getStageByTime(Util::getTime()));
			return;
		}
	
		// 得到配置的分组数据和所有服务器信息
		$beginTime = CountryWarConfig::roundStartTime(Util::getTime());
		$arrCfgTeamInfo = TeamManager::getInstance(WolrdActivityName::COUNTRYWAR, 0, $beginTime)->getAllTeam();
		ksort($arrCfgTeamInfo);
		$arrMyTeamInfo =CountryWarTeamObj::getInstance()->getAllTeamInfo();
		ksort($arrMyTeamInfo);
		$allServerInfo = ServerInfoManager::getInstance()->getAllServerInfo();
		ksort($allServerInfo);
		Logger::info('SYNC_ALL_TEAM : all config team info[%s]', $arrCfgTeamInfo);
		Logger::info('SYNC_ALL_TEAM : all my team info[%s]', $arrMyTeamInfo);
		Logger::info('SYNC_ALL_TEAM : all server info[%s]', $allServerInfo);
	
		if (!empty($arrMyTeamInfo))
		{
			Logger::warning('SYNC_ALL_TEAM : already have valid team[%s], return', $arrMyTeamInfo);
			return ;
		}
	
		// 找到配置的当前最大分组teamId
		$curMaxTeamId = 0;
		foreach ($arrCfgTeamInfo as $aTeamId => $aInfo)
		{
			if ($aTeamId > $curMaxTeamId)
			{
				$curMaxTeamId = $aTeamId;
			}
		}
		$orginMaxTeamId = $curMaxTeamId;
	
		// 得到需要自动分组的服务器
		$tmpAllServerInfo = $allServerInfo;
		foreach ($arrCfgTeamInfo as $aTeamId => $arrServerId)
		{
			foreach ($arrServerId as $aServerId)
			{
				unset($tmpAllServerInfo[$aServerId]);
			}
		}
		Logger::info('SYNC_ALL_TEAM : all new server info[%s]', $tmpAllServerInfo);
	
		// 去掉开服日期不符合要求的
		$needOpenDuration = CountryWarConfig::needOpenDays();
		foreach ($tmpAllServerInfo as $aServerId => $aInfo)
		{
			$aOpenTime = $aInfo['open_time'];
			$referTime = $beginTime;
			$betweenDays = intval((strtotime(date("Y-m-d", $referTime)) - strtotime(date("Y-m-d", $aOpenTime))) / SECONDS_OF_DAY);
			if ($betweenDays < $needOpenDuration)
			{
				unset($tmpAllServerInfo[$aServerId]);
				Logger::info('SYNC_ALL_TEAM : server id[%d] skip, open time[%s], refer time[%s], need open days[%d].', $aServerId, date("Y-m-d", $aOpenTime), date("Y-m-d", $referTime), $needOpenDuration);
			}
		}
		Logger::info('SYNC_ALL_TEAM : all new server info after open days filter[%s]', $tmpAllServerInfo);
	
		$arrDetailServerInfo = array();
		foreach ($tmpAllServerInfo as $aServerId => $aServerInfo)
		{
			$groupBase = intval($aServerId / 10000);
			$arrDetailServerInfo[$groupBase][$aServerId] = $aServerInfo;
		}
		Logger::info('SYNC_ALL_TEAM : all new server info after group base filter[%s]', $arrDetailServerInfo);
		
		foreach ($arrDetailServerInfo as $groupBase => $prefixServerInfo)
		{
			// 将剩余的服务器自动分组，合服的要在同一个组里
			if (!empty($prefixServerInfo))
			{
				// 处理合服的情况，db -> array(serverId...)
				$arrDb2Info = array();
				foreach ($prefixServerInfo as $aServerId => $aInfo)
				{
					if (!isset($arrDb2Info[$aInfo['db_name']]))
					{
						$arrDb2Info[$aInfo['db_name']] = array();
					}
					$arrDb2Info[$aInfo['db_name']][] = $aServerId;
				}
				Logger::info('SYNC_ALL_TEAM : db 2 info of new server[%s]', $arrDb2Info);
				// 处理正常的分组
				$minCount = defined('PlatformConfig::COUNTRY_WAR_TEAM_MIN_COUNT') ? PlatformConfig::COUNTRY_WAR_TEAM_MIN_COUNT : 25;
				$maxCount = defined('PlatformConfig::COUNTRY_WAR_TEAM_MAX_COUNT') ? PlatformConfig::COUNTRY_WAR_TEAM_MAX_COUNT : 30;
				Logger::info('SYNC_ALL_TEAM : min server count[%d], max server count[%d]', $minCount, $maxCount);
			
				$curServerCount = 0;
				$curTeamNeedCount = mt_rand($minCount, $maxCount);
				$curTeamId = ++$curMaxTeamId;
				$curPrefixFirstTeamId = $curTeamId;
				Logger::info('SYNC_ALL_TEAM : generate new team[%d], new team server count[%d]', $curTeamId, $curTeamNeedCount);
				$arrExclude = array();
				foreach ($prefixServerInfo as $aServerId => $aInfo)
				{
					if (in_array($aServerId, $arrExclude))
					{
						continue;
					}
			
					if ($curServerCount >= $curTeamNeedCount)
					{
						$curServerCount = 0;
						$curTeamNeedCount = mt_rand($minCount, $maxCount);
						$curTeamId = ++$curMaxTeamId;
						Logger::info('SYNC_ALL_TEAM : generate new team[%d], new team server count[%d]', $curTeamId, $curTeamNeedCount);
					}
			
					$arrCfgTeamInfo[$curTeamId][] = $aServerId;
					Logger::info('SYNC_ALL_TEAM : generate new team[%d], add a normal server[%d]', $curTeamId, $aServerId);
					foreach ($arrDb2Info[$aInfo['db_name']] as $aMergeServerId)
					{
						if ($aMergeServerId == $aServerId)
						{
							continue;
						}
						$arrCfgTeamInfo[$curTeamId][] = $aMergeServerId;
						$arrExclude[] = $aMergeServerId;
						Logger::info('SYNC_ALL_TEAM : generate new team[%d], add a merge server[%d]', $curTeamId, $aMergeServerId);
					}
					++$curServerCount;
				}
			
				// 处理当最后一个分组个数没有达到最低个数的情况，就直接塞到最后一组吧
				if ($curServerCount < $minCount)
				{
					if (isset($arrCfgTeamInfo[$curTeamId - 1]) && $curTeamId > $curPrefixFirstTeamId)
					{
						$arrCfgTeamInfo[$curTeamId - 1] = array_merge($arrCfgTeamInfo[$curTeamId - 1], $arrCfgTeamInfo[$curTeamId]);
						unset($arrCfgTeamInfo[$curTeamId]);
						Logger::info('SYNC_ALL_TEAM : cur team[%d] count[%d] less than min[%d], add to last', $curTeamId, $curServerCount, $minCount);
					}
				}
			}
		}
	
		ksort($arrCfgTeamInfo);
		Logger::info('SYNC_ALL_TEAM : final team info[%s]', $arrCfgTeamInfo);
	
		// 更新跨服库分组信息
		foreach ($arrCfgTeamInfo as $aTeamId => $arrServerId)
		{
			foreach ($arrServerId as $aServerId)
			{
				if (!isset($allServerInfo[$aServerId]))
				{
					Logger::fatal('SYNC_ALL_TEAM : no server info of teamId[%d], serverId[%d], skip.', $aTeamId, $aServerId);
				}
				else
				{
					for ($i = 1; $i <= 3; ++$i)
					{
						try
						{
							if ($commit)
							{
								$arrField = array
								(
										CountryWarTeamField::TEAM_ID => $aTeamId,
										CountryWarTeamField::SERVER_ID => $aServerId,
										CountryWarTeamField::UPDATE_TIME => $beginTime + 1,
								);
								CountryWarTeamDao::insertOrUpdateTeamInfo($arrField);
							}
							Logger::info('SYNC_ALL_TEAM : sync teamdId[%d] server[%d] success.', $aTeamId, $aServerId);
								
							break;
						}
						catch (Exception $e)
						{
							usleep(1000);
							Logger::fatal('SYNC_ALL_TEAM : occur exception when sync teamdId[%d] server[%d], exception[%s], trace[%s], retry...', $aTeamId, $aServerId, $e->getMessage(), $e->getTraceAsString());
						}
							
						if ($i == 3)
						{
							Logger::fatal('SYNC_ALL_TEAM : occur exception when sync teamdId[%d] server[%d], failed', $aTeamId, $aServerId);
						}
					}
				}
			}
		}
		Logger::info('SYNC_ALL_TEAM : sync team info from plat to cross done');
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
