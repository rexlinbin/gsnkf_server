<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildWarResend.php 158737 2015-02-12 10:52:05Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/GuildWarResend.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-02-12 10:52:05 +0000 (Thu, 12 Feb 2015) $
 * @version $Revision: 158737 $
 * @brief 
 *  
 **/
 
/**
 * 示例：
 * btscript gamexxx GuildWarResend.php [do|check] support [uid]
 */
class GuildWarResend extends BaseScript
{
	/**
	 * (non-PHPdoc)
	 * @see BaseScript::executeScript()
	 */
	protected function executeScript($arrOption)
	{
		if (count($arrOption) < 2)
		{
			printf("invalid args!btscript gamexxx GuildWarResend.php [do|check] support [uid]");
			return;
		}
	
		$doSend = FALSE;
		if ($arrOption[0] == 'do')
		{
			$doSend = TRUE;
		}
		$rewardType = $arrOption[1];
	
		if ($rewardType == 'support')
		{
			$arrUid = array();
			if (isset($arrOption[2]))
			{
				$arrUid = array(intval($arrOption[2]));
			}
			$this->sendSupportRewardToAll($doSend, $arrUid);
		}
		else
		{
			Logger::fatal('invalid reward tyep:%s', $rewardType);
		}
	
		printf("done\n");
	}
	
	public function sendSupportRewardToAll($doSend = FALSE, $arrUid = array())
	{
		$confObj = GuildWarConfObj::getInstance();
		$session = $confObj->getSession();
		$serverId = GuildWarUtil::getMinServerId();
		$teamId = GuildWarUtil::getTeamIdByServerId($session, $serverId);
		$auditionStartTime = $confObj->getRoundStartTime(GuildWarRound::AUDITION);
		$activityStartTime = $confObj->getActivityStartTime();
		
		$procedureObj = GuildWarProcedureObj::getInstance($session);
		$teamObj = $procedureObj->getTeamObj($teamId);
		$curRound = $teamObj->getCurRound();
		$maxRewardRound = $curRound;
		$curStatus = $teamObj->getCurStatus();
		if ($curStatus < GuildWarStatus::DONE) 
		{
			$maxRewardRound = $confObj->getPreRound($curRound);
		}
	
		$arrUser = array();
		if (empty($arrUid))
		{
			$data = new CData();
			$offset = 0;
			$arrRet = array();
			for ($i = 0; $i < 65535; $i++)
			{
				$result = $data->select(array(GuildWarUserField::TBL_FIELD_UID))->from('t_guild_war_inner_user')
								->where(GuildWarUserField::TBL_FIELD_LAST_JOIN_TIME, '>=', $activityStartTime)
								->orderBy(GuildWarUserField::TBL_FIELD_UID, TRUE)->limit($offset, DataDef::MAX_FETCH)->query();
					
				$arrRet= array_merge($arrRet, $result);
				if (count($result) < DataDef::MAX_FETCH)
				{
					break;
				}
				$offset += DataDef::MAX_FETCH;
			}
			$arrUser = Util::arrayIndex($arrRet, GuildWarUserField::TBL_FIELD_UID);
		}
		else
		{
			$data = new CData();
			$result = $data->select(array(GuildWarUserField::TBL_FIELD_UID))->from('t_guild_war_inner_user')
							->where(GuildWarUserField::TBL_FIELD_UID, 'in', $arrUid)
							->query();
			$arrUser = Util::arrayIndex($result, GuildWarUserField::TBL_FIELD_UID);
		}
		
		// 获得一个组所有进入晋级赛的军团
		$arrGuildInfo = GuildWarDao::selectFinalsGuildInfo(array(), $session, $teamId, $confObj->getFailNum(), $confObj->getSignUpStartTime());
		
		$arrUid = array_keys($arrUser);
		foreach ($arrUid as $aUid)
		{
			$this->sendSupportRewardToUser($arrGuildInfo, $serverId, $aUid, $maxRewardRound, $auditionStartTime, $doSend);
			usleep(50);
		}
	}
	
	public function sendSupportRewardToUser($arrGuildInfo, $serverId, $uid, $maxRewardRound, $innerAuditionStartTime, $doSend = FALSE)
	{
		$confObj = GuildWarConfObj::getInstance();
		
		$guildWarUserObj = GuildWarUserObj::getInstance($serverId, $uid);
		$arrCheerInfo = $guildWarUserObj->getAllCheerInfo();
		
		foreach ($arrCheerInfo as $round => $cheerInfo)
		{
			if ($round > $maxRewardRound) 
			{
				Logger::info('GuildWarResend.sendSupportRewardToUser uid[%d], round[%d] > max[%d], ignore', $uid, $round, $maxRewardRound);
				continue;
			}
			
			$cheerGuildId = $cheerInfo[GuildWarUserField::TBL_VA_EXTRA_GUILD_ID];
			$cheerServerId = $cheerInfo[GuildWarUserField::TBL_VA_EXTRA_SERVER_ID];
			$cheerRank = 0;
			foreach ($arrGuildInfo as $aWinInfo)
			{
				if ($aWinInfo[GuildWarServerField::TBL_FIELD_GUILD_ID] == $cheerGuildId
					&& $aWinInfo[GuildWarServerField::TBL_FIELD_GUILD_SERVER_ID] == $cheerServerId) 
				{
					$cheerRank = $aWinInfo[GuildWarServerField::TBL_FIELD_FINAL_RANK];
					break;
				}
			}
			
			if ($cheerRank == GuildWarConf::$all_rank[$round]) 
			{
				$rewardTime = $cheerInfo[GuildWarUserField::TBL_VA_EXTRA_REWARD_TIME];
				$roundStartTime = $confObj->getRoundStartTime($round);
				if ($rewardTime < $roundStartTime) 
				{
					$rewardArr = $confObj->getCheerPrize($round);
					$guildWarUserObj->cheerRewardEnd();
					if ($doSend) 
					{
						Util::kickOffUser($uid);//补发助威奖励的时候，要将在线的玩家踢掉
						$guildWarUserObj->setCheerRewardTime($round, Util::getTime());
						$guildWarUserObj->update();
						RewardUtil::reward3DtoCenter($uid, array($rewardArr), RewardSource::GUILDWAR_SUPPORT, array('round' => $round));
					}
					Logger::INFO("GuildWarResend.sendSupportRewardToUser reward support. serverId[%d], uid[%d], round[%d], reward[%s]", $serverId, $uid, $round, $rewardArr);
				}
				else 
				{
					Logger::INFO("GuildWarResend.sendSupportRewardToUser already reward. serverId[%d], uid[%d], round[%d], time[%s]", $serverId, $uid, $round, date('Y-m-d H:i:s', $rewardTime));
				}
			}
			else 
			{
				Logger::INFO("GuildWarResend.sendSupportRewardToUser cheer guild lose, no need reward. serverId[%d], uid[%d], round[%d], win rank[%d], cheer guild rank[%d]", $serverId, $uid, $round, GuildWarConf::$all_rank[$round], $cheerRank);
			}
		}
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */